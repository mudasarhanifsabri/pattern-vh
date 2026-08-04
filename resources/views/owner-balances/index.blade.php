<x-app-layout>
    <x-slot name="header"><div><p class="text-[11px] font-bold uppercase tracking-[0.22em] text-blue-600">Accounting</p><h1 class="text-2xl font-bold text-[#071a3b]">Owner Balances</h1></div></x-slot>

    <div class="space-y-5">
        <section class="grid gap-4 md:grid-cols-3">
            <article class="overflow-hidden rounded-3xl bg-gradient-to-br from-[#071a3b] to-blue-700 p-5 text-white shadow-xl shadow-blue-950/15"><p class="text-xs font-bold uppercase tracking-[0.14em] text-blue-200">Total payable to owners</p><p class="mt-3 text-3xl font-black">AED {{ number_format($totalPayable, 2) }}</p></article>
            <article class="erp-card p-5"><p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-400">Owner owes Pattern</p><p class="mt-3 text-2xl font-black text-rose-600">AED {{ number_format($totalReceivable, 2) }}</p></article>
            <article class="erp-card p-5"><p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-400">Rows</p><p class="mt-3 text-2xl font-black text-[#071a3b]">{{ $rows->count() }}</p></article>
        </section>

        <section class="erp-card p-4">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div class="inline-flex w-fit rounded-2xl bg-slate-100 p-1">
                    <a href="{{ route('owner-balances.index', ['group_by' => 'owner', 'search' => request('search')]) }}" class="rounded-xl px-4 py-2.5 text-sm font-black {{ $groupBy === 'owner' ? 'bg-white text-blue-700 shadow-sm' : 'text-slate-500' }}">Owner-wise</a>
                    <a href="{{ route('owner-balances.index', ['group_by' => 'unit', 'search' => request('search')]) }}" class="rounded-xl px-4 py-2.5 text-sm font-black {{ $groupBy === 'unit' ? 'bg-white text-blue-700 shadow-sm' : 'text-slate-500' }}">Unit-wise</a>
                </div>
                <div class="flex flex-col gap-2 sm:flex-row">
                    <form method="GET" class="flex gap-2"><input type="hidden" name="group_by" value="{{ $groupBy }}"><input name="search" value="{{ request('search') }}" placeholder="Search owner" class="erp-focus h-11 min-w-0 rounded-xl border border-slate-200 px-3 text-sm"><button class="h-11 rounded-xl bg-[#071a3b] px-4 text-sm font-black text-white">Search</button></form>
                    <a href="{{ route('owner-balances.index', ['group_by' => $groupBy, 'search' => request('search'), 'export' => 1]) }}" class="inline-flex h-11 items-center justify-center rounded-xl border border-emerald-200 bg-emerald-50 px-4 text-sm font-black text-emerald-700">Download CSV</a>
                </div>
            </div>
        </section>

        <section class="erp-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-[11px] font-black uppercase tracking-[0.12em] text-slate-500"><tr><th class="px-4 py-3">Owner</th>@if($groupBy === 'unit')<th class="px-4 py-3">Property / Unit</th>@else<th class="px-4 py-3">Units</th>@endif<th class="px-4 py-3 text-right">Payable</th><th class="px-4 py-3 text-right">Owner owes</th><th class="px-4 py-3 text-right">Net balance</th><th class="px-4 py-3 text-right">Ledger</th></tr></thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse($rows as $row)
                            <tr class="hover:bg-slate-50"><td class="px-4 py-4"><p class="font-black text-[#071a3b]">{{ $row['owner']->full_name }}</p><p class="mt-1 text-xs text-slate-500">{{ $row['owner']->email ?: $row['owner']->mobile_no }}</p></td>@if($groupBy === 'unit')<td class="px-4 py-4"><p class="font-bold text-[#071a3b]">{{ $row['unit']->building?->name }}</p><p class="text-xs text-blue-600">Unit {{ $row['unit']->unit_no }}</p></td>@else<td class="px-4 py-4 font-bold text-slate-600">{{ $row['owner']->units_count }}</td>@endif<td class="px-4 py-4 text-right font-black text-emerald-700">{{ $row['balance'] > 0 ? 'AED '.number_format($row['balance'], 2) : '—' }}</td><td class="px-4 py-4 text-right font-black text-rose-600">{{ $row['balance'] < 0 ? 'AED '.number_format(abs($row['balance']), 2) : '—' }}</td><td class="px-4 py-4 text-right font-black {{ $row['balance'] < 0 ? 'text-rose-600' : 'text-[#071a3b]' }}">AED {{ number_format($row['balance'], 2) }}</td><td class="px-4 py-4 text-right"><a href="{{ route('owners.account.index', array_filter([$row['owner'], 'unit_id' => $row['unit']?->id])) }}" class="rounded-xl bg-blue-50 px-3 py-2 text-xs font-black text-blue-700">Open</a></td></tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-12 text-center text-slate-500">No owners match this search.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-app-layout>
