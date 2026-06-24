<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\OperationsTeamMember;
use App\Models\Owner;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PeopleModulesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_people_module_permissions_are_seeded(): void
    {
        $this->seed();

        $superAdmin = Role::findByName('Super Admin');

        $this->assertTrue($superAdmin->hasPermissionTo('tenants.manage'));
        $this->assertTrue($superAdmin->hasPermissionTo('agents.manage'));
        $this->assertTrue($superAdmin->hasPermissionTo('operations-team.manage'));
    }

    public function test_admin_can_create_tenant_agent_and_operations_records(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@example.com')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('tenants.store'), [
                'full_name' => 'Nora Tenant',
                'mobile_no' => '+971501010101',
                'email' => 'tenant@example.com',
                'identity_type' => 'emirates_id',
                'nationality' => 'UAE',
                'emergency_contact_name' => 'Emergency Person',
                'emergency_contact_mobile' => '+971502020202',
            ])
            ->assertRedirect();

        $this->actingAs($admin)
            ->post(route('agents.store'), [
                'full_name' => 'Amin Agent',
                'mobile_no' => '+971503030303',
                'email' => 'agent@example.com',
                'identity_type' => 'passport',
                'agency_name' => 'Pattern Broker Network',
                'rera_no' => 'RERA-100',
                'commission_percent' => 5,
            ])
            ->assertRedirect();

        $this->actingAs($admin)
            ->post(route('operations-team.store'), [
                'full_name' => 'Sara Cleaner',
                'mobile_no' => '+971504040404',
                'email' => 'cleaner@example.com',
                'identity_type' => 'emirates_id',
                'team_role' => 'cleaner',
                'specialty' => 'Checkout cleaning',
                'service_area' => 'Dubai Marina',
                'auto_assign_checkout_cleaning' => '1',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas(Tenant::class, ['full_name' => 'Nora Tenant']);
        $this->assertDatabaseHas(Agent::class, ['full_name' => 'Amin Agent', 'commission_percent' => 5]);
        $this->assertDatabaseHas(OperationsTeamMember::class, [
            'full_name' => 'Sara Cleaner',
            'team_role' => 'cleaner',
            'auto_assign_checkout_cleaning' => true,
        ]);
    }

    public function test_shared_people_modules_open_existing_duplicate_instead_of_creating_new_rows(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@example.com')->firstOrFail();

        $modules = [
            ['route' => 'tenants', 'model' => Tenant::class, 'email' => 'duplicate.tenant@example.com', 'extra' => ['emergency_contact_name' => 'Emergency']],
            ['route' => 'agents', 'model' => Agent::class, 'email' => 'duplicate.agent@example.com', 'extra' => ['commission_percent' => 5]],
            ['route' => 'operations-team', 'model' => OperationsTeamMember::class, 'email' => 'duplicate.cleaner@example.com', 'extra' => ['team_role' => 'cleaner']],
        ];

        foreach ($modules as $module) {
            $payload = array_merge([
                'full_name' => 'Duplicate Person',
                'mobile_no' => '+971501010999',
                'email' => $module['email'],
                'identity_type' => 'passport',
                'identity_no' => 'P-DUP-100',
            ], $module['extra']);

            $this->actingAs($admin)->post(route($module['route'].'.store'), $payload)->assertRedirect();
            $first = $module['model']::where('email', $module['email'])->firstOrFail();

            $this->actingAs($admin)
                ->post(route($module['route'].'.store'), array_merge($payload, ['mobile_no' => '050 101 0999']))
                ->assertRedirect(route($module['route'].'.show', $first));

            $this->assertSame(1, $module['model']::where('email', $module['email'])->count());
            $this->assertSame(1, $module['model']::where('identity_no', 'P-DUP-100')->count());
        }
    }

    public function test_people_create_pages_open_and_demo_data_is_seeded(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@example.com')->firstOrFail();

        $this->actingAs($admin)->get(route('tenants.create'))->assertOk()->assertSee('Add Tenant');
        $this->actingAs($admin)->get(route('agents.create'))->assertOk()->assertSee('Commission %');
        $this->actingAs($admin)->get(route('operations-team.create'))->assertOk()->assertSee('Task management setup');

        $this->assertDatabaseHas(Tenant::class, ['email' => 'nora.tenant@example.com']);
        $this->assertDatabaseHas(Agent::class, ['email' => 'amin.agent@example.com', 'commission_percent' => 5]);
        $this->assertDatabaseHas(OperationsTeamMember::class, ['email' => 'sara.cleaner@example.com', 'team_role' => 'cleaner']);
        $this->assertDatabaseHas(OperationsTeamMember::class, ['email' => 'omar.technician@example.com', 'team_role' => 'technician']);
    }

    public function test_all_people_module_core_pages_open(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@example.com')->firstOrFail();

        $modules = [
            'owners' => Owner::where('email', 'mariam.owner@example.com')->firstOrFail(),
            'tenants' => Tenant::where('email', 'nora.tenant@example.com')->firstOrFail(),
            'agents' => Agent::where('email', 'amin.agent@example.com')->firstOrFail(),
            'operations-team' => OperationsTeamMember::where('email', 'sara.cleaner@example.com')->firstOrFail(),
        ];

        foreach ($modules as $route => $record) {
            $this->actingAs($admin)->get(route($route.'.index'))->assertOk();
            $this->actingAs($admin)->get(route($route.'.create'))->assertOk();
            $this->actingAs($admin)->get(route($route.'.show', $record))->assertOk();
            $this->actingAs($admin)->get(route($route.'.edit', $record))->assertOk();
        }

        $this->actingAs($admin)
            ->get(route('owners.show', $modules['owners']))
            ->assertOk()
            ->assertSee('Owner units')
            ->assertSee('Unit contracts');

        $this->actingAs($admin)
            ->get(route('tenants.show', $modules['tenants']))
            ->assertOk()
            ->assertSee('Booking history')
            ->assertSee('Current booking');
    }
}
