<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Tenant;
use Illuminate\Http\Request;

class TenantInvoiceController extends Controller
{
    public function index(Request $request)
    {
        $tenant = $this->tenantFor($request);
        abort_unless($tenant, 403);

        $invoices = Invoice::query()
            ->with(['booking.unit.building', 'payments.receipt'])
            ->where('tenant_id', $tenant->id)
            ->when($request->filled('booking_id'), fn ($query) => $query->where('booking_id', $request->integer('booking_id')))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('tenant.invoices.index', [
            'tenant' => $tenant,
            'invoices' => $invoices,
            'balanceDue' => (float) Invoice::where('tenant_id', $tenant->id)->where('balance_amount', '>', 0)->sum('balance_amount'),
            'paidTotal' => (float) Invoice::where('tenant_id', $tenant->id)->sum('paid_amount'),
        ]);
    }

    public function show(Request $request, Invoice $invoice)
    {
        $tenant = $this->tenantFor($request);
        abort_unless($tenant && (int) $invoice->tenant_id === (int) $tenant->id, 403);

        return view('tenant.invoices.show', [
            'tenant' => $tenant,
            'invoice' => $invoice->load(['booking.unit.building', 'payments.receipt']),
        ]);
    }

    private function tenantFor(Request $request): ?Tenant
    {
        if (! $request->user()?->can('portal.tenant')) {
            return null;
        }

        return Tenant::query()
            ->where('user_id', $request->user()->id)
            ->orWhere('email', $request->user()->email)
            ->first();
    }
}
