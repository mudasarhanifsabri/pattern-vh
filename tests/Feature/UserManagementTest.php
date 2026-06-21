<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_seeded_roles_and_permissions_are_available(): void
    {
        $this->seed();

        foreach (['Super Admin', 'Owner', 'Tenant', 'Operations Team', 'Cleaner', 'Technician'] as $role) {
            $this->assertDatabaseHas('roles', ['name' => $role]);
        }

        foreach (['users.manage', 'roles.manage', 'permissions.manage', 'activity.view', 'portal.owner', 'portal.cleaner', 'portal.technician'] as $permission) {
            $this->assertDatabaseHas('permissions', ['name' => $permission]);
        }
    }

    public function test_super_admin_can_open_user_management_pages(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@example.com')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('User management');

        $this->actingAs($admin)
            ->get(route('admin.roles.index'))
            ->assertOk()
            ->assertSee('Portal roles');

        $this->actingAs($admin)
            ->get(route('admin.permissions.index'))
            ->assertOk()
            ->assertSee('Permission catalog');
    }

    public function test_admin_can_create_user_and_assign_role(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@example.com')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'Test Cleaner',
                'email' => 'cleaner@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'roles' => ['Cleaner'],
            ])
            ->assertRedirect(route('admin.users.index'));

        $user = User::where('email', 'cleaner@example.com')->firstOrFail();

        $this->assertTrue($user->hasRole('Cleaner'));
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'users.created',
            'subject_id' => $user->id,
        ]);
    }

    public function test_admin_can_send_password_setup_email_when_creating_user(): void
    {
        Notification::fake();
        $this->seed();
        $admin = User::where('email', 'admin@example.com')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'Portal Owner',
                'email' => 'owner.portal@example.com',
                'send_password_setup' => '1',
                'roles' => ['Owner'],
            ])
            ->assertRedirect(route('admin.users.index'));

        $user = User::where('email', 'owner.portal@example.com')->firstOrFail();

        Notification::assertSentTo($user, ResetPassword::class);
        $this->assertTrue($user->hasRole('Owner'));
    }

    public function test_admin_can_update_role_permissions(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $role = Role::findByName('Cleaner');
        Permission::findOrCreate('cleaning.tasks.view');

        $this->actingAs($admin)
            ->put(route('admin.roles.update', $role), [
                'name' => 'Cleaner',
                'permissions' => ['dashboard.view', 'profile.manage', 'portal.cleaner', 'cleaning.tasks.view'],
            ])
            ->assertRedirect(route('admin.roles.index'));

        $this->assertTrue($role->fresh()->hasPermissionTo('cleaning.tasks.view'));
        $this->assertTrue(ActivityLog::where('action', 'roles.updated')->exists());
    }
}
