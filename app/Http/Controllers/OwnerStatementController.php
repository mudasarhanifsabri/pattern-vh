<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Owner;
use App\Models\OwnerAccountEntry;
use App\Models\OwnerPayoutTransfer;
use App\Models\Unit;
use App\Support\OwnerStatementPdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OwnerStatementController extends Controller
{
    public function storeOpeningBalance(Request $request)
    {
        $validated = $request->validate([
            'owner_id' => ['required', 'integer', 'exists:owners,id'],
            'unit_id' => ['required', 'integer', 'exists:units,id'],
            'entry_date' => ['required', 'date'],
            'direction' => ['required', 'in:credit,debit'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'description' => ['nullable', 'string', 'max:191'],
            'statement_from' => ['nullable', 'date'],
            'statement_to' => ['nullable', 'date'],
        ]);
        $owner = Owner::with('units')->findOrFail($validated['owner_id']);
        abort_unless($owner->units->contains('id', (int) $validated['unit_id']), 422);

        $owner->accountEntries()->create([
            'unit_id' => $validated['unit_id'],
            'entry_date' => $validated['entry_date'],
            'type' => 'opening_balance',
            'direction' => $validated['direction'],
            'amount' => $validated['amount'],
            'description' => $validated['description'] ?: 'Zoho opening balance',
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('owner-statements.index', array_filter([
            'owner_id' => $owner->id,
            'unit_id' => $validated['unit_id'],
            'from' => $validated['statement_from'] ?? null,
            'to' => $validated['statement_to'] ?? null,
        ]))->with('status', 'Unit opening balance added successfully.');
    }

    public function index(Request $request)
    {
        $owner = $this->ownerFor($request);
        $from = $request->date('from') ?: now()->startOfMonth();
        $to = $request->date('to') ?: now()->endOfMonth();
        $unit = $owner ? $this->unitFor($request, $owner) : null;
        $statement = $owner ? $this->buildStatement($owner, $from, $to, $unit?->id) : null;

        if ($owner && $request->boolean('export')) {
            return $this->export($owner, $statement, $from, $to);
        }

        return view('owner-statements.index', [
            'owners' => $request->user()->can('owner-statements.manage')
                ? Owner::with('units.building')->orderBy('full_name')->get()
                : collect([$owner])->filter(),
            'owner' => $owner,
            'unit' => $unit,
            'from' => $from,
            'to' => $to,
            'statement' => $statement,
        ]);
    }

    public function pdf(Request $request, OwnerStatementPdf $pdf)
    {
        $owner = $this->ownerFor($request);
        abort_unless($owner, 404);

        $from = $request->date('from') ?: now()->startOfMonth();
        $to = $request->date('to') ?: now()->endOfMonth();
        $unit = $this->unitFor($request, $owner);
        $statement = $this->buildStatement($owner, $from, $to, $unit?->id);
        $filename = 'owner-statement-'.$owner->id.($unit ? '-unit-'.$unit->id : '').'-'.$from->format('Ymd').'-'.$to->format('Ymd').'.pdf';

        return response($pdf->make($owner, $statement, $from, $to, $unit), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => ($request->boolean('download') ? 'attachment' : 'inline').'; filename="'.$filename.'"',
        ]);
    }

    public function preview(Request $request)
    {
        $owner = $this->ownerFor($request);
        abort_unless($owner, 404);

        $from = $request->date('from') ?: now()->startOfMonth();
        $to = $request->date('to') ?: now()->endOfMonth();
        $unit = $this->unitFor($request, $owner);
        $pdfQuery = $request->except('download');

        return view('owner-statements.pdf-preview', [
            'owner' => $owner,
            'unit' => $unit,
            'from' => $from,
            'to' => $to,
            'pdfUrl' => route('owner-statements.pdf', $pdfQuery),
            'downloadUrl' => route('owner-statements.pdf', array_merge($pdfQuery, ['download' => 1])),
            'backUrl' => route('owner-statements.index', $request->query()),
        ]);
    }

    private function unitFor(Request $request, Owner $owner): ?Unit
    {
        if (! $request->filled('unit_id')) {
            return null;
        }

        return $owner->units->firstWhere('id', $request->integer('unit_id'));
    }

    private function ownerFor(Request $request): ?Owner
    {
        if ($request->user()->can('owner-statements.manage') && $request->filled('owner_id')) {
            return Owner::with('units.building')->find($request->integer('owner_id'));
        }

        if ($request->user()->can('portal.owner')) {
            return Owner::with('units.building')
                ->where('user_id', $request->user()->id)
                ->orWhere('email', $request->user()->email)
                ->first();
        }

        return Owner::with('units.building')->first();
    }

    private function buildStatement(Owner $owner, $from, $to, ?int $unitId = null): array
    {
        $unitIds = $owner->units->pluck('id')
            ->when($unitId, fn ($ids) => $ids->filter(fn ($id) => (int) $id === $unitId));
        $shareByUnit = $owner->units->mapWithKeys(fn ($unit) => [$unit->id => (float) ($unit->pivot->share_percent ?? 100)]);
        $managementByUnit = $owner->units->mapWithKeys(fn ($unit) => [$unit->id => (float) ($unit->management_fee_percent ?? 0)]);

        // Every invoice is reported in its own checkout period; extensions do not inherit original stay dates.
        $invoices = Invoice::query()
            ->with(['booking.unit.building', 'extensionRequest'])
            ->whereIn('unit_id', $unitIds)
            ->where(function ($query) use ($from, $to): void {
                $query->where(function ($query) use ($from, $to): void {
                    $query->whereHas('extensionRequest')
                        ->whereDate('period_end', '>=', $from->toDateString())
                        ->whereDate('period_end', '<=', $to->toDateString());
                })->orWhere(function ($query) use ($from, $to): void {
                    $query->whereDoesntHave('extensionRequest')
                        ->whereHas('booking', fn ($bookingQuery) => $bookingQuery
                            ->whereDate('check_out_date', '>=', $from->toDateString())
                            ->whereDate('check_out_date', '<=', $to->toDateString()));
                });
            })
            ->get();

        $revenueRows = $invoices->map(function (Invoice $invoice) use ($shareByUnit, $managementByUnit): array {
            $booking = $invoice->booking;
            $share = $shareByUnit[$invoice->unit_id] ?? 100;
            // Payments may include deposits and fees; owner rent can never exceed the invoice rent amount.
            $rentCollected = min((float) $invoice->paid_amount, (float) $invoice->rent_amount);
            $ownerRentCollected = min($rentCollected, max((float) $invoice->rent_amount - (float) $invoice->pattern_topup_amount, 0));
            $gross = $ownerRentCollected * ($share / 100);
            $management = $gross * (($managementByUnit[$invoice->unit_id] ?? 0) / 100);

            return [
                'date' => $invoice->stay_check_out_date,
                'description' => $invoice->invoice_no.' / '.$booking->booking_no.' / '.$booking->unit->building->name.' '.$booking->unit->unit_no,
                'booking_rent' => $ownerRentCollected,
                'rent_collected' => $ownerRentCollected,
                'booking_from' => $invoice->stay_check_in_date,
                'booking_to' => $invoice->stay_check_out_date,
                'booking_duration' => 'Check-in: '.$invoice->stay_check_in_date?->format('M d, Y').' / Check-out: '.$invoice->stay_check_out_date?->format('M d, Y'),
                'gross' => $gross,
                'management_fee' => $management,
                'owner_expense' => 0,
                'net' => $gross - $management,
            ];
        });

        $expenses = Expense::query()
            ->with('unit.building')
            ->where('owner_id', $owner->id)
            ->when($unitId, fn ($query) => $query->where('unit_id', $unitId))
            ->whereDate('incurred_on', '>=', $from->toDateString())
            ->whereDate('incurred_on', '<=', $to->toDateString())
            ->get()
            ->map(fn (Expense $expense): array => [
                'date' => $expense->incurred_on,
                'description' => $expense->name.' / '.($expense->unit?->unit_no ? $expense->unit->building->name.' '.$expense->unit->unit_no : str($expense->type)->headline()),
                'booking_rent' => null,
                'rent_collected' => null,
                'booking_from' => null,
                'booking_to' => null,
                'booking_duration' => null,
                'gross' => 0,
                'management_fee' => 0,
                'owner_expense' => (float) $expense->amount,
                'net' => -1 * (float) $expense->amount,
            ]);

        $manualEntries = OwnerAccountEntry::query()
            ->where('owner_id', $owner->id)
            ->when($unitId, fn ($query) => $query->where('unit_id', $unitId))
            ->whereDate('entry_date', '>=', $from->toDateString())
            ->whereDate('entry_date', '<=', $to->toDateString())
            ->get()
            ->map(fn (OwnerAccountEntry $entry): array => [
                'date' => $entry->entry_date,
                'description' => $entry->description.' / '.(OwnerAccountEntry::TYPES[$entry->type] ?? str($entry->type)->headline()),
                'booking_rent' => null, 'rent_collected' => null, 'booking_from' => null, 'booking_to' => null, 'booking_duration' => null,
                'gross' => $entry->direction === 'credit' ? (float) $entry->amount : 0,
                'management_fee' => 0,
                'owner_expense' => $entry->direction === 'debit' ? (float) $entry->amount : 0,
                'net' => ($entry->direction === 'credit' ? 1 : -1) * (float) $entry->amount,
            ]);

        $payouts = OwnerPayoutTransfer::query()
            ->where('owner_id', $owner->id)
            ->whereNotNull('transferred_at')
            ->when($unitId, fn ($query) => $query->where('unit_id', $unitId))
            ->whereDate('transferred_at', '>=', $from->toDateString())
            ->whereDate('transferred_at', '<=', $to->toDateString())
            ->get()
            ->map(fn (OwnerPayoutTransfer $transfer): array => [
                'date' => $transfer->transferred_at,
                'description' => 'Owner payout'.($transfer->reference_no ? ' / '.$transfer->reference_no : ''),
                'booking_rent' => null, 'rent_collected' => null, 'booking_from' => null, 'booking_to' => null, 'booking_duration' => null,
                'gross' => 0, 'management_fee' => 0, 'owner_expense' => (float) $transfer->net_payout, 'net' => -1 * (float) $transfer->net_payout,
            ]);

        $rows = $revenueRows->concat($expenses)->concat($manualEntries)->concat($payouts)->sortBy('date')->values();
        $openingBalance = $this->openingBalance($owner, $from, $unitId, $shareByUnit, $managementByUnit);
        $periodNet = (float) $rows->sum('net');

        return [
            'rows' => $rows,
            'gross' => $rows->sum('gross'),
            'management_fee' => $rows->sum('management_fee'),
            'expenses' => $rows->sum('owner_expense'),
            'opening_balance' => $openingBalance,
            'period_net' => $periodNet,
            'net' => $openingBalance + $periodNet,
        ];
    }

    private function openingBalance(Owner $owner, $from, ?int $unitId, $shareByUnit, $managementByUnit): float
    {
        $unitIds = $owner->units->pluck('id')->when($unitId, fn ($ids) => $ids->filter(fn ($id) => (int) $id === $unitId));
        $invoiceNet = Invoice::query()
            ->with(['booking', 'extensionRequest'])
            ->whereIn('unit_id', $unitIds)
            ->get()
            ->filter(fn (Invoice $invoice) => $invoice->stay_check_out_date?->lt($from))
            ->sum(function (Invoice $invoice) use ($shareByUnit, $managementByUnit): float {
                $rentCollected = min((float) $invoice->paid_amount, (float) $invoice->rent_amount);
                $ownerRent = min($rentCollected, max((float) $invoice->rent_amount - (float) $invoice->pattern_topup_amount, 0));
                $gross = $ownerRent * (($shareByUnit[$invoice->unit_id] ?? 100) / 100);
                return $gross - ($gross * (($managementByUnit[$invoice->unit_id] ?? 0) / 100));
            });
        $expenseDebits = (float) Expense::where('owner_id', $owner->id)
            ->when($unitId, fn ($query) => $query->where('unit_id', $unitId))
            ->whereDate('incurred_on', '<', $from->toDateString())->sum('amount');
        $manualNet = (float) OwnerAccountEntry::where('owner_id', $owner->id)
            ->when($unitId, fn ($query) => $query->where('unit_id', $unitId))
            ->whereDate('entry_date', '<', $from->toDateString())
            ->get()->sum(fn (OwnerAccountEntry $entry) => ($entry->direction === 'credit' ? 1 : -1) * (float) $entry->amount);
        $payoutDebits = (float) OwnerPayoutTransfer::where('owner_id', $owner->id)
            ->when($unitId, fn ($query) => $query->where('unit_id', $unitId))
            ->whereNotNull('transferred_at')->whereDate('transferred_at', '<', $from->toDateString())->sum('net_payout');

        return (float) $invoiceNet - $expenseDebits + $manualNet - $payoutDebits;
    }

    private function export(Owner $owner, array $statement, $from, $to): StreamedResponse
    {
        return response()->streamDownload(function () use ($owner, $statement): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Owner', $owner->full_name]);
            fputcsv($handle, ['Opening Balance', $statement['opening_balance']]);
            // Include both booking stay dates so the exported owner ledger can be reconciled by checkout period.
            fputcsv($handle, ['Date', 'Description', 'Owner Rent Entitlement', 'Owner Rent Collected', 'Check-in Date', 'Check-out Date', 'Rent Share', 'Management Fee', 'Owner Expense', 'Net']);
            foreach ($statement['rows'] as $row) {
                fputcsv($handle, [
                    $row['date']->format('Y-m-d'),
                    $row['description'],
                    $row['booking_rent'],
                    $row['rent_collected'],
                    $row['booking_from']?->format('Y-m-d'),
                    $row['booking_to']?->format('Y-m-d'),
                    $row['gross'],
                    $row['management_fee'],
                    $row['owner_expense'],
                    $row['net'],
                ]);
            }
            fputcsv($handle, ['Closing Balance', '', '', '', '', '', $statement['gross'], $statement['management_fee'], $statement['expenses'], $statement['net']]);
            fclose($handle);
        }, 'owner-statement-'.$owner->id.'-'.$from->format('Ymd').'-'.$to->format('Ymd').'.csv', ['Content-Type' => 'text/csv']);
    }
}
