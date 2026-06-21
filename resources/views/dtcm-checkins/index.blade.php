<x-app-layout>
<x-slot name="header">
    <div>
        <p class="text-[11px] font-bold uppercase tracking-[0.22em] text-blue-600">Authority workflow</p>
        <h1 class="text-2xl font-bold text-[#071a3b]">DTCM check-ins</h1>
    </div>
</x-slot>

@php
    $statusClasses = [
        'pending' => 'bg-amber-50 text-amber-700',
        'registered' => 'bg-emerald-50 text-emerald-700',
    ];
@endphp

<div class="space-y-5">
    @if (session('status'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">{{ session('status') }}</div>
    @endif

    <div class="erp-card p-5">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 class="text-lg font-bold text-[#071a3b]">Guest authority registration</h2>
                <p class="mt-1 text-sm text-slate-500">After receipt/payment approval, register the guest in DTCM. Completing DTCM changes booking to checked-in.</p>
            </div>
            <form method="GET" class="flex gap-2">
                <select name="status" class="erp-focus h-11 rounded-xl border border-slate-200 bg-white px-3 text-sm">
                    <option value="">All statuses</option>
                    @foreach (['pending', 'registered'] as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ str($status)->headline() }}</option>
                    @endforeach
                </select>
                <button class="rounded-xl bg-slate-900 px-4 text-sm font-bold text-white">Filter</button>
            </form>
        </div>
    </div>

    <div class="grid gap-4">
        @forelse ($checkins as $checkin)
            <div class="erp-card p-5">
                <div class="grid gap-4 lg:grid-cols-[1fr_340px]">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="text-lg font-black text-[#071a3b]">{{ $checkin->booking->booking_no }}</h3>
                            <span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $statusClasses[$checkin->status] ?? 'bg-slate-100 text-slate-600' }}">{{ str($checkin->status)->headline() }}</span>
                        </div>
                        <p class="mt-2 text-sm text-slate-500">{{ $checkin->booking->tenant->full_name }} / {{ $checkin->booking->unit->building->name }} / Unit {{ $checkin->booking->unit->unit_no }}</p>
                        <dl class="mt-4 grid gap-3 md:grid-cols-4">
                            <div class="rounded-2xl bg-slate-50 p-3">
                                <dt class="text-xs font-bold uppercase text-slate-400">Booking status</dt>
                                <dd class="font-bold text-[#071a3b]">{{ str($checkin->booking->booking_status)->headline() }}</dd>
                            </div>
                            <div class="rounded-2xl bg-slate-50 p-3">
                                <dt class="text-xs font-bold uppercase text-slate-400">Check-in</dt>
                                <dd class="font-bold text-[#071a3b]">{{ $checkin->booking->check_in_date->format('M d, Y') }}</dd>
                            </div>
                            <div class="rounded-2xl bg-slate-50 p-3">
                                <dt class="text-xs font-bold uppercase text-slate-400">Portal ref</dt>
                                <dd class="font-bold text-[#071a3b]">{{ $checkin->portal_reference ?: 'Not added' }}</dd>
                            </div>
                            <div class="rounded-2xl bg-slate-50 p-3">
                                <dt class="text-xs font-bold uppercase text-slate-400">Submitted</dt>
                                <dd class="font-bold text-[#071a3b]">{{ $checkin->submitted_at?->format('M d, Y H:i') ?? 'Pending' }}</dd>
                            </div>
                        </dl>
                        @if ($checkin->notes)
                            <p class="mt-3 rounded-2xl bg-blue-50 p-3 text-sm text-slate-600">{{ $checkin->notes }}</p>
                        @endif
                    </div>

                    <div>
                        @can('dtcm-checkins.manage')
                            @if ($checkin->status !== 'registered')
                                <form method="POST" action="{{ route('dtcm-checkins.complete', $checkin) }}" class="rounded-2xl border border-emerald-100 bg-emerald-50 p-3">
                                    @csrf
                                    <input name="portal_reference" class="erp-focus h-10 w-full rounded-xl border border-emerald-100 bg-white px-3 text-xs" placeholder="DTCM portal reference">
                                    <textarea name="notes" rows="3" class="erp-focus mt-2 w-full rounded-xl border border-emerald-100 bg-white px-3 py-2 text-xs" placeholder="Registration notes"></textarea>
                                    <button class="mt-2 w-full rounded-xl bg-emerald-600 px-4 py-2.5 text-xs font-bold text-white">Mark DTCM registered / booking checked in</button>
                                </form>
                            @else
                                <div class="rounded-2xl border border-emerald-100 bg-emerald-50 p-4 text-sm font-bold text-emerald-700">Guest registered in DTCM. Booking checked in.</div>
                            @endif
                        @endcan
                    </div>
                </div>
            </div>
        @empty
            <div class="erp-card px-4 py-12 text-center text-sm text-slate-500">No DTCM check-ins prepared yet.</div>
        @endforelse
    </div>

    {{ $checkins->links() }}
</div>
</x-app-layout>
