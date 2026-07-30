<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('owner_account_entries', function (Blueprint $table): void {
            $table->foreignId('unit_id')->nullable()->after('owner_id')->constrained()->nullOnDelete();
            $table->index(['owner_id', 'unit_id', 'entry_date']);
        });
    }

    public function down(): void
    {
        Schema::table('owner_account_entries', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('unit_id');
        });
    }
};
