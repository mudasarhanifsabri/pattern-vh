<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tt_lock_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tt_lock_id')->nullable()->constrained('tt_locks')->nullOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained()->nullOnDelete();
            $table->string('lock_id')->nullable()->index();
            $table->string('lock_name')->nullable();
            $table->string('event_type')->default('unlock')->index();
            $table->string('operator_name')->nullable();
            $table->string('keyboard_pwd')->nullable();
            $table->string('record_id')->nullable()->index();
            $table->timestamp('event_at')->nullable()->index();
            $table->string('source')->default('callback');
            $table->json('payload')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tt_lock_events');
    }
};
