<?php

namespace Tests\Feature;

use App\Models\NotificationLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class NotificationCenterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_bell_feed_is_scoped_and_can_mark_notifications_read(): void
    {
        $this->seed();

        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $tenant = User::factory()->create(['email' => 'tenant-bell@example.com']);
        $tenant->assignRole('Tenant');

        $tenantNotification = NotificationLog::create([
            'channel' => 'push',
            'recipient' => 'user:'.$tenant->id,
            'subject' => 'Tenant booking update',
            'message' => 'Your booking has a new update.',
            'status' => 'pending',
            'payload' => ['url' => route('dashboard')],
        ]);

        NotificationLog::create([
            'channel' => 'push',
            'recipient' => 'user:999999',
            'subject' => 'Other user update',
            'message' => 'This should not show for tenant.',
            'status' => 'pending',
        ]);

        $this->actingAs($tenant)
            ->getJson(route('notifications.feed'))
            ->assertOk()
            ->assertJsonPath('unread_count', 1)
            ->assertJsonPath('items.0.title', 'Tenant booking update');

        $this->actingAs($tenant)->post(route('notifications.read', $tenantNotification))->assertRedirect(route('dashboard'));

        $this->actingAs($tenant)
            ->getJson(route('notifications.feed'))
            ->assertOk()
            ->assertJsonPath('unread_count', 0)
            ->assertJsonPath('items.0.is_read', true);

        $this->actingAs($admin)
            ->getJson(route('notifications.feed'))
            ->assertOk()
            ->assertJsonFragment(['title' => 'Tenant booking update'])
            ->assertJsonFragment(['title' => 'Other user update']);
    }
}
