<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            $table->string('smart_lock_keyboard_pwd_id')->nullable()->after('smart_lock_code_generated_at');
            $table->timestamp('smart_lock_code_changed_by_tenant_at')->nullable()->after('smart_lock_keyboard_pwd_id');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            $table->dropColumn([
                'smart_lock_keyboard_pwd_id',
                'smart_lock_code_changed_by_tenant_at',
            ]);
        });
    }
};
