<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table): void {
            $table->id();
            $table->string('expense_no')->unique();
            $table->string('name');
            $table->string('type');
            $table->string('expense_to_role')->default('company');
            $table->unsignedBigInteger('expense_to_id')->nullable();
            $table->foreignId('owner_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained()->nullOnDelete();
            $table->string('association')->default('company');
            $table->date('incurred_on');
            $table->decimal('amount', 12, 2);
            $table->text('notes')->nullable();
            $table->string('receipt_disk')->nullable();
            $table->string('receipt_path')->nullable();
            $table->string('receipt_original_name')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['expense_to_role', 'expense_to_id']);
            $table->index(['owner_id', 'unit_id', 'incurred_on']);
            $table->index(['type', 'incurred_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
