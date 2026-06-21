<x-app-layout>
<x-slot name="header">
    <div>
        <p class="flex items-center gap-2 text-[11px] font-bold uppercase tracking-[0.22em] text-slate-500">
            <span class="h-0.5 w-7 rounded-full bg-blue-600"></span>
            Portfolio planning
        </p>
        <h1 class="mt-4 text-4xl font-black tracking-[-0.05em] text-[#071a3b]">Availability calendar</h1>
    </div>
</x-slot>

@php
    $cellWidth = 96;
    $unitWidth = 178;
    $activeStatuses = ['confirmed', 'checked_in', 'checkout_requested'];
    $selectedDays = (int) request('days', 14);
    $mode = request('mode', $selectedDays > 14 ? 'month' : 'week');
    $statusStyles = [
        'confirmed' => 'border-blue-600 bg-blue-100 text-[#071a3b]',
        'checked_in' => 'border-indigo-600 bg-indigo-100 text-[#071a3b]',
        'checkout_requested' => 'border-violet-600 bg-violet-100 text-[#071a3b]',
    ];
    $mobileCards = $units->flatMap(fn ($unit) => ($bookingsByUnit[$unit->id] ?? collect())->map(fn ($booking) => [$unit, $booking]))->take(8);
@endphp

<div class="space-y-6">
    <div class="-mt-3 flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
        <p class="max-w-3xl text-base leading-7 text-slate-500">Scan booked, available, blocked, owner stays, and maintenance periods across every unit.</p>
        @can('bookings.manage')
            <a href="{{ route('bookings.create') }}" class="inline-flex h-14 items-center justify-center gap-2 rounded-2xl bg-blue-600 px-6 text-sm font-black text-white shadow-2xl shadow-blue-600/25 transition hover:bg-blue-700 active:scale-[0.98]">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                Add booking
            </a>
        @endcan
    </div>

    <section class="overflow-hidden rounded-[1.7rem] border border-slate-200 bg-white shadow-2xl shadow-slate-200/60">
        <div class="flex flex-col gap-4 border-b border-slate-200 px-2 py-2 sm:flex-row sm:items-center sm:justify-between">
            <nav class="flex rounded-2xl bg-white p-1">
                <a href="{{ route('availability-calendar.index', array_merge(request()->except(['days', 'mode']), ['days' => 31, 'mode' => 'month'])) }}" class="rounded-xl px-5 py-3 text-xs font-black {{ $mode === 'month' ? 'bg-blue-50 text-blue-700' : 'text-slate-500 hover:text-blue-700' }}">Month</a>
                <a href="{{ route('availability-calendar.index', array_merge(request()->except(['days', 'mode']), ['days' => 14, 'mode' => 'week'])) }}" class="rounded-xl px-5 py-3 text-xs font-black {{ $mode === 'week' ? 'bg-blue-50 text-blue-700' : 'text-slate-500 hover:text-blue-700' }}">Week</a>
                <a href="{{ route('availability-calendar.index', array_merge(request()->except(['days', 'mode']), ['days' => 7, 'mode' => 'agenda'])) }}" class="rounded-xl px-5 py-3 text-xs font-black {{ $mode === 'agenda' ? 'bg-blue-50 text-blue-700' : 'text-slate-500 hover:text-blue-700' }}">Agenda</a>
            </nav>
            <div class="hidden items-center gap-3 px-4 text-[11px] font-bold text-slate-500 lg:flex">
                <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-sm bg-emerald-200"></span> Available</span>
                <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-sm bg-blue-200"></span> Booked</span>
                <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-sm bg-violet-200"></span> Owner stay</span>
                <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-sm bg-amber-200"></span> Maintenance</span>
                <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-sm bg-slate-300"></span> Blocked</span>
            </div>
        </div>

        <div class="border-b border-slate-200 p-4">
            <form method="GET" class="grid gap-3 xl:grid-cols-[44px_64px_44px_1fr_170px_170px_170px_auto] xl:items-center">
                <input type="hidden" name="days" value="{{ $selectedDays }}">
                <input type="hidden" name="mode" value="{{ $mode }}">
                <a href="{{ route('availability-calendar.index', array_merge(request()->except('start'), ['start' => $start->copy()->subDays($selectedDays)->toDateString()])) }}" class="grid h-11 w-11 place-items-center rounded-xl border border-slate-200 bg-white text-lg font-black text-slate-500 transition hover:border-blue-200 hover:text-blue-700">&lsaquo;</a>
                <a href="{{ route('availability-calendar.index', request()->except('start')) }}" class="grid h-11 place-items-center rounded-xl border border-slate-200 bg-white px-4 text-xs font-black text-slate-600 transition hover:border-blue-200 hover:text-blue-700">Today</a>
                <a href="{{ route('availability-calendar.index', array_merge(request()->except('start'), ['start' => $start->copy()->addDays($selectedDays)->toDateString()])) }}" class="grid h-11 w-11 place-items-center rounded-xl border border-slate-200 bg-white text-lg font-black text-slate-500 transition hover:border-blue-200 hover:text-blue-700">&rsaquo;</a>
                <div class="flex min-h-11 items-center rounded-xl bg-white px-1 text-xl font-black tracking-[-0.03em] text-[#071a3b]">{{ $start->format('F Y') }}</div>
                <select name="building_id" class="erp-focus h-11 rounded-xl border border-slate-200 bg-white px-3 text-sm font-semibold text-[#071a3b]">
                    <option value="">All buildings</option>
                    @foreach($buildings as $building)
                        <option value="{{ $building->id }}" @selected(request('building_id') == $building->id)>{{ $building->name }}</option>
                    @endforeach
                </select>
                <select name="unit_id" class="erp-focus h-11 rounded-xl border border-slate-200 bg-white px-3 text-sm font-semibold text-[#071a3b]">
                    <option value="">All units</option>
                    @foreach($allUnits as $unit)
                        <option value="{{ $unit->id }}" @selected(request('unit_id') == $unit->id)>{{ $unit->building->name }} / {{ $unit->unit_no }}</option>
                    @endforeach
                </select>
                <select name="source" class="erp-focus h-11 rounded-xl border border-slate-200 bg-white px-3 text-sm font-semibold text-[#071a3b]">
                    <option value="">All sources</option>
                    @foreach($sources as $source)
                        <option value="{{ $source }}" @selected(request('source') === $source)>{{ $source }}</option>
                    @endforeach
                </select>
                <button class="h-11 rounded-xl bg-slate-950 px-5 text-sm font-black text-white shadow-lg shadow-slate-950/10">Apply</button>
            </form>
        </div>

        <div class="hidden overflow-x-auto lg:block">
            <div style="min-width: {{ $unitWidth + ($days->count() * $cellWidth) }}px;">
                <div class="grid border-b border-slate-200 bg-slate-50 text-[10px] font-black uppercase tracking-[0.12em] text-slate-500" style="grid-template-columns: {{ $unitWidth }}px repeat({{ $days->count() }}, {{ $cellWidth }}px);">
                    <div class="sticky left-0 z-20 bg-slate-50 px-4 py-4">Unit</div>
                    @foreach($days as $day)
                        <div class="border-l border-slate-200 px-3 py-3 text-center {{ $day->isToday() ? 'bg-blue-600 text-white' : '' }}">
                            <div>{{ $day->format('D') }}</div>
                            <div class="mt-1 text-sm">{{ $day->format('d') }}</div>
                        </div>
                    @endforeach
                </div>

                @forelse($units as $unit)
                    @php
                        $unitBookings = $bookingsByUnit[$unit->id] ?? collect();
                    @endphp
                    <div class="relative grid min-h-[74px] border-b border-slate-100" style="grid-template-columns: {{ $unitWidth }}px repeat({{ $days->count() }}, {{ $cellWidth }}px);">
                        <a href="{{ route('units.show', $unit) }}" class="sticky left-0 z-20 flex flex-col justify-center bg-white px-4 py-4 hover:bg-slate-50">
                            <span class="text-[11px] font-medium text-slate-500">{{ $unit->building->name }}</span>
                            <span class="mt-1 w-fit rounded bg-blue-600 px-1.5 py-0.5 text-sm font-black leading-none text-white">{{ $unit->unit_no }}</span>
                        </a>
                        @foreach($days as $day)
                            <div class="border-l border-slate-100 {{ $day->isToday() ? 'bg-blue-50/70' : 'bg-white' }}"></div>
                        @endforeach

                        @foreach($unitBookings as $booking)
                            @php
                                $barStart = $booking->check_in_date->greaterThan($start) ? $booking->check_in_date : $start;
                                $barEnd = $booking->check_out_date->lessThan($end) ? $booking->check_out_date : $end;
                                $offset = $start->diffInDays($barStart);
                                $span = max(1, $barStart->diffInDays($barEnd) + 1);
                                $left = $unitWidth + ($offset * $cellWidth) + 4;
                                $width = ($span * $cellWidth) - 10;
                                $style = $statusStyles[$booking->booking_status] ?? $statusStyles['confirmed'];
                            @endphp
                            <a href="{{ route('bookings.show', $booking) }}" class="absolute top-4 z-10 h-11 rounded-xl border-l-4 px-3 py-1 text-[11px] shadow-sm transition hover:-translate-y-0.5 hover:shadow-md {{ $style }}" style="left: {{ $left }}px; width: {{ $width }}px;">
                                <span class="block truncate font-black">{{ $booking->tenant->full_name }}</span>
                                <span class="block truncate text-slate-500">{{ str($booking->booking_status)->replace('_', ' ')->headline() }}</span>
                            </a>
                        @endforeach

                        @if($unitBookings->isEmpty() && $unit->availability_status === 'available')
                            @php
                                $availableWidth = min(4, $days->count()) * $cellWidth - 10;
                            @endphp
                            <div class="absolute top-4 z-10 h-11 rounded-xl bg-emerald-100 px-3 py-1 text-[11px] text-emerald-700" style="left: {{ $unitWidth + (max(0, min(3, $days->count() - 1)) * $cellWidth) + 4 }}px; width: {{ max(120, $availableWidth) }}px;">
                                <span class="block truncate font-black">Available</span>
                                <span class="block truncate text-emerald-600">No active booking</span>
                            </div>
                        @elseif(in_array($unit->availability_status, ['maintenance','blocked'], true))
                            <div class="absolute top-4 z-10 h-11 rounded-xl px-3 py-1 text-[11px] {{ $unit->availability_status === 'maintenance' ? 'bg-amber-100 text-amber-800' : 'bg-slate-200 text-slate-700' }}" style="left: {{ $unitWidth + $cellWidth + 4 }}px; width: {{ min(3, $days->count()) * $cellWidth - 10 }}px;">
                                <span class="block truncate font-black">{{ str($unit->availability_status)->headline() }}</span>
                                <span class="block truncate">{{ str($unit->availability_status)->headline() }}</span>
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="px-4 py-16 text-center text-sm font-semibold text-slate-500">No units found for this filter.</div>
                @endforelse
            </div>
        </div>

        <div class="space-y-3 p-4 lg:hidden">
            @forelse($mobileCards as $mobileCard)
                @php
                    $unit = $mobileCard[0];
                    $booking = $mobileCard[1];
                @endphp
                <a href="{{ route('bookings.show', $booking) }}" class="block rounded-3xl border border-slate-200 bg-white p-4 shadow-lg shadow-slate-200/60">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-bold text-slate-500">{{ $unit->building->name }} / Unit {{ $unit->unit_no }}</p>
                            <h3 class="mt-1 text-lg font-black text-[#071a3b]">{{ $booking->tenant->full_name }}</h3>
                            <p class="mt-1 text-xs text-slate-500">{{ $booking->check_in_date->format('M d') }} to {{ $booking->check_out_date->format('M d, Y') }}</p>
                        </div>
                        <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-black text-blue-700">{{ str($booking->booking_status)->replace('_', ' ')->headline() }}</span>
                    </div>
                </a>
            @empty
                <div class="rounded-3xl border border-dashed border-slate-200 bg-white p-8 text-center text-sm text-slate-500">No active bookings in this calendar range.</div>
            @endforelse
        </div>
    </section>
</div>
</x-app-layout>
