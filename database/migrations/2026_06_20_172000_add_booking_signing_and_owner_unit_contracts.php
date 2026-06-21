<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            $table->string('confirmation_token')->nullable()->unique()->after('confirmation_sent_at');
            $table->json('confirmation_delivery_channels')->nullable()->after('confirmation_token');
            $table->timestamp('confirmation_link_sent_at')->nullable()->after('confirmation_delivery_channels');
            $table->timestamp('confirmation_signed_at')->nullable()->after('confirmation_link_sent_at');
            $table->string('confirmation_signed_by')->nullable()->after('confirmation_signed_at');
            $table->string('confirmation_signature_text')->nullable()->after('confirmation_signed_by');
            $table->string('confirmation_signed_ip')->nullable()->after('confirmation_signature_text');
        });

        Schema::create('owner_unit_contracts', function (Blueprint $table): void {
            $table->id();
            $table->string('contract_no')->unique();
            $table->foreignId('owner_id')->constrained()->cascadeOnDelete();
            $table->foreignId('unit_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('draft');
            $table->date('contract_start_date')->nullable();
            $table->date('contract_end_date')->nullable();
            $table->date('effective_date')->nullable();
            $table->string('company_name')->default('Pattern Vacation Homes Rental');
            $table->string('company_registration_no')->default('1123804');
            $table->string('company_contact_no')->default('+971 4 329 9693');
            $table->string('company_email')->default('customerservice@pattern.ae');
            $table->string('company_address')->nullable();
            $table->string('owner_name');
            $table->string('owner_nationality')->nullable();
            $table->string('owner_passport_no')->nullable();
            $table->string('owner_contact_no')->nullable();
            $table->string('owner_email')->nullable();
            $table->string('property_name')->nullable();
            $table->string('floor_no')->nullable();
            $table->string('community')->nullable();
            $table->string('property_no')->nullable();
            $table->string('property_type')->nullable();
            $table->string('dewa_account_no')->nullable();
            $table->decimal('management_fee_percent', 5, 2)->default(10);
            $table->decimal('startup_fee', 12, 2)->nullable();
            $table->decimal('furniture_fee', 12, 2)->nullable();
            $table->decimal('vat_amount', 12, 2)->nullable();
            $table->decimal('grand_total', 12, 2)->nullable();
            $table->string('bank_account_holder')->nullable();
            $table->string('bank_currency')->default('AED');
            $table->string('bank_name')->nullable();
            $table->string('bank_account_no')->nullable();
            $table->string('iban')->nullable();
            $table->string('swift_code')->nullable();
            $table->text('special_terms')->nullable();
            $table->timestamp('company_signed_at')->nullable();
            $table->timestamp('owner_signed_at')->nullable();
            $table->string('signed_document_disk')->nullable();
            $table->string('signed_document_path')->nullable();
            $table->string('signed_document_original_name')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['owner_id', 'unit_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('owner_unit_contracts');

        Schema::table('bookings', function (Blueprint $table): void {
            $table->dropColumn([
                'confirmation_token',
                'confirmation_delivery_channels',
                'confirmation_link_sent_at',
                'confirmation_signed_at',
                'confirmation_signed_by',
                'confirmation_signature_text',
                'confirmation_signed_ip',
            ]);
        });
    }
};
