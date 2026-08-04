<?php

namespace App\Support;

use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Owner;
use App\Models\OwnerAccountEntry;
use App\Models\OwnerPayoutTransfer;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class OwnerBalanceCalculator
{
    public function calculate(Owner $owner, ?int $unitId = null, ?Carbon $asOf = null): float
    {
        $units = $owner->units()->when($unitId, fn ($query) => $query->where('units.id', $unitId))->get();
        $unitIds = $units->pluck('id');
        $shareByUnit = $units->mapWithKeys(fn ($unit) => [$unit->id => (float) ($unit->pivot->share_percent ?? 100)]);
        $managementByUnit = $units->mapWithKeys(fn ($unit) => [$unit->id => (float) ($unit->management_fee_percent ?? 0)]);

        $rentNet = Invoice::query()
            ->with(['booking', 'extensionRequest'])
            ->whereIn('unit_id', $unitIds)
            ->where('paid_amount', '>', 0)
            ->whereNotIn('status', ['draft', 'cancelled'])
            ->get()
            ->when($asOf, fn (Collection $invoices) => $invoices->filter(fn (Invoice $invoice) => $invoice->stay_check_out_date?->lte($asOf)))
            ->sum(fn (Invoice $invoice): float => $this->invoiceNet($invoice, $shareByUnit, $managementByUnit));
        $manualNet = OwnerAccountEntry::where('owner_id', $owner->id)
            ->when($unitId, fn ($query) => $query->where('unit_id', $unitId))
            ->when($asOf, fn ($query) => $query->whereDate('entry_date', '<=', $asOf->toDateString()))->get()
            ->sum(fn (OwnerAccountEntry $entry): float => ($entry->direction === 'credit' ? 1 : -1) * (float) $entry->amount);
        $expenses = (float) Expense::where('owner_id', $owner->id)
            ->when($unitId, fn ($query) => $query->where('unit_id', $unitId))
            ->when($asOf, fn ($query) => $query->whereDate('incurred_on', '<=', $asOf->toDateString()))->sum('amount');
        $payouts = (float) OwnerPayoutTransfer::where('owner_id', $owner->id)->whereNotNull('transferred_at')
            ->when($unitId, fn ($query) => $query->where('unit_id', $unitId))
            ->when($asOf, fn ($query) => $query->whereDate('transferred_at', '<=', $asOf->toDateString()))->sum('net_payout');

        return $rentNet + $manualNet - $expenses - $payouts;
    }

    public function upcoming(Owner $owner, ?Carbon $asOf = null): Collection
    {
        $asOf ??= today();
        $units = $owner->units()->get();
        $unitIds = $units->pluck('id');
        $shareByUnit = $units->mapWithKeys(fn ($unit) => [$unit->id => (float) ($unit->pivot->share_percent ?? 100)]);
        $managementByUnit = $units->mapWithKeys(fn ($unit) => [$unit->id => (float) ($unit->management_fee_percent ?? 0)]);

        return Invoice::query()
            ->with(['booking.tenant', 'unit.building', 'extensionRequest'])
            ->whereIn('unit_id', $unitIds)
            ->where('paid_amount', '>', 0)
            ->whereNotIn('status', ['draft', 'cancelled'])
            ->get()
            ->filter(fn (Invoice $invoice) => $invoice->stay_check_out_date?->gt($asOf))
            ->map(fn (Invoice $invoice): array => [
                'invoice' => $invoice,
                'amount' => $this->invoiceNet($invoice, $shareByUnit, $managementByUnit),
                'checkout' => $invoice->stay_check_out_date,
                'is_extension' => $invoice->extensionRequest !== null,
            ])
            ->filter(fn (array $row) => $row['amount'] > 0)
            ->sortBy('checkout')
            ->values();
    }

    private function invoiceNet(Invoice $invoice, Collection $shareByUnit, Collection $managementByUnit): float
    {
        $rentCollected = min((float) $invoice->paid_amount, (float) $invoice->rent_amount);
        $eligibleRent = min($rentCollected, max((float) $invoice->rent_amount - (float) $invoice->pattern_topup_amount, 0));
        $gross = $eligibleRent * (($shareByUnit[$invoice->unit_id] ?? 100) / 100);

        return $gross - ($gross * (($managementByUnit[$invoice->unit_id] ?? 0) / 100));
    }
}
