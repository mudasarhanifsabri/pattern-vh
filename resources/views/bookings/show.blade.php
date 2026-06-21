<x-app-layout>
@php
    $tenantPortal = auth()->user()->can('portal.tenant') && ! auth()->user()->can('bookings.manage');
    $issueCount = $booking->checkInInspectionItems->whereIn('condition_status', ['damaged', 'missing', 'needs_attention'])->count();
    $bookingTabs = ['overview' => 'Overview', 'confirmation' => 'Confirmation', 'inspection' => 'Inspection', 'extensions' => 'Extensions', 'refund' => 'Refund'];

    if (! $tenantPortal) {
        $bookingTabs['tasks'] = 'Tasks';
    }
@endphp

<x-slot name="header">
    <div>
        <p class="text-[11px] font-bold uppercase tracking-[0.22em] text-blue-600">{{ $tenantPortal ? 'My stay' : 'Booking command center' }}</p>
        <h1 class="text-2xl font-bold text-[#071a3b]">{{ $booking->booking_no }}</h1>
    </div>
</x-slot>

<div class="space-y-5">
    @if (session('status'))<div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">{{ session('status') }}</div>@endif
    @if ($errors->any())<div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ $errors->first() }}</div>@endif

    <section class="erp-card p-5">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-600">{{ str($booking->booking_type)->replace('_', ' ')->headline() }}</p>
                <h2 class="mt-1 text-2xl font-black text-[#071a3b]">{{ $booking->unit->building->name }} / Unit {{ $booking->unit->unit_no }}</h2>
                <p class="mt-1 text-sm text-slate-500">{{ $booking->tenant->full_name }} / {{ $booking->check_in_date->format('M d, Y') }} to {{ $booking->check_out_date->format('M d, Y') }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                @can('bookings.manage')<a href="{{ route('bookings.edit', $booking) }}" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-bold text-slate-600">Edit</a>@endcan
                @can('invoices.manage')<a href="{{ route('invoices.create', ['booking_id' => $booking->id]) }}" class="rounded-xl border border-blue-200 px-4 py-2.5 text-sm font-bold text-blue-700">Create invoice</a>@endcan
                <a href="{{ route('bookings.confirmation-pdf', $booking) }}" target="_blank" class="rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-bold text-white">Booking Confirmation PDF</a>
            </div>
        </div>
        <dl class="mt-5 grid gap-3 md:grid-cols-5">
            <div class="rounded-2xl bg-slate-50 p-4"><dt class="text-xs font-bold uppercase text-slate-400">Status</dt><dd class="font-bold text-[#071a3b]">{{ str($booking->booking_status)->replace('_', ' ')->headline() }}</dd></div>
            <div class="rounded-2xl bg-slate-50 p-4"><dt class="text-xs font-bold uppercase text-slate-400">Rent</dt><dd class="font-bold text-[#071a3b]">AED {{ number_format((float) $booking->rent_amount, 2) }}</dd></div>
            <div class="rounded-2xl bg-blue-50 p-4"><dt class="text-xs font-bold uppercase text-blue-400">VAT 5% rent only</dt><dd class="font-bold text-blue-700">AED {{ number_format((float) $booking->vat_amount, 2) }}</dd></div>
            <div class="rounded-2xl bg-slate-50 p-4"><dt class="text-xs font-bold uppercase text-slate-400">Deposit</dt><dd class="font-bold text-[#071a3b]">AED {{ number_format((float) $booking->deposit_amount, 2) }}</dd></div>
            <div class="rounded-2xl bg-slate-50 p-4"><dt class="text-xs font-bold uppercase text-slate-400">Total</dt><dd class="font-bold text-[#071a3b]">AED {{ number_format((float) $booking->total_amount, 2) }}</dd></div>
        </dl>
    </section>

    <div class="sticky top-20 z-10 overflow-x-auto rounded-[1.35rem] border border-slate-200 bg-white/95 p-2 shadow-xl shadow-slate-950/5 backdrop-blur" data-record-tabs>
        <div class="flex min-w-max gap-1">
            @foreach($bookingTabs as $key => $label)
                <button type="button" data-record-tab="{{ $key }}" class="rounded-2xl px-4 py-2.5 text-xs font-black text-slate-500 transition hover:bg-blue-50 hover:text-blue-700 aria-selected:bg-blue-100 aria-selected:text-blue-700" aria-selected="{{ $key === 'overview' ? 'true' : 'false' }}">{{ $label }}</button>
            @endforeach
        </div>
    </div>

    <div class="grid gap-5 xl:grid-cols-[1fr_360px]">
        <div class="space-y-5">
            <section class="erp-card p-5" data-record-panel="overview">
                <h2 class="text-lg font-bold text-[#071a3b]">Booking workflow</h2>
                <div class="mt-5 grid gap-3 md:grid-cols-4">
                    @foreach([
                        ['Paid / confirmed', in_array($booking->booking_status, ['confirmed','checked_in','checkout_requested','checked_out'], true)],
                        [$tenantPortal ? 'Authority check-in' : 'DTCM check-in', $booking->dtcmCheckin?->status === 'registered'],
                        ['Checkout', $booking->booking_status === 'checked_out'],
                        ['Deposit refund', in_array($booking->depositRefund?->status, ['accepted','refund_processing','refunded'], true)],
                    ] as [$label, $done])
                        <div class="rounded-2xl {{ $done ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-50 text-slate-500' }} p-4 text-sm font-bold">{{ $done ? 'Done' : 'Open' }} - {{ $label }}</div>
                    @endforeach
                </div>
            </section>

            <section class="erp-card p-5" data-record-panel="confirmation">
                <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                    <div><h2 class="text-lg font-bold text-[#071a3b]">Confirmation signing</h2><p class="mt-1 text-sm text-slate-500">Send confirmation link by email, WhatsApp, SMS, and portal.</p></div>
                    <span class="rounded-full {{ $booking->confirmation_signed_at ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }} px-3 py-1 text-xs font-bold">{{ $booking->confirmation_signed_at ? 'Signed' : 'Not signed' }}</span>
                </div>
                <div class="mt-5 grid gap-3 md:grid-cols-3">
                    <div class="rounded-2xl bg-slate-50 p-4"><p class="text-xs font-bold uppercase text-slate-400">Link sent</p><p class="mt-1 font-bold text-[#071a3b]">{{ $booking->confirmation_link_sent_at?->format('M d, Y H:i') ?? 'Not sent' }}</p></div>
                    <div class="rounded-2xl bg-slate-50 p-4"><p class="text-xs font-bold uppercase text-slate-400">Channels</p><p class="mt-1 font-bold text-[#071a3b]">{{ collect($booking->confirmation_delivery_channels ?? [])->map(fn($c) => str($c)->headline())->implode(', ') ?: 'None' }}</p></div>
                    <div class="rounded-2xl bg-slate-50 p-4"><p class="text-xs font-bold uppercase text-slate-400">Signed by</p><p class="mt-1 font-bold text-[#071a3b]">{{ $booking->confirmation_signed_by ?: 'Waiting' }}</p></div>
                </div>
                @if ($booking->confirmation_token)
                    <a href="{{ route('booking-confirmations.sign', [$booking, $booking->confirmation_token]) }}" target="_blank" class="mt-4 inline-flex rounded-xl border border-blue-200 px-4 py-2.5 text-sm font-bold text-blue-700">Open signing link</a>
                @endif
                @can('bookings.manage')
                    <form method="POST" action="{{ route('bookings.send-confirmation-link', $booking) }}" class="mt-4 rounded-2xl border border-blue-100 bg-blue-50 p-4">
                        @csrf
                        <p class="text-sm font-bold text-[#071a3b]">Delivery channels</p>
                        <div class="mt-3 grid gap-2 sm:grid-cols-4">
                            @foreach(['email' => 'Email', 'whatsapp' => 'WhatsApp', 'sms' => 'SMS', 'portal' => 'Portal'] as $value => $label)
                                <label class="rounded-xl bg-white px-3 py-2 text-sm font-bold text-slate-600"><input type="checkbox" name="channels[]" value="{{ $value }}" class="mr-2 rounded border-slate-300" @checked(in_array($value, ['email','whatsapp','portal'], true))>{{ $label }}</label>
                            @endforeach
                        </div>
                        <button class="mt-3 rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-bold text-white">Send / refresh signing link</button>
                    </form>
                @endcan
            </section>

            <section class="erp-card p-5" data-record-panel="inspection">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div><h2 class="text-lg font-bold text-[#071a3b]">Apartment inspection</h2><p class="mt-1 text-sm text-slate-500">Full grouped condition report by unit type.</p></div>
                    <span class="rounded-full {{ $issueCount ? 'bg-amber-50 text-amber-700' : 'bg-emerald-50 text-emerald-700' }} px-3 py-1 text-xs font-bold">{{ $issueCount }} issues</span>
                </div>
                <div class="mt-5 grid gap-3 md:grid-cols-3">
                    <div class="rounded-2xl bg-slate-50 p-4"><p class="text-xs font-bold uppercase text-slate-400">Unit type</p><p class="mt-1 font-bold text-[#071a3b]">{{ str($booking->unit->unit_type)->headline() }}</p></div>
                    <div class="rounded-2xl bg-slate-50 p-4"><p class="text-xs font-bold uppercase text-slate-400">Saved checks</p><p class="mt-1 font-bold text-[#071a3b]">{{ $booking->checkInInspectionItems->count() }}</p></div>
                    <div class="rounded-2xl bg-slate-50 p-4"><p class="text-xs font-bold uppercase text-slate-400">Purpose</p><p class="mt-1 font-bold text-[#071a3b]">Check-in / checkout</p></div>
                </div>
                <a href="{{ route('bookings.inspection', $booking) }}" class="mt-5 inline-flex rounded-xl bg-blue-600 px-5 py-3 text-sm font-black text-white">{{ $tenantPortal ? 'Open check-in inspection' : 'Open full apartment inspection' }}</a>
            </section>

            <section class="erp-card p-5" data-record-panel="extensions">
                <div class="flex items-center justify-between"><h2 class="text-lg font-bold text-[#071a3b]">Extension requests</h2><span class="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-bold text-blue-700">{{ $booking->extensionRequests->count() }} request</span></div>
                <div class="mt-4 space-y-3">
                    @forelse ($booking->extensionRequests as $extension)
                        <div class="rounded-2xl border border-slate-200 p-4">
                            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                                <div><div class="font-bold text-[#071a3b]">Extend to {{ $extension->requested_check_out_date->format('M d, Y') }}</div><p class="text-xs text-slate-500">{{ str($extension->status)->replace('_', ' ')->headline() }} @if($extension->invoice) / Invoice {{ $extension->invoice->invoice_no }} @endif</p></div>
                                @can('bookings.manage')
                                    @if ($extension->status === 'requested')
                                        <div class="grid gap-2 md:grid-cols-2">
                                            <form method="POST" action="{{ route('booking-extension-requests.approve', $extension) }}" class="flex gap-2">
                                                @csrf
                                                <input name="extra_rent_amount" class="erp-focus h-10 w-28 rounded-xl border border-slate-200 px-3 text-xs" placeholder="Amount">
                                                <button class="rounded-xl bg-emerald-600 px-3 text-xs font-bold text-white">Approve</button>
                                            </form>
                                            <form method="POST" action="{{ route('booking-extension-requests.reject', $extension) }}">
                                                @csrf
                                                <button class="h-10 rounded-xl bg-rose-600 px-3 text-xs font-bold text-white">Reject</button>
                                            </form>
                                        </div>
                                    @endif
                                @endcan
                            </div>
                            @if($extension->tenant_notes)<p class="mt-3 rounded-2xl bg-slate-50 p-3 text-sm text-slate-600">{{ $extension->tenant_notes }}</p>@endif
                        </div>
                    @empty
                        <p class="rounded-2xl border border-dashed border-slate-200 px-4 py-6 text-center text-sm text-slate-500">No extension requests yet.</p>
                    @endforelse
                </div>
            </section>

            <section class="erp-card p-5" data-record-panel="refund">
                <h2 class="text-lg font-bold text-[#071a3b]">Checkout, inspection, and deposit refund</h2>
                @if ($booking->depositRefund)
                    @php($refund = $booking->depositRefund)
                    <dl class="mt-5 grid gap-3 md:grid-cols-4">
                        <div class="rounded-2xl bg-slate-50 p-4"><dt class="text-xs font-bold uppercase text-slate-400">Status</dt><dd class="font-bold text-[#071a3b]">{{ str($refund->status)->replace('_', ' ')->headline() }}</dd></div>
                        <div class="rounded-2xl bg-slate-50 p-4"><dt class="text-xs font-bold uppercase text-slate-400">Deposit</dt><dd class="font-bold text-[#071a3b]">AED {{ number_format((float) $refund->deposit_amount, 2) }}</dd></div>
                        <div class="rounded-2xl bg-slate-50 p-4"><dt class="text-xs font-bold uppercase text-slate-400">Damage</dt><dd class="font-bold text-[#071a3b]">AED {{ number_format((float) $refund->damage_amount, 2) }}</dd></div>
                        <div class="rounded-2xl bg-slate-50 p-4"><dt class="text-xs font-bold uppercase text-slate-400">Refund</dt><dd class="font-bold text-[#071a3b]">AED {{ number_format((float) $refund->refund_amount, 2) }}</dd></div>
                    </dl>
                    @can('bookings.manage')
                        @if ($refund->status === 'pending_inspection')
                            <form method="POST" action="{{ route('booking-deposit-refunds.complete-inspection', $refund) }}" class="mt-5 grid gap-3 rounded-2xl border border-amber-100 bg-amber-50 p-4">
                                @csrf
                                <input name="damage_amount" class="erp-focus h-11 rounded-xl border border-amber-100 bg-white px-3 text-sm" placeholder="Damage amount, 0 if clear">
                                <textarea name="inspection_notes" rows="2" class="erp-focus rounded-xl border border-amber-100 bg-white px-3 py-2 text-sm" placeholder="Inspection notes"></textarea>
                                <textarea name="damage_report" rows="3" class="erp-focus rounded-xl border border-amber-100 bg-white px-3 py-2 text-sm" placeholder="Damage report shared with tenant"></textarea>
                                <button class="rounded-xl bg-amber-600 px-4 py-2.5 text-sm font-bold text-white">Send report to tenant</button>
                            </form>
                        @elseif (in_array($refund->status, ['accepted','refund_processing'], true))
                            <form method="POST" action="{{ route('booking-deposit-refunds.process', $refund) }}" class="mt-5">
                                @csrf
                                <button class="rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-bold text-white">Mark refund processed</button>
                            </form>
                        @endif
                    @endcan
                    @if($refund->damage_report)<div class="mt-4 rounded-2xl bg-slate-50 p-4"><p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">{{ $tenantPortal ? 'Deposit report' : 'Damage report shared with tenant' }}</p><p class="mt-2 text-sm text-slate-600">{{ $refund->damage_report }}</p></div>@endif
                @else
                    <p class="mt-4 rounded-2xl border border-dashed border-slate-200 px-4 py-6 text-center text-sm text-slate-500">Deposit refund starts after checkout is completed.</p>
                @endif
            </section>

            @unless($tenantPortal)
                <section class="erp-card p-5" data-record-panel="tasks">
                    <div class="flex items-center justify-between gap-3"><h2 class="text-lg font-bold text-[#071a3b]">Auto tasks</h2><a href="{{ route('tasks.index', ['task_type' => 'checkout_cleaning']) }}" class="text-xs font-bold text-blue-600">Open task board</a></div>
                    <div class="mt-4 space-y-3">
                        @forelse ($booking->tasks as $task)
                            <div class="rounded-2xl border border-slate-200 p-4"><div class="flex items-center justify-between gap-3"><div class="font-bold text-[#071a3b]">{{ $task->title }}</div><span class="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-bold text-blue-700">{{ str($task->status)->headline() }}</span></div><p class="mt-1 text-xs text-slate-500">Assigned to {{ $task->assignee?->full_name ?? 'Unassigned' }} / Due {{ $task->due_at?->format('M d, Y H:i') ?? 'Not set' }}</p><p class="mt-2 text-xs text-slate-500">Timeline entries: {{ $task->events->count() }}</p>@if($task->task_type === 'checkout_inspection')<a href="{{ route('bookings.inspection', $booking) }}" class="mt-3 inline-flex rounded-xl border border-blue-200 px-3 py-2 text-xs font-bold text-blue-700">Open inspection</a>@endif</div>
                        @empty
                            <p class="rounded-2xl border border-dashed border-slate-200 px-4 py-6 text-center text-sm text-slate-500">No tasks yet.</p>
                        @endforelse
                    </div>
                </section>
            @endunless
        </div>

        <aside class="space-y-5">
            <div class="erp-card p-5">
                <h2 class="text-lg font-bold text-[#071a3b]">{{ $tenantPortal ? 'Stay actions' : 'Operations controls' }}</h2>
                <div class="mt-4 space-y-3">
                    @can('portal.tenant')
                        <form method="POST" action="{{ route('bookings.request-extension', $booking) }}" class="rounded-2xl border border-blue-100 bg-blue-50 p-3">@csrf<input name="requested_check_out_date" type="date" class="erp-focus h-10 w-full rounded-xl border border-blue-100 bg-white px-3 text-xs"><textarea name="tenant_notes" rows="2" class="erp-focus mt-2 w-full rounded-xl border border-blue-100 bg-white px-3 py-2 text-xs" placeholder="Extension reason"></textarea><button class="mt-2 w-full rounded-xl bg-blue-600 px-4 py-2.5 text-xs font-bold text-white">Request extension</button></form>
                        <form method="POST" action="{{ route('bookings.request-checkout', $booking) }}">@csrf<button class="w-full rounded-xl bg-slate-900 px-4 py-2.5 text-xs font-bold text-white">Confirm checkout</button></form>
                    @endcan
                    @can('bookings.manage')
                        <form method="POST" action="{{ route('bookings.complete-checkout', $booking) }}">@csrf<button class="w-full rounded-xl bg-blue-600 px-4 py-2.5 text-xs font-bold text-white">Complete checkout and start deposit workflow</button></form>
                        <a href="{{ route('dtcm-checkins.index') }}" class="block rounded-xl border border-slate-200 px-4 py-2.5 text-center text-xs font-bold text-slate-600">Open DTCM check-ins</a>
                    @endcan
                </div>
            </div>

            @unless($tenantPortal)
                <div class="erp-card p-5"><h2 class="text-lg font-bold text-[#071a3b]">People</h2><dl class="mt-5 space-y-4"><div><dt class="text-xs font-bold uppercase text-slate-400">Tenant</dt><dd class="font-bold text-[#071a3b]">{{ $booking->tenant->full_name }}</dd><dd class="text-xs text-slate-500">{{ $booking->tenant->mobile_no }} / {{ $booking->tenant->email }}</dd></div><div><dt class="text-xs font-bold uppercase text-slate-400">Agent</dt><dd class="font-bold text-[#071a3b]">{{ $booking->agent?->full_name ?: 'Direct booking' }}</dd></div><div><dt class="text-xs font-bold uppercase text-slate-400">Source</dt><dd class="font-bold text-[#071a3b]">{{ $booking->source ?: 'Not set' }}</dd></div></dl></div>
                <div class="erp-card p-5"><h2 class="text-lg font-bold text-[#071a3b]">Notification log</h2><div class="mt-4 space-y-3">@forelse ($booking->notificationLogs->take(6) as $log)@php($displayStatus = $log->sent_at ? 'sent' : $log->status)<div class="rounded-2xl border border-slate-200 p-4"><div class="flex items-center justify-between gap-3"><div class="font-bold text-[#071a3b]">{{ str($log->subject ?: $log->channel)->headline() }}</div><span class="rounded-full {{ $displayStatus === 'sent' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }} px-2.5 py-1 text-xs font-bold">{{ str($displayStatus)->headline() }}</span></div><p class="mt-1 text-xs text-slate-500">{{ $log->recipient ?: 'No recipient' }}</p><p class="mt-2 text-sm text-slate-600">{{ $log->message }}</p></div>@empty<p class="rounded-2xl border border-dashed border-slate-200 px-4 py-6 text-center text-sm text-slate-500">No notifications logged yet.</p>@endforelse</div></div>
            @endunless
        </aside>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const tabs = Array.from(document.querySelectorAll('[data-record-tab]'));
        const panels = Array.from(document.querySelectorAll('[data-record-panel]'));
        const showTab = (key) => {
            tabs.forEach((tab) => tab.setAttribute('aria-selected', tab.dataset.recordTab === key ? 'true' : 'false'));
            panels.forEach((panel) => panel.toggleAttribute('hidden', panel.dataset.recordPanel !== key));
        };
        const requested = window.location.hash ? window.location.hash.substring(1) : 'overview';
        showTab(tabs.some((tab) => tab.dataset.recordTab === requested) ? requested : 'overview');
        tabs.forEach((tab) => tab.addEventListener('click', () => {
            showTab(tab.dataset.recordTab);
            history.replaceState(null, '', `#${tab.dataset.recordTab}`);
        }));
    });
</script>
</x-app-layout>
