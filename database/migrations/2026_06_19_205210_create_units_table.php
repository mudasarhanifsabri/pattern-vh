<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('building_id')->constrained()->cascadeOnDelete();
            $table->string('unit_no');
            $table->string('unit_type');
            $table->string('availability_status')->default('available');
            $table->string('floor')->nullable();
            $table->unsignedInteger('bedrooms')->nullable();
            $table->unsignedInteger('bathrooms')->nullable();
            $table->decimal('size_sqft', 10, 2)->nullable();
            $table->string('view')->nullable();
            $table->string('parking_no')->nullable();
            $table->string('wifi_name')->nullable();
            $table->string('wifi_password')->nullable();
            $table->decimal('management_fee_percent', 5, 2)->nullable();
            $table->string('rent_period')->default('monthly');
            $table->decimal('rent_amount', 12, 2)->nullable();
            $table->json('amenities')->nullable();
            $table->json('pictures')->nullable();
            $table->string('internet_provider')->nullable();
            $table->string('internet_account_no')->nullable();
            $table->string('electricity_company')->nullable();
            $table->boolean('electricity_paid_by_us')->default(false);
            $table->string('electricity_username')->nullable();
            $table->string('electricity_password')->nullable();
            $table->string('gas_company')->nullable();
            $table->text('gas_details')->nullable();
            $table->text('hvac_details')->nullable();
            $table->text('other_utility_details')->nullable();
            $table->string('title_deed_no')->nullable();
            $table->date('title_deed_expiry_date')->nullable();
            $table->string('title_deed_disk')->nullable();
            $table->string('title_deed_path')->nullable();
            $table->string('title_deed_original_name')->nullable();
            $table->string('dtcm_permit_no')->nullable();
            $table->date('dtcm_permit_expiry_date')->nullable();
            $table->string('dtcm_permit_disk')->nullable();
            $table->string('dtcm_permit_path')->nullable();
            $table->string('dtcm_permit_original_name')->nullable();
            $table->json('ttlock_settings')->nullable();
            $table->string('ttlock_attachment_disk')->nullable();
            $table->string('ttlock_attachment_path')->nullable();
            $table->string('ttlock_attachment_original_name')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['building_id', 'unit_no']);
            $table->index(['unit_type', 'availability_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('units');
    }
};
