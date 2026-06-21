<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('utility_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained()->cascadeOnDelete();
            $table->string('provider_type');
            $table->string('provider_name');
            $table->string('account_no')->nullable();
            $table->string('username')->nullable();
            $table->string('password')->nullable();
            $table->boolean('paid_by_company')->default(false);
            $table->unsignedTinyInteger('billing_day')->nullable();
            $table->date('next_due_date')->nullable();
            $table->decimal('estimated_amount', 12, 2)->nullable();
            $table->string('status')->default('active');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('utility_bills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('utility_account_id')->constrained()->cascadeOnDelete();
            $table->date('bill_date')->nullable();
            $table->date('due_date');
            $table->decimal('amount', 12, 2);
            $table->string('status')->default('pending');
            $table->string('receipt_disk')->nullable();
            $table->string('receipt_path')->nullable();
            $table->string('receipt_original_name')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('plate_no')->unique();
            $table->string('vehicle_type')->nullable();
            $table->string('make_model')->nullable();
            $table->string('status')->default('available');
            $table->unsignedInteger('odometer')->nullable();
            $table->date('registration_expiry_date')->nullable();
            $table->date('insurance_expiry_date')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('vehicle_handovers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('team_member_id')->nullable()->constrained('operations_team_members')->nullOnDelete();
            $table->string('handover_type');
            $table->dateTime('handover_at');
            $table->unsignedInteger('odometer')->nullable();
            $table->string('fuel_level')->nullable();
            $table->json('photos')->nullable();
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('category')->default('general');
            $table->string('sku')->nullable()->unique();
            $table->string('storage_location')->nullable();
            $table->decimal('quantity', 12, 2)->default(0);
            $table->decimal('reorder_level', 12, 2)->default(0);
            $table->decimal('unit_cost', 12, 2)->nullable();
            $table->string('status')->default('available');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_item_id')->constrained()->cascadeOnDelete();
            $table->string('movement_type');
            $table->decimal('quantity', 12, 2);
            $table->nullableMorphs('related');
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
        Schema::dropIfExists('inventory_items');
        Schema::dropIfExists('vehicle_handovers');
        Schema::dropIfExists('vehicles');
        Schema::dropIfExists('utility_bills');
        Schema::dropIfExists('utility_accounts');
    }
};
