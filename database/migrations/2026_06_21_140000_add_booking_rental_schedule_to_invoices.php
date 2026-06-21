<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            $table->json('rental_periods')->nullable()->after('agency_fee');
        });

        Schema::table('invoices', function (Blueprint $table): void {
            $table->date('period_start')->nullable()->after('due_date');
            $table->date('period_end')->nullable()->after('period_start');
            $table->unsignedInteger('period_index')->nullable()->after('period_end');
            $table->boolean('is_initial_invoice')->default(false)->after('period_index');
            $table->index(['booking_id', 'period_index']);
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            $table->dropIndex(['booking_id', 'period_index']);
            $table->dropColumn(['period_start', 'period_end', 'period_index', 'is_initial_invoice']);
        });

        Schema::table('bookings', function (Blueprint $table): void {
            $table->dropColumn('rental_periods');
        });
    }
};
