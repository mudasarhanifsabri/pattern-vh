<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <p class="text-[11px] font-bold uppercase tracking-[0.22em] text-blue-600">Owner profile</p>
            <h1 class="text-2xl font-bold text-[#071a3b]">{{ $owner->full_name }}</h1>
        </div>
    </x-slot>

    <div class="space-y-6">
        @if (session('status'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">{{ session('status') }}</div>
        @endif

        <div class="erp-card p-2">
            <div class="flex flex-wrap gap-2">
                <button type="button" data-record-tab="overview" class="record-tab rounded-xl px-4 py-2.5 text-sm font-bold">Overview</button>
                <button type="button" data-record-tab="units" class="record-tab rounded-xl px-4 py-2.5 text-sm font-bold">Units</button>
                <button type="button" data-record-tab="contracts" class="record-tab rounded-xl px-4 py-2.5 text-sm font-bold">Unit contracts</button>
                <a href="{{ route('owner-statements.index', ['owner_id' => $owner->id]) }}" class="rounded-xl px-4 py-2.5 text-sm font-bold text-slate-500 hover:bg-slate-50 hover:text-blue-700">Statement</a>
            </div>
        </div>

        <div data-record-tab-panel="overview">
        <div class="grid gap-5 xl:grid-cols-[1fr_380px]">
            <div class="space-y-5">
                <div class="erp-card p-5">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div>
                            <div class="flex flex-wrap items-center gap-3">
                                <h2 class="text-xl font-bold text-[#071a3b]">{{ $owner->full_name }}</h2>
                                @if ($owner->is_blacklisted)
                                    <span class="rounded-full bg-rose-50 px-2.5 py-1 text-xs font-bold text-rose-700">Blacklisted</span>
                                @else
                                    <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-700">Active</span>
                                @endif
                            </div>
                            <p class="mt-2 text-sm text-slate-500">Owner record for future unit assignment and payouts.</p>
                        </div>
                        @can('owners.manage')
                            <div class="flex gap-2">
                                <a href="{{ route('owners.edit', $owner) }}" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-bold text-slate-600 hover:bg-slate-50">Edit owner</a>
                                @if ($owner->email)
                                    <form method="POST" action="{{ route('owners.send-invite', $owner) }}">
                                        @csrf
                                        <button class="rounded-xl border border-blue-200 px-4 py-2.5 text-sm font-bold text-blue-700 hover:bg-blue-50" type="submit">
                                            {{ $owner->portal_invitation_sent_at ? 'Resend welcome email' : 'Send welcome email' }}
                                        </button>
                                    </form>
                                @endif
                                <form method="POST" action="{{ route('owners.destroy', $owner) }}" onsubmit="return confirm('Delete this owner?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="rounded-xl border border-rose-200 px-4 py-2.5 text-sm font-bold text-rose-600 hover:bg-rose-50">Delete</button>
                                </form>
                            </div>
                        @endcan
                    </div>

                    <dl class="mt-6 grid gap-4 md:grid-cols-2">
                        <div class="rounded-2xl bg-slate-50 p-4">
                            <dt class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">Mobile</dt>
                            <dd class="mt-1 font-bold text-[#071a3b]">{{ $owner->mobile_no }} @if ($owner->mobile_has_whatsapp)<span class="text-sm text-emerald-600">WhatsApp</span>@endif</dd>
                        </div>
                        <div class="rounded-2xl bg-slate-50 p-4">
                            <dt class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">Email</dt>
                            <dd class="mt-1 font-bold text-[#071a3b]">{{ $owner->email ?: 'Not added' }}</dd>
                            <dd class="mt-1 text-xs text-slate-500">
                                Portal invite: {{ $owner->portal_invitation_sent_at?->format('M d, Y H:i') ?? 'Not sent' }}
                            </dd>
                        </div>
                        <div class="rounded-2xl bg-slate-50 p-4">
                            <dt class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">Identity</dt>
                            <dd class="mt-1 font-bold text-[#071a3b]">{{ str($owner->identity_type)->replace('_', ' ')->headline() }} {{ $owner->identity_no }}</dd>
                            <dd class="mt-1 text-xs text-slate-500">Issued {{ $owner->identity_issue_date?->format('M d, Y') ?? 'Not set' }} / Expires {{ $owner->identity_expiry_date?->format('M d, Y') ?? 'Not set' }}</dd>
                        </div>
                        <div class="rounded-2xl bg-slate-50 p-4">
                            <dt class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">DOB / nationality</dt>
                            <dd class="mt-1 font-bold text-[#071a3b]">{{ $owner->date_of_birth?->format('M d, Y') ?? 'Not added' }} / {{ $owner->nationality ?: 'Nationality not added' }}</dd>
                        </div>
                    </dl>

                    @if ($owner->is_blacklisted)
                        <div class="mt-5 rounded-2xl border border-rose-200 bg-rose-50 p-4">
                            <p class="text-xs font-bold uppercase tracking-[0.16em] text-rose-500">Blacklist reason</p>
                            <p class="mt-2 text-sm text-rose-700">{{ $owner->blacklist_reason }}</p>
                        </div>
                    @endif
                </div>

                <div class="erp-card p-5">
                    <h2 class="text-lg font-bold text-[#071a3b]">Bank details</h2>
                    <dl class="mt-5 grid gap-4 md:grid-cols-2">
                        <div><dt class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">Bank</dt><dd class="mt-1 text-sm font-bold text-[#071a3b]">{{ $owner->bank_name ?: 'Not added' }}</dd></div>
                        <div><dt class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">Account name</dt><dd class="mt-1 text-sm font-bold text-[#071a3b]">{{ $owner->bank_account_name ?: 'Not added' }}</dd></div>
                        <div><dt class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">Account no</dt><dd class="mt-1 text-sm font-bold text-[#071a3b]">{{ $owner->bank_account_no ?: 'Not added' }}</dd></div>
                        <div><dt class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">IBAN</dt><dd class="mt-1 text-sm font-bold text-[#071a3b]">{{ $owner->iban ?: 'Not added' }}</dd></div>
                        <div><dt class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">SWIFT</dt><dd class="mt-1 text-sm font-bold text-[#071a3b]">{{ $owner->swift_code ?: 'Not added' }}</dd></div>
                    </dl>
                </div>
            </div>

            <div class="space-y-5">
                <div class="erp-card p-5">
                    <h2 class="text-lg font-bold text-[#071a3b]">Document</h2>
                    @if ($owner->document_path)
                        <p class="mt-1 text-sm text-slate-500">{{ $owner->document_original_name }}</p>
                        <p class="mt-2 break-all text-xs text-slate-400">{{ $owner->document_path }}</p>
                        <a href="{{ route('owners.document', $owner) }}" target="_blank" class="mt-4 inline-flex h-11 items-center rounded-xl bg-blue-600 px-4 text-sm font-bold text-white hover:bg-blue-700">Open document</a>
                    @else
                        <p class="mt-3 rounded-2xl border border-dashed border-slate-200 px-4 py-6 text-center text-sm text-slate-500">No passport or Emirates ID file uploaded.</p>
                    @endif
                </div>

                <div class="erp-card p-5">
                    <h2 class="text-lg font-bold text-[#071a3b]">Notes history</h2>

                    @can('owners.manage')
                        <form method="POST" action="{{ route('owners.notes.store', $owner) }}" class="mt-4">
                            @csrf
                            <textarea name="note" rows="4" class="erp-focus block w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" placeholder="Add an owner note..." required></textarea>
                            <x-input-error :messages="$errors->get('note')" class="mt-2" />
                            <div class="mt-3 flex justify-end"><x-primary-button>Add note</x-primary-button></div>
                        </form>
                    @endcan

                    <div class="mt-5 space-y-3">
                        @forelse ($owner->notes as $note)
                            <div class="rounded-2xl border border-slate-200 p-4">
                                <p class="text-sm text-slate-700">{{ $note->note }}</p>
                                <p class="mt-3 text-xs text-slate-400">{{ $note->user?->name ?? 'System' }} - {{ $note->created_at->format('M d, Y H:i') }}</p>
                            </div>
                        @empty
                            <p class="rounded-2xl border border-dashed border-slate-200 px-4 py-6 text-center text-sm text-slate-500">No notes yet.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
        </div>

        <div data-record-tab-panel="units" class="hidden">
            <div class="erp-card p-5">
                <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h2 class="text-lg font-bold text-[#071a3b]">Owner units</h2>
                        <p class="mt-1 text-sm text-slate-500">Apartments attached to this owner and their share allocation.</p>
                    </div>
                    <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-bold text-blue-700">{{ $owner->units->count() }} units</span>
                </div>

                <div class="mt-5 grid gap-4 lg:grid-cols-2">
                    @forelse ($owner->units as $unit)
                        <a href="{{ route('units.show', $unit) }}" class="rounded-3xl border border-slate-200 bg-white p-5 transition hover:border-blue-200 hover:bg-blue-50/30">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">{{ $unit->building?->name ?? 'No building' }}</p>
                                    <h3 class="mt-1 text-xl font-black text-[#071a3b]">Unit {{ $unit->unit_no }}</h3>
                                    <p class="mt-1 text-sm text-slate-500">{{ $unit->unit_type }} / {{ $unit->bedrooms ?? '-' }} bed / {{ $unit->size_sqft ? number_format((float) $unit->size_sqft).' sqft' : 'size not set' }}</p>
                                </div>
                                <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700">{{ $unit->pivot->share_percent }}%</span>
                            </div>
                            <div class="mt-5 grid gap-3 sm:grid-cols-3">
                                <div class="rounded-2xl bg-slate-50 p-3">
                                    <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-slate-400">Status</p>
                                    <p class="mt-1 text-sm font-bold text-[#071a3b]">{{ str($unit->availability_status)->headline() }}</p>
                                </div>
                                <div class="rounded-2xl bg-slate-50 p-3">
                                    <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-slate-400">Rent</p>
                                    <p class="mt-1 text-sm font-bold text-[#071a3b]">{{ $unit->rent_amount ? 'AED '.number_format((float) $unit->rent_amount, 0) : 'Not set' }}</p>
                                </div>
                                <div class="rounded-2xl bg-slate-50 p-3">
                                    <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-slate-400">Bookings</p>
                                    <p class="mt-1 text-sm font-bold text-[#071a3b]">{{ $unit->bookings_count }}</p>
                                </div>
                            </div>
                        </a>
                    @empty
                        <div class="rounded-3xl border border-dashed border-slate-200 p-8 text-center text-sm text-slate-500 lg:col-span-2">No units attached to this owner yet.</div>
                    @endforelse
                </div>
            </div>
        </div>

        <div data-record-tab-panel="contracts" class="hidden">
            <div class="erp-card p-5">
                <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h2 class="text-lg font-bold text-[#071a3b]">Unit contracts</h2>
                        <p class="mt-1 text-sm text-slate-500">Prepared space for management agreements, title deed renewals, owner payout terms, and signed contract files.</p>
                    </div>
                    @can('owner-contracts.manage')<a href="{{ route('owner-contracts.create', ['owner_id' => $owner->id]) }}" class="rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-bold text-white">Add contract</a>@endcan
                </div>
                <div class="mt-5 grid gap-4 lg:grid-cols-2">
                    @forelse($owner->unitContracts as $contract)
                        <a href="{{ route('owner-contracts.show', $contract) }}" class="rounded-3xl border border-slate-200 bg-white p-5 hover:bg-blue-50/30">
                            <div class="flex items-start justify-between gap-3">
                                <div><p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">{{ $contract->contract_no }}</p><h3 class="mt-1 text-lg font-black text-[#071a3b]">{{ $contract->unit->building?->name }} / Unit {{ $contract->unit->unit_no }}</h3><p class="mt-1 text-sm text-slate-500">{{ $contract->contract_start_date?->format('M d, Y') ?? 'No start' }} - {{ $contract->contract_end_date?->format('M d, Y') ?? 'No end' }}</p></div>
                                <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-bold text-blue-700">{{ str($contract->status)->headline() }}</span>
                            </div>
                            <div class="mt-4 grid grid-cols-2 gap-3">
                                <div class="rounded-2xl bg-slate-50 p-3"><p class="text-[10px] font-bold uppercase text-slate-400">Management</p><p class="font-bold text-[#071a3b]">{{ $contract->management_fee_percent }}%</p></div>
                                <div class="rounded-2xl bg-slate-50 p-3"><p class="text-[10px] font-bold uppercase text-slate-400">Signed</p><p class="font-bold text-[#071a3b]">{{ $contract->owner_signed_at ? 'Yes' : 'No' }}</p></div>
                            </div>
                        </a>
                    @empty
                        <div class="rounded-3xl border border-dashed border-slate-200 p-8 text-center text-sm text-slate-500 lg:col-span-2">No owner contracts yet.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const tabs = document.querySelectorAll('[data-record-tab]');
            const panels = document.querySelectorAll('[data-record-tab-panel]');
            const activate = (name) => {
                tabs.forEach((tab) => {
                    const active = tab.dataset.recordTab === name;
                    tab.classList.toggle('bg-blue-50', active);
                    tab.classList.toggle('text-blue-700', active);
                    tab.classList.toggle('text-slate-500', !active);
                    tab.classList.toggle('hover:bg-slate-50', !active);
                });
                panels.forEach((panel) => panel.classList.toggle('hidden', panel.dataset.recordTabPanel !== name));
            };
            tabs.forEach((tab) => tab.addEventListener('click', () => activate(tab.dataset.recordTab)));
            activate('overview');
        });
    </script>
</x-app-layout>
