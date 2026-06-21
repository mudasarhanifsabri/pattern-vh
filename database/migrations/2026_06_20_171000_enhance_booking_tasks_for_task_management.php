<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_tasks', function (Blueprint $table): void {
            $table->string('priority')->default('normal')->after('status');
            $table->dateTime('started_at')->nullable()->after('priority');
            $table->dateTime('completed_at')->nullable()->after('started_at');
            $table->text('completion_notes')->nullable()->after('completed_at');
            $table->json('checklist')->nullable()->after('completion_notes');
            $table->json('attachments')->nullable()->after('checklist');
        });

        Schema::create('booking_task_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('booking_task_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event_type');
            $table->text('description')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->index(['booking_task_id', 'created_at']);
            $table->index('event_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_task_events');

        Schema::table('booking_tasks', function (Blueprint $table): void {
            $table->dropColumn(['priority', 'started_at', 'completed_at', 'completion_notes', 'checklist', 'attachments']);
        });
    }
};
