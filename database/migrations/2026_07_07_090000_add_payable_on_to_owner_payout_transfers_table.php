<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('owner_payout_transfers', function (Blueprint $table): void {
            $table->date('collection_date')->nullable()->after('net_payout');
            $table->date('payable_on')->nullable()->after('collection_date');
            $table->dateTime('transferred_at')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('owner_payout_transfers', function (Blueprint $table): void {
            $table->dateTime('transferred_at')->nullable(false)->change();
            $table->dropColumn(['collection_date', 'payable_on']);
        });
    }
};
