<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Owner;
use App\Models\OwnerAccountEntry;
use App\Models\OwnerPayoutTransfer;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\Rule;

class OwnerAccountController extends Controller
{
    public function index(Request $request, Owner $owner)
    {
        $this->authorizeOwner($request, $owner);
        $owner->load(['units.building']);

        $search = trim($request->string('search')->toString());
        $type = $request->string('type')->toString();
        $unitId = $request->integer('unit_id') ?: null;
        abort_if($unitId && ! $owner->units->contains('id', $unitId), 404);
        $from = $request->date('from');
        $to = $request->date('to');

        $manual = OwnerAccountEntry::query()
            ->with(['creator', 'unit.building'])
            ->where('owner_id', $owner->id)
            ->when($unitId, fn ($query) => $query->where('unit_id', $unitId))
            ->get()
            ->map(fn (OwnerAccountEntry $entry): array => [
                'key' => 'entry-'.$entry->id,
                'date' => $entry->entry_date,
                'type' => $entry->type,
                'type_label' => OwnerAccountEntry::TYPES[$entry->type] ?? str($entry->type)->headline(),
                'description' => $entry->description,
                'reference' => $entry->reference_no,
                'notes' => $entry->notes,
                'debit' => $entry->direction === 'debit' ? (float) $entry->amount : 0,
                'credit' => $entry->direction === 'credit' ? (float) $entry->amount : 0,
                'source' => 'Manual entry',
                'created_by' => $entry->creator?->name,
                'unit' => $entry->unit,
            ]);

        $expenses = Expense::query()
            ->with('unit.building')
            ->where('owner_id', $owner->id)
            ->when($unitId, fn ($query) => $query->where('unit_id', $unitId))
            ->get()
            ->map(fn (Expense $expense): array => [
                'key' => 'expense-'.$expense->id,
                'date' => $expense->incurred_on,
                'type' => 'expense',
                'type_label' => 'Expense',
                'description' => $expense->name,
                'reference' => $expense->expense_no,
                'notes' => $expense->unit ? $expense->unit->building->name.' / '.$expense->unit->unit_no : $expense->notes,
                'debit' => (float) $expense->amount,
                'credit' => 0,
                'source' => 'Expense registry',
                'created_by' => null,
                'unit' => $expense->unit,
            ]);

        $transfers = OwnerPayoutTransfer::query()
            ->where('owner_id', $owner->id)
            ->whereNotNull('transferred_at')
            ->when($unitId, fn ($query) => $query->where('unit_id', $unitId))
            ->with('unit.building')
            ->get()
            ->map(fn (OwnerPayoutTransfer $transfer): array => [
                'key' => 'transfer-'.$transfer->id,
                'date' => $transfer->transferred_at,
                'type' => 'bank_transfer',
                'type_label' => 'Bank transfer',
                'description' => 'Payout transferred to owner',
                'reference' => $transfer->reference_no,
                'notes' => $transfer->notes,
                'debit' => (float) $transfer->net_payout,
                'credit' => 0,
                'source' => 'Owner payout',
                'created_by' => null,
                'unit' => $transfer->unit,
            ]);

        $shareByUnit = $owner->units->mapWithKeys(
            fn ($unit) => [$unit->id => (float) ($unit->pivot->share_percent ?? 100)]
        );
        $managementByUnit = $owner->units->mapWithKeys(
            fn ($unit) => [$unit->id => (float) ($unit->management_fee_percent ?? 0)]
        );

        $rentRows = Invoice::query()
            ->with(['booking', 'unit.building', 'extensionRequest'])
            ->whereIn('unit_id', $owner->units->pluck('id'))
            ->when($unitId, fn ($query) => $query->where('unit_id', $unitId))
            ->where('paid_amount', '>', 0)
            ->whereNotIn('status', ['draft', 'cancelled'])
            ->get()
            ->flatMap(function (Invoice $invoice) use ($shareByUnit, $managementByUnit) {
                $rentCollected = min((float) $invoice->paid_amount, (float) $invoice->rent_amount);
                $ownerEligibleRent = min(
                    $rentCollected,
                    max((float) $invoice->rent_amount - (float) $invoice->pattern_topup_amount, 0)
                );
                $grossShare = $ownerEligibleRent * (($shareByUnit[$invoice->unit_id] ?? 100) / 100);
                $managementFee = $grossShare * (($managementByUnit[$invoice->unit_id] ?? 0) / 100);
                $date = $invoice->stay_check_out_date ?: $invoice->period_end ?: $invoice->invoice_date;

                if ($grossShare <= 0 || ! $date) {
                    return [];
                }

                $description = 'Rent collected — '.$invoice->invoice_no
                    .($invoice->booking?->booking_no ? ' / '.$invoice->booking->booking_no : '');
                $base = [
                    'date' => $date,
                    'reference' => $invoice->invoice_no,
                    'notes' => $invoice->unit?->building?->name.' / '.$invoice->unit?->unit_no,
                    'source' => 'Paid invoice',
                    'created_by' => null,
                    'unit' => $invoice->unit,
                ];

                $rows = [[
                    ...$base,
                    'key' => 'invoice-rent-'.$invoice->id,
                    'type' => 'rent_income',
                    'type_label' => 'Rent income',
                    'description' => $description,
                    'debit' => 0,
                    'credit' => $grossShare,
                ]];

                if ($managementFee > 0) {
                    $rows[] = [
                        ...$base,
                        'key' => 'invoice-fee-'.$invoice->id,
                        'type' => 'management_fee',
                        'type_label' => 'Management fee',
                        'description' => 'Management fee — '.$invoice->invoice_no,
                        'debit' => $managementFee,
                        'credit' => 0,
                    ];
                }

                return $rows;
            });

        $allRows = $manual->concat($rentRows)->concat($expenses)->concat($transfers)
            ->filter(function (array $row) use ($search, $type, $from, $to): bool {
                if ($type && $row['type'] !== $type) {
                    return false;
                }
                if ($from && $row['date']->lt($from)) {
                    return false;
                }
                if ($to && $row['date']->gt($to)) {
                    return false;
                }
                if ($search && ! str_contains(strtolower(implode(' ', [$row['description'], $row['reference'], $row['notes'], $row['source']])), strtolower($search))) {
                    return false;
                }

                return true;
            })
            ->sortBy(fn (array $row) => $row['date']->format('Y-m-d H:i:s').' '.$row['key'])
            ->values();

        $balance = 0;
        $allRows = $allRows->map(function (array $row) use (&$balance): array {
            $balance += $row['credit'] - $row['debit'];
            $row['balance'] = $balance;

            return $row;
        })->reverse()->values();

        $perPage = min(max($request->integer('per_page', 15), 10), 50);
        $page = LengthAwarePaginator::resolveCurrentPage();
        $entries = new LengthAwarePaginator(
            $allRows->forPage($page, $perPage)->values(),
            $allRows->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()],
        );

        return view('owners.account', [
            'owner' => $owner,
            'entries' => $entries,
            'types' => OwnerAccountEntry::TYPES,
            'selectedUnitId' => $unitId,
            'ownerPortal' => $request->user()->can('portal.owner') && ! $request->user()->can('owners.manage'),
            'totals' => [
                'credits' => $allRows->sum('credit'),
                'debits' => $allRows->sum('debit'),
                'balance' => $allRows->sum('credit') - $allRows->sum('debit'),
            ],
        ]);
    }

    public function store(Request $request, Owner $owner)
    {
        $data = $request->validate([
            'unit_id' => ['nullable', Rule::exists('owner_unit', 'unit_id')->where('owner_id', $owner->id)],
            'entry_date' => ['required', 'date'],
            'type' => ['required', Rule::in(array_keys(OwnerAccountEntry::TYPES))],
            'direction' => ['required', Rule::in(['credit', 'debit'])],
            'amount' => ['required', 'numeric', 'gt:0', 'max:9999999999.99'],
            'reference_no' => ['nullable', 'string', 'max:120'],
            'description' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $entry = $owner->accountEntries()->create($data + ['created_by' => $request->user()->id]);
        ActivityLogger::log('owner_accounts.entry_created', "Added {$entry->type} entry to {$owner->full_name}'s account.", $entry);

        return redirect()->route('owners.account.index', $owner)->with('status', 'Account entry added successfully.');
    }

    private function authorizeOwner(Request $request, Owner $owner): void
    {
        if ($request->user()->can('owners.view') || $request->user()->can('owners.manage')) {
            return;
        }

        $portalOwner = Owner::query()
            ->where('user_id', $request->user()->id)
            ->orWhere('email', $request->user()->email)
            ->first();

        abort_unless($portalOwner?->is($owner), 403);
    }
}
