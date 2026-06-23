<?php

namespace Tests\Feature;

use App\Mail\BookingSecurityCheckInMail;
use App\Models\Agent;
use App\Models\Booking;
use App\Models\BookingDepositRefund;
use App\Models\BookingExtensionRequest;
use App\Models\BookingTask;
use App\Models\Building;
use App\Models\Invoice;
use App\Models\OperationsTeamMember;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class BookingModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_booking_permissions_and_demo_booking_are_seeded(): void
    {
        $this->seed();

        $this->assertTrue(Role::findByName('Super Admin')->hasPermissionTo('bookings.manage'));
        $this->assertDatabaseHas(Booking::class, ['booking_no' => 'BK-DEMO-0001', 'booking_status' => 'confirmed']);
        $this->assertDatabaseCount('booking_tasks', 2);
        $this->assertDatabaseCount('notification_logs', 9);
        $this->assertDatabaseHas('notification_logs', ['subject' => 'Building security check-in details', 'status' => 'sent']);
        $this->assertDatabaseHas('notification_logs', ['channel' => 'push', 'subject' => 'Checkout cleaning assigned']);
        $this->assertDatabaseHas('notification_logs', ['channel' => 'push', 'subject' => 'Checkout inspection assigned']);
    }

    public function test_admin_can_create_confirmed_booking_with_tasks_and_notifications(): void
    {
        $this->seed();
        Mail::fake();

        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $building = Building::create(['name' => 'Horizon Tower', 'security_emails' => ['security@horizon.test']]);
        $unit = Unit::create([
            'building_id' => $building->id,
            'unit_no' => '2201',
            'unit_type' => '1 BHK',
            'availability_status' => 'available',
            'rent_period' => 'monthly',
        ]);
        $tenant = Tenant::create(['full_name' => 'Booking Tenant', 'mobile_no' => '+971501234000', 'identity_type' => 'passport']);
        $agent = Agent::create(['full_name' => 'Booking Agent', 'mobile_no' => '+971501234111', 'identity_type' => 'passport', 'commission_percent' => 5]);
        OperationsTeamMember::create(['full_name' => 'Auto Cleaner', 'mobile_no' => '+971501234222', 'identity_type' => 'emirates_id', 'team_role' => 'cleaner', 'availability_status' => 'available', 'auto_assign_checkout_cleaning' => true]);
        OperationsTeamMember::create(['full_name' => 'Auto Technician', 'mobile_no' => '+971501234333', 'identity_type' => 'emirates_id', 'team_role' => 'technician', 'availability_status' => 'available', 'auto_assign_checkout_inspection' => true]);

        $this->actingAs($admin)
            ->post(route('bookings.store'), [
                'booking_type' => 'holiday_home',
                'unit_id' => $unit->id,
                'tenant_id' => $tenant->id,
                'agent_id' => $agent->id,
                'check_in_date' => '2026-07-01',
                'check_out_date' => '2026-07-05',
                'check_in_time' => '15:00',
                'check_out_time' => '11:00',
                'guest_count' => 2,
                'rent_amount' => 4000,
                'deposit_amount' => 1000,
                'dtcm_fee' => 50,
                'cleaning_fee' => 200,
                'agency_fee' => 200,
                'booking_status' => 'confirmed',
                'source' => 'Direct',
            ])
            ->assertRedirect();

        $booking = Booking::where('unit_id', $unit->id)->firstOrFail();

        $this->assertEquals(200, (float) $booking->vat_amount);
        $this->assertEquals(5650, (float) $booking->total_amount);
        $this->assertCount(2, $booking->tasks);
        $this->assertCount(6, $booking->notificationLogs);
        $this->assertDatabaseHas('notification_logs', ['booking_id' => $booking->id, 'channel' => 'push', 'subject' => 'Checkout cleaning assigned']);
        $this->assertDatabaseHas('notification_logs', ['booking_id' => $booking->id, 'channel' => 'push', 'subject' => 'Checkout inspection assigned']);
        Mail::assertQueued(BookingSecurityCheckInMail::class, fn (BookingSecurityCheckInMail $mail): bool => $mail->booking->is($booking)
            && count($mail->attachments()) >= 1);
    }

    public function test_booking_pages_and_confirmation_pdf_open(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $booking = Booking::where('booking_no', 'BK-DEMO-0001')->firstOrFail();

        $this->actingAs($admin)->get(route('bookings.index'))->assertOk()->assertSee('Booking registry');
        $this->actingAs($admin)->get(route('availability-calendar.index'))->assertOk()->assertSee('Availability calendar')->assertSee($booking->unit->unit_no);
        $this->actingAs($admin)->get(route('bookings.create'))->assertOk()->assertSee('Automation preview');
        $this->actingAs($admin)->get(route('bookings.show', $booking))->assertOk()->assertSee('Booking Confirmation PDF');
        $this->actingAs($admin)->get(route('bookings.confirmation-pdf', $booking))->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertDatabaseHas('notification_logs', ['booking_id' => $booking->id, 'subject' => 'Booking confirmation', 'status' => 'sent']);
    }

    public function test_booking_confirmation_link_can_be_sent_opened_and_signed(): void
    {
        $this->seed();

        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $booking = Booking::where('booking_no', 'BK-DEMO-0001')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('bookings.send-confirmation-link', $booking), [
                'channels' => ['email', 'whatsapp', 'sms', 'portal'],
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'Booking confirmation signing link marked sent.');

        $booking->refresh();

        $this->assertNotNull($booking->confirmation_token);
        $this->assertDatabaseHas('notification_logs', ['booking_id' => $booking->id, 'channel' => 'sms', 'subject' => 'Booking confirmation signing link', 'status' => 'sent']);

        $this->get(route('booking-confirmations.sign', [$booking, $booking->confirmation_token]))
            ->assertOk()
            ->assertSee('Not signed');

        $this->get(route('booking-confirmations.pdf', [$booking, $booking->confirmation_token]))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->post(route('booking-confirmations.sign.store', [$booking, $booking->confirmation_token]), [
            'signed_by' => 'Nora Al Mansoori',
            'signature_text' => 'Nora Al Mansoori',
            'signature_data' => 'data:image/png;base64,'.base64_encode('tenant-signature'),
            'accepted_terms' => '1',
        ])
            ->assertRedirect()
            ->assertSessionHas('status', 'Booking confirmation signed successfully.');

        $this->assertDatabaseHas(Booking::class, ['id' => $booking->id, 'confirmation_signed_by' => 'Nora Al Mansoori']);
    }

    public function test_tenant_can_have_only_one_active_booking(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $booking = Booking::where('booking_no', 'BK-DEMO-0001')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('bookings.store'), [
                'booking_type' => 'holiday_home',
                'unit_id' => $booking->unit_id,
                'tenant_id' => $booking->tenant_id,
                'check_in_date' => now()->addDays(20)->toDateString(),
                'check_out_date' => now()->addDays(25)->toDateString(),
                'guest_count' => 1,
                'booking_status' => 'confirmed',
            ])
            ->assertSessionHasErrors('tenant_id');
    }

    public function test_tenant_extension_request_generates_invoice_and_paid_extension_updates_booking(): void
    {
        $this->seed();
        Mail::fake();

        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $booking = Booking::where('booking_no', 'BK-DEMO-0001')->firstOrFail();
        $tenantUser = User::factory()->create(['email' => $booking->tenant->email, 'name' => $booking->tenant->full_name]);
        $tenantUser->assignRole('Tenant');
        $booking->tenant->update(['user_id' => $tenantUser->id]);

        $newCheckout = $booking->check_out_date->copy()->addDays(2)->toDateString();

        $this->actingAs($tenantUser)
            ->post(route('bookings.request-extension', $booking), [
                'requested_check_out_date' => $newCheckout,
                'tenant_notes' => 'Need two more nights.',
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'Extension request sent to reservations team.');

        $extension = BookingExtensionRequest::firstOrFail();

        $this->actingAs($admin)
            ->post(route('booking-extension-requests.approve', $extension), [
                'extra_rent_amount' => 1200,
                'approval_notes' => 'Approved subject to payment.',
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'Extension approved and invoice generated.');

        $extension->refresh();
        $invoice = $extension->invoice()->firstOrFail();
        $payment = Payment::create([
            'invoice_id' => $invoice->id,
            'booking_id' => $booking->id,
            'payment_no' => 'PAY-EXT-0001',
            'method' => 'cash',
            'status' => 'pending',
            'amount' => 1260,
            'paid_at' => now(),
        ]);

        $this->actingAs($admin)->post(route('payments.approve', $payment))->assertRedirect();

        $this->assertDatabaseHas(BookingExtensionRequest::class, ['id' => $extension->id, 'status' => 'paid_extended']);
        $this->assertSame($newCheckout, $booking->fresh()->check_out_date->format('Y-m-d'));
        $this->assertDatabaseHas('notification_logs', ['booking_id' => $booking->id, 'subject' => 'Building security extension details']);
    }

    public function test_checkout_inspection_and_deposit_refund_flow(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $booking = Booking::where('booking_no', 'BK-DEMO-0001')->firstOrFail();
        $tenantUser = User::factory()->create(['email' => $booking->tenant->email, 'name' => $booking->tenant->full_name]);
        $tenantUser->assignRole('Tenant');
        $booking->tenant->update(['user_id' => $tenantUser->id]);

        $this->actingAs($tenantUser)
            ->post(route('bookings.request-checkout', $booking))
            ->assertRedirect()
            ->assertSessionHas('status', 'Checkout confirmation sent to operations.');

        $this->actingAs($admin)
            ->post(route('bookings.complete-checkout', $booking))
            ->assertRedirect()
            ->assertSessionHas('status', 'Booking checked out. Cleaning/inspection tasks and deposit refund workflow are ready.');

        $refund = BookingDepositRefund::firstOrFail();

        $this->actingAs($admin)
            ->post(route('booking-deposit-refunds.complete-inspection', $refund), [
                'damage_amount' => 250,
                'inspection_notes' => 'Technician inspection complete.',
                'damage_report' => 'One glass table corner chipped.',
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'Inspection report sent for tenant review.');

        $this->actingAs($tenantUser)
            ->post(route('booking-deposit-refunds.accept', $refund))
            ->assertRedirect()
            ->assertSessionHas('status', 'Deposit report accepted. Refund processing can begin.');

        $this->actingAs($admin)
            ->post(route('booking-deposit-refunds.process', $refund))
            ->assertRedirect()
            ->assertSessionHas('status', 'Deposit refund marked as processed.');

        $this->assertDatabaseHas(BookingDepositRefund::class, [
            'id' => $refund->id,
            'damage_amount' => 250,
            'refund_amount' => 1250,
            'status' => 'refunded',
        ]);
    }

    public function test_tenant_booking_page_hides_internal_logs_and_tasks(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $booking = Booking::where('booking_no', 'BK-DEMO-0001')->firstOrFail();
        $tenantUser = User::factory()->create(['email' => $booking->tenant->email, 'name' => $booking->tenant->full_name]);
        $tenantUser->assignRole('Tenant');
        $booking->tenant->update(['user_id' => $tenantUser->id]);

        $this->actingAs($admin)->post(route('bookings.complete-checkout', $booking))->assertRedirect();
        $refund = BookingDepositRefund::firstOrFail();

        $this->actingAs($admin)
            ->post(route('booking-deposit-refunds.complete-inspection', $refund), [
                'damage_amount' => 250,
                'inspection_notes' => 'Internal technician notes should stay private.',
                'damage_report' => 'Tenant-facing report: one glass table corner chipped.',
            ])
            ->assertRedirect();

        $this->actingAs($tenantUser)
            ->get(route('bookings.show', $booking))
            ->assertOk()
            ->assertSee('Deposit report')
            ->assertSee('Tenant-facing report: one glass table corner chipped.')
            ->assertDontSee('Notification log')
            ->assertDontSee('Building security check-in details')
            ->assertDontSee('Auto tasks')
            ->assertDontSee('Internal technician notes should stay private.')
            ->assertDontSee('Operations controls');
    }

    public function test_task_management_board_and_tenant_checkin_report_workflow(): void
    {
        $this->seed();

        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $booking = Booking::where('booking_no', 'BK-DEMO-0001')->firstOrFail();
        $tenantUser = User::factory()->create(['email' => $booking->tenant->email, 'name' => $booking->tenant->full_name]);
        $tenantUser->assignRole('Tenant');
        $booking->tenant->update(['user_id' => $tenantUser->id]);

        $this->actingAs($admin)
            ->get(route('tasks.index'))
            ->assertOk()
            ->assertSee('Task management')
            ->assertSee('Checkout cleaning')
            ->assertSee('Timeline');

        $task = BookingTask::where('task_type', 'checkout_cleaning')->firstOrFail();

        $this->actingAs($admin)
            ->patch(route('tasks.update', $task), [
                'status' => 'in_progress',
                'priority' => 'high',
                'due_at' => now()->addHour()->format('Y-m-d H:i:s'),
                'timeline_note' => 'Cleaner started the task.',
                'checklist' => ['Kitchen checked', 'Bathrooms checked'],
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'Task updated.');

        $this->assertDatabaseHas(BookingTask::class, ['id' => $task->id, 'status' => 'in_progress', 'priority' => 'high']);
        $this->assertDatabaseHas('booking_task_events', ['booking_task_id' => $task->id, 'event_type' => 'status_changed']);

        $this->actingAs($tenantUser)
            ->post(route('bookings.tenant-check-in-report', $booking), [
                'items' => [
                    ['area' => 'Living room', 'item' => 'Sofa', 'condition_status' => 'good', 'notes' => 'Looks fine.'],
                    ['area' => 'Kitchen', 'item' => 'Glassware', 'condition_status' => 'damaged', 'notes' => 'One glass chipped.'],
                ],
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'Check-in condition report submitted.');

        $this->assertDatabaseHas('check_in_inspection_items', ['booking_id' => $booking->id, 'item' => 'Glassware', 'condition_status' => 'damaged']);
        $this->assertDatabaseHas(BookingTask::class, ['booking_id' => $booking->id, 'task_type' => 'tenant_checkin_review']);
    }
}
