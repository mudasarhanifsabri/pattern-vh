<?php

namespace App\Support;

use App\Models\Booking;
use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class BookingInvoiceScheduler
{
    public function syncInvoices(Booking $booking): void
    {
        $booking->loadMissing(['tenant', 'unit']);

        if (! in_array($booking->booking_status, ['confirmed', 'checked_in', 'checkout_requested'], true)) {
            return;
        }

        $periods = $this->periodsFor($booking);

        if ($periods->isEmpty()) {
            return;
        }

        $periods->each(function (array $period) use ($booking): void {
            $isInitial = (int) $period['index'] === 1;
            $rent = (float) $period['rent_amount'];
            $deposit = $isInitial ? (float) $booking->deposit_amount : 0;
            $dtcm = $isInitial ? (float) $booking->dtcm_fee : 0;
            $cleaning = $isInitial ? (float) $booking->cleaning_fee : 0;
            $agency = $isInitial ? (float) $booking->agency_fee : 0;
            $vat = TaxCalculator::rentVat($rent);
            $total = $rent + $vat + $deposit + $dtcm + $cleaning + $agency;

            $invoice = Invoice::firstOrNew([
                'booking_id' => $booking->id,
                'period_index' => $period['index'],
            ]);

            if ($invoice->exists && in_array($invoice->status, ['paid', 'partially_paid'], true)) {
                return;
            }

            $invoice->fill([
                'invoice_no' => $invoice->invoice_no ?: $this->nextInvoiceNo(),
                'tenant_id' => $booking->tenant_id,
                'unit_id' => $booking->unit_id,
                'invoice_date' => $period['start'],
                'due_date' => $period['start'],
                'period_start' => $period['start'],
                'period_end' => $period['end'],
                'is_initial_invoice' => $isInitial,
                'rent_amount' => $rent,
                'deposit_amount' => $deposit,
                'dtcm_fee' => $dtcm,
                'cleaning_fee' => $cleaning,
                'agency_fee' => $agency,
                'vat_amount' => $vat,
                'total_amount' => $total,
                'paid_amount' => 0,
                'balance_amount' => $total,
                'status' => 'sent',
                'notes' => $isInitial
                    ? 'Initial booking invoice with rent and booking charges.'
                    : 'Monthly rent invoice for '.$period['label'].'.',
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ])->save();
        });
    }

    public function cancelFutureUnpaidInvoices(Booking $booking): int
    {
        return Invoice::query()
            ->where('booking_id', $booking->id)
            ->whereNotIn('status', ['paid', 'partially_paid', 'cancelled'])
            ->whereDate('period_start', '>', today())
            ->update([
                'status' => 'cancelled',
                'balance_amount' => 0,
                'updated_by' => auth()->id(),
                'notes' => 'Cancelled automatically because booking checked out early.',
            ]);
    }

    public function periodsFor(Booking $booking): Collection
    {
        $stored = collect($booking->rental_periods ?? [])
            ->filter(fn ($period): bool => ! empty($period['start']) && ! empty($period['end']));

        if ($stored->isNotEmpty()) {
            return $stored->values()->map(fn (array $period, int $index): array => [
                'index' => (int) ($period['index'] ?? $index + 1),
                'label' => $period['label'] ?? Carbon::parse($period['start'])->format('M Y'),
                'start' => Carbon::parse($period['start'])->toDateString(),
                'end' => Carbon::parse($period['end'])->toDateString(),
                'rent_amount' => (float) ($period['rent_amount'] ?? 0),
            ]);
        }

        $start = $booking->check_in_date?->copy()->startOfDay();
        $end = $booking->check_out_date?->copy()->subDay()->startOfDay();

        if (! $start || ! $end || $end->lessThan($start)) {
            return collect();
        }

        $periods = collect();
        $cursor = $start->copy();
        $index = 1;

        while ($cursor->lessThanOrEqualTo($end)) {
            $periodEnd = $cursor->copy()->addMonthNoOverflow()->subDay();
            if ($periodEnd->greaterThan($end)) {
                $periodEnd = $end->copy();
            }

            $periods->push([
                'index' => $index,
                'label' => $cursor->format('M Y'),
                'start' => $cursor->toDateString(),
                'end' => $periodEnd->toDateString(),
                'rent_amount' => (float) $booking->rent_amount,
            ]);

            $cursor = $periodEnd->copy()->addDay();
            $index++;
        }

        return $periods;
    }

    private function nextInvoiceNo(): string
    {
        return 'INV-'.now()->format('Ymd').'-'.str_pad((string) (Invoice::withTrashed()->whereDate('created_at', today())->count() + 1), 4, '0', STR_PAD_LEFT);
    }
}
