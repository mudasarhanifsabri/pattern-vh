<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('owner_payout_transfers', function (Blueprint $table): void {
            $table->decimal('owner_expenses', 12, 2)->default(0)->after('management_fee');
        });
    }

    public function down(): void
    {
        Schema::table('owner_payout_transfers', function (Blueprint $table): void {
            $table->dropColumn('owner_expenses');
        });
    }
};
