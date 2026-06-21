<?php

namespace Tests\Feature;

use App\Models\InventoryItem;
use App\Models\OperationsTeamMember;
use App\Models\Unit;
use App\Models\User;
use App\Models\UtilityAccount;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class OperationsManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_operations_permissions_and_demo_data_are_seeded(): void
    {
        $this->seed();

        $this->assertTrue(Role::findByName('Super Admin')->hasPermissionTo('utilities.manage'));
        $this->assertTrue(Role::findByName('Operations Team')->hasPermissionTo('vehicles.manage'));
        $this->assertTrue(Role::findByName('Cleaner')->hasPermissionTo('vehicles.manage'));
        $this->assertDatabaseHas(UtilityAccount::class, ['provider_name' => 'DEWA']);
        $this->assertDatabaseHas(Vehicle::class, ['plate_no' => 'D 45231']);
        $this->assertDatabaseHas(InventoryItem::class, ['sku' => 'LOCK-BATTERY-AA']);
    }

    public function test_admin_can_open_operations_pages_and_record_workflows(): void
    {
        $this->seed();

        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $unit = Unit::where('unit_no', '1402')->firstOrFail();
        $teamMember = OperationsTeamMember::where('team_role', 'cleaner')->firstOrFail();

        $this->actingAs($admin)->get(route('utilities.index'))->assertOk()->assertSee('Utility management')->assertSee('Due calendar');
        $this->actingAs($admin)->get(route('vehicles.index'))->assertOk()->assertSee('Vehicle management')->assertSee('Check in / check out');
        $this->actingAs($admin)->get(route('inventory.index'))->assertOk()->assertSee('Inventory management')->assertSee('Inventory register');
        $this->actingAs($admin)
            ->get(route('planning-sheet.index', ['preset' => '14_days', 'start' => now()->toDateString()]))
            ->assertOk()
            ->assertSee('Planning sheet')
            ->assertSee('Pending invoices');
        $this->actingAs($admin)
            ->get(route('planning-sheet.pdf', ['preset' => '2_days', 'start' => now()->toDateString()]))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->actingAs($admin)
            ->post(route('utilities.accounts.store'), [
                'unit_id' => $unit->id,
                'provider_type' => 'gas',
                'provider_name' => 'Lootah Gas',
                'account_no' => 'GAS-1402',
                'billing_day' => 21,
                'next_due_date' => now()->addDays(5)->toDateString(),
                'estimated_amount' => 120,
                'status' => 'active',
                'paid_by_company' => '1',
            ])
            ->assertRedirect();

        $utilityAccount = UtilityAccount::where('provider_name', 'Lootah Gas')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('utilities.bills.store'), [
                'utility_account_id' => $utilityAccount->id,
                'due_date' => now()->addDays(5)->toDateString(),
                'amount' => 118,
                'status' => 'pending',
            ])
            ->assertRedirect();

        $vehicle = Vehicle::create(['name' => 'Test Van', 'plate_no' => 'T 10001', 'status' => 'available']);

        $this->actingAs($admin)
            ->post(route('vehicles.handover', $vehicle), [
                'team_member_id' => $teamMember->id,
                'handover_type' => 'check_out',
                'handover_at' => now()->format('Y-m-d H:i:s'),
                'odometer' => 1200,
                'fuel_level' => 'Full',
                'remarks' => 'Assigned for checkout cleaning.',
            ])
            ->assertRedirect();

        $this->assertSame('checked_out', $vehicle->fresh()->status);

        $item = InventoryItem::create([
            'name' => 'Towels',
            'category' => 'linen',
            'quantity' => 20,
            'reorder_level' => 5,
            'status' => 'available',
        ]);

        $this->actingAs($admin)
            ->post(route('inventory.movement', $item), [
                'movement_type' => 'stock_out',
                'quantity' => 3,
                'reference' => 'Unit 1402',
            ])
            ->assertRedirect();

        $this->assertEquals(17, (float) $item->fresh()->quantity);
    }
}
