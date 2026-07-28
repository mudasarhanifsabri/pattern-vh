<?php

namespace App\Http\Controllers;

use App\Models\Vendor;
use App\Models\VendorDocument;
use App\Support\ActivityLogger;
use App\Support\ErpStoragePath;
use App\Support\ReferenceNumber;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class VendorController extends Controller
{
    public function index(Request $request)
    {
        $vendors = Vendor::query()
            ->withCount('documents')
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = $request->string('search')->trim()->toString();

                $query->where(function ($query) use ($search): void {
                    $query->where('supplier_no', 'like', "%{$search}%")
                        ->orWhere('company_name', 'like', "%{$search}%")
                        ->orWhere('contact_person', 'like', "%{$search}%")
                        ->orWhere('trade_license_no', 'like', "%{$search}%")
                        ->orWhere('mobile_no', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
            ->when($request->filled('category'), fn ($query) => $query->where('category', $request->string('category')->toString()))
            ->orderBy('company_name')
            ->paginate(15)
            ->withQueryString();

        return view('vendors.index', [
            'vendors' => $vendors,
            'categories' => Vendor::CATEGORIES,
            'statuses' => Vendor::STATUSES,
        ]);
    }

    public function create()
    {
        return view('vendors.create', [
            'categories' => Vendor::CATEGORIES,
            'statuses' => Vendor::STATUSES,
            'documentTypes' => VendorDocument::TYPES,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validated($request);
        unset($validated['documents'], $validated['remove_document_ids']);
        $validated['supplier_no'] = $this->nextSupplierNumber();
        $validated['created_by'] = $request->user()->id;
        $validated['updated_by'] = $request->user()->id;

        $vendor = Vendor::create($validated);
        $this->storeDocuments($vendor, $request);

        ActivityLogger::log('vendors.created', "Registered vendor {$vendor->company_name}.", $vendor);

        return redirect()->route('vendors.show', $vendor)->with('status', 'Vendor/supplier registered successfully.');
    }

    public function show(Vendor $vendor)
    {
        return view('vendors.show', [
            'vendor' => $vendor->load(['documents' => fn ($query) => $query->latest()]),
        ]);
    }

    public function edit(Vendor $vendor)
    {
        return view('vendors.edit', [
            'vendor' => $vendor->load(['documents' => fn ($query) => $query->latest()]),
            'categories' => Vendor::CATEGORIES,
            'statuses' => Vendor::STATUSES,
            'documentTypes' => VendorDocument::TYPES,
        ]);
    }

    public function update(Request $request, Vendor $vendor)
    {
        $validated = $this->validated($request, $vendor);
        unset($validated['documents'], $validated['remove_document_ids']);
        $validated['updated_by'] = $request->user()->id;

        $vendor->update($validated);
        $this->removeDocuments($vendor, $request->input('remove_document_ids', []));
        $this->storeDocuments($vendor, $request);

        ActivityLogger::log('vendors.updated', "Updated vendor {$vendor->company_name}.", $vendor);

        return redirect()->route('vendors.show', $vendor)->with('status', 'Vendor/supplier updated successfully.');
    }

    public function document(Vendor $vendor, VendorDocument $vendorDocument)
    {
        abort_unless((int) $vendorDocument->vendor_id === (int) $vendor->id, 404);

        $disk = Storage::disk($vendorDocument->disk ?? config('filesystems.default'));

        if (method_exists($disk, 'temporaryUrl')) {
            try {
                return redirect()->away($disk->temporaryUrl($vendorDocument->path, now()->addMinutes(10)));
            } catch (\Throwable) {
                // Fall back to a streamed download when the configured disk cannot sign URLs.
            }
        }

        try {
            return Response::streamDownload(
                fn () => print $disk->get($vendorDocument->path),
                $vendorDocument->original_name ?: basename($vendorDocument->path),
            );
        } catch (\Throwable) {
            abort(404);
        }
    }

    private function validated(Request $request, ?Vendor $vendor = null): array
    {
        return $request->validate([
            'company_name' => ['required', 'string', 'max:191'],
            'legal_name' => ['nullable', 'string', 'max:191'],
            'contact_person' => ['nullable', 'string', 'max:191'],
            'mobile_no' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:191'],
            'category' => ['required', Rule::in(Vendor::CATEGORIES)],
            'trade_license_no' => ['nullable', 'string', 'max:100', Rule::unique('vendors', 'trade_license_no')->ignore($vendor?->id)],
            'trade_license_expiry_date' => ['nullable', 'date'],
            'tax_registration_no' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:2000'],
            'bank_name' => ['nullable', 'string', 'max:191'],
            'bank_account_name' => ['nullable', 'string', 'max:191'],
            'iban' => ['nullable', 'string', 'max:100'],
            'payment_terms' => ['nullable', 'string', 'max:191'],
            'status' => ['required', Rule::in(Vendor::STATUSES)],
            'notes' => ['nullable', 'string', 'max:4000'],
            'documents' => ['nullable', 'array'],
            'documents.*.document_type' => ['nullable', Rule::in(VendorDocument::TYPES)],
            'documents.*.title' => ['nullable', 'string', 'max:191'],
            'documents.*.document_number' => ['nullable', 'string', 'max:100'],
            'documents.*.expiry_date' => ['nullable', 'date'],
            'documents.*.file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp,doc,docx,xls,xlsx', 'max:10240'],
            'remove_document_ids' => ['nullable', 'array'],
            'remove_document_ids.*' => ['integer', 'exists:vendor_documents,id'],
        ]);
    }

    private function storeDocuments(Vendor $vendor, Request $request): void
    {
        foreach ($request->file('documents', []) as $index => $document) {
            $file = data_get($document, 'file');

            if (! $file instanceof UploadedFile) {
                continue;
            }

            $metadata = $request->input("documents.{$index}", []);
            $documentType = $metadata['document_type'] ?? 'other';
            $title = trim((string) ($metadata['title'] ?? '')) ?: str($documentType)->replace('_', ' ')->headline()->toString();
            $disk = config('filesystems.default');
            $path = ErpStoragePath::documentPath(
                'Vendors',
                $vendor->supplier_no,
                'documents',
                $file,
                Str::slug($documentType).'-'.Str::random(8).'.'.$file->getClientOriginalExtension(),
            );

            try {
                $stored = Storage::disk($disk)->put($path, file_get_contents($file->getRealPath()));
            } catch (\Throwable $exception) {
                report($exception);
                $stored = false;
            }

            if (! $stored) {
                throw ValidationException::withMessages(['documents' => 'One of the documents could not be uploaded. Please check storage settings and try again.']);
            }

            $vendor->documents()->create([
                'document_type' => $documentType,
                'title' => $title,
                'document_number' => $metadata['document_number'] ?? null,
                'expiry_date' => $metadata['expiry_date'] ?? null,
                'disk' => $disk,
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'created_by' => $request->user()->id,
            ]);
        }
    }

    private function removeDocuments(Vendor $vendor, array $documentIds): void
    {
        $vendor->documents()
            ->whereIn('id', $documentIds)
            ->get()
            ->each(function (VendorDocument $document): void {
                Storage::disk($document->disk ?? config('filesystems.default'))->delete($document->path);
                $document->delete();
            });
    }

    private function nextSupplierNumber(): string
    {
        return ReferenceNumber::next(Vendor::class, 'supplier_no', 'VEN', 'Ymd', 4, true);
    }
}
