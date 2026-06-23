<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_accounts', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('bank_name')->nullable();
            $table->string('account_no')->nullable();
            $table->string('iban')->nullable();
            $table->string('currency', 10)->default('AED');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('bank_statement_imports', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('bank_account_id')->constrained()->cascadeOnDelete();
            $table->string('original_name');
            $table->date('statement_from')->nullable();
            $table->date('statement_to')->nullable();
            $table->unsignedInteger('rows_total')->default(0);
            $table->unsignedInteger('rows_imported')->default(0);
            $table->unsignedInteger('rows_duplicate')->default(0);
            $table->string('status')->default('completed');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('bank_transactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('bank_account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bank_statement_import_id')->nullable()->constrained()->nullOnDelete();
            $table->date('transaction_date');
            $table->string('type', 20);
            $table->decimal('amount', 12, 2);
            $table->decimal('balance', 12, 2)->nullable();
            $table->string('reference_no')->nullable();
            $table->text('description')->nullable();
            $table->string('status', 30)->default('unmatched');
            $table->string('matched_type')->nullable();
            $table->unsignedBigInteger('matched_id')->nullable();
            $table->timestamp('matched_at')->nullable();
            $table->foreignId('matched_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('fingerprint', 64);
            $table->timestamps();

            $table->unique(['bank_account_id', 'fingerprint'], 'bank_txn_account_fingerprint_unique');
            $table->index(['status', 'transaction_date'], 'bank_txn_status_date_idx');
            $table->index(['matched_type', 'matched_id'], 'bank_txn_matched_idx');
        });

        Schema::create('bank_transaction_matches', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('bank_transaction_id')->constrained()->cascadeOnDelete();
            $table->string('matchable_type');
            $table->unsignedBigInteger('matchable_id');
            $table->unsignedTinyInteger('confidence')->default(50);
            $table->string('status', 30)->default('suggested');
            $table->string('reason')->nullable();
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();

            $table->unique(['bank_transaction_id', 'matchable_type', 'matchable_id'], 'bank_match_txn_target_unique');
            $table->index(['matchable_type', 'matchable_id'], 'bank_match_target_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_transaction_matches');
        Schema::dropIfExists('bank_transactions');
        Schema::dropIfExists('bank_statement_imports');
        Schema::dropIfExists('bank_accounts');
    }
};
