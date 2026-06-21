<x-app-layout>
    <x-slot name="header"><div><p class="text-[11px] font-bold uppercase tracking-[0.22em] text-blue-600">Reservations</p><h1 class="text-2xl font-bold text-[#071a3b]">Bookings</h1></div></x-slot>

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
</x-app-layout>
