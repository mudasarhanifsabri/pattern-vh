<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-[11px] font-bold uppercase tracking-[0.22em] text-blue-600">Owner contract</p>
            <h1 class="text-2xl font-bold text-[#071a3b]">{{ $contract->contract_no }}</h1>
            <p class="mt-2 text-sm text-slate-500">{{ $contract->owner_name }} / {{ $contract->unit->building?->name }} Unit {{ $contract->unit->unit_no }}</p>
        </div>
    </x-slot>

    <div class="space-y-6">
        @if(session('status'))<div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">{{ session('status') }}</div>@endif
        @if(session('signature_link'))
            <div class="rounded-2xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800">
                <p class="font-black">Owner signature link</p>
                <p class="mt-1 break-all">{{ session('signature_link') }}</p>
            </div>
        @endif

        <div class="grid gap-5 xl:grid-cols-[1fr_380px]">
            <div class="space-y-5">
                <div class="erp-card p-5">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h2 class="text-xl font-black text-[#071a3b]">{{ $contract->property_name ?: $contract->unit->building?->name }} / Unit {{ $contract->property_no ?: $contract->unit->unit_no }}</h2>
                            <p class="mt-1 text-sm text-slate-500">{{ $contract->contract_start_date?->format('M d, Y') ?? 'No start' }} to {{ $contract->contract_end_date?->format('M d, Y') ?? 'No end' }}</p>
                        </div>
                        <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-bold text-blue-700">{{ str($contract->status)->headline() }}</span>
                    </div>
                    <dl class="mt-6 grid gap-4 md:grid-cols-3">
                        <div class="rounded-2xl bg-slate-50 p-4"><dt class="text-xs font-bold uppercase text-slate-400">Management fee</dt><dd class="font-bold text-[#071a3b]">{{ $contract->management_fee_percent }}%</dd></div>
                        <div class="rounded-2xl bg-slate-50 p-4"><dt class="text-xs font-bold uppercase text-slate-400">Startup</dt><dd class="font-bold text-[#071a3b]">AED {{ number_format((float)$contract->startup_fee, 2) }}</dd></div>
                        <div class="rounded-2xl bg-slate-50 p-4"><dt class="text-xs font-bold uppercase text-slate-400">Grand total</dt><dd class="font-bold text-[#071a3b]">AED {{ number_format((float)$contract->grand_total, 2) }}</dd></div>
                    </dl>
                </div>

                <div class="erp-card p-5">
                    <h2 class="text-lg font-bold text-[#071a3b]">Owner and property details</h2>
                    <dl class="mt-5 grid gap-4 md:grid-cols-2">
                        @foreach(['owner_nationality'=>'Nationality','owner_passport_no'=>'Passport / ID','owner_contact_no'=>'Contact','owner_email'=>'Email','community'=>'Community','floor_no'=>'Floor','property_type'=>'Property type','dewa_account_no'=>'DEWA account'] as $field=>$label)
                            <div><dt class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">{{ $label }}</dt><dd class="mt-1 text-sm font-bold text-[#071a3b]">{{ $contract->{$field} ?: 'Not set' }}</dd></div>
                        @endforeach
                    </dl>
                </div>

                <div class="erp-card p-5">
                    <h2 class="text-lg font-bold text-[#071a3b]">Special terms</h2>
                    <p class="mt-3 whitespace-pre-line text-sm leading-6 text-slate-600">{{ $contract->special_terms ?: 'No special terms added.' }}</p>
                </div>
            </div>

            <aside class="space-y-5">
                <div class="erp-card p-5">
                    <h2 class="text-lg font-bold text-[#071a3b]">Actions</h2>
                    <div class="mt-4 space-y-2">
                        @can('owner-contracts.manage')
                            <a href="{{ route('owner-contracts.edit', $contract) }}" class="block rounded-xl border border-slate-200 px-4 py-2.5 text-center text-sm font-bold text-slate-600">Edit contract setup</a>
                            <form method="POST" action="{{ route('owner-contracts.signature-link', $contract) }}">@csrf<button class="w-full rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-bold text-white">Email owner signing link</button></form>
                        @endcan
                        @if($contract->contract_document_path)
                            <a href="{{ route('owner-contracts.prepared-document', $contract) }}" target="_blank" class="block rounded-xl bg-blue-600 px-4 py-2.5 text-center text-sm font-bold text-white">Open uploaded contract PDF</a>
                        @endif
                        <a href="{{ route('owner-contracts.pdf', $contract) }}" target="_blank" class="block rounded-xl bg-slate-900 px-4 py-2.5 text-center text-sm font-bold text-white">Open generated fallback PDF</a>
                        @if($signatureLink)
                            <a href="{{ $signatureLink }}" target="_blank" class="block rounded-xl border border-blue-200 px-4 py-2.5 text-center text-sm font-bold text-blue-700">Open signing page</a>
                        @endif
                        @if($contract->signed_document_path)
                            <a href="{{ route('owner-contracts.document', $contract) }}" class="block rounded-xl bg-slate-900 px-4 py-2.5 text-center text-sm font-bold text-white">Open signed file</a>
                        @endif
                    </div>
                    @if($contract->signature_link_emailed_at)
                        <p class="mt-3 rounded-2xl bg-emerald-50 px-4 py-3 text-xs font-bold text-emerald-700">Signing link emailed {{ $contract->signature_link_emailed_at->format('M d, Y H:i') }}</p>
                    @endif
                </div>

                <div class="erp-card p-5">
                    <h2 class="text-lg font-bold text-[#071a3b]">Signature status</h2>
                    <div class="mt-4 space-y-4">
                        <div class="rounded-2xl border border-slate-200 p-4">
                            <p class="text-xs font-black uppercase tracking-[0.16em] text-slate-400">Company</p>
                            <p class="mt-1 text-sm font-bold text-[#071a3b]">{{ $contract->company_signature_name ?: 'Not signed' }}</p>
                            <p class="text-xs text-slate-500">{{ $contract->company_signed_at?->format('M d, Y H:i') }}</p>
                            @if($contract->company_signature_data)<img src="{{ $contract->company_signature_data }}" alt="Company signature" class="mt-3 h-20 max-w-full object-contain">@endif
                        </div>
                        <div class="rounded-2xl border border-slate-200 p-4">
                            <p class="text-xs font-black uppercase tracking-[0.16em] text-slate-400">Owner</p>
                            <p class="mt-1 text-sm font-bold text-[#071a3b]">{{ $contract->owner_signature_name ?: 'Not signed' }}</p>
                            <p class="text-xs text-slate-500">{{ $contract->owner_signed_at?->format('M d, Y H:i') }}</p>
                            @if($contract->owner_signature_data)<img src="{{ $contract->owner_signature_data }}" alt="Owner signature" class="mt-3 h-20 max-w-full object-contain">@endif
                        </div>
                    </div>
                </div>

                @can('owner-contracts.manage')
                    @if(! $contract->company_signed_at)
                        <form method="POST" action="{{ route('owner-contracts.company-signature', $contract) }}" class="erp-card p-5">
                            @csrf
                            <h2 class="text-lg font-bold text-[#071a3b]">Company signature</h2>
                            <input name="signed_by" value="{{ old('signed_by', auth()->user()->name) }}" class="erp-focus mt-4 h-11 w-full rounded-xl border border-slate-200 px-3 text-sm" placeholder="Company signer name" required>
                            <div class="mt-4">
                                <x-signature-pad input-name="signature_data" input-id="company_contract_signature" label="Company signature" />
                            </div>
                            <button class="mt-4 w-full rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-bold text-white">Save company signature</button>
                        </form>
                    @endif
                @endcan

                @can('owner-contracts.manage')
                    <details class="erp-card group p-5">
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-4">
                            <span>
                                <span class="block text-lg font-bold text-[#071a3b]">Admin template helper</span>
                                <span class="mt-1 block text-sm text-slate-500">Internal placeholders for contract template setup.</span>
                            </span>
                            <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-black text-blue-700 group-open:hidden">Show</span>
                            <span class="hidden rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-600 group-open:inline-flex">Hide</span>
                        </summary>
                        <div class="mt-4 grid gap-2 text-xs">
                            @foreach(config('owner-contract-template.tags', []) as $tag => $description)
                                <div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2">
                                    <code class="font-black text-blue-700">{{ $tag }}</code>
                                    <p class="mt-1 text-slate-500">{{ $description }}</p>
                                </div>
                            @endforeach
                        </div>
                    </details>
                @endcan
            </aside>
        </div>
    </div>
</x-app-layout>
