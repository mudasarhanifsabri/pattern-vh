<x-app-layout>
@php
    $tenantPortal = auth()->user()->can('portal.tenant') && ! auth()->user()->can('bookings.manage');
    $issueCount = $booking->checkInInspectionItems->whereIn('condition_status', ['damaged', 'missing', 'needs_attention'])->count();
    $lock = $booking->unit->ttLock;
    $accessMode = old('smart_lock_code_mode', $booking->smart_lock_code_mode ?: 'auto');
    $accessValidFrom = old('smart_lock_code_valid_from', $booking->smart_lock_code_valid_from?->format('Y-m-d\TH:i'));
    $accessValidUntil = old('smart_lock_code_valid_until', $booking->smart_lock_code_valid_until?->format('Y-m-d\TH:i'));
    $bookingJourneySteps = [
        ['key' => 'overview', 'label' => 'Booking information', 'complete' => true, 'status' => 'Complete', 'note' => $booking->unit->building->name.' / Unit '.$booking->unit->unit_no],
        ['key' => 'confirmation', 'label' => 'Booking confirmation', 'complete' => (bool) $booking->confirmation_signed_at, 'status' => $booking->confirmation_signed_at ? 'Signed' : 'Pending signature', 'note' => $booking->confirmation_link_sent_at ? 'Link sent '.$booking->confirmation_link_sent_at->format('M d, H:i') : 'Send confirmation link'],
        ['key' => 'access', 'label' => 'Smart lock access', 'complete' => (bool) $booking->smart_lock_code, 'status' => $booking->smart_lock_code ? 'Code ready' : 'Code pending', 'note' => $lock?->lock_name ?: 'No lock attached'],
        ['key' => 'inspection', 'label' => 'Apartment inspection', 'complete' => $booking->checkInInspectionItems->isNotEmpty(), 'status' => $booking->checkInInspectionItems->isNotEmpty() ? $booking->checkInInspectionItems->count().' items' : 'Not submitted', 'note' => $issueCount ? $issueCount.' issue(s) flagged' : 'No issues flagged'],
        ['key' => 'extensions', 'label' => 'Extensions', 'complete' => $booking->extensionRequests->isEmpty() || $booking->extensionRequests->whereNotIn('status', ['requested'])->isNotEmpty(), 'status' => $booking->extensionRequests->count().' request(s)', 'note' => 'Tenant extension workflow'],
        ['key' => 'refund', 'label' => 'Checkout and deposit', 'complete' => in_array($booking->depositRefund?->status, ['accepted', 'refund_processing', 'refunded'], true), 'status' => $booking->depositRefund ? str($booking->depositRefund->status)->replace('_', ' ')->headline() : 'Not started', 'note' => 'Refund starts after checkout'],
        ['key' => 'tasks', 'label' => 'Operations tasks', 'complete' => $booking->tasks->isNotEmpty() && $booking->tasks->where('status', '!=', 'completed')->isEmpty(), 'status' => $booking->tasks->count().' task(s)', 'note' => 'Cleaning, inspection, and follow-up'],
    ];
@endphp

<x-slot name="header">
    <div>
        <p class="text-[11px] font-bold uppercase tracking-[0.22em] text-blue-600">{{ $tenantPortal ? 'My stay' : 'Booking command center' }}</p>
        <h1 class="text-2xl font-bold text-[#071a3b]">{{ $booking->booking_no }}</h1>
    </div>
</x-slot>

@if($tenantPortal)
    @php
        $nights = $booking->check_in_date->diffInDays($booking->check_out_date);
        $wifiName = $booking->unit->wifi_name ?: 'Pattern_Guest';
        $wifiPassword = $booking->unit->wifi_password ?: 'Ask support';
        $smartLockCodeDisplay = $booking->smart_lock_code ? trim(chunk_split($booking->smart_lock_code, 1, ' ')) : 'Pending';
        $smartLockValidFrom = $booking->smart_lock_code_valid_from ?: \Illuminate\Support\Carbon::parse($booking->check_in_date->format('Y-m-d').' '.($booking->check_in_time ?: '15:00'));
        $smartLockValidUntil = $booking->smart_lock_code_valid_until ?: \Illuminate\Support\Carbon::parse($booking->check_out_date->format('Y-m-d').' '.($booking->check_out_time ?: '11:00'));
    @endphp

    <div class="space-y-5">
        @if (session('status'))
            <div class="rounded-[1.35rem] border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="rounded-[1.35rem] border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700">{{ $errors->first() }}</div>
        @endif

        <div class="flex items-center justify-between">
            <a href="{{ route('bookings.index') }}" class="grid h-11 w-11 place-items-center rounded-2xl bg-white text-slate-700 shadow-sm ring-1 ring-slate-200">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M15 19l-7-7 7-7" /></svg>
            </a>
            <h1 class="text-base font-black text-[#0b1736]">Booking Details</h1>
            <a href="{{ route('bookings.confirmation-pdf', $booking) }}" target="_blank" class="grid h-11 w-11 place-items-center rounded-2xl bg-white text-slate-700 shadow-sm ring-1 ring-slate-200">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 9V2h12v7" /><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2" /><path d="M6 14h12v8H6z" /></svg>
            </a>
        </div>

        <section class="overflow-hidden rounded-[1.8rem] bg-white p-4 shadow-[0_16px_40px_rgba(15,23,42,0.08)] ring-1 ring-slate-100">
            <div class="grid grid-cols-[120px_1fr] gap-4">
                <div class="h-28 overflow-hidden rounded-[1.35rem] bg-[linear-gradient(135deg,rgba(15,23,42,.18),rgba(37,99,235,.18)),url('https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?auto=format&fit=crop&w=600&q=80')] bg-cover bg-center"></div>
                <div class="min-w-0">
                    <span class="inline-flex rounded-full bg-emerald-50 px-3 py-1 text-xs font-black text-emerald-700">{{ str($booking->booking_status)->replace('_', ' ')->headline() }}</span>
                    <h2 class="mt-3 text-lg font-black leading-tight text-[#0b1736]">{{ $booking->unit->building->name }} Apartment</h2>
                    <p class="mt-1 text-sm font-semibold text-slate-500">Dubai, UAE</p>
                </div>
            </div>
        </section>

        <section class="rounded-[1.8rem] bg-white p-4 shadow-[0_16px_40px_rgba(15,23,42,0.08)] ring-1 ring-slate-100">
            <div class="space-y-3">
                @foreach([
                    ['Check-in', $booking->check_in_date->format('d M Y, h:i A'), 'M8 7V3m8 0V3M7 11h10M5 21h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2z'],
                    ['Check-out', $booking->check_out_date->format('d M Y, h:i A'), 'M8 7V3m8 0V3M7 11h10M5 21h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2z'],
                    ['Guests', ($booking->guest_count ?: 1).' guest(s)', 'M17 20h5v-2a4 4 0 0 0-4-4h-1M9 20H4v-2a4 4 0 0 1 4-4h1m4-4a4 4 0 1 0-8 0 4 4 0 0 0 8 0zm8 0a4 4 0 1 0-8 0 4 4 0 0 0 8 0z'],
                    ['Nights', $nights.' Nights', 'M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0z'],
                    ['Booking ID', $booking->booking_no, 'M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l5 5v11a2 2 0 0 1-2 2z'],
                ] as [$label, $value, $path])
                    <div class="flex items-center gap-3 rounded-2xl bg-slate-50 px-3 py-3">
                        <span class="grid h-10 w-10 shrink-0 place-items-center rounded-2xl bg-blue-50 text-blue-600">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="{{ $path }}" /></svg>
                        </span>
                        <span class="text-sm font-semibold text-slate-500">{{ $label }}</span>
                        <span class="ml-auto text-right text-sm font-black text-[#0b1736]">{{ $value }}</span>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="rounded-[1.8rem] bg-white p-5 shadow-[0_16px_40px_rgba(15,23,42,0.08)] ring-1 ring-slate-100">
            <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                <h2 class="text-lg font-black text-[#0b1736]">Smart Lock Access</h2>
                <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-black text-emerald-700">Active</span>
            </div>
            <div class="mt-5 grid grid-cols-[110px_1fr] gap-4">
                <div class="text-center">
                    <div class="grid h-24 w-24 place-items-center rounded-full bg-blue-50 ring-[16px] ring-blue-50/60">
                        <svg class="h-12 w-12 text-blue-600" fill="currentColor" viewBox="0 0 24 24"><path d="M17 8h-1V6a4 4 0 0 0-8 0v2H7a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-9a2 2 0 0 0-2-2Zm-7-2a2 2 0 1 1 4 0v2h-4V6Z" /></svg>
                    </div>
                    <p class="mt-3 text-sm font-black text-blue-600">Tap to Unlock</p>
                </div>
                <div>
                    <p class="text-sm font-black text-[#0b1736]">Main Door</p>
                    <p class="mt-4 text-xs font-semibold text-slate-500">Access Code</p>
                    <div class="mt-2 flex items-center justify-between rounded-2xl bg-blue-50 px-4 py-3 text-3xl font-black tracking-[0.35em] text-blue-600">{{ $smartLockCodeDisplay }} <span class="text-blue-500"><svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M8 16H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v2" /><path d="M10 8h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2h-8a2 2 0 0 1-2-2v-8a2 2 0 0 1 2-2z" /></svg></span></div>
                    <p class="mt-4 text-xs font-semibold text-slate-500">Valid From</p>
                    <p class="text-sm font-black text-blue-600">{{ $smartLockValidFrom->format('d M Y, h:i A') }}</p>
                    <p class="mt-3 text-xs font-semibold text-slate-500">Valid Until</p>
                    <p class="text-sm font-black text-blue-600">{{ $smartLockValidUntil->format('d M Y, h:i A') }}</p>
                </div>
            </div>
            <button class="mt-5 h-12 w-full rounded-2xl bg-blue-600 px-4 py-3 text-sm font-black text-white shadow-xl shadow-blue-600/20">Open Smart Lock</button>
        </section>

        <div class="grid grid-cols-2 gap-4">
            <a href="{{ route('bookings.inspection', $booking) }}" class="rounded-[1.45rem] bg-white p-4 shadow-sm ring-1 ring-slate-100">
                <span class="grid h-12 w-12 place-items-center rounded-2xl bg-blue-50 text-blue-600"><svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 12h6m-6 4h6M7 3h10a2 2 0 0 1 2 2v14l-4-2-4 2-4-2-4 2V5a2 2 0 0 1 2-2z" /></svg></span>
                <h3 class="mt-3 font-black text-[#0b1736]">Check-in Guide</h3>
                <p class="mt-1 text-sm font-semibold leading-5 text-slate-500">Step by step instructions</p>
            </a>
            <div class="rounded-[1.45rem] bg-white p-4 shadow-sm ring-1 ring-slate-100">
                <span class="grid h-12 w-12 place-items-center rounded-2xl bg-blue-50 text-blue-600"><svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M5 13a10 10 0 0 1 14 0M8.5 16.5a5 5 0 0 1 7 0M12 20h.01M2 10a15 15 0 0 1 20 0" /></svg></span>
                <h3 class="mt-3 font-black text-[#0b1736]">Wi-Fi Details</h3>
                <p class="mt-1 text-sm font-semibold leading-5 text-slate-500">{{ $wifiName }} / {{ $wifiPassword }}</p>
            </div>
            <a href="{{ route('dashboard') }}" class="rounded-[1.45rem] bg-white p-4 shadow-sm ring-1 ring-slate-100">
                <span class="grid h-12 w-12 place-items-center rounded-2xl bg-blue-50 text-blue-600"><svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4" /><path d="M12 3l7 4v5c0 5-3.5 8-7 9-3.5-1-7-4-7-9V7l7-4z" /></svg></span>
                <h3 class="mt-3 font-black text-[#0b1736]">House Rules</h3>
                <p class="mt-1 text-sm font-semibold leading-5 text-slate-500">Important rules to follow</p>
            </a>
            <a href="{{ route('support.index') }}" class="rounded-[1.45rem] bg-white p-4 shadow-sm ring-1 ring-slate-100">
                <span class="grid h-12 w-12 place-items-center rounded-2xl bg-blue-50 text-blue-600"><svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M18 10a6 6 0 0 0-12 0v4a3 3 0 0 0 3 3h1" /><path d="M18 14v2a2 2 0 0 1-2 2h-2" /></svg></span>
                <h3 class="mt-3 font-black text-[#0b1736]">Need Help?</h3>
                <p class="mt-1 text-sm font-semibold leading-5 text-slate-500">Contact support 24/7</p>
            </a>
        </div>

        <section class="rounded-[1.8rem] bg-white p-5 shadow-[0_16px_40px_rgba(15,23,42,0.08)] ring-1 ring-slate-100">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-black text-[#0b1736]">Booking Summary</h2>
                <a href="{{ route('bookings.confirmation-pdf', $booking) }}" target="_blank" class="text-sm font-black text-blue-600">View PDF</a>
            </div>
            <div class="mt-4 divide-y divide-slate-100 text-sm">
                <div class="flex justify-between py-3"><span class="font-semibold text-slate-500">Rent</span><span class="font-black text-[#0b1736]">AED {{ number_format((float) $booking->rent_amount, 2) }}</span></div>
                <div class="flex justify-between py-3"><span class="font-semibold text-slate-500">VAT 5% on rent</span><span class="font-black text-[#0b1736]">AED {{ number_format((float) $booking->vat_amount, 2) }}</span></div>
                <div class="flex justify-between py-3"><span class="font-semibold text-slate-500">Security deposit</span><span class="font-black text-[#0b1736]">AED {{ number_format((float) $booking->deposit_amount, 2) }}</span></div>
                <div class="flex justify-between py-3 text-base"><span class="font-black text-[#0b1736]">Total</span><span class="font-black text-blue-600">AED {{ number_format((float) $booking->total_amount, 2) }}</span></div>
            </div>
        </section>

        @if($booking->depositRefund)
            @php
                $refund = $booking->depositRefund;
            @endphp
            <section class="rounded-[1.8rem] bg-white p-5 shadow-[0_16px_40px_rgba(15,23,42,0.08)] ring-1 ring-slate-100">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-black text-[#0b1736]">Deposit report</h2>
                        <p class="mt-1 text-sm font-semibold text-slate-500">Inspection and refund status for your stay.</p>
                    </div>
                    <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-black text-blue-700">{{ str($refund->status)->replace('_', ' ')->headline() }}</span>
                </div>
                <div class="mt-4 grid grid-cols-3 gap-2 text-center">
                    <div class="rounded-2xl bg-slate-50 p-3">
                        <p class="text-[10px] font-black uppercase tracking-[0.12em] text-slate-400">Deposit</p>
                        <p class="mt-1 text-sm font-black text-[#0b1736]">AED {{ number_format((float) $refund->deposit_amount, 2) }}</p>
                    </div>
                    <div class="rounded-2xl bg-slate-50 p-3">
                        <p class="text-[10px] font-black uppercase tracking-[0.12em] text-slate-400">Damage</p>
                        <p class="mt-1 text-sm font-black text-[#0b1736]">AED {{ number_format((float) $refund->damage_amount, 2) }}</p>
                    </div>
                    <div class="rounded-2xl bg-emerald-50 p-3">
                        <p class="text-[10px] font-black uppercase tracking-[0.12em] text-emerald-500">Refund</p>
                        <p class="mt-1 text-sm font-black text-emerald-700">AED {{ number_format((float) $refund->refund_amount, 2) }}</p>
                    </div>
                </div>
                @if($refund->damage_report)
                    <div class="mt-4 rounded-2xl bg-slate-50 p-4">
                        <p class="text-[10px] font-black uppercase tracking-[0.16em] text-slate-400">Deposit report</p>
                        <p class="mt-2 text-sm font-semibold leading-6 text-slate-600">{{ $refund->damage_report }}</p>
                    </div>
                @endif
                @if($refund->status === 'tenant_review')
                    <form method="POST" action="{{ route('booking-deposit-refunds.accept', $refund) }}" class="mt-4">
                        @csrf
                        <button class="h-12 w-full rounded-2xl bg-blue-600 px-4 py-3 text-sm font-black text-white shadow-xl shadow-blue-600/20">Accept report</button>
                    </form>
                @endif
            </section>
        @endif
    </div>
@else
@include('bookings.partials.admin-command-center')
@if(false)
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

    <section class="overflow-hidden rounded-[1.5rem] border border-slate-200 bg-white shadow-xl shadow-slate-950/5">
        <div class="border-b border-slate-100 px-5 py-4">
            <p class="text-xs font-black uppercase tracking-[0.18em] text-blue-600">Booking journey</p>
            <h2 class="mt-1 text-xl font-black text-[#071a3b]">Everything related to this booking in one place</h2>
        </div>
        <div class="divide-y divide-slate-100">
            @foreach($bookingJourneySteps as $index => $step)
                <a href="#booking-step-{{ $step['key'] }}" class="group relative grid gap-3 px-5 py-4 transition hover:bg-slate-50 md:grid-cols-[1fr_160px] md:items-center">
                    <div class="relative flex gap-4">
                        @if(! $loop->last)
                            <span class="absolute left-[13px] top-8 h-[calc(100%+1rem)] w-0.5 {{ $step['complete'] ? 'bg-cyan-500' : 'bg-rose-400' }}"></span>
                        @endif
                        <span class="relative z-10 grid h-7 w-7 shrink-0 place-items-center rounded-full {{ $step['complete'] ? 'bg-cyan-500 text-white' : 'border-2 border-rose-500 bg-white text-rose-600' }} text-xs font-black shadow-sm">
                            {{ $step['complete'] ? '✓' : $index + 1 }}
                        </span>
                        <span>
                            <span class="block text-base font-black text-[#071a3b]">{{ $step['label'] }}</span>
                            <span class="mt-1 block text-xs font-semibold text-slate-500">{{ $step['note'] }}</span>
                        </span>
                    </div>
                    <div class="flex items-center justify-between gap-3 md:justify-end">
                        <span class="rounded-full {{ $step['complete'] ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }} px-3 py-1 text-xs font-black">{{ $step['status'] }}</span>
                        <svg class="h-4 w-4 text-slate-300 transition group-hover:translate-x-1 group-hover:text-blue-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
                    </div>
                </a>
            @endforeach
        </div>
    </section>

    <div class="grid gap-5 xl:grid-cols-[1fr_360px]">
        <div class="space-y-5">
            <section id="booking-step-overview" class="erp-card p-5 scroll-mt-24" data-record-panel="overview">
                <h2 class="text-lg font-bold text-[#071a3b]">Booking information</h2>
                <p class="mt-1 text-sm text-slate-500">Stay, payment, workflow, tenant, unit, and status at a glance.</p>
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

            <section id="booking-step-confirmation" class="erp-card p-5 scroll-mt-24" data-record-panel="confirmation">
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

            <section id="booking-step-access" class="erp-card p-5 scroll-mt-24" data-record-panel="access">
                <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.18em] text-blue-600">Smart access</p>
                        <h2 class="mt-1 text-lg font-bold text-[#071a3b]">Lock code and booking access details</h2>
                        <p class="mt-1 text-sm text-slate-500">Auto-generate a booking code after confirmation or enter a manual code from TTLock.</p>
                    </div>
                    <span class="rounded-full {{ $booking->smart_lock_code ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }} px-3 py-1 text-xs font-bold">{{ $booking->smart_lock_code ? 'Ready' : 'Pending' }}</span>
                </div>
                <div class="mt-5 grid gap-4 xl:grid-cols-[1fr_380px]">
                    <dl class="grid gap-3 md:grid-cols-2">
                        <div class="rounded-2xl bg-slate-50 p-4 md:col-span-2">
                            <dt class="text-xs font-bold uppercase text-slate-400">Attached lock</dt>
                            <dd class="mt-1 font-black text-[#071a3b]">{{ $lock?->lock_name ?: 'No lock attached to unit' }}</dd>
                            @if($lock)
                                <dd class="mt-1 text-xs font-semibold text-slate-500">TTLock ID {{ $lock->lock_id }} / {{ $lock->gateway_id ? 'Gateway '.$lock->gateway_id : 'Bluetooth only' }} / Battery {{ $lock->battery_level !== null ? $lock->battery_level.'%' : 'N/A' }}</dd>
                            @else
                                <dd class="mt-1 text-xs font-semibold text-amber-600">Attach a lock from the unit Smart Lock tab first.</dd>
                            @endif
                        </div>
                        <div class="rounded-2xl bg-blue-50 p-4">
                            <dt class="text-xs font-bold uppercase text-blue-400">Mode</dt>
                            <dd class="mt-1 font-black text-blue-700">{{ str($booking->smart_lock_code_mode ?: 'auto')->headline() }}</dd>
                        </div>
                        <div class="rounded-2xl bg-blue-50 p-4">
                            <dt class="text-xs font-bold uppercase text-blue-400">Access code</dt>
                            <dd class="mt-1 font-black tracking-[0.22em] text-blue-700">{{ $booking->smart_lock_code ?: 'Pending' }}</dd>
                        </div>
                        <div class="rounded-2xl bg-slate-50 p-4">
                            <dt class="text-xs font-bold uppercase text-slate-400">Valid from</dt>
                            <dd class="mt-1 font-bold text-[#071a3b]">{{ $booking->smart_lock_code_valid_from?->format('M d, Y H:i') ?? 'After confirmation' }}</dd>
                        </div>
                        <div class="rounded-2xl bg-slate-50 p-4">
                            <dt class="text-xs font-bold uppercase text-slate-400">Valid until</dt>
                            <dd class="mt-1 font-bold text-[#071a3b]">{{ $booking->smart_lock_code_valid_until?->format('M d, Y H:i') ?? 'Checkout' }}</dd>
                        </div>
                    </dl>
                    @can('bookings.manage')
                        <form method="POST" action="{{ route('bookings.smart-lock-access.update', $booking) }}" class="space-y-3 rounded-2xl border border-blue-100 bg-blue-50 p-4">
                            @csrf
                            <label class="block text-xs font-black text-[#071a3b]">Code mode
                                <select name="smart_lock_code_mode" class="erp-focus mt-1 h-10 w-full rounded-xl border border-blue-100 bg-white px-3 text-sm">
                                    <option value="auto" @selected($accessMode === 'auto')>Auto generate after confirmation</option>
                                    <option value="manual" @selected($accessMode === 'manual')>Manual code</option>
                                </select>
                            </label>
                            <label class="block text-xs font-black text-[#071a3b]">Manual code
                                <input name="smart_lock_code" value="{{ old('smart_lock_code', $booking->smart_lock_code) }}" class="erp-focus mt-1 h-10 w-full rounded-xl border border-blue-100 bg-white px-3 text-sm" placeholder="Enter code if manual">
                            </label>
                            <div class="grid gap-2 sm:grid-cols-2">
                                <label class="block text-xs font-black text-[#071a3b]">Valid from
                                    <input name="smart_lock_code_valid_from" type="datetime-local" value="{{ $accessValidFrom }}" class="erp-focus mt-1 h-10 w-full rounded-xl border border-blue-100 bg-white px-3 text-sm">
                                </label>
                                <label class="block text-xs font-black text-[#071a3b]">Valid until
                                    <input name="smart_lock_code_valid_until" type="datetime-local" value="{{ $accessValidUntil }}" class="erp-focus mt-1 h-10 w-full rounded-xl border border-blue-100 bg-white px-3 text-sm">
                                </label>
                            </div>
                            <label class="block text-xs font-black text-[#071a3b]">Internal note
                                <textarea name="smart_lock_code_note" rows="2" class="erp-focus mt-1 w-full rounded-xl border border-blue-100 bg-white px-3 py-2 text-sm" placeholder="Example: Auto code prepared, send after DTCM">{{ old('smart_lock_code_note', $booking->smart_lock_code_note) }}</textarea>
                            </label>
                            <label class="inline-flex items-center gap-2 text-xs font-black text-slate-600">
                                <input type="checkbox" name="regenerate" value="1" class="rounded border-slate-300">
                                Regenerate auto code now
                            </label>
                            <button class="w-full rounded-xl bg-blue-600 px-4 py-2.5 text-xs font-black text-white">Save access code</button>
                        </form>
                    @endcan
                </div>
            </section>

            <section id="booking-step-inspection" class="erp-card p-5 scroll-mt-24" data-record-panel="inspection">
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

            <section id="booking-step-extensions" class="erp-card p-5 scroll-mt-24" data-record-panel="extensions">
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

            <section id="booking-step-refund" class="erp-card p-5 scroll-mt-24" data-record-panel="refund">
                <h2 class="text-lg font-bold text-[#071a3b]">Checkout, inspection, and deposit refund</h2>
                @if ($booking->depositRefund)
                    @php
                        $refund = $booking->depositRefund;
                    @endphp
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
                <section id="booking-step-tasks" class="erp-card p-5 scroll-mt-24" data-record-panel="tasks">
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
                <div class="erp-card p-5">
                    <h2 class="text-lg font-bold text-[#071a3b]">Notification log</h2>
                    <div class="mt-4 space-y-3">
                        @forelse ($booking->notificationLogs->take(6) as $log)
                            @php
                                $displayStatus = $log->sent_at ? 'sent' : $log->status;
                            @endphp
                            <div class="rounded-2xl border border-slate-200 p-4">
                                <div class="flex items-center justify-between gap-3">
                                    <div class="font-bold text-[#071a3b]">{{ str($log->subject ?: $log->channel)->headline() }}</div>
                                    <span class="rounded-full {{ $displayStatus === 'sent' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }} px-2.5 py-1 text-xs font-bold">{{ str($displayStatus)->headline() }}</span>
                                </div>
                                <p class="mt-1 text-xs text-slate-500">{{ $log->recipient ?: 'No recipient' }}</p>
                                <p class="mt-2 text-sm text-slate-600">{{ $log->message }}</p>
                            </div>
                        @empty
                            <p class="rounded-2xl border border-dashed border-slate-200 px-4 py-6 text-center text-sm text-slate-500">No notifications logged yet.</p>
                        @endforelse
                    </div>
                </div>
            @endunless
        </aside>
    </div>
</div>
@endif
@endif

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const tabs = Array.from(document.querySelectorAll('[data-record-tab]'));
        const panels = Array.from(document.querySelectorAll('[data-record-panel]'));
        if (!tabs.length || !panels.length) return;
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
