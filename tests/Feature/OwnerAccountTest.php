<?php

namespace Tests\Feature;

use App\Models\Owner;
use App\Models\OwnerAccountEntry;
use App\Models\User;
use App\Models\Unit;
use App\Models\Building;
use App\Models\Invoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OwnerAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_owner_account_and_add_all_supported_entry_types(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $owner = Owner::create(['full_name' => 'Ledger Owner', 'mobile_no' => '+971500000001']);

        foreach (array_keys(OwnerAccountEntry::TYPES) as $index => $type) {
            $this->actingAs($admin)->post(route('owners.account.store', $owner), [
                'entry_date' => now()->toDateString(),
                'type' => $type,
                'direction' => $index % 2 ? 'debit' : 'credit',
                'amount' => 100 + $index,
                'description' => "Test {$type}",
                'reference_no' => "REF-{$index}",
            ])->assertRedirect(route('owners.account.index', $owner));
        }

        $this->assertSame(count(OwnerAccountEntry::TYPES), $owner->accountEntries()->count());
        $this->actingAs($admin)->get(route('owners.account.index', $owner))
            ->assertOk()
            ->assertSee('Statement of Account')
            ->assertSee('Test bank_transfer')
            ->assertSee('Test expense');
    }

    public function test_owner_account_is_paginated(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $owner = Owner::create(['full_name' => 'Paged Owner', 'mobile_no' => '+971500000002']);

        for ($i = 1; $i <= 16; $i++) {
            $owner->accountEntries()->create([
                'entry_date' => now()->subDays($i),
                'type' => 'adjustment',
                'direction' => 'credit',
                'amount' => $i,
                'description' => "Ledger row {$i}",
                'created_by' => $admin->id,
            ]);
        }

        $this->actingAs($admin)->get(route('owners.account.index', $owner))
            ->assertOk()
            ->assertSee('Ledger row 1')
            ->assertDontSee('Ledger row 16');
    }

    public function test_account_can_be_filtered_by_owner_unit_and_portal_owner_can_only_view_their_account(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $portalUser = User::factory()->create(['email' => 'portal-ledger@example.com']);
        $portalUser->assignRole('Owner');
        $owner = Owner::create(['full_name' => 'Portal Ledger', 'mobile_no' => '+971500000003', 'email' => $portalUser->email, 'user_id' => $portalUser->id]);
        $otherOwner = Owner::create(['full_name' => 'Other Ledger', 'mobile_no' => '+971500000004']);
        $building = Building::create(['name' => 'Portal Tower']);
        $unit = Unit::create([
            'building_id' => $building->id,
            'unit_no' => '1801',
            'unit_type' => '1 BHK',
        ]);
        $owner->units()->attach($unit->id, ['share_percent' => 100]);

        $this->actingAs($admin)->post(route('owners.account.store', $owner), [
            'unit_id' => $unit->id,
            'entry_date' => now()->toDateString(),
            'type' => 'rent_income',
            'direction' => 'credit',
            'amount' => 500,
            'description' => 'Unit-specific rent',
        ])->assertRedirect();

        $this->actingAs($portalUser)
            ->get(route('owners.account.index', [$owner, 'unit_id' => $unit->id]))
            ->assertOk()
            ->assertSee('Unit-specific rent');

        $this->actingAs($portalUser)
            ->get(route('owners.account.index', $otherOwner))
            ->assertForbidden();
    }

    public function test_paid_invoice_rent_and_management_fee_are_fetched_into_owner_account(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $invoice = Invoice::with('booking.unit.owners')->where('invoice_no', 'INV-DEMO-0001')->firstOrFail();
        $owner = $invoice->booking->unit->owners->firstOrFail();

        $this->actingAs($admin)
            ->get(route('owners.account.index', [$owner, 'unit_id' => $invoice->unit_id]))
            ->assertOk()
            ->assertSee('Rent collected')
            ->assertSee($invoice->invoice_no)
            ->assertSee('Paid invoice')
            ->assertSee('Invoice period')
            ->assertSee(($invoice->period_start ?: $invoice->stay_check_in_date)->format('M d, Y'));
    }
}
