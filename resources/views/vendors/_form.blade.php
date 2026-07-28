@csrf

@if($errors->any())
    <div class="mb-5 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"><p class="font-bold">Please fix the highlighted fields.</p><ul class="mt-2 list-inside list-disc">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif

@php
    $documentRows = old('documents', [
        ['document_type' => 'trade_license', 'title' => 'Trade licence', 'document_number' => '', 'expiry_date' => ''],
        ['document_type' => 'tax_registration_certificate', 'title' => 'Tax registration certificate', 'document_number' => '', 'expiry_date' => ''],
    ]);
@endphp

<div class="grid gap-5 xl:grid-cols-[1fr_360px]">
    <div class="space-y-5">
        <section class="erp-card p-5">
            <div><h2 class="text-lg font-bold text-[#071a3b]">Company details</h2><p class="mt-1 text-sm text-slate-500">Use the legal supplier details that appear on invoices and compliance documents.</p></div>
            <div class="mt-5 grid gap-4 md:grid-cols-2">
                <div><x-input-label for="company_name" value="Company name" /><x-text-input id="company_name" name="company_name" class="mt-1 block w-full" value="{{ old('company_name', $vendor->company_name ?? '') }}" required autofocus /></div>
                <div><x-input-label for="legal_name" value="Legal name" /><x-text-input id="legal_name" name="legal_name" class="mt-1 block w-full" value="{{ old('legal_name', $vendor->legal_name ?? '') }}" /></div>
                <div><x-input-label for="category" value="Service category" /><select id="category" name="category" class="erp-focus mt-1 h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm" required>@foreach($categories as $category)<option value="{{ $category }}" @selected(old('category', $vendor->category ?? 'other') === $category)>{{ str($category)->replace('_', ' ')->headline() }}</option>@endforeach</select></div>
                <div><x-input-label for="status" value="Registration status" /><select id="status" name="status" class="erp-focus mt-1 h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm" required>@foreach($statuses as $status)<option value="{{ $status }}" @selected(old('status', $vendor->status ?? 'pending_review') === $status)>{{ str($status)->replace('_', ' ')->headline() }}</option>@endforeach</select></div>
                <div><x-input-label for="contact_person" value="Contact person" /><x-text-input id="contact_person" name="contact_person" class="mt-1 block w-full" value="{{ old('contact_person', $vendor->contact_person ?? '') }}" /></div>
                <div><x-input-label for="mobile_no" value="Mobile number" /><x-text-input id="mobile_no" name="mobile_no" class="mt-1 block w-full" value="{{ old('mobile_no', $vendor->mobile_no ?? '') }}" /></div>
                <div><x-input-label for="email" value="Email" /><x-text-input id="email" name="email" type="email" class="mt-1 block w-full" value="{{ old('email', $vendor->email ?? '') }}" /></div>
                <div><x-input-label for="payment_terms" value="Payment terms" /><x-text-input id="payment_terms" name="payment_terms" class="mt-1 block w-full" placeholder="e.g. Net 30" value="{{ old('payment_terms', $vendor->payment_terms ?? '') }}" /></div>
            </div>
            <div class="mt-4"><x-input-label for="address" value="Address" /><textarea id="address" name="address" rows="3" class="erp-focus mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">{{ old('address', $vendor->address ?? '') }}</textarea></div>
        </section>

        <section class="erp-card p-5">
            <div><h2 class="text-lg font-bold text-[#071a3b]">Licensing and payment details</h2><p class="mt-1 text-sm text-slate-500">Record the supplier's registration information. Documents can be attached below.</p></div>
            <div class="mt-5 grid gap-4 md:grid-cols-2">
                <div><x-input-label for="trade_license_no" value="Trade licence number" /><x-text-input id="trade_license_no" name="trade_license_no" class="mt-1 block w-full" value="{{ old('trade_license_no', $vendor->trade_license_no ?? '') }}" /></div>
                <div><x-input-label for="trade_license_expiry_date" value="Trade licence expiry" /><x-text-input id="trade_license_expiry_date" name="trade_license_expiry_date" type="date" class="mt-1 block w-full" value="{{ old('trade_license_expiry_date', isset($vendor) && $vendor->trade_license_expiry_date ? $vendor->trade_license_expiry_date->format('Y-m-d') : '') }}" /></div>
                <div><x-input-label for="tax_registration_no" value="TRN / tax registration number" /><x-text-input id="tax_registration_no" name="tax_registration_no" class="mt-1 block w-full" value="{{ old('tax_registration_no', $vendor->tax_registration_no ?? '') }}" /></div>
                <div><x-input-label for="bank_name" value="Bank name" /><x-text-input id="bank_name" name="bank_name" class="mt-1 block w-full" value="{{ old('bank_name', $vendor->bank_name ?? '') }}" /></div>
                <div><x-input-label for="bank_account_name" value="Bank account name" /><x-text-input id="bank_account_name" name="bank_account_name" class="mt-1 block w-full" value="{{ old('bank_account_name', $vendor->bank_account_name ?? '') }}" /></div>
                <div><x-input-label for="iban" value="IBAN" /><x-text-input id="iban" name="iban" class="mt-1 block w-full" value="{{ old('iban', $vendor->iban ?? '') }}" /></div>
            </div>
            <div class="mt-4"><x-input-label for="notes" value="Internal notes" /><textarea id="notes" name="notes" rows="4" class="erp-focus mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">{{ old('notes', $vendor->notes ?? '') }}</textarea></div>
        </section>
    </div>

    <div class="space-y-5">
        <section class="erp-card p-5">
            <div class="flex items-start justify-between gap-3"><div><h2 class="text-lg font-bold text-[#071a3b]">Documents</h2><p class="mt-1 text-sm text-slate-500">Attach multiple documents, including the trade licence, tax certificate, insurance, and bank letter.</p></div><button type="button" id="add-vendor-document" class="rounded-xl border border-blue-200 px-3 py-2 text-xs font-bold text-blue-700">Add document</button></div>

            @if(isset($vendor) && $vendor->documents->isNotEmpty())
                <div class="mt-4 space-y-2 border-b border-slate-100 pb-4">
                    @foreach($vendor->documents as $document)
                        <label class="flex items-center justify-between gap-3 rounded-xl bg-slate-50 p-3 text-xs"><span><a href="{{ route('vendors.documents.show', [$vendor, $document]) }}" class="font-bold text-blue-700 hover:underline">{{ $document->title ?: str($document->document_type)->replace('_', ' ')->headline() }}</a><span class="mt-1 block text-slate-500">{{ $document->original_name }}</span></span><span class="inline-flex items-center gap-2 font-bold text-rose-600"><input type="checkbox" name="remove_document_ids[]" value="{{ $document->id }}"> Remove</span></label>
                    @endforeach
                </div>
            @endif

            <div id="vendor-document-rows" class="mt-4 space-y-4">
                @foreach($documentRows as $index => $documentRow)
                    <div class="vendor-document-row rounded-2xl border border-slate-200 p-3">
                        <div class="flex items-center justify-between"><p class="text-xs font-black uppercase tracking-[0.14em] text-slate-500">New attachment</p><button type="button" class="remove-vendor-document text-xs font-bold text-rose-600">Remove</button></div>
                        <div class="mt-3 space-y-3">
                            <select name="documents[{{ $index }}][document_type]" class="erp-focus h-10 w-full rounded-xl border border-slate-200 bg-white px-3 text-xs">@foreach($documentTypes as $type)<option value="{{ $type }}" @selected(($documentRow['document_type'] ?? 'other') === $type)>{{ str($type)->replace('_', ' ')->headline() }}</option>@endforeach</select>
                            <input name="documents[{{ $index }}][title]" value="{{ $documentRow['title'] ?? '' }}" placeholder="Document title" class="erp-focus h-10 w-full rounded-xl border border-slate-200 px-3 text-xs">
                            <div class="grid grid-cols-2 gap-2"><input name="documents[{{ $index }}][document_number]" value="{{ $documentRow['document_number'] ?? '' }}" placeholder="Document number" class="erp-focus h-10 w-full rounded-xl border border-slate-200 px-3 text-xs"><input type="date" name="documents[{{ $index }}][expiry_date]" value="{{ $documentRow['expiry_date'] ?? '' }}" class="erp-focus h-10 w-full rounded-xl border border-slate-200 px-3 text-xs"></div>
                            <input type="file" name="documents[{{ $index }}][file]" accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx,.xls,.xlsx" class="block w-full text-xs text-slate-500 file:mr-3 file:rounded-lg file:border-0 file:bg-blue-50 file:px-3 file:py-2 file:font-bold file:text-blue-700">
                        </div>
                    </div>
                @endforeach
            </div>
            <p class="mt-3 text-xs leading-5 text-slate-500">Accepted: PDF, image, Word, and Excel files up to 10 MB each. Leave a new attachment empty if it is not needed.</p>
        </section>

        <div class="flex justify-end gap-3"><a href="{{ isset($vendor) ? route('vendors.show', $vendor) : route('vendors.index') }}" class="rounded-xl border border-slate-200 px-4 py-3 text-sm font-bold text-slate-600">Cancel</a><button class="rounded-xl bg-blue-600 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-blue-600/20">{{ $submitLabel }}</button></div>
    </div>
</div>

<template id="vendor-document-template">
    <div class="vendor-document-row rounded-2xl border border-slate-200 p-3">
        <div class="flex items-center justify-between"><p class="text-xs font-black uppercase tracking-[0.14em] text-slate-500">New attachment</p><button type="button" class="remove-vendor-document text-xs font-bold text-rose-600">Remove</button></div>
        <div class="mt-3 space-y-3">
            <select data-name="document_type" class="erp-focus h-10 w-full rounded-xl border border-slate-200 bg-white px-3 text-xs">@foreach($documentTypes as $type)<option value="{{ $type }}">{{ str($type)->replace('_', ' ')->headline() }}</option>@endforeach</select>
            <input data-name="title" placeholder="Document title" class="erp-focus h-10 w-full rounded-xl border border-slate-200 px-3 text-xs">
            <div class="grid grid-cols-2 gap-2"><input data-name="document_number" placeholder="Document number" class="erp-focus h-10 w-full rounded-xl border border-slate-200 px-3 text-xs"><input type="date" data-name="expiry_date" class="erp-focus h-10 w-full rounded-xl border border-slate-200 px-3 text-xs"></div>
            <input type="file" data-name="file" accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx,.xls,.xlsx" class="block w-full text-xs text-slate-500 file:mr-3 file:rounded-lg file:border-0 file:bg-blue-50 file:px-3 file:py-2 file:font-bold file:text-blue-700">
        </div>
    </div>
</template>

<script>
    (() => {
        const rows = document.getElementById('vendor-document-rows');
        const template = document.getElementById('vendor-document-template');
        const addButton = document.getElementById('add-vendor-document');
        let index = rows.querySelectorAll('.vendor-document-row').length;

        addButton.addEventListener('click', () => {
            const fragment = template.content.cloneNode(true);
            fragment.querySelectorAll('[data-name]').forEach((field) => {
                field.name = `documents[${index}][${field.dataset.name}]`;
            });
            rows.appendChild(fragment);
            index += 1;
        });

        rows.addEventListener('click', (event) => {
            if (event.target.classList.contains('remove-vendor-document')) {
                event.target.closest('.vendor-document-row').remove();
            }
        });
    })();
</script>
