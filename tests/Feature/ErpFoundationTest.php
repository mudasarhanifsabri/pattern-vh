<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ErpFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_dashboard(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Operations overview');
    }

    public function test_super_admin_bypasses_gate_authorization(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::create(['name' => 'Super Admin']));
        Gate::define('manage-system', fn (): bool => false);

        $this->assertTrue($user->can('manage-system'));
    }

    public function test_database_seeder_creates_super_admin(): void
    {
        $this->seed();

        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->assertTrue($admin->hasRole('Super Admin'));
    }

    public function test_owner_dashboard_shows_unit_rent_and_booking_duration(): void
    {
        $this->seed();

        $owner = User::where('email', 'demo.owner@example.com')->firstOrFail();

        $this->actingAs($owner)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('My units status')
            ->assertSee('Unit 1402')
            ->assertSee('Rent AED 8,500.00')
            ->assertSee('Nora Al Mansoori')
            ->assertSee('Jul 23, 2026 to Jul 28, 2026');
    }

    public function test_manifest_and_service_worker_are_available(): void
    {
        $this->assertFileExists(public_path('manifest.webmanifest'));
        $this->assertFileExists(public_path('service-worker.js'));
    }
}
