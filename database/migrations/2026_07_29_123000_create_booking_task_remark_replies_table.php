<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The failed server migration may have created the table before MySQL rejected the index name.
        if (Schema::hasTable('booking_task_remark_replies')) {
            return;
        }

        // Replies are deliberately separate from remarks so each maintainer update keeps its own conversation thread.
        Schema::create('booking_task_remark_replies', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('booking_task_remark_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('reply');
            $table->timestamps();
            // MySQL index identifiers are limited to 64 characters.
            $table->index(['booking_task_remark_id', 'created_at'], 'task_remark_replies_remark_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_task_remark_replies');
    }
};
