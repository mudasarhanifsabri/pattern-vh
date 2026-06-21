<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\Owner;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AccountingModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_accounting_pages_open_and_expense_can_be_recorded(): void
    {
        $this->seed();
        Storage::fake(config('filesystems.default'));

        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $owner = Owner::where('email', 'mariam.owner@example.com')->firstOrFail();
        $unit = Unit::where('unit_no', '1402')->firstOrFail();

        $this->actingAs($admin)->get(route('accounting.index'))->assertOk()->assertSee('Accounting command center');
        $this->actingAs($admin)->get(route('expenses.index'))->assertOk()->assertSee('Expense registry');
        $this->actingAs($admin)->get(route('expenses.create'))->assertOk()->assertSee('Expense name');

        $this->actingAs($admin)
            ->post(route('expenses.store'), [
                'name' => 'Owner AC service',
                'type' => 'maintenance',
                'expense_to_role' => 'owner',
                'expense_to_id' => $owner->id,
                'owner_id' => $owner->id,
                'unit_id' => $unit->id,
                'association' => 'owner_account',
                'incurred_on' => now()->toDateString(),
                'amount' => 450,
                'notes' => 'AC service for owner statement.',
                'receipt' => UploadedFile::fake()->create('receipt.pdf', 80, 'application/pdf'),
            ])
            ->assertRedirect();

        $expense = Expense::where('name', 'Owner AC service')->firstOrFail();

        $this->assertSame('owner_account', $expense->association);
        $this->assertNotNull($expense->receipt_path);

        $this->actingAs($admin)->get(route('expenses.show', $expense))->assertOk()->assertSee('Owner AC service');
        $this->actingAs($admin)->get(route('owner-statements.index', ['owner_id' => $owner->id]))->assertOk()->assertSee('Owner Account Statement')->assertSee('Statement PDF');
        $this->actingAs($admin)->get(route('owner-statements.pdf', ['owner_id' => $owner->id]))->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->actingAs($admin)->get(route('owner-payouts.index', ['owner_id' => $owner->id]))->assertOk()->assertSee('Owner Payouts')->assertSee('30 days');
        $this->actingAs($admin)->get(route('reports.index'))->assertOk()->assertSeeText('Reports & Exports');
        $this->actingAs($admin)->get(route('reports.export', ['type' => 'expenses']))->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_expense_target_flow_requires_matching_records(): void
    {
        $this->seed();

        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $owner = Owner::where('email', 'mariam.owner@example.com')->firstOrFail();
        $wrongUnit = Unit::whereDoesntHave('owners', fn ($query) => $query->whereKey($owner->id))->firstOrFail();
        $tenant = Tenant::where('email', 'nora.tenant@example.com')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('expenses.create'))
            ->assertOk()
            ->assertSee('Select the target first')
            ->assertSee('Select owner first')
            ->assertDontSee('Selected person ID');

        $this->actingAs($admin)
            ->post(route('expenses.store'), [
                'name' => 'Wrong unit expense',
                'type' => 'maintenance',
                'expense_to_role' => 'owner',
                'owner_id' => $owner->id,
                'unit_id' => $wrongUnit->id,
                'association' => 'owner_account',
                'incurred_on' => now()->toDateString(),
                'amount' => 125,
            ])
            ->assertSessionHasErrors('unit_id');

        $this->actingAs($admin)
            ->post(route('expenses.store'), [
                'name' => 'Tenant key delivery',
                'type' => 'other',
                'expense_to_role' => 'tenant',
                'expense_to_id' => $tenant->id,
                'association' => 'booking',
                'incurred_on' => now()->toDateString(),
                'amount' => 75,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas(Expense::class, [
            'name' => 'Tenant key delivery',
            'expense_to_role' => 'tenant',
            'expense_to_id' => $tenant->id,
            'owner_id' => null,
            'unit_id' => null,
        ]);
    }
}
