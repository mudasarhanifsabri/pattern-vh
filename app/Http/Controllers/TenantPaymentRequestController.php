<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\PaymentCollectionRequest;
use App\Models\Tenant;
use App\Support\ActivityLogger;
use App\Support\PushEventLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TenantPaymentRequestController extends Controller
{
    public function index(Request $request)
    {
        $tenant = $this->tenantFor($request);

        abort_unless($tenant, 403);

        return view('tenant-portal.payment-requests.index', [
            'tenant' => $tenant,
            'invoices' => Invoice::query()
                ->with(['booking.unit.building'])
                ->where('tenant_id', $tenant->id)
                ->where('balance_amount', '>', 0)
                ->latest()
                ->get(),
            'requests' => PaymentCollectionRequest::query()
                ->with(['invoice', 'booking.unit.building', 'payment.receipt'])
                ->where('tenant_id', $tenant->id)
                ->latest()
                ->get(),
        ]);
    }

    public function store(Request $request, PushEventLogger $push)
    {
        $tenant = $this->tenantFor($request);

        abort_unless($tenant, 403);

        $validated = $request->validate([
            'invoice_id' => ['required', Rule::exists('invoices', 'id')->where('tenant_id', $tenant->id)],
            'collection_method' => ['required', Rule::in(PaymentCollectionRequest::METHODS)],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'preferred_date' => ['nullable', 'date', 'after_or_equal:today'],
            'preferred_time_window' => ['nullable', 'string', 'max:100'],
            'contact_mobile' => ['required', 'string', 'max:50'],
            'contact_has_whatsapp' => ['nullable', 'boolean'],
            'collection_address' => ['required', 'string', 'max:500'],
            'tenant_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $invoice = Invoice::where('tenant_id', $tenant->id)->findOrFail($validated['invoice_id']);
        $amount = min((float) $validated['amount'], (float) $invoice->balance_amount);

        $collectionRequest = PaymentCollectionRequest::create(array_merge($validated, [
            'booking_id' => $invoice->booking_id,
            'tenant_id' => $tenant->id,
            'requested_by' => $request->user()->id,
            'request_no' => $this->nextRequestNo(),
            'amount' => $amount,
            'contact_has_whatsapp' => $request->boolean('contact_has_whatsapp'),
            'status' => 'requested',
            'created_by' => $request->user()->id,
        ]));

        $invoice->booking->notificationLogs()->create([
            'channel' => 'internal',
            'recipient' => 'finance',
            'subject' => 'Tenant payment collection requested',
            'message' => "{$tenant->full_name} requested {$collectionRequest->collection_method} collection for AED ".number_format($amount, 2).'.',
            'status' => 'pending',
            'payload' => ['collection_request_id' => $collectionRequest->id],
        ]);

        ActivityLogger::log('payment_collection_requests.created', "Tenant requested payment collection {$collectionRequest->request_no}.", $collectionRequest);

        $push->toUserIds(
            \App\Models\User::permission('payment-collection-requests.manage')->pluck('id'),
            'Payment collection requested',
            "{$tenant->full_name} requested {$collectionRequest->collection_method} collection for AED ".number_format($amount, 2).'.',
            ['type' => 'collection_requested', 'collection_request_id' => $collectionRequest->id, 'url' => route('payment-collection-requests.index')],
            $invoice->booking
        );

        return redirect()->route('tenant.payment-requests.index')->with('status', 'Collection request sent to finance team.');
    }

    private function tenantFor(Request $request): ?Tenant
    {
        return Tenant::query()
            ->where('user_id', $request->user()->id)
            ->orWhere('email', $request->user()->email)
            ->first();
    }

    private function nextRequestNo(): string
    {
        return 'PCR-'.now()->format('Ymd').'-'.str_pad((string) (PaymentCollectionRequest::whereDate('created_at', today())->count() + 1), 4, '0', STR_PAD_LEFT);
    }
}
