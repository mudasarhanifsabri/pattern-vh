<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Owner;
use App\Support\OwnerStatementPdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OwnerStatementController extends Controller
{
    public function index(Request $request)
    {
        $owner = $this->ownerFor($request);
        $from = $request->date('from') ?: now()->startOfMonth();
        $to = $request->date('to') ?: now()->endOfMonth();
        $statement = $owner ? $this->buildStatement($owner, $from, $to) : null;

        if ($owner && $request->boolean('export')) {
            return $this->export($owner, $statement, $from, $to);
        }

        return view('owner-statements.index', [
            'owners' => Owner::orderBy('full_name')->get(),
            'owner' => $owner,
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
        $statement = $this->buildStatement($owner, $from, $to);
        $filename = 'owner-statement-'.$owner->id.'-'.$from->format('Ymd').'-'.$to->format('Ymd').'.pdf';

        return response($pdf->make($owner, $statement, $from, $to), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }

    public function preview(Request $request)
    {
        $owner = $this->ownerFor($request);
        abort_unless($owner, 404);

        $from = $request->date('from') ?: now()->startOfMonth();
        $to = $request->date('to') ?: now()->endOfMonth();

        return view('owner-statements.pdf-preview', [
            'owner' => $owner,
            'from' => $from,
            'to' => $to,
            'pdfUrl' => route('owner-statements.pdf', $request->query()),
            'backUrl' => route('owner-statements.index', $request->query()),
        ]);
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

    private function buildStatement(Owner $owner, $from, $to): array
    {
        $unitIds = $owner->units->pluck('id');
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

        $rows = $revenueRows->concat($expenses)->sortBy('date')->values();

        return [
            'rows' => $rows,
            'gross' => $rows->sum('gross'),
            'management_fee' => $rows->sum('management_fee'),
            'expenses' => $rows->sum('owner_expense'),
            'net' => $rows->sum('net'),
        ];
    }

    private function export(Owner $owner, array $statement, $from, $to): StreamedResponse
    {
        return response()->streamDownload(function () use ($owner, $statement): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Owner', $owner->full_name]);
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
            fputcsv($handle, ['Totals', '', '', '', '', '', $statement['gross'], $statement['management_fee'], $statement['expenses'], $statement['net']]);
            fclose($handle);
        }, 'owner-statement-'.$owner->id.'-'.$from->format('Ymd').'-'.$to->format('Ymd').'.csv', ['Content-Type' => 'text/csv']);
    }
}
