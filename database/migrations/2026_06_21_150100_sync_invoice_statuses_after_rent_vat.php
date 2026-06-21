<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('invoices')
            ->where('status', 'paid')
            ->where('balance_amount', '>', 0)
            ->update(['status' => 'partially_paid']);
    }

    public function down(): void
    {
        //
    }
};
