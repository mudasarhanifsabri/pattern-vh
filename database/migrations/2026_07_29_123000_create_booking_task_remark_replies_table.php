<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Replies are deliberately separate from remarks so each maintainer update keeps its own conversation thread.
        Schema::create('booking_task_remark_replies', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('booking_task_remark_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('reply');
            $table->timestamps();
            $table->index(['booking_task_remark_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_task_remark_replies');
    }
};
