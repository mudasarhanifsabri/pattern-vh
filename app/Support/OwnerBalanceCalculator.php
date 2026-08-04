<?php

namespace App\Support;

use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Owner;
use App\Models\OwnerAccountEntry;
use App\Models\OwnerPayoutTransfer;

class OwnerBalanceCalculator
{
    public function calculate(Owner $owner, ?int $unitId = null): float
    {
        $units = $owner->units()->when($unitId, fn ($query) => $query->where('units.id', $unitId))->get();
        $unitIds = $units->pluck('id');
        $shareByUnit = $units->mapWithKeys(fn ($unit) => [$unit->id => (float) ($unit->pivot->share_percent ?? 100)]);
        $managementByUnit = $units->mapWithKeys(fn ($unit) => [$unit->id => (float) ($unit->management_fee_percent ?? 0)]);

        $rentNet = Invoice::query()
            ->whereIn('unit_id', $unitIds)
            ->where('paid_amount', '>', 0)
            ->whereNotIn('status', ['draft', 'cancelled'])
            ->get()
            ->sum(function (Invoice $invoice) use ($shareByUnit, $managementByUnit): float {
                $rentCollected = min((float) $invoice->paid_amount, (float) $invoice->rent_amount);
                $eligibleRent = min($rentCollected, max((float) $invoice->rent_amount - (float) $invoice->pattern_topup_amount, 0));
                $gross = $eligibleRent * (($shareByUnit[$invoice->unit_id] ?? 100) / 100);

                return $gross - ($gross * (($managementByUnit[$invoice->unit_id] ?? 0) / 100));
            });
        $manualNet = OwnerAccountEntry::where('owner_id', $owner->id)
            ->when($unitId, fn ($query) => $query->where('unit_id', $unitId))->get()
            ->sum(fn (OwnerAccountEntry $entry): float => ($entry->direction === 'credit' ? 1 : -1) * (float) $entry->amount);
        $expenses = (float) Expense::where('owner_id', $owner->id)
            ->when($unitId, fn ($query) => $query->where('unit_id', $unitId))->sum('amount');
        $payouts = (float) OwnerPayoutTransfer::where('owner_id', $owner->id)->whereNotNull('transferred_at')
            ->when($unitId, fn ($query) => $query->where('unit_id', $unitId))->sum('net_payout');

        return $rentNet + $manualNet - $expenses - $payouts;
    }
}
