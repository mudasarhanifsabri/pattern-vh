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
        @if ($errors->any())
            <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ $errors->first() }}</div>
        @endif

        <section class="erp-card overflow-hidden">
            <div class="border-b border-slate-100 bg-white p-5">
                <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                    <div class="flex items-start gap-4">
                        <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-blue-600 text-xl font-black text-white">
                            {{ str($owner->full_name)->substr(0, 2)->upper() }}
                        </div>
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="text-2xl font-black text-[#071a3b]">{{ $owner->full_name }}</h2>
                                <span class="rounded-full px-3 py-1 text-xs font-bold {{ $owner->is_blacklisted ? 'bg-rose-50 text-rose-700' : 'bg-emerald-50 text-emerald-700' }}">{{ $owner->is_blacklisted ? 'Blacklisted' : 'Active' }}</span>
                            </div>
                            <p class="mt-1 max-w-3xl text-sm text-slate-500">Owner record with profile, documents, units, contracts, notes, and payout bank details in one place.</p>
                            <div class="mt-3 flex flex-wrap gap-2 text-xs font-bold text-slate-500">
                                <span class="rounded-full bg-slate-100 px-3 py-1">{{ $owner->mobile_no }}</span>
                                <span class="rounded-full bg-slate-100 px-3 py-1">{{ $owner->email ?: 'No email' }}</span>
                                @if ($owner->mobile_has_whatsapp)<span class="rounded-full bg-emerald-50 px-3 py-1 text-emerald-700">WhatsApp</span>@endif
                            </div>
                        </div>
                    </div>

                    @can('owners.manage')
                        <div class="flex flex-wrap gap-2">
                            <a href="{{ route('owners.edit', $owner) }}" class="inline-flex h-11 items-center rounded-xl border border-slate-200 px-4 text-sm font-bold text-slate-700 hover:bg-slate-50">Edit</a>
                            <button type="button" data-modal-open="owner-note-modal" class="inline-flex h-11 items-center rounded-xl border border-blue-200 px-4 text-sm font-bold text-blue-700 hover:bg-blue-50">Add note</button>
                            <button type="button" data-modal-open="owner-actions-modal" class="inline-flex h-11 items-center rounded-xl bg-[#111827] px-4 text-sm font-bold text-white hover:bg-black">More actions</button>
                        </div>
                    @endcan
                </div>
            </div>

            <div class="grid gap-0 border-b border-slate-100 md:grid-cols-4">
                <div class="border-b border-slate-100 p-5 md:border-b-0 md:border-r">
                    <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">Portal</p>
                    <p class="mt-2 text-lg font-black text-[#071a3b]">{{ $owner->user ? 'Connected' : 'Not linked' }}</p>
                    <p class="mt-1 text-xs text-slate-500">{{ $owner->portal_invitation_sent_at?->format('M d, Y H:i') ?? 'Invite not sent' }}</p>
                </div>
                <div class="border-b border-slate-100 p-5 md:border-b-0 md:border-r">
                    <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">Units</p>
                    <p class="mt-2 text-lg font-black text-[#071a3b]">{{ $owner->units->count() }}</p>
                    <p class="mt-1 text-xs text-slate-500">Attached apartments</p>
                </div>
                <div class="border-b border-slate-100 p-5 md:border-b-0 md:border-r">
                    <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">Contracts</p>
                    <p class="mt-2 text-lg font-black text-[#071a3b]">{{ $owner->unitContracts->count() }}</p>
                    <p class="mt-1 text-xs text-slate-500">Management agreements</p>
                </div>
                <div class="p-5">
                    <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">Notes</p>
                    <p class="mt-2 text-lg font-black text-[#071a3b]">{{ $owner->notes->count() }}</p>
                    <p class="mt-1 text-xs text-slate-500">Internal updates</p>
                </div>
            </div>

            @if ($owner->is_blacklisted)
                <div class="border-b border-rose-100 bg-rose-50 px-5 py-4 text-sm text-rose-700">
                    <span class="font-bold">Blacklist reason:</span> {{ $owner->blacklist_reason }}
                </div>
            @endif
        </section>

        <div class="grid gap-6 xl:grid-cols-[1.25fr_0.75fr]">
            <div class="space-y-6">
                <section class="erp-card p-5">
                    <div class="flex items-center justify-between gap-4">
                        <h2 class="text-lg font-bold text-[#071a3b]">Profile details</h2>
                        @if ($owner->document_path)
                            <a href="{{ route('owners.document', $owner) }}" target="_blank" class="text-sm font-bold text-blue-700 hover:text-blue-900">Open document</a>
                        @endif
                    </div>
                    <dl class="mt-5 grid gap-4 md:grid-cols-2">
                        <div class="rounded-2xl bg-slate-50 p-4"><dt class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">Mobile</dt><dd class="mt-1 font-bold text-[#071a3b]">{{ $owner->mobile_no }}</dd></div>
                        <div class="rounded-2xl bg-slate-50 p-4"><dt class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">Email</dt><dd class="mt-1 font-bold text-[#071a3b]">{{ $owner->email ?: 'Not added' }}</dd></div>
                        <div class="rounded-2xl bg-slate-50 p-4"><dt class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">Identity</dt><dd class="mt-1 font-bold text-[#071a3b]">{{ str($owner->identity_type)->replace('_', ' ')->headline() }} {{ $owner->identity_no ?: '' }}</dd><dd class="mt-1 text-xs text-slate-500">Issued {{ $owner->identity_issue_date?->format('M d, Y') ?? 'Not set' }} / Expires {{ $owner->identity_expiry_date?->format('M d, Y') ?? 'Not set' }}</dd></div>
                        <div class="rounded-2xl bg-slate-50 p-4"><dt class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">DOB / nationality</dt><dd class="mt-1 font-bold text-[#071a3b]">{{ $owner->date_of_birth?->format('M d, Y') ?? 'Not added' }} / {{ $owner->nationality ?: 'Not added' }}</dd></div>
                    </dl>
                </section>

                <section class="erp-card p-5">
                    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                        <div>
                            <h2 class="text-lg font-bold text-[#071a3b]">Owner units</h2>
                            <p class="mt-1 text-sm text-slate-500">Apartments attached to this owner and share allocation.</p>
                        </div>
                        <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-bold text-blue-700">{{ $owner->units->count() }} units</span>
                    </div>

                    <div class="mt-5 overflow-hidden rounded-2xl border border-slate-200">
                        @forelse ($owner->units as $unit)
                            <a href="{{ route('units.show', $unit) }}" class="grid gap-3 border-b border-slate-100 p-4 hover:bg-slate-50 md:grid-cols-[1fr_120px_120px] md:items-center last:border-b-0">
                                <span>
                                    <span class="block font-bold text-[#071a3b]">{{ $unit->building?->name ?? 'No building' }} / Unit {{ $unit->unit_no }}</span>
                                    <span class="mt-1 block text-xs text-slate-500">{{ $unit->unit_type }} / {{ $unit->bedrooms ?? '-' }} bed / {{ $unit->size_sqft ? number_format((float) $unit->size_sqft).' sqft' : 'size not set' }}</span>
                                </span>
                                <span class="text-sm font-bold text-[#071a3b]">{{ $unit->rent_amount ? 'AED '.number_format((float) $unit->rent_amount, 0) : 'Rent not set' }}</span>
                                <span class="justify-self-start rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700 md:justify-self-end">{{ $unit->pivot->share_percent }}%</span>
                            </a>
                        @empty
                            <p class="px-4 py-8 text-center text-sm text-slate-500">No units attached to this owner yet.</p>
                        @endforelse
                    </div>
                </section>

                <section class="erp-card p-5">
                    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                        <div>
                            <h2 class="text-lg font-bold text-[#071a3b]">Unit contracts</h2>
                            <p class="mt-1 text-sm text-slate-500">Management agreements and signature status.</p>
                        </div>
                        @can('owner-contracts.manage')
                            <a href="{{ route('owner-contracts.create', ['owner_id' => $owner->id]) }}" class="inline-flex h-10 items-center rounded-xl bg-blue-600 px-4 text-sm font-bold text-white hover:bg-blue-700">Add contract</a>
                        @endcan
                    </div>

                    <div class="mt-5 overflow-hidden rounded-2xl border border-slate-200">
                        @forelse($owner->unitContracts as $contract)
                            <a href="{{ route('owner-contracts.show', $contract) }}" class="grid gap-3 border-b border-slate-100 p-4 hover:bg-slate-50 md:grid-cols-[1fr_120px_120px] md:items-center last:border-b-0">
                                <span>
                                    <span class="block font-bold text-[#071a3b]">{{ $contract->contract_no }} / {{ $contract->unit->building?->name }} Unit {{ $contract->unit->unit_no }}</span>
                                    <span class="mt-1 block text-xs text-slate-500">{{ $contract->contract_start_date?->format('M d, Y') ?? 'No start' }} - {{ $contract->contract_end_date?->format('M d, Y') ?? 'No end' }}</span>
                                </span>
                                <span class="text-sm font-bold text-[#071a3b]">{{ $contract->management_fee_percent }}%</span>
                                <span class="justify-self-start rounded-full bg-blue-50 px-3 py-1 text-xs font-bold text-blue-700 md:justify-self-end">{{ str($contract->status)->headline() }}</span>
                            </a>
                        @empty
                            <p class="px-4 py-8 text-center text-sm text-slate-500">No owner contracts yet.</p>
                        @endforelse
                    </div>
                </section>
            </div>

            <aside class="space-y-6">
                <section class="erp-card p-5">
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="text-lg font-bold text-[#071a3b]">Owner statement</h2>
                        <a href="{{ route('owner-statements.index', ['owner_id' => $owner->id]) }}" class="text-sm font-bold text-blue-700 hover:text-blue-900">Open</a>
                    </div>
                    <p class="mt-3 text-sm text-slate-500">View collected rent, owner expenses, and statement lines filtered to this owner.</p>
                </section>

                <section class="erp-card p-5">
                    <h2 class="text-lg font-bold text-[#071a3b]">Bank details</h2>
                    <dl class="mt-5 space-y-4">
                        @foreach (['bank_name' => 'Bank', 'bank_account_name' => 'Account name', 'bank_account_no' => 'Account no', 'iban' => 'IBAN', 'swift_code' => 'SWIFT'] as $field => $label)
                            <div>
                                <dt class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">{{ $label }}</dt>
                                <dd class="mt-1 break-words text-sm font-bold text-[#071a3b]">{{ $owner->{$field} ?: 'Not added' }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </section>

                <section class="erp-card p-5">
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="text-lg font-bold text-[#071a3b]">Notes</h2>
                        @can('owners.manage')
                            <button type="button" data-modal-open="owner-note-modal" class="text-sm font-bold text-blue-700">Add</button>
                        @endcan
                    </div>
                    <div class="mt-5 space-y-3">
                        @forelse ($owner->notes->take(5) as $note)
                            <div class="rounded-2xl border border-slate-200 p-4">
                                <p class="text-sm text-slate-700">{{ $note->note }}</p>
                                <p class="mt-3 text-xs text-slate-400">{{ $note->user?->name ?? 'System' }} - {{ $note->created_at->format('M d, Y H:i') }}</p>
                            </div>
                        @empty
                            <p class="rounded-2xl border border-dashed border-slate-200 px-4 py-6 text-center text-sm text-slate-500">No notes yet.</p>
                        @endforelse
                    </div>
                </section>
            </aside>
        </div>
    </div>

    @can('owners.manage')
        <div id="owner-note-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/40 p-4" data-modal>
            <div class="w-full max-w-lg rounded-2xl bg-white p-5 shadow-2xl">
                <div class="flex items-center justify-between gap-4">
                    <h2 class="text-lg font-bold text-[#071a3b]">Add owner note</h2>
                    <button type="button" data-modal-close class="rounded-lg px-3 py-2 text-sm font-bold text-slate-500 hover:bg-slate-100">Close</button>
                </div>
                <form method="POST" action="{{ route('owners.notes.store', $owner) }}" class="mt-4">
                    @csrf
                    <textarea name="note" rows="5" class="erp-focus block w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" placeholder="Write a clear internal note..." required></textarea>
                    <div class="mt-4 flex justify-end"><x-primary-button>Add note</x-primary-button></div>
                </form>
            </div>
        </div>

        <div id="owner-actions-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/40 p-4" data-modal>
            <div class="w-full max-w-lg rounded-2xl bg-white p-5 shadow-2xl">
                <div class="flex items-center justify-between gap-4">
                    <h2 class="text-lg font-bold text-[#071a3b]">Owner actions</h2>
                    <button type="button" data-modal-close class="rounded-lg px-3 py-2 text-sm font-bold text-slate-500 hover:bg-slate-100">Close</button>
                </div>
                <div class="mt-5 space-y-3">
                    @if ($owner->email)
                        <form method="POST" action="{{ route('owners.send-invite', $owner) }}">
                            @csrf
                            <button class="w-full rounded-xl border border-blue-200 px-4 py-3 text-left text-sm font-bold text-blue-700 hover:bg-blue-50">{{ $owner->portal_invitation_sent_at ? 'Resend welcome email' : 'Send welcome email' }}</button>
                        </form>
                    @endif
                    <form method="POST" action="{{ route('owners.destroy', $owner) }}" onsubmit="return confirm('Delete this owner?')">
                        @csrf
                        @method('DELETE')
                        <button class="w-full rounded-xl border border-rose-200 px-4 py-3 text-left text-sm font-bold text-rose-600 hover:bg-rose-50">Delete owner</button>
                    </form>
                </div>
            </div>
        </div>
    @endcan

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('[data-modal-open]').forEach((button) => {
                button.addEventListener('click', () => {
                    const modal = document.getElementById(button.dataset.modalOpen);
                    modal?.classList.remove('hidden');
                    modal?.classList.add('flex');
                });
            });

            document.querySelectorAll('[data-modal]').forEach((modal) => {
                modal.addEventListener('click', (event) => {
                    if (event.target === modal || event.target.closest('[data-modal-close]')) {
                        modal.classList.add('hidden');
                        modal.classList.remove('flex');
                    }
                });
            });
        });
    </script>
</x-app-layout>
