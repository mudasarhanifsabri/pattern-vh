<?php

namespace Tests\Feature;

use App\Mail\UnitAccessCardRequestMail;
use App\Models\Building;
use App\Models\Owner;
use App\Models\TtLock;
use App\Models\TtLockSetting;
use App\Models\Unit;
use App\Models\User;
use App\Models\UtilityAccount;
use App\Support\UnitDocumentOcr;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PortfolioModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_portfolio_permissions_are_seeded(): void
    {
        $this->seed();

        $this->assertTrue(Role::findByName('Super Admin')->hasPermissionTo('buildings.manage'));
        $this->assertTrue(Role::findByName('Operations Team')->hasPermissionTo('units.manage'));
    }

    public function test_admin_can_create_building_with_security_emails_and_coordinates(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@example.com')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('buildings.store'), [
                'name' => 'Horizon Tower',
                'area' => 'Dubai Marina',
                'security_emails' => 'security@example.com, management@example.com',
                'latitude' => '25.0800000',
                'longitude' => '55.1400000',
                'amenities' => "Pool\nGym",
            ])
            ->assertRedirect();

        $building = Building::where('name', 'Horizon Tower')->firstOrFail();

        $this->assertSame(['security@example.com', 'management@example.com'], $building->security_emails);
        $this->assertSame(['Pool', 'Gym'], $building->amenities);
    }

    public function test_admin_can_view_portfolio_pages(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $building = Building::create(['name' => 'Horizon Tower']);
        $unit = Unit::create([
            'building_id' => $building->id,
            'unit_no' => '1204',
            'unit_type' => '1 BHK',
            'availability_status' => 'available',
            'rent_period' => 'monthly',
        ]);

        $this->actingAs($admin)->get(route('buildings.index'))->assertOk()->assertSee('Building registry');
        $this->actingAs($admin)->get(route('buildings.create'))->assertOk()->assertSee('Security emails');
        $this->actingAs($admin)->get(route('buildings.show', $building))->assertOk()->assertSee('Horizon Tower');
        $this->actingAs($admin)->get(route('units.index'))->assertOk()->assertSee('Apartment registry');
        $this->actingAs($admin)
            ->get(route('units.create'))
            ->assertOk()
            ->assertSee('Owner share allocation')
            ->assertSee('Utility accounts')
            ->assertSee('Smart lock')
            ->assertSee('title_deed_issue_date')
            ->assertSee('No utility accounts added yet')
            ->assertDontSee('Legacy quick notes');
        $this->actingAs($admin)
            ->get(route('units.show', $unit))
            ->assertOk()
            ->assertSee('Unit 1204')
            ->assertSee('Bookings')
            ->assertSee('Current / upcoming')
            ->assertSee('Booking history');
    }

    public function test_owner_can_only_view_assigned_units(): void
    {
        $this->seed();

        $ownerUser = User::where('email', 'demo.owner@example.com')->firstOrFail();
        $owner = Owner::where('user_id', $ownerUser->id)->firstOrFail();
        $ownedUnit = $owner->units()->firstOrFail();
        $otherUnit = Unit::whereDoesntHave('owners', fn ($query) => $query->whereKey($owner->id))->firstOrFail();

        $this->assertTrue($ownerUser->can('units.view'));

        $this->actingAs($ownerUser)
            ->get(route('units.show', $ownedUnit))
            ->assertOk()
            ->assertSee($ownedUnit->unit_no);

        $this->actingAs($ownerUser)
            ->get(route('units.show', $otherUnit))
            ->assertForbidden();

        $this->actingAs($ownerUser)
            ->get(route('units.index'))
            ->assertOk()
            ->assertSee($ownedUnit->unit_no)
            ->assertDontSee($otherUnit->unit_no);
    }

    public function test_admin_can_create_unit_with_owner_share_and_documents(): void
    {
        $this->seed();
        $disk = config('filesystems.default');
        Storage::fake($disk);

        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $building = Building::create(['name' => 'Horizon Tower']);
        $owner = Owner::create(['full_name' => 'Layla Hassan', 'mobile_no' => '+971501234567']);
        $ttLock = TtLock::create([
            'lock_name' => 'Horizon 1204 Main Door',
            'lock_id' => 'TT-1204',
            'gateway_id' => 'GW-1204',
            'battery_level' => 91,
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->post(route('units.store'), [
                'building_id' => $building->id,
                'unit_no' => '1204',
                'unit_type' => '1 BHK',
                'availability_status' => 'available',
                'rent_period' => 'monthly',
                'rent_amount' => '12000',
                'management_fee_percent' => '20',
                'ownership_rows' => [
                    ['owner_id' => $owner->id, 'share_percent' => 100],
                ],
                'internet_provider' => 'du',
                'internet_account_no' => 'DU-123',
                'electricity_company' => 'DEWA',
                'electricity_paid_by_us' => '1',
                'utility_accounts' => [
                    [
                        'provider_type' => 'dewa',
                        'provider_name' => 'DEWA',
                        'account_no' => 'DEWA-1204',
                        'billing_day' => 15,
                        'next_due_date' => '2026-07-15',
                        'estimated_amount' => '850',
                        'paid_by_company' => '1',
                    ],
                    [
                        'provider_type' => 'internet',
                        'provider_name' => 'du',
                        'account_no' => 'DU-123',
                    ],
                ],
                'title_deed_no' => 'TD-100',
                'title_deed_issue_date' => '2026-06-01',
                'title_deed' => UploadedFile::fake()->create('title.pdf', 100, 'application/pdf'),
                'dtcm_permit_no' => 'DTCM-200',
                'dtcm_permit' => UploadedFile::fake()->create('dtcm.pdf', 100, 'application/pdf'),
                'tt_lock_id' => $ttLock->id,
                'pictures_upload' => [UploadedFile::fake()->image('living.jpg')],
            ])
            ->assertRedirect();

        $unit = Unit::where('unit_no', '1204')->firstOrFail();

        $this->assertTrue($unit->owners()->where('owners.id', $owner->id)->exists());
        $this->assertEquals(100, (float) $unit->owners()->first()->pivot->share_percent);
        $this->assertSame('2026-06-01', $unit->title_deed_issue_date->format('Y-m-d'));
        Storage::disk($disk)->assertExists($unit->title_deed_path);
        Storage::disk($disk)->assertExists($unit->dtcm_permit_path);
        $this->assertCount(1, $unit->pictures);
        $this->assertSame($ttLock->id, $unit->tt_lock_id);
        $this->assertTrue(UtilityAccount::where('unit_id', $unit->id)->where('provider_name', 'DEWA')->where('paid_by_company', true)->exists());
        $this->assertTrue(UtilityAccount::where('unit_id', $unit->id)->where('account_no', 'DU-123')->exists());
    }

    public function test_admin_can_scan_unit_documents_with_ocr(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@example.com')->firstOrFail();

        $this->mock(UnitDocumentOcr::class, function ($mock): void {
            $mock->shouldReceive('extract')
                ->once()
                ->withArgs(fn ($file, string $type): bool => $type === 'title_deed' && $file instanceof UploadedFile)
                ->andReturn([
                    'ok' => true,
                    'message' => 'Document scanned. Please review extracted unit fields before saving.',
                    'fields' => [
                        'title_deed_no' => 'TD-2026-1402',
                        'title_deed_issue_date' => '2026-06-01',
                    ],
                    'raw_text' => 'Title Deed No TD-2026-1402 Issue Date 01/06/2026',
                ]);
        });

        $this->actingAs($admin)
            ->postJson(route('unit-documents.ocr'), [
                'document_type' => 'title_deed',
                'document' => UploadedFile::fake()->create('title-deed.pdf', 100, 'application/pdf'),
            ])
            ->assertOk()
            ->assertJsonPath('fields.title_deed_no', 'TD-2026-1402')
            ->assertJsonPath('fields.title_deed_issue_date', '2026-06-01');
    }

    public function test_admin_can_manage_tt_lock_settings_and_inventory(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@example.com')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('tt-lock-settings.index'))
            ->assertOk()
            ->assertSee('API credential groups')
            ->assertSee('Installed locks');

        $this->actingAs($admin)
            ->post(route('tt-lock-settings.groups.store'), [
                'client_id' => 'client-123',
                'client_secret' => 'secret-123',
                'username' => 'tt@example.com',
                'password' => 'password',
                'redirect_uri' => 'https://rms.pattern.ae/tt-lock/callback',
                'is_active' => '1',
            ])
            ->assertRedirect();

        $setting = TtLockSetting::where('client_id', 'client-123')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('tt-lock-settings.locks.store'), [
                'tt_lock_setting_id' => $setting->id,
                'lock_name' => 'Demo Door',
                'lock_id' => 'TT-DEMO-LOCK',
                'gateway_id' => 'GW-DEMO',
                'battery_level' => 88,
                'signal_strength' => 'Good',
                'status' => 'active',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas(TtLock::class, ['lock_id' => 'TT-DEMO-LOCK', 'battery_level' => 88]);
    }

    public function test_unit_owner_shares_must_total_one_hundred_percent(): void
    {
        $this->seed();

        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $building = Building::create(['name' => 'Horizon Tower']);
        $owner = Owner::create(['full_name' => 'Layla Hassan', 'mobile_no' => '+971501234567']);

        $this->actingAs($admin)
            ->post(route('units.store'), [
                'building_id' => $building->id,
                'unit_no' => '1205',
                'unit_type' => '1 BHK',
                'availability_status' => 'available',
                'rent_period' => 'monthly',
                'ownership_rows' => [
                    ['owner_id' => $owner->id, 'share_percent' => 80],
                ],
            ])
            ->assertSessionHasErrors('ownership_rows');
    }

    public function test_admin_can_queue_unit_access_card_request_email(): void
    {
        $this->seed();
        Mail::fake();
        $disk = config('filesystems.default');
        Storage::fake($disk);

        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $building = Building::create([
            'name' => 'Horizon Tower',
            'security_emails' => ['security@example.com'],
        ]);
        $unit = Unit::create([
            'building_id' => $building->id,
            'unit_no' => '1206',
            'unit_type' => '1 BHK',
            'availability_status' => 'available',
            'rent_period' => 'monthly',
            'title_deed_disk' => $disk,
            'title_deed_path' => 'units/1206/title-deed.pdf',
            'title_deed_original_name' => 'Title Deed - Unit 1206.pdf',
        ]);
        Storage::disk($disk)->put('units/1206/title-deed.pdf', 'title deed');

        $primaryOwner = Owner::create([
            'full_name' => 'Primary Owner',
            'mobile_no' => '+971501111111',
            'document_disk' => $disk,
            'document_path' => 'owners/primary/emirates-id.pdf',
            'document_original_name' => 'Emirates ID - Primary Owner.pdf',
        ]);
        Storage::disk($disk)->put('owners/primary/emirates-id.pdf', 'primary id');

        $secondaryOwner = Owner::create([
            'full_name' => 'Secondary Owner',
            'mobile_no' => '+971502222222',
            'document_disk' => $disk,
            'document_path' => 'owners/secondary/emirates-id.pdf',
            'document_original_name' => 'Emirates ID - Secondary Owner.pdf',
        ]);
        Storage::disk($disk)->put('owners/secondary/emirates-id.pdf', 'secondary id');

        $unit->owners()->sync([
            $primaryOwner->id => ['share_percent' => 70],
            $secondaryOwner->id => ['share_percent' => 30],
        ]);

        $this->actingAs($admin)
            ->post(route('units.access-card-request', $unit), [
                'request_type' => 'Replacement card',
                'card_type' => 'Access and parking card',
                'notes' => 'Tenant lost the old card.',
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'Access card request email queued for building security.');

        Mail::assertQueued(UnitAccessCardRequestMail::class, fn (UnitAccessCardRequestMail $mail): bool => $mail->unit->is($unit)
            && $mail->requestType === 'Replacement card'
            && $mail->cardType === 'Access and parking card'
            && count($mail->attachments()) === 2);
    }

    public function test_admin_can_view_unit_picture_route(): void
    {
        $this->seed();
        $disk = config('filesystems.default');
        Storage::fake($disk);

        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $building = Building::create(['name' => 'Horizon Tower']);
        $unit = Unit::create([
            'building_id' => $building->id,
            'unit_no' => '1207',
            'unit_type' => '1 BHK',
            'availability_status' => 'available',
            'rent_period' => 'monthly',
            'pictures' => [
                ['disk' => $disk, 'path' => 'units/1207/living.jpg', 'name' => 'living.jpg'],
            ],
        ]);
        Storage::disk($disk)->put('units/1207/living.jpg', 'image-bytes');

        $response = $this->actingAs($admin)->get(route('units.picture', [$unit, 0]));

        $this->assertContains($response->getStatusCode(), [200, 302]);
    }
}
