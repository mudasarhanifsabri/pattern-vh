<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('owner_account_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('owner_id')->constrained()->cascadeOnDelete();
            $table->date('entry_date');
            $table->string('type', 40);
            $table->string('direction', 10);
            $table->decimal('amount', 12, 2);
            $table->string('reference_no', 120)->nullable();
            $table->string('description');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['owner_id', 'entry_date']);
            $table->index(['owner_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('owner_account_entries');
    }
};
