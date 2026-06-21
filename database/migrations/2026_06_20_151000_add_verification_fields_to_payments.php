<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->string('status')->default('pending')->after('method');
            $table->string('proof_disk')->nullable()->after('notes');
            $table->string('proof_path')->nullable()->after('proof_disk');
            $table->string('proof_original_name')->nullable()->after('proof_path');
            $table->foreignId('approved_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            $table->text('verification_notes')->nullable()->after('approved_at');
            $table->index(['status', 'paid_at']);
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropIndex(['status', 'paid_at']);
            $table->dropColumn([
                'status',
                'proof_disk',
                'proof_path',
                'proof_original_name',
                'approved_by',
                'approved_at',
                'verification_notes',
            ]);
        });
    }
};
