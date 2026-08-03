<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('owner_opening_balances');
    }

    public function down(): void
    {
        // The removed opening-balance feature is intentionally not restored.
    }
};
