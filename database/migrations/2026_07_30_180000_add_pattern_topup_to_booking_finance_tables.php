<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', fn (Blueprint $table) => $table->decimal('pattern_topup_amount', 12, 2)->default(0)->after('rent_amount'));
        Schema::table('invoices', fn (Blueprint $table) => $table->decimal('pattern_topup_amount', 12, 2)->default(0)->after('rent_amount'));
        Schema::table('booking_extension_requests', fn (Blueprint $table) => $table->decimal('pattern_topup_amount', 12, 2)->default(0)->after('extra_rent_amount'));
    }

    public function down(): void
    {
        Schema::table('booking_extension_requests', fn (Blueprint $table) => $table->dropColumn('pattern_topup_amount'));
        Schema::table('invoices', fn (Blueprint $table) => $table->dropColumn('pattern_topup_amount'));
        Schema::table('bookings', fn (Blueprint $table) => $table->dropColumn('pattern_topup_amount'));
    }
};
