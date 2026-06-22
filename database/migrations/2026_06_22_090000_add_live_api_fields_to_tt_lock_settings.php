<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tt_lock_settings', function (Blueprint $table): void {
            $table->text('access_token')->nullable()->after('is_active');
            $table->text('refresh_token')->nullable()->after('access_token');
            $table->timestamp('token_expires_at')->nullable()->after('refresh_token');
            $table->timestamp('last_tested_at')->nullable()->after('token_expires_at');
            $table->text('last_error')->nullable()->after('last_tested_at');
        });
    }

    public function down(): void
    {
        Schema::table('tt_lock_settings', function (Blueprint $table): void {
            $table->dropColumn([
                'access_token',
                'refresh_token',
                'token_expires_at',
                'last_tested_at',
                'last_error',
            ]);
        });
    }
};
