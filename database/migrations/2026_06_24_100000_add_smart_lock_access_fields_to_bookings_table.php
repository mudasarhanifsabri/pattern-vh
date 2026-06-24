<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            $table->string('smart_lock_code_mode')->default('auto')->after('notes');
            $table->string('smart_lock_code')->nullable()->after('smart_lock_code_mode');
            $table->dateTime('smart_lock_code_valid_from')->nullable()->after('smart_lock_code');
            $table->dateTime('smart_lock_code_valid_until')->nullable()->after('smart_lock_code_valid_from');
            $table->timestamp('smart_lock_code_generated_at')->nullable()->after('smart_lock_code_valid_until');
            $table->text('smart_lock_code_note')->nullable()->after('smart_lock_code_generated_at');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            $table->dropColumn([
                'smart_lock_code_mode',
                'smart_lock_code',
                'smart_lock_code_valid_from',
                'smart_lock_code_valid_until',
                'smart_lock_code_generated_at',
                'smart_lock_code_note',
            ]);
        });
    }
};
