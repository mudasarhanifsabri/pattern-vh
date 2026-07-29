<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Invoice;
use App\Models\Tenant;
use App\Support\ActivityLogger;
use App\Support\ReferenceNumber;
use App\Support\TaxCalculator;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Mpdf\Mpdf;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
            'payout_due_date' => $booking->check_out_date?->toDateString(),
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

    public function pdf(Invoice $invoice)
    {
        $this->authorizeTenantInvoice($invoice);

        // Load the complete booking and payment ledger once for the detailed export template.
        $invoice = $this->invoiceForExport($invoice);
        $disposition = request()->boolean('download') ? 'attachment' : 'inline';
        $tempDir = storage_path('app/mpdf');

        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0775, true);
        }

        // mPDF supports multiple pages, so every payment remains visible on longer invoice histories.
        $pdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'default_font' => 'dejavusans',
            'tempDir' => $tempDir,
            'margin_left' => 12,
            'margin_right' => 12,
            'margin_top' => 12,
            'margin_bottom' => 12,
        ]);
        $pdf->SetTitle($invoice->invoice_no.' Booking Invoice');
        $pdf->WriteHTML(view('pdfs.invoice-export', compact('invoice'))->render());

        return response($pdf->Output('', 'S'), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => $disposition.'; filename="'.$invoice->invoice_no.'-booking-invoice.pdf"',
        ]);
    }

    public function excel(Invoice $invoice): StreamedResponse
    {
        $this->authorizeTenantInvoice($invoice);
        $invoice = $this->invoiceForExport($invoice);

        return response()->streamDownload(function () use ($invoice): void {
            $booking = $invoice->booking;
            $handle = fopen('php://output', 'w');

            // Excel opens CSV natively; metadata, charges, and payments are grouped into readable sections.
            fputcsv($handle, ['Booking Invoice Export']);
            fputcsv($handle, ['Invoice no', $invoice->invoice_no]);
            fputcsv($handle, ['Invoice status', str($invoice->status)->replace('_', ' ')->headline()->toString()]);
            fputcsv($handle, ['Tenant', $invoice->tenant?->full_name]);
            fputcsv($handle, ['Booking no', $booking?->booking_no]);
            fputcsv($handle, ['Property', $booking?->unit?->building?->name]);
            fputcsv($handle, ['Unit', $booking?->unit?->unit_no]);
            fputcsv($handle, ['Check-in date', $booking?->check_in_date?->format('Y-m-d')]);
            fputcsv($handle, ['Check-in time', $booking?->check_in_time]);
            fputcsv($handle, ['Check-out date', $booking?->check_out_date?->format('Y-m-d')]);
            fputcsv($handle, ['Check-out time', $booking?->check_out_time]);
            fputcsv($handle, ['Guests', $booking?->guest_count]);
            fputcsv($handle, []);

            fputcsv($handle, ['Invoice Charges']);
            fputcsv($handle, ['Description', 'Amount (AED)']);
            foreach ($this->chargeRows($invoice) as [$description, $amount]) {
                fputcsv($handle, [$description, number_format($amount, 2, '.', '')]);
            }
            fputcsv($handle, ['Invoice total', number_format((float) $invoice->total_amount, 2, '.', '')]);
            fputcsv($handle, ['Approved paid', number_format((float) $invoice->paid_amount, 2, '.', '')]);
            fputcsv($handle, ['Balance due', number_format((float) $invoice->balance_amount, 2, '.', '')]);
            fputcsv($handle, []);

            fputcsv($handle, ['All Payments']);
            fputcsv($handle, ['Payment no', 'Status', 'Method', 'Amount (AED)', 'Paid at', 'Approved at', 'Reference', 'Receipt no', 'Check-in code', 'Notes', 'Verification notes']);
            foreach ($invoice->payments as $payment) {
                fputcsv($handle, [
                    $payment->payment_no,
                    str($payment->status)->replace('_', ' ')->headline()->toString(),
                    str($payment->method)->replace('_', ' ')->headline()->toString(),
                    number_format((float) $payment->amount, 2, '.', ''),
                    $payment->paid_at?->format('Y-m-d H:i'),
                    $payment->approved_at?->format('Y-m-d H:i'),
                    $payment->reference_no,
                    $payment->receipt?->receipt_no,
                    $payment->receipt?->check_in_code,
                    $payment->notes,
                    $payment->verification_notes,
                ]);
            }

            fclose($handle);
        }, $invoice->invoice_no.'-booking-invoice.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
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
        return ReferenceNumber::next(Invoice::class, 'invoice_no', 'INV', 'Ymd', 4, true);
    }

    private function invoiceForExport(Invoice $invoice): Invoice
    {
        return $invoice->load([
            'booking.unit.building',
            'tenant',
            'payments' => fn ($query) => $query->with('receipt')->orderBy('paid_at')->orderBy('id'),
        ]);
    }

    private function chargeRows(Invoice $invoice): array
    {
        return [
            ['Rent', (float) $invoice->rent_amount],
            ['VAT 5% on rent only', (float) $invoice->vat_amount],
            ['Security deposit', (float) $invoice->deposit_amount],
            ['DTCM fee', (float) $invoice->dtcm_fee],
            ['Cleaning fee', (float) $invoice->cleaning_fee],
            ['Agency fee', (float) $invoice->agency_fee],
        ];
    }

    private function tenantFor(Request $request): ?Tenant
    {
        if (! $request->user()?->can('portal.tenant') || $request->user()?->can('invoices.manage')) {
            return null;
        }

        return Tenant::query()
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
