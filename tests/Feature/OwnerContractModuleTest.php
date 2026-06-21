<?php

namespace Tests\Feature;

use App\Mail\OwnerContractSignatureLinkMail;
use App\Models\Owner;
use App\Models\OwnerUnitContract;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class OwnerContractModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_owner_contract_permissions_and_demo_contract_are_seeded(): void
    {
        $this->seed();

        $this->assertTrue(Role::findByName('Super Admin')->hasPermissionTo('owner-contracts.manage'));
        $this->assertTrue(Role::findByName('Owner')->hasPermissionTo('owner-contracts.view'));
        $this->assertDatabaseHas(OwnerUnitContract::class, ['contract_no' => 'PMC-DEMO-0001', 'status' => 'active']);
    }

    public function test_admin_can_create_and_view_owner_contract(): void
    {
        $this->seed();
        Mail::fake();
        Storage::fake(config('filesystems.default'));

        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $owner = Owner::where('email', 'mariam.owner@example.com')->firstOrFail();
        $unit = Unit::where('unit_no', '1402')->firstOrFail();

        $this->actingAs($admin)->get(route('owner-contracts.index'))->assertOk()->assertSee('Owner contracts');
        $this->actingAs($admin)->get(route('owner-contracts.create', ['owner_id' => $owner->id, 'unit_id' => $unit->id]))->assertOk()->assertSee('Contract setup');

        $this->actingAs($admin)
            ->post(route('owner-contracts.store'), [
                'owner_id' => $owner->id,
                'unit_id' => $unit->id,
                'status' => 'draft',
                'contract_start_date' => now()->toDateString(),
                'contract_end_date' => now()->addYear()->toDateString(),
                'company_name' => 'Pattern Vacation Homes Rental',
                'company_registration_no' => '1123804',
                'company_contact_no' => '+971 4 329 9693',
                'company_email' => 'customerservice@pattern.ae',
                'owner_name' => $owner->full_name,
                'owner_contact_no' => $owner->mobile_no,
                'owner_email' => $owner->email,
                'property_name' => 'Marina Vista',
                'property_no' => '1402',
                'property_type' => '1 BHK',
                'management_fee_percent' => 10,
                'startup_fee' => 3000,
                'furniture_fee' => 23800,
                'bank_currency' => 'AED',
                'contract_document' => UploadedFile::fake()->create('owner-contract.pdf', 100, 'application/pdf'),
                'send_signature_link' => '1',
            ])
            ->assertRedirect();

        $contract = OwnerUnitContract::where('owner_id', $owner->id)->latest()->firstOrFail();

        $this->assertNotNull($contract->contract_document_path);
        $this->assertNotNull($contract->signing_token);
        Mail::assertQueued(OwnerContractSignatureLinkMail::class);

        $this->actingAs($admin)->get(route('owner-contracts.show', $contract))->assertOk()->assertSee($contract->contract_no)->assertSee('Signature status');
        $this->actingAs($admin)->get(route('owner-contracts.prepared-document', $contract))->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->actingAs($admin)->get(route('owners.show', $owner))->assertOk()->assertSee('Unit contracts')->assertSee($contract->contract_no);

        $this->actingAs($admin)
            ->post(route('owner-contracts.signature-link', $contract))
            ->assertRedirect()
            ->assertSessionHas('status', 'Owner signature link is ready and emailed if owner email is available.');

        $contract->refresh();

        $this->get(route('owner-contracts.sign', [$contract, $contract->signing_token]))
            ->assertOk()
            ->assertSee('Management agreement')
            ->assertSee('Signature section');

        $this->post(route('owner-contracts.sign.store', [$contract, $contract->signing_token]), [
            'signed_by' => $owner->full_name,
            'signature_data' => 'data:image/png;base64,'.base64_encode('owner-signature'),
            'accepted_terms' => '1',
        ])
            ->assertRedirect()
            ->assertSessionHas('status', 'Owner contract signed successfully.');

        $this->assertDatabaseHas(OwnerUnitContract::class, [
            'id' => $contract->id,
            'owner_signature_name' => $owner->full_name,
        ]);
    }

    public function test_owner_portal_does_not_show_internal_contract_template_tags(): void
    {
        $this->seed();

        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $ownerUser = User::where('email', 'demo.owner@example.com')->firstOrFail();
        $contract = OwnerUnitContract::where('contract_no', 'PMC-DEMO-0001')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('owner-contracts.show', $contract))
            ->assertOk()
            ->assertSee('Admin template helper')
            ->assertSee('{{contract_no}}');

        $this->actingAs($ownerUser)
            ->get(route('owner-contracts.show', $contract))
            ->assertOk()
            ->assertDontSee('Admin template helper')
            ->assertDontSee('{{contract_no}}');
    }
}
