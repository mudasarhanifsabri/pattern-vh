<?php

namespace Tests\Feature;

use App\Mail\ReceiptIssuedMail;
use App\Models\Booking;
use App\Models\DtcmCheckin;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentCollectionRequest;
use App\Models\Receipt;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class InvoiceReceiptModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_invoice_permissions_and_demo_finance_records_are_seeded(): void
    {
        $this->seed();

        $this->assertTrue(Role::findByName('Super Admin')->hasPermissionTo('invoices.manage'));
        $this->assertDatabaseHas(Invoice::class, ['invoice_no' => 'INV-DEMO-0001', 'status' => 'paid']);
        $this->assertDatabaseHas(Payment::class, ['payment_no' => 'PAY-DEMO-0001', 'method' => 'card_machine', 'status' => 'approved']);
        $this->assertDatabaseCount('receipts', 1);
        $this->assertDatabaseCount('dtcm_checkins', 1);
        $this->assertDatabaseCount('check_in_inspection_items', 0);
    }

    public function test_admin_can_create_invoice_and_approve_payment_to_issue_receipt(): void
    {
        $this->seed();
        Mail::fake();
        Storage::fake(config('filesystems.default'));

        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $booking = Booking::where('booking_no', 'BK-DEMO-0001')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('invoices.store'), [
                'booking_id' => $booking->id,
                'invoice_date' => '2026-07-01',
                'due_date' => '2026-07-02',
                'rent_amount' => 1000,
                'deposit_amount' => 500,
                'dtcm_fee' => 50,
                'cleaning_fee' => 100,
                'agency_fee' => 50,
                'status' => 'sent',
            ])
            ->assertRedirect();

        $invoice = Invoice::where('invoice_no', '!=', 'INV-DEMO-0001')->latest()->firstOrFail();

        $this->actingAs($admin)
            ->post(route('invoices.payments.store', $invoice), [
                'method' => 'cash',
                'amount' => 1750,
                'paid_at' => '2026-07-01 10:00',
                'reference_no' => 'CASH-001',
                'payment_proof' => UploadedFile::fake()->create('cash-proof.pdf', 100, 'application/pdf'),
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'Payment recorded and waiting for verification.');

        $invoice->refresh();
        $payment = Payment::where('reference_no', 'CASH-001')->firstOrFail();

        $this->assertSame('sent', $invoice->status);
        $this->assertSame('pending', $payment->status);
        $this->assertNotNull($payment->proof_path);
        $this->assertSame(50.0, (float) $invoice->vat_amount);
        $this->assertSame(1750.0, (float) $invoice->total_amount);
        $this->assertDatabaseMissing(Receipt::class, ['invoice_id' => $invoice->id, 'amount' => 1750]);

        $this->actingAs($admin)
            ->post(route('payments.approve', $payment), [
                'verification_notes' => 'Bank/cash collection confirmed.',
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'Payment approved. Invoice paid and receipt issued.');

        $invoice->refresh();
        $payment->refresh();

        $this->assertSame('paid', $invoice->status);
        $this->assertSame('approved', $payment->status);
        $this->assertDatabaseHas(Receipt::class, ['invoice_id' => $invoice->id, 'amount' => 1750]);
        $this->assertDatabaseHas('dtcm_checkins', ['booking_id' => $booking->id, 'status' => 'pending']);
        $this->assertDatabaseMissing('check_in_inspection_items', ['booking_id' => $booking->id]);
        Mail::assertQueued(ReceiptIssuedMail::class);
    }

    public function test_dtcm_registration_marks_booking_checked_in(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $checkin = DtcmCheckin::with('booking')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('dtcm-checkins.complete', $checkin), [
                'portal_reference' => 'DTCM-PORTAL-001',
                'notes' => 'Guest registered in DTCM portal.',
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'DTCM guest registration completed. Booking is now checked in.');

        $this->assertDatabaseHas(DtcmCheckin::class, [
            'id' => $checkin->id,
            'status' => 'registered',
            'portal_reference' => 'DTCM-PORTAL-001',
        ]);
        $this->assertDatabaseHas(Booking::class, [
            'id' => $checkin->booking_id,
            'booking_status' => 'checked_in',
        ]);
    }

    public function test_tenant_can_request_doorstep_collection_and_staff_records_pending_payment(): void
    {
        $this->seed();
        Storage::fake(config('filesystems.default'));

        $tenant = Tenant::where('email', 'nora.tenant@example.com')->firstOrFail();
        $tenantUser = User::factory()->create(['name' => $tenant->full_name, 'email' => $tenant->email]);
        $tenantUser->assignRole('Tenant');
        $tenant->update(['user_id' => $tenantUser->id]);

        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $invoice = Invoice::where('invoice_no', 'INV-DEMO-0001')->firstOrFail();
        $invoice->update(['status' => 'sent', 'paid_amount' => 0, 'balance_amount' => $invoice->total_amount]);
        $invoice->payments()->delete();
        $invoice->receipts()->delete();

        $this->actingAs($tenantUser)
            ->post(route('tenant.payment-requests.store'), [
                'invoice_id' => $invoice->id,
                'collection_method' => 'card_machine',
                'amount' => 500,
                'preferred_date' => now()->addDay()->toDateString(),
                'preferred_time_window' => '6 PM - 8 PM',
                'contact_mobile' => $tenant->mobile_no,
                'contact_has_whatsapp' => '1',
                'collection_address' => 'Marina Vista, Unit 1402',
                'tenant_notes' => 'Please bring card machine.',
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'Collection request sent to finance team.');

        $collectionRequest = PaymentCollectionRequest::firstOrFail();
        $this->assertSame('requested', $collectionRequest->status);

        $this->actingAs($admin)
            ->post(route('payment-collection-requests.collect', $collectionRequest), [
                'amount' => 500,
                'collected_at' => now()->format('Y-m-d H:i:s'),
                'reference_no' => 'CARD-DOOR-001',
                'payment_proof' => UploadedFile::fake()->image('card-slip.jpg'),
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'Doorstep collection recorded as pending payment. Finance can approve it now.');

        $collectionRequest->refresh();
        $this->assertSame('collected_pending_verification', $collectionRequest->status);
        $this->assertDatabaseHas(Payment::class, [
            'collection_request_id' => $collectionRequest->id,
            'method' => 'card_machine',
            'status' => 'pending',
            'amount' => 500,
        ]);
    }

    public function test_admin_can_reject_unverified_payment(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $invoice = Invoice::where('invoice_no', 'INV-DEMO-0001')->firstOrFail();

        $payment = Payment::create([
            'invoice_id' => $invoice->id,
            'booking_id' => $invoice->booking_id,
            'payment_no' => 'PAY-REJECT-0001',
            'method' => 'bank_transfer',
            'status' => 'pending',
            'amount' => 100,
            'paid_at' => now(),
        ]);

        $this->actingAs($admin)
            ->post(route('payments.reject', $payment), [
                'verification_notes' => 'Amount not found in bank.',
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'Payment rejected.');

        $this->assertDatabaseHas(Payment::class, [
            'payment_no' => 'PAY-REJECT-0001',
            'status' => 'rejected',
            'verification_notes' => 'Amount not found in bank.',
        ]);
    }

    public function test_invoice_and_receipt_pdf_routes_open(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $invoice = Invoice::where('invoice_no', 'INV-DEMO-0001')->firstOrFail();
        $receipt = Receipt::firstOrFail();

        $this->actingAs($admin)->get(route('invoices.index'))->assertOk()->assertSee('Invoice registry');
        $this->actingAs($admin)->get(route('invoices.show', $invoice))->assertOk()->assertSee('Record payment');
        $this->actingAs($admin)->get(route('payments.index'))->assertOk()->assertSee('Payment registry')->assertSee('Stripe placeholder');
        $this->actingAs($admin)->get(route('security-deposits.index'))->assertOk()->assertSee('Security Deposits')->assertSee('Active deposit ledger');
        $this->actingAs($admin)->get(route('invoices.pdf', $invoice))->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->actingAs($admin)->get(route('receipts.pdf', $receipt))->assertOk()->assertHeader('content-type', 'application/pdf');
    }
}
