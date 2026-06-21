<x-app-layout>
<x-slot name="header"><div><p class="text-[11px] font-bold uppercase tracking-[0.22em] text-blue-600">Finance</p><h1 class="text-2xl font-bold text-[#071a3b]">Security Deposits</h1></div></x-slot>

@php
    $statusClasses = [
        'pending_inspection' => 'bg-amber-50 text-amber-700',
        'tenant_review' => 'bg-blue-50 text-blue-700',
        'accepted' => 'bg-emerald-50 text-emerald-700',
        'refund_processing' => 'bg-violet-50 text-violet-700',
        'refunded' => 'bg-slate-100 text-slate-700',
    ];
@endphp

<div class="space-y-6">
    <div class="grid gap-4 md:grid-cols-4">
        @foreach ([
            ['label' => 'Held deposits', 'value' => 'AED '.number_format((float) $stats['held'], 2)],
            ['label' => 'Pending review', 'value' => 'AED '.number_format((float) $stats['pending_review'], 2)],
            ['label' => 'Damage deductions', 'value' => 'AED '.number_format((float) $stats['damage'], 2)],
            ['label' => 'Refunded', 'value' => 'AED '.number_format((float) $stats['refunded'], 2)],
        ] as $card)
            <div class="erp-card p-5"><p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">{{ $card['label'] }}</p><p class="mt-3 text-2xl font-black tracking-[-0.04em] text-[#071a3b]">{{ $card['value'] }}</p></div>
        @endforeach
    </div>

    <div class="grid gap-5 xl:grid-cols-[1fr_360px]">
        <section class="erp-card p-5">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div><h2 class="text-lg font-bold text-[#071a3b]">Deposit refund workflow</h2><p class="mt-1 text-sm text-slate-500">Inspection, tenant acceptance, damage deduction, and refund processing.</p></div>
                <form method="GET"><select name="status" class="erp-focus h-11 rounded-xl border border-slate-200 bg-white px-3 text-sm" onchange="this.form.submit()"><option value="">All statuses</option>@foreach(\App\Models\BookingDepositRefund::STATUSES as $status)<option value="{{ $status }}" @selected(request('status') === $status)>{{ str($status)->replace('_', ' ')->headline() }}</option>@endforeach</select></form>
            </div>

            <div class="mt-5 space-y-3 md:hidden">
                @forelse($refunds as $refund)
                    <a href="{{ route('bookings.show', $refund->booking) }}" class="block rounded-[1.5rem] border border-slate-200 bg-white p-4 shadow-sm"><div class="flex items-start justify-between gap-3"><div><p class="text-xs font-bold uppercase tracking-[0.16em] text-blue-600">{{ $refund->booking->booking_no }}</p><h3 class="mt-1 text-lg font-black text-[#071a3b]">{{ $refund->tenant->full_name }}</h3><p class="mt-1 text-sm text-slate-500">{{ $refund->booking->unit->building->name }} / Unit {{ $refund->booking->unit->unit_no }}</p></div><span class="rounded-full px-2.5 py-1 text-[11px] font-bold {{ $statusClasses[$refund->status] ?? 'bg-slate-50 text-slate-600' }}">{{ str($refund->status)->replace('_', ' ')->headline() }}</span></div><div class="mt-4 grid grid-cols-3 gap-2 text-center text-xs"><div class="rounded-2xl bg-slate-50 p-3"><p class="font-bold text-slate-400">Deposit</p><p class="mt-1 font-black text-[#071a3b]">{{ number_format((float) $refund->deposit_amount, 0) }}</p></div><div class="rounded-2xl bg-rose-50 p-3"><p class="font-bold text-rose-400">Damage</p><p class="mt-1 font-black text-rose-700">{{ number_format((float) $refund->damage_amount, 0) }}</p></div><div class="rounded-2xl bg-emerald-50 p-3"><p class="font-bold text-emerald-500">Refund</p><p class="mt-1 font-black text-emerald-700">{{ number_format((float) $refund->refund_amount, 0) }}</p></div></div></a>
                @empty
                    <p class="rounded-2xl border border-dashed border-slate-200 px-4 py-10 text-center text-sm text-slate-500">No deposit refund records.</p>
                @endforelse
            </div>

            <div class="mt-5 hidden overflow-hidden rounded-2xl border border-slate-200 md:block">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-[11px] font-bold uppercase tracking-[0.16em] text-slate-500"><tr><th class="px-4 py-3">Booking</th><th class="px-4 py-3">Tenant</th><th class="px-4 py-3">Amounts</th><th class="px-4 py-3">Status</th><th class="px-4 py-3 text-right">Action</th></tr></thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse($refunds as $refund)
                            <tr><td class="px-4 py-4"><div class="font-bold text-[#071a3b]">{{ $refund->booking->booking_no }}</div><div class="text-xs text-slate-500">{{ $refund->booking->unit->building->name }} / {{ $refund->booking->unit->unit_no }}</div></td><td class="px-4 py-4 text-slate-600">{{ $refund->tenant->full_name }}</td><td class="px-4 py-4"><div class="font-bold text-[#071a3b]">Deposit AED {{ number_format((float) $refund->deposit_amount, 2) }}</div><div class="text-xs text-slate-500">Damage AED {{ number_format((float) $refund->damage_amount, 2) }} / Refund AED {{ number_format((float) $refund->refund_amount, 2) }}</div></td><td class="px-4 py-4"><span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $statusClasses[$refund->status] ?? 'bg-slate-50 text-slate-600' }}">{{ str($refund->status)->replace('_', ' ')->headline() }}</span></td><td class="px-4 py-4 text-right"><a href="{{ route('bookings.show', $refund->booking) }}" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-bold text-slate-600">Open booking</a></td></tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-10 text-center text-sm text-slate-500">No deposit refund records.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-5">{{ $refunds->links() }}</div>
        </section>

        <aside class="erp-card p-5">
            <h2 class="text-lg font-bold text-[#071a3b]">Active deposit ledger</h2>
            <p class="mt-1 text-sm text-slate-500">Bookings holding a security deposit now.</p>
            <div class="mt-4 space-y-3">
                @forelse($activeBookings as $booking)
                    <a href="{{ route('bookings.show', $booking) }}" class="block rounded-2xl border border-slate-200 p-4 hover:bg-slate-50"><div class="flex items-center justify-between gap-3"><div><p class="font-bold text-[#071a3b]">{{ $booking->booking_no }}</p><p class="mt-1 text-xs text-slate-500">{{ $booking->tenant->full_name }} / {{ $booking->unit->building->name }} {{ $booking->unit->unit_no }}</p></div><span class="font-black text-[#071a3b]">AED {{ number_format((float) $booking->deposit_amount, 2) }}</span></div><p class="mt-2 text-xs text-slate-500">Refund workflow: {{ $booking->depositRefund ? str($booking->depositRefund->status)->replace('_', ' ')->headline() : 'Not started' }}</p></a>
                @empty
                    <p class="rounded-2xl border border-dashed border-slate-200 px-4 py-8 text-center text-sm text-slate-500">No active deposits.</p>
                @endforelse
            </div>
        </aside>
    </div>
</div>
</x-app-layout>
