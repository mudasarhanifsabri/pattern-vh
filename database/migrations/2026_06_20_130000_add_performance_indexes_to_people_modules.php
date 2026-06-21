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
                $table->index('created_at', "{$tableName}_created_at_idx");
                $table->index('deleted_at', "{$tableName}_deleted_at_idx");
                $table->index('identity_expiry_date', "{$tableName}_identity_expiry_idx");
                $table->index('portal_invitation_sent_at', "{$tableName}_portal_invite_idx");

                if ($tableName !== 'owners') {
                    $table->index('is_blacklisted', "{$tableName}_blacklisted_idx");
                }
            });
        }

        Schema::table('operations_team_members', function (Blueprint $table): void {
            $table->index('auto_assign_checkout_cleaning', 'ops_auto_cleaning_idx');
            $table->index('auto_assign_checkout_inspection', 'ops_auto_inspection_idx');
            $table->index('auto_assign_stay_tasks', 'ops_auto_stay_tasks_idx');
        });
    }

    public function down(): void
    {
        Schema::table('operations_team_members', function (Blueprint $table): void {
            $table->dropIndex('ops_auto_cleaning_idx');
            $table->dropIndex('ops_auto_inspection_idx');
            $table->dropIndex('ops_auto_stay_tasks_idx');
        });

        foreach (['owners', 'tenants', 'agents', 'operations_team_members'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                $table->dropIndex("{$tableName}_created_at_idx");
                $table->dropIndex("{$tableName}_deleted_at_idx");
                $table->dropIndex("{$tableName}_identity_expiry_idx");
                $table->dropIndex("{$tableName}_portal_invite_idx");

                if ($tableName !== 'owners') {
                    $table->dropIndex("{$tableName}_blacklisted_idx");
                }
            });
        }
    }
};
