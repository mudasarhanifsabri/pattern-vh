<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class CeoUserSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'ceo.dashboard', 'dashboard.view', 'profile.manage', 'accounting.view', 'expenses.view',
            'owner-statements.view', 'owner-payouts.view', 'reports.view', 'reports.export',
            'invoices.view', 'payments.view', 'bookings.view', 'availability-calendar.view',
            'booking-tasks.view', 'units.view', 'owners.view',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $role = Role::findOrCreate('CEO', 'web');
        $role->syncPermissions($permissions);
        Role::where('name', 'Super Admin')->first()?->givePermissionTo(['ceo.dashboard', 'software-updates.manage']);

        $user = User::updateOrCreate(
            ['email' => env('CEO_EMAIL', 'ceo@pattern.ae')],
            [
                'name' => env('CEO_NAME', 'Pattern CEO'),
                'email_verified_at' => now(),
                'password' => Hash::make(env('CEO_PASSWORD', 'ChangeMe123!')),
            ],
        );
        $user->syncRoles([$role]);
    }
}
