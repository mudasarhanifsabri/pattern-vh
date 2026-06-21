<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('owner_unit_contracts', function (Blueprint $table): void {
            $table->string('contract_document_disk')->nullable()->after('signing_token');
            $table->string('contract_document_path')->nullable()->after('contract_document_disk');
            $table->string('contract_document_original_name')->nullable()->after('contract_document_path');
            $table->timestamp('signature_link_emailed_at')->nullable()->after('owner_signed_user_agent');
        });
    }

    public function down(): void
    {
        Schema::table('owner_unit_contracts', function (Blueprint $table): void {
            $table->dropColumn([
                'contract_document_disk',
                'contract_document_path',
                'contract_document_original_name',
                'signature_link_emailed_at',
            ]);
        });
    }
};
