@php
    $activeBooking = $unit->bookings->first();
    $displayStatus = $activeBooking
        ? 'Occupied'
        : (in_array($unit->availability_status, ['maintenance', 'blocked'], true)
            ? str($unit->availability_status)->headline()
            : 'Available');
@endphp

<a href="{{ route('units.show', $unit) }}" class="rounded-3xl border border-slate-200 bg-white p-4 hover:bg-slate-50">
    <div class="flex items-start justify-between gap-3">
        <div>
            <p class="text-xs font-bold text-slate-500">{{ $unit->building->name }}</p>
            <h3 class="mt-1 text-lg font-black text-[#071a3b]">Unit {{ $unit->unit_no }}</h3>
        </div>
        <span class="rounded-full {{ $activeBooking ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }} px-2.5 py-1 text-[11px] font-black">
            {{ $displayStatus }}
        </span>
    </div>
    <div class="mt-3 grid gap-2 text-xs font-semibold text-slate-500">
        <p>{{ $unit->unit_type }}</p>
        @if($activeBooking)
            <p class="rounded-2xl bg-blue-50 px-3 py-2 font-black text-blue-700">
                {{ $activeBooking->check_in_date?->format('M d, Y') }} to {{ $activeBooking->effective_check_out_date?->format('M d, Y') }}
            </p>
            <p>{{ $activeBooking->tenant?->full_name }} / {{ str($activeBooking->booking_status)->replace('_', ' ')->headline() }}</p>
        @else
            <p class="rounded-2xl bg-slate-50 px-3 py-2 font-bold text-slate-500">No active booking duration</p>
        @endif
    </div>
</a>
