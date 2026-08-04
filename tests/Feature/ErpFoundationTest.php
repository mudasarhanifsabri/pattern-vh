<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Unit;
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

    public function test_owner_dashboard_hides_unit_rent_and_shows_booking_duration(): void
    {
        $this->seed();

        $owner = User::where('email', 'demo.owner@example.com')->firstOrFail();
        $booking = Booking::query()
            ->whereHas('unit', fn ($query) => $query->where('unit_no', '1402'))
            ->whereIn('booking_status', Booking::ACTIVE_STATUSES)
            ->orderBy('check_in_date')
            ->firstOrFail();

        $this->actingAs($owner)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('<title>Pattern Owner Portal</title>', false)
            ->assertSee('owner-portal-app', false)
            ->assertDontSee('patternSidebarCollapsed', false)
            ->assertSee('My units status')
            ->assertSee('Unit 1402')
            ->assertDontSee('Rent AED 8,500.00')
            ->assertSee('Nora Al Mansoori')
            ->assertSee($booking->check_in_date->format('M d, Y').' to '.$booking->check_out_date->format('M d, Y'));
    }

    public function test_owner_can_open_portal_pages(): void
    {
        $this->seed();

        $owner = User::where('email', 'demo.owner@example.com')->firstOrFail();
        $unit = Unit::where('unit_no', '1402')->firstOrFail();

        $this->actingAs($owner);

        foreach ([
            route('dashboard'),
            route('units.index'),
            route('units.show', $unit),
            route('owner-statements.index'),
            route('owner-payouts.index'),
            route('owner-contracts.index'),
            route('reports.index'),
        ] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_manifest_and_service_worker_are_available(): void
    {
        $this->assertFileExists(public_path('manifest.webmanifest'));
        $this->assertFileExists(public_path('service-worker.js'));
    }
}
