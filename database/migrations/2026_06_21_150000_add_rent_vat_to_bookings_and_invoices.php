<?php

use App\Support\TaxCalculator;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            $table->decimal('vat_amount', 12, 2)->default(0)->after('agency_fee');
        });

        Schema::table('invoices', function (Blueprint $table): void {
            $table->decimal('vat_amount', 12, 2)->default(0)->after('agency_fee');
        });

        DB::table('bookings')->orderBy('id')->each(function (object $booking): void {
            $rent = collect(json_decode($booking->rental_periods ?? '[]', true) ?: [])
                ->sum(fn (array $period): float => (float) ($period['rent_amount'] ?? 0));
            $rent = $rent > 0 ? $rent : (float) $booking->rent_amount;
            $vat = TaxCalculator::rentVat($rent);
            $total = $rent + $vat + (float) $booking->deposit_amount + (float) $booking->dtcm_fee + (float) $booking->cleaning_fee + (float) $booking->agency_fee;

            DB::table('bookings')->where('id', $booking->id)->update([
                'vat_amount' => $vat,
                'total_amount' => round($total, 2),
            ]);
        });

        DB::table('invoices')->orderBy('id')->each(function (object $invoice): void {
            $vat = TaxCalculator::rentVat($invoice->rent_amount);
            $total = (float) $invoice->rent_amount + $vat + (float) $invoice->deposit_amount + (float) $invoice->dtcm_fee + (float) $invoice->cleaning_fee + (float) $invoice->agency_fee;
            $paid = (float) $invoice->paid_amount;

            DB::table('invoices')->where('id', $invoice->id)->update([
                'vat_amount' => $vat,
                'total_amount' => round($total, 2),
                'balance_amount' => max(0, round($total - $paid, 2)),
                'status' => match (true) {
                    $invoice->status === 'cancelled' => 'cancelled',
                    $paid >= $total => 'paid',
                    $paid > 0 => 'partially_paid',
                    default => $invoice->status,
                },
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            $table->dropColumn('vat_amount');
        });

        Schema::table('bookings', function (Blueprint $table): void {
            $table->dropColumn('vat_amount');
        });
    }
};
