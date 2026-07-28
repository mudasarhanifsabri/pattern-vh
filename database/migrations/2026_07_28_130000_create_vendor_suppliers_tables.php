<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendors', function (Blueprint $table): void {
            $table->id();
            $table->string('supplier_no')->unique();
            $table->string('company_name');
            $table->string('legal_name')->nullable();
            $table->string('contact_person')->nullable();
            $table->string('mobile_no', 50)->nullable();
            $table->string('email')->nullable();
            $table->string('category')->default('other');
            $table->string('trade_license_no')->nullable()->unique();
            $table->date('trade_license_expiry_date')->nullable();
            $table->string('tax_registration_no')->nullable();
            $table->text('address')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bank_account_name')->nullable();
            $table->string('iban', 100)->nullable();
            $table->string('payment_terms')->nullable();
            $table->string('status')->default('pending_review');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['status', 'category']);
        });

        Schema::create('vendor_documents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            $table->string('document_type');
            $table->string('title')->nullable();
            $table->string('document_number', 100)->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('disk')->nullable();
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['vendor_id', 'document_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_documents');
        Schema::dropIfExists('vendors');
    }
};
