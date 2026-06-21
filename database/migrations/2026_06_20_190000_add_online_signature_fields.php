<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            $table->longText('confirmation_signature_data')->nullable()->after('confirmation_signature_text');
            $table->string('confirmation_signature_mime')->nullable()->after('confirmation_signature_data');
            $table->string('confirmation_signed_user_agent', 500)->nullable()->after('confirmation_signed_ip');
        });

        Schema::table('owner_unit_contracts', function (Blueprint $table): void {
            $table->string('signing_token')->nullable()->after('status')->unique();
            $table->string('owner_signature_name')->nullable()->after('owner_signed_at');
            $table->longText('owner_signature_data')->nullable()->after('owner_signature_name');
            $table->string('owner_signed_ip')->nullable()->after('owner_signature_data');
            $table->string('owner_signed_user_agent', 500)->nullable()->after('owner_signed_ip');
            $table->string('company_signature_name')->nullable()->after('company_signed_at');
            $table->longText('company_signature_data')->nullable()->after('company_signature_name');
            $table->string('company_signed_ip')->nullable()->after('company_signature_data');
        });
    }

    public function down(): void
    {
        Schema::table('owner_unit_contracts', function (Blueprint $table): void {
            $table->dropUnique(['signing_token']);
            $table->dropColumn([
                'signing_token',
                'owner_signature_name',
                'owner_signature_data',
                'owner_signed_ip',
                'owner_signed_user_agent',
                'company_signature_name',
                'company_signature_data',
                'company_signed_ip',
            ]);
        });

        Schema::table('bookings', function (Blueprint $table): void {
            $table->dropColumn([
                'confirmation_signature_data',
                'confirmation_signature_mime',
                'confirmation_signed_user_agent',
            ]);
        });
    }
};
