<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = collect([
            'dashboard.view',
            'users.view',
            'users.manage',
            'roles.view',
            'roles.manage',
            'permissions.view',
            'permissions.manage',
            'activity.view',
            'profile.manage',
            'owners.view',
            'owners.manage',
            'tenants.view',
            'tenants.manage',
            'agents.view',
            'agents.manage',
            'operations-team.view',
            'operations-team.manage',
            'buildings.view',
            'buildings.manage',
            'units.view',
            'units.manage',
            'bookings.view',
            'bookings.manage',
            'availability-calendar.view',
            'booking-tasks.view',
            'booking-tasks.manage',
            'notifications.view',
            'notifications.manage',
            'utilities.view',
            'utilities.manage',
            'vehicles.view',
            'vehicles.manage',
            'inventory.view',
            'inventory.manage',
            'accounting.view',
            'accounting.manage',
            'expenses.view',
            'expenses.manage',
            'owner-statements.view',
            'owner-statements.manage',
            'owner-payouts.view',
            'owner-payouts.manage',
            'owner-contracts.view',
            'owner-contracts.manage',
            'reports.view',
            'reports.export',
            'invoices.view',
            'invoices.manage',
            'payments.view',
            'payments.manage',
            'security-deposits.view',
            'security-deposits.manage',
            'payment-collection-requests.view',
            'payment-collection-requests.manage',
            'receipts.view',
            'receipts.manage',
            'dtcm-checkins.view',
            'dtcm-checkins.manage',
            'checkin-inspections.view',
            'checkin-inspections.manage',
            'support.view',
            'support.manage',
            'support.reports',
            'software-updates.manage',
            'portal.owner',
            'portal.tenant',
            'portal.agent',
            'portal.operations',
            'portal.cleaner',
            'portal.technician',
        ])->map(fn (string $permission) => Permission::findOrCreate($permission, 'web'));

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $rolePermissions = [
            'Super Admin' => $permissions->pluck('name')->all(),
            'Owner' => ['dashboard.view', 'profile.manage', 'units.view', 'owner-statements.view', 'owner-payouts.view', 'owner-contracts.view', 'reports.view', 'support.view', 'portal.owner'],
            'Tenant' => ['dashboard.view', 'profile.manage', 'bookings.view', 'invoices.view', 'receipts.view', 'support.view', 'portal.tenant'],
            'Agent' => ['dashboard.view', 'profile.manage', 'support.view', 'portal.agent'],
            'Operations Team' => ['dashboard.view', 'profile.manage', 'owners.view', 'owners.manage', 'tenants.view', 'tenants.manage', 'agents.view', 'agents.manage', 'operations-team.view', 'operations-team.manage', 'buildings.view', 'buildings.manage', 'units.view', 'units.manage', 'bookings.view', 'bookings.manage', 'availability-calendar.view', 'booking-tasks.view', 'booking-tasks.manage', 'notifications.view', 'notifications.manage', 'utilities.view', 'utilities.manage', 'vehicles.view', 'vehicles.manage', 'inventory.view', 'inventory.manage', 'accounting.view', 'accounting.manage', 'expenses.view', 'expenses.manage', 'owner-statements.view', 'owner-statements.manage', 'owner-payouts.view', 'owner-payouts.manage', 'owner-contracts.view', 'owner-contracts.manage', 'reports.view', 'reports.export', 'invoices.view', 'invoices.manage', 'payments.view', 'payments.manage', 'security-deposits.view', 'security-deposits.manage', 'payment-collection-requests.view', 'payment-collection-requests.manage', 'receipts.view', 'receipts.manage', 'dtcm-checkins.view', 'dtcm-checkins.manage', 'checkin-inspections.view', 'checkin-inspections.manage', 'support.view', 'support.manage', 'support.reports', 'portal.operations'],
            'Cleaner' => ['dashboard.view', 'profile.manage', 'booking-tasks.view', 'vehicles.view', 'vehicles.manage', 'inventory.view', 'support.view', 'portal.cleaner'],
            'Technician' => ['dashboard.view', 'profile.manage', 'booking-tasks.view', 'vehicles.view', 'vehicles.manage', 'inventory.view', 'support.view', 'portal.technician'],
        ];

        foreach ($rolePermissions as $roleName => $permissionNames) {
            Role::findOrCreate($roleName, 'web')->syncPermissions($permissionNames);
        }

        $role = Role::findByName('Super Admin', 'web');

        $user = User::updateOrCreate(
            ['email' => env('SUPER_ADMIN_EMAIL', 'admin@example.com')],
            [
                'name' => env('SUPER_ADMIN_NAME', 'Super Admin'),
                'email_verified_at' => now(),
                'password' => Hash::make(env('SUPER_ADMIN_PASSWORD', 'ChangeMe123!')),
            ],
        );

        $user->syncRoles([$role]);

        $this->call(CeoUserSeeder::class);
        $this->call(SupportCenterSeeder::class);

        if ((bool) env('SEED_DEMO_DATA', app()->environment(['local', 'testing']))) {
            $this->call(DemoPortfolioSeeder::class);
        }
    }
}
