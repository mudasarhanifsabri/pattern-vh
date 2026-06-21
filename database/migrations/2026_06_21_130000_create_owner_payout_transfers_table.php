<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('owner_payout_transfers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('owner_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('booking_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('gross_share', 12, 2)->default(0);
            $table->decimal('management_fee', 12, 2)->default(0);
            $table->decimal('net_payout', 12, 2)->default(0);
            $table->dateTime('transferred_at');
            $table->string('reference_no')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['owner_id', 'payment_id']);
            $table->index(['transferred_at', 'owner_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('owner_payout_transfers');
    }
};
