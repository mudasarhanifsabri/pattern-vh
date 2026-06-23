<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Receipt;
use App\Support\ActivityLogger;
use App\Support\ErpStoragePath;
use App\Support\InvoicePaymentWorkflow;
use App\Support\PushEventLogger;
use App\Support\SimpleFinancePdf;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class PaymentController extends Controller
{
    public function index()
    {
        $payments = Payment::query()
            ->with(['invoice.tenant', 'booking.unit.building', 'receipt'])
            ->when(request('search'), function ($query, string $search): void {
                $query->where('payment_no', 'like', "%{$search}%")
                    ->orWhere('reference_no', 'like', "%{$search}%")
                    ->orWhereHas('invoice', fn ($query) => $query->where('invoice_no', 'like', "%{$search}%"))
                    ->orWhereHas('invoice.tenant', fn ($query) => $query->where('full_name', 'like', "%{$search}%"));
            })
            ->when(request('status'), fn ($query, string $status) => $query->where('status', $status))
            ->when(request('method'), fn ($query, string $method) => $query->where('method', $method))
            ->latest('paid_at')
            ->paginate(12)
            ->withQueryString();

        $openInvoices = Invoice::query()
            ->with(['tenant', 'booking.unit.building'])
            ->where('balance_amount', '>', 0)
            ->when(request('invoice_status') === 'overdue', fn ($query) => $query->whereDate('due_date', '<', today()))
            ->when(request('invoice_status') === 'unpaid', fn ($query) => $query->where('paid_amount', '<=', 0))
            ->when(request('invoice_status') === 'partially_paid', fn ($query) => $query->where('paid_amount', '>', 0)->where('balance_amount', '>', 0))
            ->orderByRaw('case when due_date is null then 1 else 0 end')
            ->orderBy('due_date')
            ->limit(8)
            ->get();

        return view('payments.index', [
            'payments' => $payments,
            'openInvoices' => $openInvoices,
            'stats' => [
                'approved' => Payment::where('status', 'approved')->sum('amount'),
                'pending' => Payment::where('status', 'pending')->sum('amount'),
                'open_balance' => Invoice::where('balance_amount', '>', 0)->sum('balance_amount'),
                'overdue' => Invoice::where('balance_amount', '>', 0)->whereDate('due_date', '<', today())->sum('balance_amount'),
            ],
        ]);
    }

    public function store(Request $request, Invoice $invoice)
    {
        $validated = $request->validate([
            'method' => ['required', Rule::in(Payment::METHODS)],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'paid_at' => ['required', 'date'],
            'reference_no' => ['nullable', 'string', 'max:191'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'payment_proof' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
        ]);

        if ($request->hasFile('payment_proof')) {
            $validated = array_merge($validated, $this->storeProof($request->file('payment_proof'), $invoice));
        }

        unset($validated['payment_proof']);

        $payment = Payment::create(array_merge($validated, [
            'invoice_id' => $invoice->id,
            'booking_id' => $invoice->booking_id,
            'payment_no' => $this->nextPaymentNo(),
            'status' => 'pending',
            'created_by' => auth()->id(),
        ]));

        ActivityLogger::log('payments.created', "Recorded payment {$payment->payment_no}.", $payment);

        return redirect()->route('invoices.show', $invoice)->with('status', 'Payment recorded and waiting for verification.');
    }

    public function approve(Request $request, Payment $payment, InvoicePaymentWorkflow $workflow, PushEventLogger $push)
    {
        $validated = $request->validate([
            'verification_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $payment->forceFill([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'verification_notes' => $validated['verification_notes'] ?? null,
        ])->save();

        $receipt = $workflow->afterPayment($payment);

        if ($payment->collectionRequest) {
            $payment->collectionRequest->update([
                'status' => 'approved',
                'updated_by' => auth()->id(),
            ]);
        }

        ActivityLogger::log('payments.approved', "Approved payment {$payment->payment_no}.", $payment);

        $payment->loadMissing(['invoice.tenant', 'booking.tenant']);
        $tenant = $payment->invoice?->tenant ?: $payment->booking?->tenant;
        $push->toTenant(
            $tenant,
            'Payment approved',
            "Your payment {$payment->payment_no} for AED ".number_format((float) $payment->amount, 2).' has been approved.',
            ['type' => 'payment_approved', 'payment_id' => $payment->id, 'url' => route('dashboard')],
            $payment->booking
        );

        return redirect()->route('invoices.show', $payment->invoice)->with('status', $receipt ? 'Payment approved. Invoice paid and receipt issued.' : 'Payment approved successfully.');
    }

    public function reject(Request $request, Payment $payment, InvoicePaymentWorkflow $workflow, PushEventLogger $push)
    {
        $validated = $request->validate([
            'verification_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $payment->forceFill([
            'status' => 'rejected',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'verification_notes' => $validated['verification_notes'] ?? null,
        ])->save();

        $workflow->afterPayment($payment);

        if ($payment->collectionRequest) {
            $payment->collectionRequest->update([
                'status' => 'rejected',
                'updated_by' => auth()->id(),
            ]);
        }

        ActivityLogger::log('payments.rejected', "Rejected payment {$payment->payment_no}.", $payment);

        $payment->loadMissing(['invoice.tenant', 'booking.tenant']);
        $tenant = $payment->invoice?->tenant ?: $payment->booking?->tenant;
        $push->toTenant(
            $tenant,
            'Payment needs review',
            "Your payment {$payment->payment_no} needs review. Please check your tenant app.",
            ['type' => 'payment_rejected', 'payment_id' => $payment->id, 'url' => route('dashboard')],
            $payment->booking
        );

        return redirect()->route('invoices.show', $payment->invoice)->with('status', 'Payment rejected.');
    }

    public function receiptPdf(Receipt $receipt, SimpleFinancePdf $pdf)
    {
        return response($pdf->receipt($receipt), 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline; filename="'.$receipt->receipt_no.'.pdf"']);
    }

    public function proof(Payment $payment)
    {
        abort_if(! $payment->proof_path, 404);

        $disk = Storage::disk($payment->proof_disk ?? config('filesystems.default'));

        if (method_exists($disk, 'temporaryUrl')) {
            try {
                return redirect()->away($disk->temporaryUrl($payment->proof_path, now()->addMinutes(10)));
            } catch (\Throwable) {
                //
            }
        }

        try {
            return response()->streamDownload(fn () => print $disk->get($payment->proof_path), $payment->proof_original_name ?: basename($payment->proof_path));
        } catch (\Throwable) {
            abort(404);
        }
    }

    private function storeProof(UploadedFile $file, Invoice $invoice): array
    {
        $disk = config('filesystems.default');
        $name = "Payment Proof - {$invoice->invoice_no}.{$file->getClientOriginalExtension()}";
        $path = ErpStoragePath::documentPath('Payments', $invoice->invoice_no, 'proofs', $file, $name);

        Storage::disk($disk)->put($path, file_get_contents($file->getRealPath()));

        return [
            'proof_disk' => $disk,
            'proof_path' => $path,
            'proof_original_name' => $name,
        ];
    }

    private function nextPaymentNo(): string
    {
        return 'PAY-'.now()->format('Ymd').'-'.str_pad((string) (Payment::whereDate('created_at', today())->count() + 1), 4, '0', STR_PAD_LEFT);
    }
}
