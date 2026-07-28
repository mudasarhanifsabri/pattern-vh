<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            $table->date('payout_due_date')->nullable()->after('period_end');
        });

        DB::table('invoices')
            ->select(['id', 'booking_id', 'period_end'])
            ->orderBy('id')
            ->eachById(function (object $invoice): void {
                $payoutDueDate = $invoice->period_end;

                if (! $payoutDueDate && $invoice->booking_id) {
                    $payoutDueDate = DB::table('bookings')->where('id', $invoice->booking_id)->value('check_out_date');
                }

                if ($payoutDueDate) {
                    DB::table('invoices')->where('id', $invoice->id)->update(['payout_due_date' => $payoutDueDate]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            $table->dropColumn('payout_due_date');
        });
    }
};
