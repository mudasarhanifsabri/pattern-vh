<x-app-layout>
    <x-slot name="header"><div><p class="text-[11px] font-bold uppercase tracking-[0.22em] text-blue-600">{{ $config['pluralTitle'] }}</p><h1 class="text-2xl font-bold text-[#071a3b]">{{ $record->full_name }}</h1></div></x-slot>

    @php
        $tenantActiveBookings = $config['extra'] === 'tenant'
            ? $record->bookings->whereIn('booking_status', ['confirmed', 'checked_in', 'checkout_requested'])
            : collect();
        $tenantPastBookings = $config['extra'] === 'tenant'
            ? $record->bookings->whereNotIn('booking_status', ['confirmed', 'checked_in', 'checkout_requested'])
            : collect();
    @endphp

    <div class="space-y-6">
        @if (session('status'))<div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">{{ session('status') }}</div>@endif
        @if ($errors->any())<div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ $errors->first() }}</div>@endif

        @php
            $peopleTabs = [
                'profile' => 'Profile',
                'module' => $config['extra'] === 'tenant' ? 'Tenant details' : ($config['extra'] === 'agent' ? 'Agent details' : 'Team details'),
                'bookings' => 'Booking history',
                'bank' => 'Bank details',
                'document' => 'Document',
                'notes' => 'Notes',
            ];

            if ($config['extra'] !== 'tenant') {
                unset($peopleTabs['bookings']);
            }
        @endphp

        <div class="sticky top-20 z-10 overflow-x-auto rounded-[1.35rem] border border-slate-200 bg-white/95 p-2 shadow-xl shadow-slate-950/5 backdrop-blur" data-record-tabs>
            <div class="flex min-w-max gap-1">
                @foreach ($peopleTabs as $key => $label)
                    <button type="button" data-record-tab="{{ $key }}" class="rounded-2xl px-4 py-2.5 text-xs font-black text-slate-500 transition hover:bg-blue-50 hover:text-blue-700 aria-selected:bg-blue-100 aria-selected:text-blue-700" aria-selected="{{ $key === 'profile' ? 'true' : 'false' }}">{{ $label }}</button>
                @endforeach
            </div>
        </div>

        <div class="space-y-5" data-record-tab-panels>
            <div class="space-y-5">
                <div class="erp-card p-5" data-record-panel="profile">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div><div class="flex flex-wrap items-center gap-3"><h2 class="text-xl font-bold text-[#071a3b]">{{ $record->full_name }}</h2>@if ($record->is_blacklisted)<span class="rounded-full bg-rose-50 px-2.5 py-1 text-xs font-bold text-rose-700">Blacklisted</span>@else<span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-700">Active</span>@endif</div><p class="mt-2 text-sm text-slate-500">{{ $config['description'] }}</p></div>
                        @can($config['permission'].'.manage')
                            <div class="flex flex-wrap gap-2"><a href="{{ route($config['route'].'.edit', $record) }}" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-bold text-slate-600 hover:bg-slate-50">Edit</a>@if ($record->email)<form method="POST" action="{{ route($config['route'].'.send-invite', $record) }}">@csrf<button class="rounded-xl border border-blue-200 px-4 py-2.5 text-sm font-bold text-blue-700 hover:bg-blue-50">{{ $record->portal_invitation_sent_at ? 'Resend welcome email' : 'Send welcome email' }}</button></form>@endif<form method="POST" action="{{ route($config['route'].'.destroy', $record) }}" onsubmit="return confirm('Delete this record?')">@csrf @method('DELETE')<button class="rounded-xl border border-rose-200 px-4 py-2.5 text-sm font-bold text-rose-600 hover:bg-rose-50">Delete</button></form></div>
                        @endcan
                    </div>
                    <dl class="mt-6 grid gap-4 md:grid-cols-2">
                        <div class="rounded-2xl bg-slate-50 p-4"><dt class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">Mobile</dt><dd class="mt-1 font-bold text-[#071a3b]">{{ $record->mobile_no }} @if ($record->mobile_has_whatsapp)<span class="text-sm text-emerald-600">WhatsApp</span>@endif</dd></div>
                        <div class="rounded-2xl bg-slate-50 p-4"><dt class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">Email</dt><dd class="mt-1 font-bold text-[#071a3b]">{{ $record->email ?: 'Not added' }}</dd><dd class="mt-1 text-xs text-slate-500">Portal invite: {{ $record->portal_invitation_sent_at?->format('M d, Y H:i') ?? 'Not sent' }}</dd></div>
                        <div class="rounded-2xl bg-slate-50 p-4"><dt class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">Identity</dt><dd class="mt-1 font-bold text-[#071a3b]">{{ str($record->identity_type)->replace('_', ' ')->headline() }} {{ $record->identity_no }}</dd><dd class="mt-1 text-xs text-slate-500">Issued {{ $record->identity_issue_date?->format('M d, Y') ?? 'Not set' }} / Expires {{ $record->identity_expiry_date?->format('M d, Y') ?? 'Not set' }}</dd></div>
                        <div class="rounded-2xl bg-slate-50 p-4"><dt class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">DOB / nationality</dt><dd class="mt-1 font-bold text-[#071a3b]">{{ $record->date_of_birth?->format('M d, Y') ?? 'Not added' }} / {{ $record->nationality ?: 'Nationality not added' }}</dd></div>
                    </dl>
                    @if ($record->is_blacklisted)<div class="mt-5 rounded-2xl border border-rose-200 bg-rose-50 p-4"><p class="text-xs font-bold uppercase tracking-[0.16em] text-rose-500">Blacklist reason</p><p class="mt-2 text-sm text-rose-700">{{ $record->blacklist_reason }}</p></div>@endif
                </div>

                <div class="erp-card p-5" data-record-panel="module"><h2 class="text-lg font-bold text-[#071a3b]">Module details</h2><dl class="mt-5 grid gap-4 md:grid-cols-2">@if ($config['extra'] === 'agent')<div><dt class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">Agency</dt><dd class="mt-1 text-sm font-bold text-[#071a3b]">{{ $record->agency_name ?: 'Not added' }}</dd></div><div><dt class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">Commission</dt><dd class="mt-1 text-sm font-bold text-[#071a3b]">{{ $record->commission_percent ?? 0 }}%</dd></div><div><dt class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">RERA</dt><dd class="mt-1 text-sm font-bold text-[#071a3b]">{{ $record->rera_no ?: 'Not added' }}</dd></div>@elseif ($config['extra'] === 'operations')<div><dt class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">Role</dt><dd class="mt-1 text-sm font-bold text-[#071a3b]">{{ str($record->team_role)->headline() }}</dd></div><div><dt class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">Specialty</dt><dd class="mt-1 text-sm font-bold text-[#071a3b]">{{ $record->specialty ?: 'Not added' }}</dd></div><div><dt class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">Service area</dt><dd class="mt-1 text-sm font-bold text-[#071a3b]">{{ $record->service_area ?: 'Not added' }}</dd></div><div><dt class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">Auto assignment</dt><dd class="mt-1 text-sm font-bold text-[#071a3b]">{{ $record->auto_assign_checkout_cleaning ? 'Cleaning ' : '' }}{{ $record->auto_assign_checkout_inspection ? 'Inspection ' : '' }}{{ $record->auto_assign_stay_tasks ? 'Stay tasks' : '' }}</dd></div>@else<div><dt class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">Emergency contact</dt><dd class="mt-1 text-sm font-bold text-[#071a3b]">{{ $record->emergency_contact_name ?: 'Not added' }} {{ $record->emergency_contact_mobile }}</dd></div>@endif</dl></div>

                @if ($config['extra'] === 'tenant')
                    <div class="erp-card p-5" data-record-panel="bookings">
                        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                            <div>
                                <h2 class="text-lg font-bold text-[#071a3b]">Booking history</h2>
                                <p class="mt-1 text-sm text-slate-500">One active booking at a time, with previous stays kept below.</p>
                            </div>
                            <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-bold text-blue-700">{{ $record->bookings->count() }} bookings</span>
                        </div>

                        <div class="mt-5 grid gap-4 lg:grid-cols-[0.9fr_1.1fr]">
                            <div class="rounded-3xl border border-blue-100 bg-blue-50/50 p-4">
                                <p class="text-xs font-bold uppercase tracking-[0.16em] text-blue-500">Current booking</p>
                                <div class="mt-4 space-y-3">
                                    @forelse ($tenantActiveBookings as $booking)
                                        <a href="{{ route('bookings.show', $booking) }}" class="block rounded-2xl bg-white p-4 shadow-sm hover:bg-slate-50">
                                            <div class="flex items-start justify-between gap-3">
                                                <div>
                                                    <p class="font-bold text-[#071a3b]">{{ $booking->unit?->building?->name }} / Unit {{ $booking->unit?->unit_no }}</p>
                                                    <p class="mt-1 text-xs text-slate-500">{{ $booking->booking_no }} / {{ str($booking->booking_type)->headline() }}</p>
                                                </div>
                                                <span class="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-bold text-blue-700">{{ str($booking->booking_status)->headline() }}</span>
                                            </div>
                                            <p class="mt-3 text-sm font-bold text-[#071a3b]">{{ $booking->check_in_date?->format('M d, Y') }} - {{ $booking->check_out_date?->format('M d, Y') }}</p>
                                            <p class="mt-1 text-xs text-slate-500">Total AED {{ number_format((float) $booking->total_amount, 2) }}</p>
                                        </a>
                                    @empty
                                        <p class="rounded-2xl border border-dashed border-blue-200 bg-white/70 px-4 py-8 text-center text-sm text-slate-500">No active booking right now.</p>
                                    @endforelse
                                </div>
                            </div>

                            <div class="rounded-3xl border border-slate-200 bg-white p-4">
                                <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">Previous bookings</p>
                                <div class="mt-4 overflow-hidden rounded-2xl border border-slate-200">
                                    @forelse ($tenantPastBookings as $booking)
                                        <a href="{{ route('bookings.show', $booking) }}" class="grid gap-3 border-b border-slate-100 p-4 hover:bg-slate-50 md:grid-cols-[1fr_140px] md:items-center last:border-b-0">
                                            <span>
                                                <span class="block font-bold text-[#071a3b]">{{ $booking->unit?->building?->name }} / Unit {{ $booking->unit?->unit_no }}</span>
                                                <span class="mt-1 block text-xs text-slate-500">{{ $booking->booking_no }} / {{ $booking->check_in_date?->format('M d') }} - {{ $booking->check_out_date?->format('M d, Y') }}</span>
                                            </span>
                                            <span class="justify-self-start rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600 md:justify-self-end">{{ str($booking->booking_status)->headline() }}</span>
                                        </a>
                                    @empty
                                        <p class="px-4 py-8 text-center text-sm text-slate-500">No previous bookings yet.</p>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                <div class="erp-card p-5" data-record-panel="bank"><h2 class="text-lg font-bold text-[#071a3b]">Bank details</h2><dl class="mt-5 grid gap-4 md:grid-cols-2">@foreach (['bank_name' => 'Bank', 'bank_account_name' => 'Account name', 'bank_account_no' => 'Account no', 'iban' => 'IBAN', 'swift_code' => 'SWIFT'] as $field => $label)<div><dt class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">{{ $label }}</dt><dd class="mt-1 text-sm font-bold text-[#071a3b]">{{ $record->{$field} ?: 'Not added' }}</dd></div>@endforeach</dl></div>
            </div>

            <div class="space-y-5">
                <div class="erp-card p-5" data-record-panel="document"><h2 class="text-lg font-bold text-[#071a3b]">Document</h2>@if ($record->document_path)<p class="mt-1 text-sm text-slate-500">{{ $record->document_original_name }}</p><p class="mt-2 break-all text-xs text-slate-400">{{ $record->document_path }}</p><a href="{{ route($config['route'].'.document', $record) }}" target="_blank" class="mt-4 inline-flex h-11 items-center rounded-xl bg-blue-600 px-4 text-sm font-bold text-white hover:bg-blue-700">Open document</a>@else<p class="mt-3 rounded-2xl border border-dashed border-slate-200 px-4 py-6 text-center text-sm text-slate-500">No passport or Emirates ID file uploaded.</p>@endif</div>
                <div class="erp-card p-5" data-record-panel="notes"><h2 class="text-lg font-bold text-[#071a3b]">Notes history</h2>@can($config['permission'].'.manage')<form method="POST" action="{{ route($config['route'].'.notes.store', $record) }}" class="mt-4">@csrf<textarea name="note" rows="4" class="erp-focus block w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" placeholder="Add a note..." required></textarea><div class="mt-3 flex justify-end"><x-primary-button>Add note</x-primary-button></div></form>@endcan<div class="mt-5 space-y-3">@forelse ($record->notes as $note)<div class="rounded-2xl border border-slate-200 p-4"><p class="text-sm text-slate-700">{{ $note->note }}</p><p class="mt-3 text-xs text-slate-400">{{ $note->user?->name ?? 'System' }} - {{ $note->created_at->format('M d, Y H:i') }}</p></div>@empty<p class="rounded-2xl border border-dashed border-slate-200 px-4 py-6 text-center text-sm text-slate-500">No notes yet.</p>@endforelse</div></div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const tabs = Array.from(document.querySelectorAll('[data-record-tab]'));
            const panels = Array.from(document.querySelectorAll('[data-record-panel]'));

            if (! tabs.length || ! panels.length) {
                return;
            }

            const showTab = (key) => {
                tabs.forEach((tab) => tab.setAttribute('aria-selected', tab.dataset.recordTab === key ? 'true' : 'false'));
                panels.forEach((panel) => panel.toggleAttribute('hidden', panel.dataset.recordPanel !== key));
            };

            const requested = window.location.hash ? window.location.hash.substring(1) : 'profile';
            const initial = tabs.some((tab) => tab.dataset.recordTab === requested) ? requested : 'profile';
            showTab(initial);

            tabs.forEach((tab) => tab.addEventListener('click', () => {
                const key = tab.dataset.recordTab;
                showTab(key);
                history.replaceState(null, '', `#${key}`);
            }));
        });
    </script>
</x-app-layout>
