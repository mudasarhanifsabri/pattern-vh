<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Older extensions overwrote bookings.check_out_date. Their first extension invoice starts on the real original checkout.
        DB::table('booking_extension_requests as extension')
            ->join('invoices as invoice', 'invoice.id', '=', 'extension.invoice_id')
            ->whereNotNull('invoice.period_start')
            ->whereNull('invoice.deleted_at')
            ->selectRaw('extension.booking_id, MIN(invoice.period_start) as original_checkout')
            ->groupBy('extension.booking_id')
            ->orderBy('extension.booking_id')
            ->each(function (object $period): void {
                DB::table('bookings')
                    ->where('id', $period->booking_id)
                    ->whereDate('check_out_date', '>', $period->original_checkout)
                    ->update([
                        'check_out_date' => $period->original_checkout,
                        'updated_at' => now(),
                    ]);
            });
    }

    public function down(): void
    {
        // Restoring a historical overwrite is intentionally irreversible.
    }
};
