<?php

namespace Tests\Feature;

use App\Models\Owner;
use App\Models\User;
use App\Notifications\WelcomePasswordSetupNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class OwnerModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_owner_permissions_are_seeded_for_super_admin_and_operations_team(): void
    {
        $this->seed();

        $this->assertTrue(Role::findByName('Super Admin')->hasPermissionTo('owners.manage'));
        $this->assertTrue(Role::findByName('Operations Team')->hasPermissionTo('owners.manage'));
        $this->assertFalse(Role::findByName('Owner')->hasPermissionTo('owners.manage'));
    }

    public function test_super_admin_can_create_owner_with_document_bank_blacklist_and_note(): void
    {
        $this->seed();
        $disk = config('filesystems.default');
        Storage::fake($disk);

        $admin = User::where('email', 'admin@example.com')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('owners.store'), [
                'full_name' => 'Layla Hassan',
                'mobile_no' => '+971501234567',
                'mobile_has_whatsapp' => '1',
                'email' => 'layla@example.com',
                'identity_type' => 'emirates_id',
                'identity_no' => '784-1988-1234567-1',
                'identity_expiry_date' => '2030-06-30',
                'date_of_birth' => '1988-04-12',
                'document' => UploadedFile::fake()->image('emirates-id.jpg'),
                'is_blacklisted' => '1',
                'blacklist_reason' => 'Pending compliance review.',
                'bank_name' => 'Emirates NBD',
                'bank_account_name' => 'Layla Hassan',
                'bank_account_no' => '123456789',
                'iban' => 'AE070331234567890123456',
                'swift_code' => 'EBILAEAD',
                'note' => 'Owner documents received.',
            ])
            ->assertRedirect();

        $owner = Owner::where('email', 'layla@example.com')->firstOrFail();

        $this->assertTrue($owner->is_blacklisted);
        $this->assertTrue($owner->mobile_has_whatsapp);
        $this->assertSame('Emirates NBD', $owner->bank_name);
        $this->assertSame('Owner documents received.', $owner->notes()->first()->note);
        $this->assertStringStartsWith(now()->format('Y/m').'/owners/layla-hassan/identity-documents/', $owner->document_path);
        $this->assertStringEndsWith('/emirates-id-layla-hassan.jpg', $owner->document_path);
        $this->assertSame('Emirates ID - Layla Hassan.jpg', $owner->document_original_name);
        Storage::disk($disk)->assertExists($owner->document_path);
        $this->assertDatabaseHas('activity_logs', ['action' => 'owners.created', 'subject_id' => $owner->id]);
    }

    public function test_owner_welcome_email_can_be_sent_when_creating_owner(): void
    {
        Notification::fake();
        $this->seed();
        $admin = User::where('email', 'admin@example.com')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('owners.store'), [
                'full_name' => 'Mariam Owner',
                'mobile_no' => '+971501111111',
                'email' => 'mariam.portal@example.com',
                'identity_type' => 'emirates_id',
                'send_portal_invite' => '1',
            ])
            ->assertRedirect();

        $owner = Owner::where('email', 'mariam.portal@example.com')->firstOrFail();
        $user = User::where('email', 'mariam.portal@example.com')->firstOrFail();

        $this->assertSame($user->id, $owner->user_id);
        $this->assertTrue($user->hasRole('Owner'));
        $this->assertNotNull($owner->portal_invitation_sent_at);
        Notification::assertSentTo($user, WelcomePasswordSetupNotification::class);
    }

    public function test_owner_welcome_email_can_be_sent_later(): void
    {
        Notification::fake();
        $this->seed();
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $owner = Owner::create([
            'full_name' => 'Later Owner',
            'mobile_no' => '+971502222222',
            'email' => 'later.owner@example.com',
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->post(route('owners.send-invite', $owner))
            ->assertRedirect(route('owners.show', $owner));

        $user = User::where('email', 'later.owner@example.com')->firstOrFail();

        $this->assertSame($user->id, $owner->fresh()->user_id);
        $this->assertTrue($user->hasRole('Owner'));
        Notification::assertSentTo($user, WelcomePasswordSetupNotification::class);
    }

    public function test_owner_document_can_be_downloaded_from_private_route(): void
    {
        $this->seed();
        $disk = config('filesystems.default');
        Storage::fake($disk);

        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $owner = Owner::create([
            'full_name' => 'Layla Hassan',
            'mobile_no' => '+971501234567',
            'document_disk' => $disk,
            'document_path' => now()->format('Y/m').'/owners/layla-hassan/identity-documents/test.pdf',
            'document_original_name' => 'test.pdf',
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        Storage::disk($disk)->put($owner->document_path, 'document-body');

        $response = $this->actingAs($admin)->get(route('owners.document', $owner));

        $this->assertContains($response->getStatusCode(), [200, 302]);
    }

    public function test_owner_notes_can_be_added_from_profile(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $owner = Owner::create([
            'full_name' => 'Omar Khan',
            'mobile_no' => '+971551234567',
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->post(route('owners.notes.store', $owner), ['note' => 'Called owner about new unit onboarding.'])
            ->assertRedirect(route('owners.show', $owner));

        $this->assertDatabaseHas('owner_notes', [
            'owner_id' => $owner->id,
            'note' => 'Called owner about new unit onboarding.',
        ]);
    }

    public function test_view_only_owner_permission_cannot_create_owner(): void
    {
        Permission::findOrCreate('owners.view');
        $role = Role::findOrCreate('Owner Viewer');
        $role->givePermissionTo('owners.view');
        $user = User::factory()->create();
        $user->assignRole($role);

        $this->actingAs($user)
            ->get(route('owners.index'))
            ->assertOk();

        $this->actingAs($user)
            ->get(route('owners.create'))
            ->assertForbidden();
    }
}
