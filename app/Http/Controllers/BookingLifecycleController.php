<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\BookingDepositRefund;
use App\Models\BookingExtensionRequest;
use App\Models\Invoice;
use App\Models\OperationsTeamMember;
use App\Models\Tenant;
use App\Support\ActivityLogger;
use App\Support\BookingInvoiceScheduler;
use App\Support\TaxCalculator;
use Illuminate\Http\Request;

class BookingLifecycleController extends Controller
{
    public function requestExtension(Request $request, Booking $booking)
    {
        $tenant = $this->tenantFor($request);
        abort_unless($tenant && (int) $booking->tenant_id === (int) $tenant->id, 403);

        $validated = $request->validate([
            'requested_check_out_date' => ['required', 'date', 'after:'.$booking->check_out_date->format('Y-m-d')],
            'tenant_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $extension = $booking->extensionRequests()->create([
            'tenant_id' => $tenant->id,
            'requested_check_out_date' => $validated['requested_check_out_date'],
            'tenant_notes' => $validated['tenant_notes'] ?? null,
            'status' => 'requested',
        ]);

        $booking->notificationLogs()->create([
            'channel' => 'internal',
            'recipient' => 'reservations',
            'subject' => 'Extension request received',
            'message' => "{$tenant->full_name} requested checkout extension to {$extension->requested_check_out_date->format('M d, Y')}.",
            'status' => 'pending',
            'payload' => ['extension_request_id' => $extension->id],
        ]);

        ActivityLogger::log('booking_extensions.requested', "Tenant requested extension for {$booking->booking_no}.", $extension);

        return back()->with('status', 'Extension request sent to reservations team.');
    }

    public function approveExtension(Request $request, BookingExtensionRequest $extensionRequest)
    {
        $validated = $request->validate([
            'extra_rent_amount' => ['required', 'numeric', 'min:0.01'],
            'approval_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $booking = $extensionRequest->booking()->with(['tenant', 'unit'])->firstOrFail();
        $rent = (float) $validated['extra_rent_amount'];
        $vat = TaxCalculator::rentVat($rent);
        $total = $rent + $vat;

        $invoice = Invoice::create([
            'invoice_no' => $this->nextInvoiceNo(),
            'booking_id' => $booking->id,
            'tenant_id' => $booking->tenant_id,
            'unit_id' => $booking->unit_id,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDay()->toDateString(),
            'rent_amount' => $rent,
            'vat_amount' => $vat,
            'total_amount' => $total,
            'balance_amount' => $total,
            'status' => 'sent',
            'notes' => 'Extension invoice for checkout date '.$extensionRequest->requested_check_out_date->format('M d, Y'),
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        $extensionRequest->update([
            'invoice_id' => $invoice->id,
            'extra_rent_amount' => $validated['extra_rent_amount'],
            'approval_notes' => $validated['approval_notes'] ?? null,
            'status' => 'approved_pending_payment',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        $booking->notificationLogs()->create([
            'channel' => 'email',
            'recipient' => $booking->tenant->email,
            'subject' => 'Extension approved - payment required',
            'message' => "Extension approved to {$extensionRequest->requested_check_out_date->format('M d, Y')}. Invoice {$invoice->invoice_no} generated for payment.",
            'status' => 'sent',
            'payload' => ['extension_request_id' => $extensionRequest->id, 'invoice_id' => $invoice->id],
            'sent_at' => now(),
        ]);

        ActivityLogger::log('booking_extensions.approved', "Approved extension for {$booking->booking_no}.", $extensionRequest);

        return redirect()->route('invoices.show', $invoice)->with('status', 'Extension approved and invoice generated.');
    }

    public function rejectExtension(Request $request, BookingExtensionRequest $extensionRequest)
    {
        $validated = $request->validate(['approval_notes' => ['nullable', 'string', 'max:1000']]);

        $extensionRequest->update([
            'status' => 'rejected',
            'approval_notes' => $validated['approval_notes'] ?? null,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        ActivityLogger::log('booking_extensions.rejected', "Rejected extension request {$extensionRequest->id}.", $extensionRequest);

        return back()->with('status', 'Extension request rejected.');
    }

    public function requestCheckout(Request $request, Booking $booking)
    {
        $tenant = $this->tenantFor($request);
        abort_unless($tenant && (int) $booking->tenant_id === (int) $tenant->id, 403);

        $booking->update(['booking_status' => 'checkout_requested']);
        $booking->notificationLogs()->create([
            'channel' => 'internal',
            'recipient' => 'operations',
            'subject' => 'Tenant confirmed checkout',
            'message' => "{$tenant->full_name} confirmed checkout for {$booking->booking_no}.",
            'status' => 'pending',
        ]);

        $booking->tasks()->firstOrCreate(['task_type' => 'checkout_confirmation'], [
            'unit_id' => $booking->unit_id,
            'title' => "Tenant checkout confirmation for Unit {$booking->unit->unit_no}",
            'due_at' => now(),
            'status' => 'open',
            'priority' => 'high',
            'notes' => 'Tenant confirmed checkout from mobile portal.',
        ])->events()->create([
            'user_id' => $request->user()->id,
            'event_type' => 'tenant_checkout_requested',
            'description' => "{$tenant->full_name} confirmed checkout from tenant app.",
        ]);

        return back()->with('status', 'Checkout confirmation sent to operations.');
    }

    public function completeCheckout(Booking $booking, BookingInvoiceScheduler $invoiceScheduler)
    {
        $booking->update(['booking_status' => 'checked_out']);
        $cancelled = $invoiceScheduler->cancelFutureUnpaidInvoices($booking);
        $this->createCheckoutTasks($booking);
        $booking->depositRefund()->firstOrCreate([], [
            'tenant_id' => $booking->tenant_id,
            'deposit_amount' => $booking->deposit_amount,
            'refund_amount' => $booking->deposit_amount,
            'status' => 'pending_inspection',
        ]);

        ActivityLogger::log('bookings.checked_out', "Completed checkout for {$booking->booking_no}.", $booking);

        $message = 'Booking checked out. Cleaning/inspection tasks and deposit refund workflow are ready.';

        if ($cancelled > 0) {
            $message .= " {$cancelled} future unpaid invoice(s) cancelled.";
        }

        return back()->with('status', $message);
    }

    public function completeInspection(Request $request, BookingDepositRefund $depositRefund)
    {
        $validated = $request->validate([
            'damage_amount' => ['required', 'numeric', 'min:0'],
            'inspection_notes' => ['nullable', 'string', 'max:2000'],
            'damage_report' => ['nullable', 'string', 'max:4000'],
        ]);

        $refund = max(0, (float) $depositRefund->deposit_amount - (float) $validated['damage_amount']);
        $depositRefund->update([
            'damage_amount' => $validated['damage_amount'],
            'refund_amount' => $refund,
            'inspection_notes' => $validated['inspection_notes'] ?? null,
            'damage_report' => $validated['damage_report'] ?? null,
            'inspection_completed_at' => now(),
            'status' => 'tenant_review',
        ]);

        $depositRefund->booking->notificationLogs()->create([
            'channel' => 'email',
            'recipient' => $depositRefund->tenant->email,
            'subject' => 'Deposit inspection report ready',
            'message' => "Deposit report ready. Refund amount AED ".number_format($refund, 2).'.',
            'status' => 'sent',
            'payload' => ['deposit_refund_id' => $depositRefund->id],
            'sent_at' => now(),
        ]);

        return back()->with('status', 'Inspection report sent for tenant review.');
    }

    public function acceptDepositReport(Request $request, BookingDepositRefund $depositRefund)
    {
        $tenant = $this->tenantFor($request);
        abort_unless($tenant && (int) $depositRefund->tenant_id === (int) $tenant->id, 403);

        $depositRefund->update(['status' => 'accepted', 'tenant_accepted_at' => now()]);

        return back()->with('status', 'Deposit report accepted. Refund processing can begin.');
    }

    public function processRefund(BookingDepositRefund $depositRefund)
    {
        $depositRefund->update([
            'status' => 'refunded',
            'refund_processed_at' => now(),
            'processed_by' => auth()->id(),
        ]);

        ActivityLogger::log('deposit_refunds.processed', "Processed deposit refund for {$depositRefund->booking->booking_no}.", $depositRefund);

        return back()->with('status', 'Deposit refund marked as processed.');
    }

    private function createCheckoutTasks(Booking $booking): void
    {
        $cleaner = OperationsTeamMember::query()->where('team_role', 'cleaner')->where('auto_assign_checkout_cleaning', true)->where('availability_status', 'available')->first();
        $technician = OperationsTeamMember::query()->where('team_role', 'technician')->where('auto_assign_checkout_inspection', true)->where('availability_status', 'available')->first();

        $cleaningTask = $booking->tasks()->firstOrCreate(['task_type' => 'checkout_cleaning'], [
            'unit_id' => $booking->unit_id,
            'assigned_to_id' => $cleaner?->id,
            'title' => "Checkout cleaning for Unit {$booking->unit->unit_no}",
            'due_at' => now()->addHours(2),
            'status' => 'open',
            'notes' => 'Created after tenant checkout.',
        ]);

        $cleaningTask->events()->firstOrCreate(
            ['event_type' => 'checkout_completed'],
            ['description' => 'Checkout completed. Cleaning task is ready for operations.'],
        );

        $inspectionTask = $booking->tasks()->firstOrCreate(['task_type' => 'checkout_inspection'], [
            'unit_id' => $booking->unit_id,
            'assigned_to_id' => $technician?->id,
            'title' => "Checkout inspection for Unit {$booking->unit->unit_no}",
            'due_at' => now()->addHours(4),
            'status' => 'open',
            'notes' => 'Complete inspection before deposit refund.',
        ]);

        $inspectionTask->events()->firstOrCreate(
            ['event_type' => 'checkout_completed'],
            ['description' => 'Checkout completed. Inspection task is ready for technician review.'],
        );
    }

    private function tenantFor(Request $request): ?Tenant
    {
        return Tenant::query()->where('user_id', $request->user()->id)->orWhere('email', $request->user()->email)->first();
    }

    private function nextInvoiceNo(): string
    {
        return 'INV-'.now()->format('Ymd').'-'.str_pad((string) (Invoice::withTrashed()->whereDate('created_at', today())->count() + 1), 4, '0', STR_PAD_LEFT);
    }
}
