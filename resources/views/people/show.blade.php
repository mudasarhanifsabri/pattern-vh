<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <p class="text-[11px] font-bold uppercase tracking-[0.22em] text-blue-600">{{ $config['pluralTitle'] }}</p>
            <h1 class="text-2xl font-bold text-[#071a3b]">{{ $record->full_name }}</h1>
        </div>
    </x-slot>

    @php
        $isTenant = $config['extra'] === 'tenant';
        $isAgent = $config['extra'] === 'agent';
        $isOperations = $config['extra'] === 'operations';
        $activeBookings = $isTenant ? $record->bookings->whereIn('booking_status', ['confirmed', 'checked_in', 'checkout_requested']) : collect();
        $pastBookings = $isTenant ? $record->bookings->whereNotIn('booking_status', ['confirmed', 'checked_in', 'checkout_requested']) : collect();
        $statusText = $record->is_blacklisted ? 'Blacklisted' : 'Active';
        $autoAssignments = $isOperations
            ? collect([
                $record->auto_assign_checkout_cleaning ? 'Checkout cleaning' : null,
                $record->auto_assign_checkout_inspection ? 'Checkout inspection' : null,
                $record->auto_assign_stay_tasks ? 'Stay tasks' : null,
            ])->filter()->implode(', ')
            : null;
    @endphp

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
                            {{ str($record->full_name)->substr(0, 2)->upper() }}
                        </div>
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="text-2xl font-black text-[#071a3b]">{{ $record->full_name }}</h2>
                                <span class="rounded-full px-3 py-1 text-xs font-bold {{ $record->is_blacklisted ? 'bg-rose-50 text-rose-700' : 'bg-emerald-50 text-emerald-700' }}">{{ $statusText }}</span>
                            </div>
                            <p class="mt-1 max-w-3xl text-sm text-slate-500">{{ $config['description'] }}</p>
                            <div class="mt-3 flex flex-wrap gap-2 text-xs font-bold text-slate-500">
                                <span class="rounded-full bg-slate-100 px-3 py-1">{{ $record->mobile_no }}</span>
                                <span class="rounded-full bg-slate-100 px-3 py-1">{{ $record->email ?: 'No email' }}</span>
                                @if ($record->mobile_has_whatsapp)<span class="rounded-full bg-emerald-50 px-3 py-1 text-emerald-700">WhatsApp</span>@endif
                            </div>
                        </div>
                    </div>

                    @can($config['permission'].'.manage')
                        <div class="flex flex-wrap gap-2">
                            @if(auth()->user()?->hasRole('Super Admin'))
                                <a href="{{ route('admin.portal-preview.start', [$config['route'], $record]) }}" target="_blank" rel="noopener" class="inline-flex h-11 items-center rounded-xl bg-blue-600 px-4 text-sm font-bold text-white shadow-lg shadow-blue-600/20 hover:bg-blue-700">Open portal</a>
                            @endif
                            <a href="{{ route($config['route'].'.edit', $record) }}" class="inline-flex h-11 items-center rounded-xl border border-slate-200 px-4 text-sm font-bold text-slate-700 hover:bg-slate-50">Edit</a>
                            <button type="button" data-modal-open="note-modal" class="inline-flex h-11 items-center rounded-xl border border-blue-200 px-4 text-sm font-bold text-blue-700 hover:bg-blue-50">Add note</button>
                            <button type="button" data-modal-open="actions-modal" class="inline-flex h-11 items-center rounded-xl bg-[#111827] px-4 text-sm font-bold text-white hover:bg-black">More actions</button>
                        </div>
                    @endcan
                </div>
            </div>

            <div class="grid gap-0 border-b border-slate-100 md:grid-cols-4">
                <div class="border-b border-slate-100 p-5 md:border-b-0 md:border-r">
                    <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">Portal</p>
                    <p class="mt-2 text-lg font-black text-[#071a3b]">{{ $record->user ? 'Connected' : 'Not linked' }}</p>
                    <p class="mt-1 text-xs text-slate-500">{{ $record->portal_invitation_sent_at?->format('M d, Y H:i') ?? 'Invite not sent' }}</p>
                </div>
                <div class="border-b border-slate-100 p-5 md:border-b-0 md:border-r">
                    <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">Document</p>
                    <p class="mt-2 text-lg font-black text-[#071a3b]">{{ $record->document_path ? 'Uploaded' : 'Missing' }}</p>
                    <p class="mt-1 truncate text-xs text-slate-500">{{ $record->document_original_name ?: 'No identity file' }}</p>
                </div>
                <div class="border-b border-slate-100 p-5 md:border-b-0 md:border-r">
                    <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">Notes</p>
                    <p class="mt-2 text-lg font-black text-[#071a3b]">{{ $record->notes->count() }}</p>
                    <p class="mt-1 text-xs text-slate-500">Latest updates</p>
                </div>
                <div class="p-5">
                    <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">{{ $isTenant ? 'Bookings' : ($isAgent ? 'Commission' : 'Availability') }}</p>
                    <p class="mt-2 text-lg font-black text-[#071a3b]">
                        @if ($isTenant)
                            {{ $record->bookings->count() }}
                        @elseif ($isAgent)
                            {{ $record->commission_percent ?? 0 }}%
                        @else
                            {{ str($record->availability_status ?: 'not set')->headline() }}
                        @endif
                    </p>
                    <p class="mt-1 text-xs text-slate-500">{{ $config['singularTitle'] }} record</p>
                </div>
            </div>

            @if ($record->is_blacklisted)
                <div class="border-b border-rose-100 bg-rose-50 px-5 py-4 text-sm text-rose-700">
                    <span class="font-bold">Blacklist reason:</span> {{ $record->blacklist_reason }}
                </div>
            @endif
        </section>

        <div class="grid gap-6 xl:grid-cols-[1.25fr_0.75fr]">
            <div class="space-y-6">
                <section class="erp-card p-5">
                    <div class="flex items-center justify-between gap-4">
                        <h2 class="text-lg font-bold text-[#071a3b]">Profile details</h2>
                        @if ($record->document_path)
                            <a href="{{ route($config['route'].'.document', $record) }}" target="_blank" class="text-sm font-bold text-blue-700 hover:text-blue-900">Open document</a>
                        @endif
                    </div>
                    <dl class="mt-5 grid gap-4 md:grid-cols-2">
                        <div class="rounded-2xl bg-slate-50 p-4"><dt class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">Mobile</dt><dd class="mt-1 font-bold text-[#071a3b]">{{ $record->mobile_no }}</dd></div>
                        <div class="rounded-2xl bg-slate-50 p-4"><dt class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">Email</dt><dd class="mt-1 font-bold text-[#071a3b]">{{ $record->email ?: 'Not added' }}</dd></div>
                        <div class="rounded-2xl bg-slate-50 p-4"><dt class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">Identity</dt><dd class="mt-1 font-bold text-[#071a3b]">{{ str($record->identity_type)->replace('_', ' ')->headline() }} {{ $record->identity_no ?: '' }}</dd><dd class="mt-1 text-xs text-slate-500">Issued {{ $record->identity_issue_date?->format('M d, Y') ?? 'Not set' }} / Expires {{ $record->identity_expiry_date?->format('M d, Y') ?? 'Not set' }}</dd></div>
                        <div class="rounded-2xl bg-slate-50 p-4"><dt class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">DOB / nationality</dt><dd class="mt-1 font-bold text-[#071a3b]">{{ $record->date_of_birth?->format('M d, Y') ?? 'Not added' }} / {{ $record->nationality ?: 'Not added' }}</dd></div>
                    </dl>
                </section>

                <section class="erp-card p-5">
                    <h2 class="text-lg font-bold text-[#071a3b]">{{ $isTenant ? 'Tenant details' : ($isAgent ? 'Agent details' : 'Maintainer details') }}</h2>
                    <dl class="mt-5 grid gap-4 md:grid-cols-2">
                        @if ($isAgent)
                            <div class="rounded-2xl bg-slate-50 p-4"><dt class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">Agency</dt><dd class="mt-1 font-bold text-[#071a3b]">{{ $record->agency_name ?: 'Not added' }}</dd></div>
                            <div class="rounded-2xl bg-slate-50 p-4"><dt class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">RERA</dt><dd class="mt-1 font-bold text-[#071a3b]">{{ $record->rera_no ?: 'Not added' }}</dd></div>
                            <div class="rounded-2xl bg-slate-50 p-4"><dt class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">Commission</dt><dd class="mt-1 font-bold text-[#071a3b]">{{ $record->commission_percent ?? 0 }}%</dd></div>
                        @elseif ($isOperations)
                            <div class="rounded-2xl bg-slate-50 p-4"><dt class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">Role</dt><dd class="mt-1 font-bold text-[#071a3b]">{{ str($record->team_role)->headline() }}</dd></div>
                            <div class="rounded-2xl bg-slate-50 p-4"><dt class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">Specialty</dt><dd class="mt-1 font-bold text-[#071a3b]">{{ $record->specialty ?: 'Not added' }}</dd></div>
                            <div class="rounded-2xl bg-slate-50 p-4"><dt class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">Service area</dt><dd class="mt-1 font-bold text-[#071a3b]">{{ $record->service_area ?: 'Not added' }}</dd></div>
                            <div class="rounded-2xl bg-slate-50 p-4"><dt class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">Auto assignment</dt><dd class="mt-1 font-bold text-[#071a3b]">{{ $autoAssignments ?: 'Not enabled' }}</dd></div>
                        @else
                            <div class="rounded-2xl bg-slate-50 p-4"><dt class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">Emergency contact</dt><dd class="mt-1 font-bold text-[#071a3b]">{{ $record->emergency_contact_name ?: 'Not added' }}</dd><dd class="mt-1 text-xs text-slate-500">{{ $record->emergency_contact_mobile ?: 'No mobile' }}</dd></div>
                        @endif
                    </dl>
                </section>

                @if ($isTenant)
                    <section class="erp-card p-5">
                        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                            <div>
                                <h2 class="text-lg font-bold text-[#071a3b]">Bookings</h2>
                                <p class="mt-1 text-sm text-slate-500">Current and past stays for this tenant.</p>
                            </div>
                            <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-bold text-blue-700">{{ $record->bookings->count() }} total</span>
                        </div>
                        <div class="mt-5 overflow-hidden rounded-2xl border border-slate-200">
                            @forelse ($activeBookings->merge($pastBookings) as $booking)
                                <a href="{{ route('bookings.show', $booking) }}" class="grid gap-3 border-b border-slate-100 p-4 hover:bg-slate-50 md:grid-cols-[1fr_170px_130px] md:items-center last:border-b-0">
                                    <span>
                                        <span class="block font-bold text-[#071a3b]">{{ $booking->unit?->building?->name }} / Unit {{ $booking->unit?->unit_no }}</span>
                                        <span class="mt-1 block text-xs text-slate-500">{{ $booking->booking_no }} / {{ str($booking->booking_type)->headline() }}</span>
                                    </span>
                                    <span class="text-sm font-bold text-[#071a3b]">{{ $booking->check_in_date?->format('M d, Y') }} - {{ $booking->check_out_date?->format('M d, Y') }}</span>
                                    <span class="justify-self-start rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600 md:justify-self-end">{{ str($booking->booking_status)->headline() }}</span>
                                </a>
                            @empty
                                <p class="px-4 py-8 text-center text-sm text-slate-500">No bookings yet.</p>
                            @endforelse
                        </div>
                    </section>
                @endif
            </div>

            <aside class="space-y-6">
                <section class="erp-card p-5">
                    <h2 class="text-lg font-bold text-[#071a3b]">Bank details</h2>
                    <dl class="mt-5 space-y-4">
                        @foreach (['bank_name' => 'Bank', 'bank_account_name' => 'Account name', 'bank_account_no' => 'Account no', 'iban' => 'IBAN', 'swift_code' => 'SWIFT'] as $field => $label)
                            <div>
                                <dt class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">{{ $label }}</dt>
                                <dd class="mt-1 break-words text-sm font-bold text-[#071a3b]">{{ $record->{$field} ?: 'Not added' }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </section>

                <section class="erp-card p-5">
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="text-lg font-bold text-[#071a3b]">Notes</h2>
                        @can($config['permission'].'.manage')
                            <button type="button" data-modal-open="note-modal" class="text-sm font-bold text-blue-700">Add</button>
                        @endcan
                    </div>
                    <div class="mt-5 space-y-3">
                        @forelse ($record->notes->take(5) as $note)
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

    @can($config['permission'].'.manage')
        <div id="note-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/40 p-4" data-modal>
            <div class="w-full max-w-lg rounded-2xl bg-white p-5 shadow-2xl">
                <div class="flex items-center justify-between gap-4">
                    <h2 class="text-lg font-bold text-[#071a3b]">Add note</h2>
                    <button type="button" data-modal-close class="rounded-lg px-3 py-2 text-sm font-bold text-slate-500 hover:bg-slate-100">Close</button>
                </div>
                <form method="POST" action="{{ route($config['route'].'.notes.store', $record) }}" class="mt-4">
                    @csrf
                    <textarea name="note" rows="5" class="erp-focus block w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" placeholder="Write a clear internal note..." required></textarea>
                    <div class="mt-4 flex justify-end"><x-primary-button>Add note</x-primary-button></div>
                </form>
            </div>
        </div>

        <div id="actions-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/40 p-4" data-modal>
            <div class="w-full max-w-lg rounded-2xl bg-white p-5 shadow-2xl">
                <div class="flex items-center justify-between gap-4">
                    <h2 class="text-lg font-bold text-[#071a3b]">Record actions</h2>
                    <button type="button" data-modal-close class="rounded-lg px-3 py-2 text-sm font-bold text-slate-500 hover:bg-slate-100">Close</button>
                </div>
                <div class="mt-5 space-y-3">
                    @if(auth()->user()?->hasRole('Super Admin'))
                        <a href="{{ route('admin.portal-preview.start', [$config['route'], $record]) }}" target="_blank" rel="noopener" class="block w-full rounded-xl bg-blue-600 px-4 py-3 text-left text-sm font-bold text-white hover:bg-blue-700">Open {{ $config['singularTitle'] }} portal</a>
                    @endif
                    @if ($record->email)
                        <form method="POST" action="{{ route($config['route'].'.send-invite', $record) }}">
                            @csrf
                            <button class="w-full rounded-xl border border-blue-200 px-4 py-3 text-left text-sm font-bold text-blue-700 hover:bg-blue-50">{{ $record->portal_invitation_sent_at ? 'Resend welcome email' : 'Send welcome email' }}</button>
                        </form>
                    @endif
                    <form method="POST" action="{{ route($config['route'].'.destroy', $record) }}" onsubmit="return confirm('Delete this record?')">
                        @csrf
                        @method('DELETE')
                        <button class="w-full rounded-xl border border-rose-200 px-4 py-3 text-left text-sm font-bold text-rose-600 hover:bg-rose-50">Delete record</button>
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
