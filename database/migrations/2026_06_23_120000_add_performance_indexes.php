<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->index('bookings', ['tenant_id', 'booking_status', 'check_in_date'], 'bookings_tenant_status_checkin_idx');
        $this->index('bookings', ['check_out_date', 'booking_status'], 'bookings_checkout_status_idx');
        $this->index('invoices', ['tenant_id', 'balance_amount'], 'invoices_tenant_balance_idx');
        $this->index('invoices', ['balance_amount', 'due_date'], 'invoices_balance_due_idx');
        $this->index('payments', ['status', 'paid_at'], 'payments_status_paid_at_idx');
        $this->index('payments', ['booking_id', 'status'], 'payments_booking_status_idx');
        $this->index('booking_tasks', ['status', 'priority', 'due_at'], 'booking_tasks_status_priority_due_idx');
        $this->index('booking_tasks', ['task_type', 'status', 'due_at'], 'booking_tasks_type_status_due_idx');
        $this->index('notification_logs', ['recipient', 'created_at'], 'notification_logs_recipient_created_idx');
        $this->index('notification_logs', ['status', 'created_at'], 'notification_logs_status_created_idx');
        $this->index('units', ['availability_status', 'unit_type'], 'units_availability_type_idx');
        $this->index('owner_unit_contracts', ['contract_end_date', 'status'], 'owner_contracts_end_status_idx');
        $this->index('utility_bills', ['status', 'due_date'], 'utility_bills_status_due_idx');
    }

    public function down(): void
    {
        $this->dropIndex('utility_bills', 'utility_bills_status_due_idx');
        $this->dropIndex('owner_unit_contracts', 'owner_contracts_end_status_idx');
        $this->dropIndex('units', 'units_availability_type_idx');
        $this->dropIndex('notification_logs', 'notification_logs_status_created_idx');
        $this->dropIndex('notification_logs', 'notification_logs_recipient_created_idx');
        $this->dropIndex('booking_tasks', 'booking_tasks_type_status_due_idx');
        $this->dropIndex('booking_tasks', 'booking_tasks_status_priority_due_idx');
        $this->dropIndex('payments', 'payments_booking_status_idx');
        $this->dropIndex('payments', 'payments_status_paid_at_idx');
        $this->dropIndex('invoices', 'invoices_balance_due_idx');
        $this->dropIndex('invoices', 'invoices_tenant_balance_idx');
        $this->dropIndex('bookings', 'bookings_checkout_status_idx');
        $this->dropIndex('bookings', 'bookings_tenant_status_checkin_idx');
    }

    private function index(string $table, array $columns, string $name): void
    {
        if (! Schema::hasTable($table) || $this->hasIndex($table, $name)) {
            return;
        }

        Schema::table($table, fn (Blueprint $blueprint) => $blueprint->index($columns, $name));
    }

    private function dropIndex(string $table, string $name): void
    {
        if (! Schema::hasTable($table) || ! $this->hasIndex($table, $name)) {
            return;
        }

        Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropIndex($name));
    }

    private function hasIndex(string $table, string $name): bool
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            foreach (DB::select("PRAGMA index_list('".$this->escapeSqliteIdentifier($table)."')") as $index) {
                if (($index->name ?? null) === $name) {
                    return true;
                }
            }

            return false;
        }

        return DB::table('information_schema.statistics')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', $table)
            ->where('index_name', $name)
            ->exists();
    }

    private function escapeSqliteIdentifier(string $identifier): string
    {
        return str_replace("'", "''", $identifier);
    }
};
