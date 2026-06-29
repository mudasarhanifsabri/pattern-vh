<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Invoice;
use App\Support\ActivityLogger;
use App\Support\SimpleFinancePdf;
use App\Support\TaxCalculator;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $tenant = $this->tenantFor($request);

        $invoices = Invoice::query()
            ->with(['booking.unit.building', 'tenant'])
            ->when($tenant, fn ($query) => $query->where('tenant_id', $tenant->id))
            ->when(request('booking_id'), fn ($query, string $bookingId) => $query->where('booking_id', $bookingId))
            ->when(request('search'), fn ($query, string $search) => $query->where(function ($query) use ($search): void {
                $query->where('invoice_no', 'like', "%{$search}%")
                    ->orWhereHas('tenant', fn ($query) => $query->where('full_name', 'like', "%{$search}%"));
            }))
            ->when(request('status'), fn ($query, string $status) => $query->where('status', $status))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('invoices.index', compact('invoices', 'tenant'));
    }

    public function create(Request $request)
    {
        $booking = $request->filled('booking_id') ? Booking::with(['tenant', 'unit', 'invoices'])->find($request->integer('booking_id')) : null;

        if ($booking && $booking->invoices->isNotEmpty()) {
            return redirect()
                ->route('invoices.index', ['booking_id' => $booking->id])
                ->with('status', 'This booking already has system-generated invoice(s). Open and edit the invoice from Finance instead of creating another one.');
        }

        return view('invoices.create', ['bookings' => Booking::with(['tenant', 'unit.building'])->latest()->get(), 'booking' => $booking]);
    }

    public function store(Request $request)
    {
        $validated = $this->validated($request);
        $booking = Booking::with(['tenant', 'unit'])->findOrFail($validated['booking_id']);

        if ($booking->invoices()->exists()) {
            return redirect()
                ->route('invoices.index', ['booking_id' => $booking->id])
                ->with('status', 'This booking already has invoice(s). Please edit the existing invoice instead of creating a duplicate.');
        }

        $total = TaxCalculator::invoiceTotal($validated);
        $validated = array_merge($validated, [
            'invoice_no' => $this->nextInvoiceNo(),
            'tenant_id' => $booking->tenant_id,
            'unit_id' => $booking->unit_id,
            'vat_amount' => TaxCalculator::rentVat($validated['rent_amount'] ?? 0),
            'total_amount' => $total,
            'paid_amount' => 0,
            'balance_amount' => $total,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        $invoice = Invoice::create($validated);
        ActivityLogger::log('invoices.created', "Created invoice {$invoice->invoice_no}.", $invoice);

        return redirect()->route('invoices.show', $invoice)->with('status', 'Invoice created successfully.');
    }

    public function show(Invoice $invoice)
    {
        $this->authorizeTenantInvoice($invoice);

        return view('invoices.show', [
            'invoice' => $invoice->load(['booking.unit.building', 'tenant', 'payments.receipt', 'receipts']),
        ]);
    }

    public function edit(Invoice $invoice)
    {
        return view('invoices.edit', ['invoice' => $invoice, 'bookings' => Booking::with(['tenant', 'unit.building'])->latest()->get(), 'booking' => $invoice->booking]);
    }

    public function update(Request $request, Invoice $invoice)
    {
        $validated = $this->validated($request);
        $booking = Booking::findOrFail($validated['booking_id']);
        $total = TaxCalculator::invoiceTotal($validated);
        $paid = (float) $invoice->payments()->where('status', 'approved')->sum('amount');
        $invoice->update(array_merge($validated, [
            'tenant_id' => $booking->tenant_id,
            'unit_id' => $booking->unit_id,
            'vat_amount' => TaxCalculator::rentVat($validated['rent_amount'] ?? 0),
            'total_amount' => $total,
            'paid_amount' => $paid,
            'balance_amount' => max(0, $total - $paid),
            'updated_by' => auth()->id(),
        ]));

        return redirect()->route('invoices.show', $invoice)->with('status', 'Invoice updated successfully.');
    }

    public function pdf(Invoice $invoice, SimpleFinancePdf $pdf)
    {
        $this->authorizeTenantInvoice($invoice);

        return response($pdf->invoice($invoice), 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline; filename="'.$invoice->invoice_no.'.pdf"']);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'booking_id' => ['required', 'exists:bookings,id'],
            'invoice_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date'],
            'rent_amount' => ['nullable', 'numeric', 'min:0'],
            'deposit_amount' => ['nullable', 'numeric', 'min:0'],
            'dtcm_fee' => ['nullable', 'numeric', 'min:0'],
            'cleaning_fee' => ['nullable', 'numeric', 'min:0'],
            'agency_fee' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', Rule::in(Invoice::STATUSES)],
            'notes' => ['nullable', 'string', 'max:4000'],
        ]);
    }

    private function nextInvoiceNo(): string
    {
        return \App\Support\ReferenceNumber::next(Invoice::class, 'invoice_no', 'INV', 'Ymd', 4, true);
    }

    private function tenantFor(Request $request): ?\App\Models\Tenant
    {
        if (! $request->user()?->can('portal.tenant') || $request->user()?->can('invoices.manage')) {
            return null;
        }

        return \App\Models\Tenant::query()
            ->where('user_id', $request->user()->id)
            ->orWhere('email', $request->user()->email)
            ->first();
    }

    private function authorizeTenantInvoice(Invoice $invoice): void
    {
        $tenant = $this->tenantFor(request());

        if ($tenant) {
            abort_unless((int) $invoice->tenant_id === (int) $tenant->id, 403);
        }
    }
}
