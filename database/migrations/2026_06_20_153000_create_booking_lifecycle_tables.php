<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_extension_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->date('requested_check_out_date');
            $table->decimal('extra_rent_amount', 12, 2)->default(0);
            $table->string('status')->default('requested');
            $table->text('tenant_notes')->nullable();
            $table->text('approval_notes')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'requested_check_out_date']);
        });

        Schema::create('booking_deposit_refunds', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->decimal('deposit_amount', 12, 2)->default(0);
            $table->decimal('damage_amount', 12, 2)->default(0);
            $table->decimal('refund_amount', 12, 2)->default(0);
            $table->string('status')->default('pending_inspection');
            $table->text('inspection_notes')->nullable();
            $table->text('damage_report')->nullable();
            $table->timestamp('inspection_completed_at')->nullable();
            $table->timestamp('tenant_accepted_at')->nullable();
            $table->timestamp('refund_processed_at')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_deposit_refunds');
        Schema::dropIfExists('booking_extension_requests');
    }
};
