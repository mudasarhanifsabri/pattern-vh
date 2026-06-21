@php
    $contract = $contract ?? null;
    $selectedOwnerId = old('owner_id', request('owner_id', $contract->owner_id ?? ''));
    $selectedUnitId = old('unit_id', request('unit_id', $contract->unit_id ?? ''));
@endphp
@csrf

@if ($errors->any())
    <div class="mb-5 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
        <p class="font-bold">Please fix the highlighted fields.</p>
        <ul class="mt-2 list-inside list-disc">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
@endif

<div class="grid gap-5 xl:grid-cols-[1fr_360px]">
    <div class="space-y-5">
        <div class="erp-card p-5">
            <div class="flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
                <div>
                    <p class="text-[11px] font-black uppercase tracking-[0.18em] text-blue-600">Contract setup</p>
                    <h2 class="mt-1 text-xl font-black text-[#071a3b]">Owner, unit, dates, terms, and PDF</h2>
                    <p class="mt-1 text-sm text-slate-500">Owner and apartment details are pulled from existing records. Only contract-specific fields are entered here.</p>
                </div>
                <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-black text-blue-700">Auto-filled snapshot</span>
            </div>

            <div class="mt-6 grid gap-4 md:grid-cols-2">
                <div>
                    <x-input-label for="owner_id" value="Owner" />
                    <select id="owner_id" name="owner_id" class="erp-focus mt-1 h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm" required>
                        <option value="">Select owner</option>
                        @foreach($owners as $owner)
                            <option value="{{ $owner->id }}"
                                data-name="{{ $owner->full_name }}"
                                data-email="{{ $owner->email }}"
                                data-mobile="{{ $owner->mobile_no }}"
                                data-bank="{{ $owner->bank_name }}"
                                data-iban="{{ $owner->iban }}"
                                @selected((string) $selectedOwnerId === (string) $owner->id)>
                                {{ $owner->full_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="unit_id" value="Apartment / unit" />
                    <select id="unit_id" name="unit_id" class="erp-focus mt-1 h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm" required>
                        <option value="">Select unit</option>
                        @foreach($units as $unit)
                            <option value="{{ $unit->id }}"
                                data-title="{{ $unit->building?->name }} / Unit {{ $unit->unit_no }}"
                                data-type="{{ $unit->unit_type }}"
                                data-fee="{{ $unit->management_fee_percent }}"
                                @selected((string) $selectedUnitId === (string) $unit->id)>
                                {{ $unit->building?->name }} / {{ $unit->unit_no }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="status" value="Contract status" />
                    <select id="status" name="status" class="erp-focus mt-1 h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm">
                        @foreach($statuses as $status)
                            <option value="{{ $status }}" @selected(old('status', $contract->status ?? 'draft') === $status)>{{ str($status)->headline() }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="effective_date" value="Effective / permit date" />
                    <x-text-input id="effective_date" name="effective_date" type="date" class="mt-1 block w-full" :value="old('effective_date', $contract?->effective_date?->format('Y-m-d'))" />
                </div>
                <div>
                    <x-input-label for="contract_start_date" value="Contract start" />
                    <x-text-input id="contract_start_date" name="contract_start_date" type="date" class="mt-1 block w-full" :value="old('contract_start_date', $contract?->contract_start_date?->format('Y-m-d'))" />
                </div>
                <div>
                    <x-input-label for="contract_end_date" value="Contract end" />
                    <x-text-input id="contract_end_date" name="contract_end_date" type="date" class="mt-1 block w-full" :value="old('contract_end_date', $contract?->contract_end_date?->format('Y-m-d'))" />
                </div>
            </div>
        </div>

        <div class="erp-card p-5">
            <h2 class="text-lg font-bold text-[#071a3b]">Contract terms</h2>
            <div class="mt-5 grid gap-4 md:grid-cols-3">
                <div>
                    <x-input-label for="management_fee_percent" value="Management fee %" />
                    <x-text-input id="management_fee_percent" name="management_fee_percent" class="mt-1 block w-full" :value="old('management_fee_percent', $contract->management_fee_percent ?? 10)" required />
                </div>
                <div>
                    <x-input-label for="startup_fee" value="Startup fee" />
                    <x-text-input id="startup_fee" name="startup_fee" class="mt-1 block w-full" :value="old('startup_fee', $contract->startup_fee ?? 0)" />
                </div>
                <div>
                    <x-input-label for="furniture_fee" value="Furniture / setup fee" />
                    <x-text-input id="furniture_fee" name="furniture_fee" class="mt-1 block w-full" :value="old('furniture_fee', $contract->furniture_fee ?? 0)" />
                </div>
                <div>
                    <x-input-label for="vat_amount" value="VAT amount, if applicable" />
                    <x-text-input id="vat_amount" name="vat_amount" class="mt-1 block w-full" :value="old('vat_amount', $contract->vat_amount ?? '')" placeholder="Auto if blank" />
                </div>
                <div>
                    <x-input-label for="grand_total" value="Grand total" />
                    <x-text-input id="grand_total" name="grand_total" class="mt-1 block w-full" :value="old('grand_total', $contract->grand_total ?? '')" placeholder="Auto if blank" />
                </div>
                <div>
                    <x-input-label for="bank_currency" value="Currency" />
                    <x-text-input id="bank_currency" name="bank_currency" class="mt-1 block w-full" :value="old('bank_currency', $contract->bank_currency ?? 'AED')" />
                </div>
            </div>
            <div class="mt-4">
                <x-input-label for="special_terms" value="Special contract terms" />
                <textarea id="special_terms" name="special_terms" rows="5" class="erp-focus mt-1 block w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">{{ old('special_terms', $contract->special_terms ?? 'Initial term 12 months from permit date. Owner personal use up to 30 calendar days per year during off-season only. Management fee deducted from booking revenue.') }}</textarea>
            </div>
        </div>

        <div class="erp-card p-5">
            <h2 class="text-lg font-bold text-[#071a3b]">Prepared contract PDF</h2>
            <p class="mt-1 text-sm text-slate-500">Upload the final PDF contract you prepared. The owner will open this file from email or portal, review it, and draw their signature online.</p>
            <label for="contract_document" class="mt-5 flex cursor-pointer flex-col items-center justify-center rounded-[1.5rem] border-2 border-dashed border-blue-200 bg-blue-50/50 px-4 py-8 text-center transition hover:border-blue-400 hover:bg-blue-50">
                <span class="rounded-2xl bg-white px-4 py-2 text-sm font-black text-blue-700">Choose contract PDF</span>
                <span class="mt-3 text-xs font-bold text-slate-500">{{ $contract?->contract_document_original_name ?: 'PDF up to 20 MB' }}</span>
            </label>
            <input id="contract_document" name="contract_document" type="file" accept="application/pdf,.pdf" class="sr-only">
            @if($contract?->contract_document_path)
                <a href="{{ route('owner-contracts.prepared-document', $contract) }}" target="_blank" class="mt-4 inline-flex rounded-xl border border-blue-200 px-4 py-2.5 text-sm font-bold text-blue-700">Open current uploaded PDF</a>
            @endif
        </div>
    </div>

    <aside class="space-y-5">
        <div class="erp-card p-5">
            <h2 class="text-lg font-bold text-[#071a3b]">Auto record preview</h2>
            <div class="mt-4 space-y-3 text-sm">
                <div class="rounded-2xl bg-slate-50 p-4">
                    <p class="text-xs font-black uppercase tracking-[0.16em] text-slate-400">Owner</p>
                    <p class="mt-1 font-black text-[#071a3b]" data-preview-owner>{{ $contract->owner_name ?? 'Select owner' }}</p>
                    <p class="mt-1 text-xs text-slate-500" data-preview-owner-meta>{{ $contract->owner_email ?? 'Email and mobile come from owner profile' }}</p>
                </div>
                <div class="rounded-2xl bg-slate-50 p-4">
                    <p class="text-xs font-black uppercase tracking-[0.16em] text-slate-400">Unit</p>
                    <p class="mt-1 font-black text-[#071a3b]" data-preview-unit>{{ $contract ? (($contract->property_name ?: $contract->unit?->building?->name).' / Unit '.($contract->property_no ?: $contract->unit?->unit_no)) : 'Select unit' }}</p>
                    <p class="mt-1 text-xs text-slate-500" data-preview-unit-meta>{{ $contract->property_type ?? 'Unit type and details come from apartment record' }}</p>
                </div>
                <div class="rounded-2xl bg-emerald-50 p-4 text-emerald-700">
                    <p class="font-bold">Owner/unit fields are no longer typed twice here.</p>
                    <p class="mt-1 text-xs leading-5">The contract keeps a snapshot when saved, so old contracts stay historically stable even if owner or unit records change later.</p>
                </div>
            </div>
        </div>

        <div class="erp-card p-5">
            <h2 class="text-lg font-bold text-[#071a3b]">Delivery</h2>
            <label class="mt-4 flex items-start gap-3 rounded-2xl border border-blue-100 bg-blue-50 p-4 text-sm">
                <input type="checkbox" name="send_signature_link" value="1" class="mt-1 rounded border-slate-300" @checked(old('send_signature_link'))>
                <span>
                    <span class="block font-black text-[#071a3b]">Send owner signing link now</span>
                    <span class="mt-1 block text-xs leading-5 text-slate-500">Queues an email to the owner. The same signing link is also available in the owner portal.</span>
                </span>
            </label>
            @if($contract?->signature_link_emailed_at)
                <p class="mt-3 rounded-2xl bg-emerald-50 px-4 py-3 text-xs font-bold text-emerald-700">Last emailed {{ $contract->signature_link_emailed_at->format('M d, Y H:i') }}</p>
            @endif
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('owner-contracts.index') }}" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-bold text-slate-600">Cancel</a>
            <button class="rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-bold text-white">Save contract</button>
        </div>
    </aside>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const owner = document.getElementById('owner_id');
    const unit = document.getElementById('unit_id');
    const fee = document.getElementById('management_fee_percent');
    const ownerPreview = document.querySelector('[data-preview-owner]');
    const ownerMeta = document.querySelector('[data-preview-owner-meta]');
    const unitPreview = document.querySelector('[data-preview-unit]');
    const unitMeta = document.querySelector('[data-preview-unit-meta]');

    const updateOwner = () => {
        const option = owner?.selectedOptions[0];
        if (!option || !option.value) return;
        ownerPreview.textContent = option.dataset.name || 'Selected owner';
        ownerMeta.textContent = [option.dataset.email, option.dataset.mobile].filter(Boolean).join(' / ') || 'No email or mobile set';
    };

    const updateUnit = () => {
        const option = unit?.selectedOptions[0];
        if (!option || !option.value) return;
        unitPreview.textContent = option.dataset.title || 'Selected unit';
        unitMeta.textContent = option.dataset.type || 'No type set';
        if (fee && (!fee.value || fee.value === '10')) fee.value = option.dataset.fee || fee.value;
    };

    owner?.addEventListener('change', updateOwner);
    unit?.addEventListener('change', updateUnit);
    updateOwner();
    updateUnit();
});
</script>
