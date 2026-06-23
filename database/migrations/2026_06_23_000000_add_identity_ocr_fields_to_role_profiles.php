<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['owners', 'tenants', 'agents', 'operations_team_members'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                if (! Schema::hasColumn($tableName, 'nationality')) {
                    $table->string('nationality')->nullable()->after('date_of_birth');
                }

                if (! Schema::hasColumn($tableName, 'identity_issue_date')) {
                    $table->date('identity_issue_date')->nullable()->after('identity_no');
                }
            });
        }
    }

    public function down(): void
    {
        foreach (['owners', 'tenants', 'agents', 'operations_team_members'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                if (Schema::hasColumn($tableName, 'identity_issue_date')) {
                    $table->dropColumn('identity_issue_date');
                }

                if ($tableName !== 'tenants' && Schema::hasColumn($tableName, 'nationality')) {
                    $table->dropColumn('nationality');
                }
            });
        }
    }
};
