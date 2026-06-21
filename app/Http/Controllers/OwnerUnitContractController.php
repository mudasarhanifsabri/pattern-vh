<?php

namespace App\Http\Controllers;

use App\Mail\OwnerContractSignatureLinkMail;
use App\Models\Owner;
use App\Models\OwnerUnitContract;
use App\Models\Unit;
use App\Support\ActivityLogger;
use App\Support\ErpStoragePath;
use App\Support\OwnerContractPdf;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class OwnerUnitContractController extends Controller
{
    public function index()
    {
        $owner = $this->currentOwner();

        return view('owner-contracts.index', [
            'contracts' => OwnerUnitContract::query()
                ->with(['owner', 'unit.building'])
                ->when($owner, fn ($query) => $query->where('owner_id', $owner->id))
                ->latest()
                ->paginate(12),
        ]);
    }

    public function create()
    {
        return view('owner-contracts.create', $this->formData());
    }

    public function store(Request $request)
    {
        $validated = $this->validated($request);
        $validated = $this->applyContractSnapshot($validated);
        $validated['contract_no'] = $this->nextContractNo();
        $validated['created_by'] = $request->user()->id;
        $validated['updated_by'] = $request->user()->id;
        $validated = $this->normalizeTotals($validated);

        if ($request->hasFile('contract_document')) {
            $validated = array_merge($validated, $this->storePreparedDocument($request->file('contract_document'), $validated));
        }

        if ($request->hasFile('signed_document')) {
            $validated = array_merge($validated, $this->storeSignedDocument($request->file('signed_document'), $validated));
        }

        $sendSignatureLink = (bool) ($validated['send_signature_link'] ?? false);
        unset($validated['contract_document'], $validated['signed_document'], $validated['send_signature_link']);

        $contract = OwnerUnitContract::create($validated);
        if ($sendSignatureLink) {
            $this->prepareSignatureLink($contract, true);
        }

        ActivityLogger::log('owner_contracts.created', "Created owner contract {$contract->contract_no}.", $contract);

        return redirect()->route('owner-contracts.show', $contract)->with('status', 'Owner contract created.');
    }

    public function show(OwnerUnitContract $ownerContract)
    {
        $this->authorizeOwnerContract($ownerContract);

        return view('owner-contracts.show', [
            'contract' => $ownerContract->load(['owner', 'unit.building']),
            'signatureLink' => $ownerContract->signing_token ? route('owner-contracts.sign', [$ownerContract, $ownerContract->signing_token]) : null,
        ]);
    }

    public function edit(OwnerUnitContract $ownerContract)
    {
        return view('owner-contracts.edit', array_merge($this->formData(), ['contract' => $ownerContract]));
    }

    public function update(Request $request, OwnerUnitContract $ownerContract)
    {
        $validated = $this->validated($request);
        $validated = $this->applyContractSnapshot($validated);
        $validated['updated_by'] = $request->user()->id;
        $validated = $this->normalizeTotals($validated);

        if ($request->hasFile('contract_document')) {
            $validated = array_merge($validated, $this->storePreparedDocument($request->file('contract_document'), $validated));
        }

        if ($request->hasFile('signed_document')) {
            $validated = array_merge($validated, $this->storeSignedDocument($request->file('signed_document'), $validated));
        }

        $sendSignatureLink = (bool) ($validated['send_signature_link'] ?? false);
        unset($validated['contract_document'], $validated['signed_document'], $validated['send_signature_link']);
        $ownerContract->update($validated);
        if ($sendSignatureLink) {
            $this->prepareSignatureLink($ownerContract->fresh(['owner', 'unit.building']), true);
        }

        return redirect()->route('owner-contracts.show', $ownerContract)->with('status', 'Owner contract updated.');
    }

    public function destroy(OwnerUnitContract $ownerContract)
    {
        $ownerContract->delete();

        return redirect()->route('owner-contracts.index')->with('status', 'Owner contract deleted.');
    }

    public function document(OwnerUnitContract $ownerContract)
    {
        $this->authorizeOwnerContract($ownerContract);

        abort_if(! $ownerContract->signed_document_path, 404);

        $disk = Storage::disk($ownerContract->signed_document_disk ?? config('filesystems.default'));

        return response()->streamDownload(
            fn () => print $disk->get($ownerContract->signed_document_path),
            $ownerContract->signed_document_original_name ?: basename($ownerContract->signed_document_path),
        );
    }

    public function preparedDocument(OwnerUnitContract $ownerContract)
    {
        $this->authorizeOwnerContract($ownerContract);

        return $this->streamPreparedDocument($ownerContract);
    }

    public function pdf(OwnerUnitContract $ownerContract, OwnerContractPdf $pdf)
    {
        $this->authorizeOwnerContract($ownerContract);

        return response($pdf->make($ownerContract), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$ownerContract->contract_no.'-owner-contract.pdf"',
        ]);
    }

    public function signatureLink(OwnerUnitContract $ownerContract)
    {
        $link = $this->prepareSignatureLink($ownerContract->load(['owner', 'unit.building']), true);

        return back()
            ->with('status', 'Owner signature link is ready and emailed if owner email is available.')
            ->with('signature_link', $link);
    }

    public function signature(OwnerUnitContract $ownerContract, string $token)
    {
        abort_unless(hash_equals((string) $ownerContract->signing_token, $token), 403);

        return view('owner-contracts.sign', [
            'contract' => $ownerContract->load(['owner', 'unit.building']),
            'token' => $token,
        ]);
    }

    public function signaturePdf(OwnerUnitContract $ownerContract, string $token, OwnerContractPdf $pdf)
    {
        abort_unless(hash_equals((string) $ownerContract->signing_token, $token), 403);

        return response($pdf->make($ownerContract), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$ownerContract->contract_no.'-owner-contract.pdf"',
        ]);
    }

    public function signatureDocument(OwnerUnitContract $ownerContract, string $token)
    {
        abort_unless(hash_equals((string) $ownerContract->signing_token, $token), 403);

        return $this->streamPreparedDocument($ownerContract);
    }

    public function sign(Request $request, OwnerUnitContract $ownerContract, string $token)
    {
        abort_unless(hash_equals((string) $ownerContract->signing_token, $token), 403);

        $validated = $request->validate([
            'signed_by' => ['required', 'string', 'max:191'],
            'signature_data' => ['required', 'string', 'starts_with:data:image/png;base64,'],
            'accepted_terms' => ['accepted'],
        ]);

        $ownerContract->forceFill([
            'owner_signed_at' => now(),
            'owner_signature_name' => $validated['signed_by'],
            'owner_signature_data' => $validated['signature_data'],
            'owner_signed_ip' => $request->ip(),
            'owner_signed_user_agent' => substr((string) $request->userAgent(), 0, 500),
            'status' => $ownerContract->company_signed_at ? 'active' : 'sent',
        ])->save();

        ActivityLogger::log('owner_contracts.owner_signed', "Owner signed contract {$ownerContract->contract_no}.", $ownerContract);

        return redirect()->route('owner-contracts.sign', [$ownerContract, $token])->with('status', 'Owner contract signed successfully.');
    }

    public function companySign(Request $request, OwnerUnitContract $ownerContract)
    {
        $validated = $request->validate([
            'signed_by' => ['required', 'string', 'max:191'],
            'signature_data' => ['required', 'string', 'starts_with:data:image/png;base64,'],
        ]);

        $ownerContract->forceFill([
            'company_signed_at' => now(),
            'company_signature_name' => $validated['signed_by'],
            'company_signature_data' => $validated['signature_data'],
            'company_signed_ip' => $request->ip(),
            'status' => $ownerContract->owner_signed_at ? 'active' : $ownerContract->status,
            'updated_by' => $request->user()->id,
        ])->save();

        ActivityLogger::log('owner_contracts.company_signed', "Company signed contract {$ownerContract->contract_no}.", $ownerContract);

        return back()->with('status', 'Company signature saved.');
    }

    private function formData(): array
    {
        return [
            'owners' => Owner::orderBy('full_name')->get(),
            'units' => Unit::with(['building', 'owners'])->orderBy('unit_no')->get(),
            'statuses' => OwnerUnitContract::STATUSES,
        ];
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'owner_id' => ['required', 'exists:owners,id'],
            'unit_id' => ['required', 'exists:units,id'],
            'status' => ['required', Rule::in(OwnerUnitContract::STATUSES)],
            'contract_start_date' => ['nullable', 'date'],
            'contract_end_date' => ['nullable', 'date', 'after_or_equal:contract_start_date'],
            'effective_date' => ['nullable', 'date'],
            'management_fee_percent' => ['required', 'numeric', 'between:0,100'],
            'startup_fee' => ['nullable', 'numeric', 'min:0'],
            'furniture_fee' => ['nullable', 'numeric', 'min:0'],
            'vat_amount' => ['nullable', 'numeric', 'min:0'],
            'grand_total' => ['nullable', 'numeric', 'min:0'],
            'bank_currency' => ['nullable', 'string', 'max:20'],
            'special_terms' => ['nullable', 'string', 'max:4000'],
            'contract_document' => ['nullable', 'file', 'mimes:pdf', 'max:20480'],
            'signed_document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
            'send_signature_link' => ['nullable', 'boolean'],
        ]);
    }

    private function applyContractSnapshot(array $validated): array
    {
        $owner = Owner::findOrFail($validated['owner_id']);
        $unit = Unit::with('building')->findOrFail($validated['unit_id']);

        return array_merge($validated, [
            'company_name' => 'Pattern Vacation Homes Rental',
            'company_registration_no' => '1123804',
            'company_contact_no' => '+971 4 329 9693',
            'company_email' => 'customerservice@pattern.ae',
            'company_address' => 'Office 413, Al Attar Business Centre, Al Barsha, Dubai, UAE',
            'owner_name' => $owner->full_name,
            'owner_nationality' => null,
            'owner_passport_no' => $owner->identity_no,
            'owner_contact_no' => $owner->mobile_no,
            'owner_email' => $owner->email,
            'property_name' => $unit->building?->name,
            'floor_no' => $unit->floor,
            'community' => $unit->building?->area,
            'property_no' => $unit->unit_no,
            'property_type' => $unit->unit_type,
            'dewa_account_no' => $unit->electricity_username ?: $unit->electricity_company,
            'bank_account_holder' => $owner->bank_account_name ?: $owner->full_name,
            'bank_name' => $owner->bank_name,
            'bank_account_no' => $owner->bank_account_no,
            'iban' => $owner->iban,
            'swift_code' => $owner->swift_code,
            'bank_currency' => $validated['bank_currency'] ?? 'AED',
        ]);
    }

    private function normalizeTotals(array $validated): array
    {
        $subtotal = (float) ($validated['startup_fee'] ?? 0) + (float) ($validated['furniture_fee'] ?? 0);
        $validated['vat_amount'] = $validated['vat_amount'] ?? ($subtotal * 0.05);
        $validated['grand_total'] = $validated['grand_total'] ?? ($subtotal + (float) $validated['vat_amount']);

        return $validated;
    }

    private function storeSignedDocument(UploadedFile $file, array $data): array
    {
        $disk = config('filesystems.default');
        $name = 'Owner Contract - '.$data['owner_name'].' - '.$data['property_no'].'.'.$file->getClientOriginalExtension();
        $path = ErpStoragePath::documentPath('Owner Contracts', $data['owner_name'], 'signed-contracts', $file, $name);
        Storage::disk($disk)->put($path, file_get_contents($file->getRealPath()));

        return [
            'signed_document_disk' => $disk,
            'signed_document_path' => $path,
            'signed_document_original_name' => $name,
        ];
    }

    private function storePreparedDocument(UploadedFile $file, array $data): array
    {
        $disk = config('filesystems.default');
        $name = 'Owner Contract PDF - '.$data['owner_name'].' - '.$data['property_no'].'.'.$file->getClientOriginalExtension();
        $path = ErpStoragePath::documentPath('Owner Contracts', $data['owner_name'], 'prepared-contracts', $file, $name);
        Storage::disk($disk)->put($path, file_get_contents($file->getRealPath()));

        return [
            'contract_document_disk' => $disk,
            'contract_document_path' => $path,
            'contract_document_original_name' => $name,
        ];
    }

    private function streamPreparedDocument(OwnerUnitContract $contract)
    {
        abort_if(! $contract->contract_document_path, 404);

        $disk = Storage::disk($contract->contract_document_disk ?? config('filesystems.default'));

        $filename = $contract->contract_document_original_name ?: basename($contract->contract_document_path);

        return response()->stream(
            fn () => print $disk->get($contract->contract_document_path),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="'.$filename.'"',
            ],
        );
    }

    private function prepareSignatureLink(OwnerUnitContract $contract, bool $sendEmail = false): string
    {
        $token = $contract->signing_token ?: Str::random(48);

        $contract->forceFill([
            'signing_token' => $token,
            'status' => $contract->status === 'draft' ? 'sent' : $contract->status,
            'updated_by' => auth()->id(),
        ])->save();

        $link = route('owner-contracts.sign', [$contract, $token]);

        if ($sendEmail && $contract->owner_email) {
            Mail::to($contract->owner_email)->queue(new OwnerContractSignatureLinkMail($contract->fresh(['unit.building']), $link));
            $contract->forceFill(['signature_link_emailed_at' => now()])->save();
        }

        ActivityLogger::log('owner_contracts.signature_link_created', "Created owner signature link for {$contract->contract_no}.", $contract);

        return $link;
    }

    private function nextContractNo(): string
    {
        return 'PMC-'.now()->format('ymd').'-'.str_pad((string) (OwnerUnitContract::withTrashed()->whereDate('created_at', today())->count() + 1), 4, '0', STR_PAD_LEFT);
    }

    private function currentOwner(): ?Owner
    {
        $user = auth()->user();

        if (! $user?->hasRole('Owner')) {
            return null;
        }

        return Owner::query()
            ->where('user_id', $user->id)
            ->orWhere('email', $user->email)
            ->first();
    }

    private function authorizeOwnerContract(OwnerUnitContract $contract): void
    {
        $owner = $this->currentOwner();

        if (! $owner) {
            return;
        }

        abort_unless((int) $contract->owner_id === (int) $owner->id, 403);
    }
}
