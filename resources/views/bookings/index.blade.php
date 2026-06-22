<x-app-layout>
    <x-slot name="header"><div><p class="text-[11px] font-bold uppercase tracking-[0.22em] text-blue-600">Reservations</p><h1 class="text-2xl font-bold text-[#071a3b]">Bookings</h1></div></x-slot>

    @php
        $tenantPortal = auth()->user()->can('portal.tenant') && ! auth()->user()->can('bookings.manage');
    @endphp

    @if($tenantPortal)
        <div class="tenant-app-screen space-y-5">
            @if (session('status'))
                <div class="rounded-3xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-700">{{ session('status') }}</div>
            @endif
            <div class="flex items-center justify-between">
                <h1 class="text-3xl font-black tracking-[-0.04em] text-[#071a3b]">Bookings</h1>
                <a href="{{ route('support.index') }}" class="grid h-11 w-11 place-items-center rounded-2xl bg-white text-[#071a3b] shadow-sm">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></svg>
                </a>
            </div>
            <div class="flex gap-3 overflow-x-auto pb-1">
                @foreach(['' => 'Upcoming', 'checked_in' => 'Current', 'checked_out' => 'Past', 'cancelled' => 'Cancelled'] as $status => $label)
                    <a href="{{ route('bookings.index', $status ? ['status' => $status] : []) }}" class="shrink-0 rounded-2xl px-4 py-2 text-sm font-black {{ request('status') === $status || (!request('status') && $status === '') ? 'bg-blue-100 text-blue-600' : 'text-slate-500' }}">{{ $label }}</a>
                @endforeach
            </div>
            <div class="space-y-5">
                @forelse ($bookings as $booking)
                    @php
                        $nights = $booking->check_in_date->diffInDays($booking->check_out_date);
                    @endphp
                    <article class="overflow-hidden rounded-[1.55rem] bg-white shadow-[0_18px_45px_rgba(15,23,42,0.08)]">
                        <div class="relative h-40 bg-gradient-to-br from-slate-900 via-slate-700 to-blue-500">
                            <div class="absolute inset-0 opacity-35" style="background-image: radial-gradient(circle at 78% 18%, rgba(255,255,255,.65), transparent 24%), linear-gradient(135deg, rgba(255,255,255,.1) 0 25%, transparent 25% 50%, rgba(255,255,255,.07) 50% 75%, transparent 75%); background-size: auto, 42px 42px;"></div>
                            <span class="absolute right-4 top-4 rounded-xl {{ $booking->booking_status === 'confirmed' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }} px-3 py-1 text-xs font-black">{{ str($booking->booking_status)->replace('_', ' ')->headline() }}</span>
                        </div>
                        <div class="p-5">
                            <h2 class="text-xl font-black text-[#071a3b]">{{ $booking->unit->building->name }}</h2>
                            <p class="mt-1 text-sm font-semibold text-slate-500">Dubai, UAE</p>
                            <div class="mt-5 grid grid-cols-2 gap-4 border-b border-slate-100 pb-4">
                                <div><p class="text-sm font-semibold text-slate-500">Check-in</p><p class="font-black text-blue-600">{{ $booking->check_in_date->format('d M Y') }}</p><p class="text-sm text-slate-500">{{ $booking->check_in_time ? \Illuminate\Support\Carbon::parse($booking->check_in_time)->format('h:i A') : '03:00 PM' }}</p></div>
                                <div><p class="text-sm font-semibold text-slate-500">Check-out</p><p class="font-black text-blue-600">{{ $booking->check_out_date->format('d M Y') }}</p><p class="text-sm text-slate-500">{{ $booking->check_out_time ? \Illuminate\Support\Carbon::parse($booking->check_out_time)->format('h:i A') : '11:00 AM' }}</p></div>
                                <div><p class="text-sm font-semibold text-slate-500">Booking ID</p><p class="font-black text-blue-600">{{ $booking->booking_no }}</p></div>
                                <div><p class="text-sm font-semibold text-slate-500">Nights</p><p class="font-black text-[#071a3b]">{{ $nights }} Nights</p></div>
                            </div>
                            <a href="{{ route('bookings.show', $booking) }}" class="mt-4 flex h-12 items-center justify-center rounded-2xl bg-blue-50 text-sm font-black text-blue-600">View Details</a>
                        </div>
                    </article>
                @empty
                    <p class="rounded-3xl border border-dashed border-slate-200 bg-white px-4 py-10 text-center text-sm text-slate-500">No bookings found.</p>
                @endforelse
            </div>
            <div>{{ $bookings->links() }}</div>
        </div>
    @else
    <div class="space-y-6">
        @if (session('status'))<div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">{{ session('status') }}</div>@endif
        <div class="erp-card p-5">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div><h2 class="text-lg font-bold text-[#071a3b]">Booking registry</h2><p class="mt-1 text-sm text-slate-500">Holiday home and long-term bookings with fees, tasks, and notification logs.</p></div>
                @can('bookings.manage')<a href="{{ route('bookings.create') }}" class="inline-flex h-11 items-center justify-center rounded-xl bg-blue-600 px-4 text-sm font-bold text-white shadow-lg shadow-blue-600/20 hover:bg-blue-700">Add booking</a>@endcan
            </div>
            <form method="GET" class="mt-5 grid gap-3 md:grid-cols-[1fr_190px_auto]">
                <input name="search" value="{{ request('search') }}" placeholder="Search booking, tenant, unit..." class="erp-focus h-11 rounded-xl border border-slate-200 bg-[#f8faff] px-4 text-sm">
                <select name="status" class="erp-focus h-11 rounded-xl border border-slate-200 bg-white px-3 text-sm"><option value="">All statuses</option>@foreach (\App\Models\Booking::STATUSES as $status)<option value="{{ $status }}" @selected(request('status') === $status)>{{ str($status)->headline() }}</option>@endforeach</select>
                <button class="rounded-xl bg-slate-900 px-4 text-sm font-bold text-white">Filter</button>
            </form>
            <div class="mt-5 space-y-3 md:hidden">
                @forelse ($bookings as $booking)
                    <a href="{{ route('bookings.show', $booking) }}" class="block rounded-[1.5rem] border border-slate-200 bg-white p-4 shadow-sm active:scale-[0.99]">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-xs font-bold uppercase tracking-[0.16em] text-blue-600">{{ $booking->booking_no }}</p>
                                <h3 class="mt-1 truncate text-lg font-black text-[#071a3b]">{{ $booking->unit->building->name }}</h3>
                                <p class="mt-1 text-sm text-slate-500">Unit {{ $booking->unit->unit_no }} - {{ $booking->tenant->full_name }}</p>
                            </div>
                            <span class="shrink-0 rounded-full bg-blue-50 px-2.5 py-1 text-[11px] font-bold text-blue-700">{{ str($booking->booking_status)->replace('_', ' ')->headline() }}</span>
                        </div>
                        <div class="mt-4 grid grid-cols-2 gap-3">
                            <div class="rounded-2xl bg-slate-50 p-3">
                                <p class="text-[10px] font-bold uppercase text-slate-400">Check-in</p>
                                <p class="mt-1 text-sm font-bold text-[#071a3b]">{{ $booking->check_in_date->format('M d') }}</p>
                            </div>
                            <div class="rounded-2xl bg-slate-50 p-3">
                                <p class="text-[10px] font-bold uppercase text-slate-400">Check-out</p>
                                <p class="mt-1 text-sm font-bold text-[#071a3b]">{{ $booking->check_out_date->format('M d') }}</p>
                            </div>
                        </div>
                        <div class="mt-4 flex items-center justify-between">
                            <span class="text-sm font-black text-[#071a3b]">AED {{ number_format((float) $booking->total_amount, 2) }}</span>
                            <span class="rounded-xl bg-slate-900 px-4 py-2 text-xs font-black text-white">Open</span>
                        </div>
                    </a>
                @empty
                    <p class="rounded-2xl border border-dashed border-slate-200 px-4 py-10 text-center text-sm text-slate-500">No bookings found.</p>
                @endforelse
            </div>
            <div class="mt-5 hidden overflow-hidden rounded-2xl border border-slate-200 md:block">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-[11px] font-bold uppercase tracking-[0.16em] text-slate-500"><tr><th class="px-4 py-3">Booking</th><th class="px-4 py-3">Stay</th><th class="px-4 py-3">Fees</th><th class="px-4 py-3">Status</th><th class="px-4 py-3 text-right">Actions</th></tr></thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($bookings as $booking)
                            <tr>
                                <td class="px-4 py-4"><div class="font-bold text-[#071a3b]">{{ $booking->booking_no }}</div><div class="text-xs text-slate-500">{{ str($booking->booking_type)->replace('_', ' ')->headline() }}</div><div class="text-xs text-slate-400">{{ $booking->tenant->full_name }}</div></td>
                                <td class="px-4 py-4 text-xs text-slate-600"><div class="font-bold text-slate-700">{{ $booking->unit->building->name }} / {{ $booking->unit->unit_no }}</div><div>{{ $booking->check_in_date->format('M d, Y') }} to {{ $booking->check_out_date->format('M d, Y') }}</div></td>
                                <td class="px-4 py-4"><div class="font-bold text-[#071a3b]">AED {{ number_format((float) $booking->total_amount, 2) }}</div><div class="text-xs text-slate-500">Rent AED {{ number_format((float) $booking->rent_amount, 2) }}</div></td>
                                <td class="px-4 py-4"><span class="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-bold text-blue-700">{{ str($booking->booking_status)->headline() }}</span></td>
                                <td class="px-4 py-4"><div class="flex justify-end gap-2"><a href="{{ route('bookings.show', $booking) }}" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-bold text-slate-600">View</a>@can('bookings.manage')<a href="{{ route('bookings.edit', $booking) }}" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-bold text-slate-600">Edit</a>@endcan</div></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-10 text-center text-sm text-slate-500">No bookings found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-5">{{ $bookings->links() }}</div>
        </div>
    </div>
    @endif
</x-app-layout>
