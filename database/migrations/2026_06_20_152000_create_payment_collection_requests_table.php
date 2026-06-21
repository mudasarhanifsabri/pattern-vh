<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_collection_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('request_no')->unique();
            $table->string('collection_method')->default('cash');
            $table->decimal('amount', 12, 2);
            $table->date('preferred_date')->nullable();
            $table->string('preferred_time_window')->nullable();
            $table->string('contact_mobile')->nullable();
            $table->boolean('contact_has_whatsapp')->default(true);
            $table->string('collection_address')->nullable();
            $table->string('status')->default('requested');
            $table->text('tenant_notes')->nullable();
            $table->text('office_notes')->nullable();
            $table->foreignId('assigned_to_id')->nullable()->constrained('operations_team_members')->nullOnDelete();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('collected_at')->nullable();
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'preferred_date']);
            $table->index(['tenant_id', 'status']);
        });

        Schema::table('payments', function (Blueprint $table): void {
            $table->foreignId('collection_request_id')->nullable()->after('booking_id')->constrained('payment_collection_requests')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('collection_request_id');
        });

        Schema::dropIfExists('payment_collection_requests');
    }
};
