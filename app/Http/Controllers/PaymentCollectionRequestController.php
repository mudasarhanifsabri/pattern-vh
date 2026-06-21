<?php

namespace App\Http\Controllers;

use App\Models\OperationsTeamMember;
use App\Models\Payment;
use App\Models\PaymentCollectionRequest;
use App\Support\ActivityLogger;
use App\Support\ErpStoragePath;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class PaymentCollectionRequestController extends Controller
{
    public function index()
    {
        return view('payment-collection-requests.index', [
            'requests' => PaymentCollectionRequest::query()
                ->with(['invoice', 'tenant', 'booking.unit.building', 'assignedTo', 'payment.receipt'])
                ->when(request('status'), fn ($query, string $status) => $query->where('status', $status))
                ->latest()
                ->paginate(20)
                ->withQueryString(),
            'teamMembers' => OperationsTeamMember::query()->orderBy('full_name')->get(),
        ]);
    }

    public function schedule(Request $request, PaymentCollectionRequest $paymentCollectionRequest)
    {
        $validated = $request->validate([
            'assigned_to_id' => ['nullable', 'exists:operations_team_members,id'],
            'scheduled_at' => ['nullable', 'date'],
            'office_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $paymentCollectionRequest->update(array_merge($validated, [
            'status' => 'scheduled',
            'updated_by' => $request->user()->id,
        ]));

        ActivityLogger::log('payment_collection_requests.scheduled', "Scheduled payment collection {$paymentCollectionRequest->request_no}.", $paymentCollectionRequest);

        return back()->with('status', 'Collection request scheduled.');
    }

    public function collect(Request $request, PaymentCollectionRequest $paymentCollectionRequest)
    {
        abort_if($paymentCollectionRequest->payment_id, 422, 'Payment already recorded for this collection request.');

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'collected_at' => ['required', 'date'],
            'reference_no' => ['nullable', 'string', 'max:191'],
            'office_notes' => ['nullable', 'string', 'max:1000'],
            'payment_proof' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
        ]);

        $proof = $request->hasFile('payment_proof')
            ? $this->storeProof($request->file('payment_proof'), $paymentCollectionRequest)
            : [];

        $payment = Payment::create(array_merge($proof, [
            'invoice_id' => $paymentCollectionRequest->invoice_id,
            'booking_id' => $paymentCollectionRequest->booking_id,
            'collection_request_id' => $paymentCollectionRequest->id,
            'payment_no' => $this->nextPaymentNo(),
            'method' => $paymentCollectionRequest->collection_method,
            'status' => 'pending',
            'amount' => $validated['amount'],
            'paid_at' => $validated['collected_at'],
            'reference_no' => $validated['reference_no'] ?? null,
            'notes' => 'Collected from tenant doorstep. '.$paymentCollectionRequest->request_no,
            'created_by' => $request->user()->id,
        ]));

        $paymentCollectionRequest->update([
            'status' => 'collected_pending_verification',
            'payment_id' => $payment->id,
            'collected_at' => $validated['collected_at'],
            'office_notes' => $validated['office_notes'] ?? $paymentCollectionRequest->office_notes,
            'updated_by' => $request->user()->id,
        ]);

        ActivityLogger::log('payment_collection_requests.collected', "Recorded collection {$paymentCollectionRequest->request_no} as pending payment {$payment->payment_no}.", $paymentCollectionRequest);

        return redirect()->route('invoices.show', $paymentCollectionRequest->invoice)->with('status', 'Doorstep collection recorded as pending payment. Finance can approve it now.');
    }

    public function cancel(Request $request, PaymentCollectionRequest $paymentCollectionRequest)
    {
        $validated = $request->validate([
            'office_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $paymentCollectionRequest->update([
            'status' => 'cancelled',
            'office_notes' => $validated['office_notes'] ?? null,
            'updated_by' => $request->user()->id,
        ]);

        ActivityLogger::log('payment_collection_requests.cancelled', "Cancelled payment collection {$paymentCollectionRequest->request_no}.", $paymentCollectionRequest);

        return back()->with('status', 'Collection request cancelled.');
    }

    private function storeProof(UploadedFile $file, PaymentCollectionRequest $request): array
    {
        $disk = config('filesystems.default');
        $name = "Collection Proof - {$request->request_no}.{$file->getClientOriginalExtension()}";
        $path = ErpStoragePath::documentPath('Payments', $request->request_no, 'collection-proofs', $file, $name);

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
