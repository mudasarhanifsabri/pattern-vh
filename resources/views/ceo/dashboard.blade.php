<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div><p class="text-[11px] font-bold uppercase tracking-[0.22em] text-blue-600">Executive workspace</p><h1 class="text-3xl font-black tracking-[-0.04em] text-[#071a3b]">CEO overview</h1><p class="mt-1 text-sm text-slate-500">Profit, cash, liabilities, occupancy, and decisions in one view.</p></div>
            <form class="flex flex-wrap items-end gap-2" method="GET"><label class="text-xs font-bold text-slate-500">From<input type="date" name="from" value="{{ $from->format('Y-m-d') }}" class="erp-focus mt-1 block rounded-xl border-slate-200 text-sm"></label><label class="text-xs font-bold text-slate-500">To<input type="date" name="to" value="{{ $to->format('Y-m-d') }}" class="erp-focus mt-1 block rounded-xl border-slate-200 text-sm"></label><button class="rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-black text-white">Apply</button></form>
        </div>
    </x-slot>

    <div class="space-y-5">
        <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            @foreach([
                ['Revenue', $metrics['revenue'], 'Rent + operating fees, excluding VAT/deposits', 'blue'],
                ['Net profit', $metrics['netProfit'], $metrics['margin'].'% margin', $metrics['netProfit'] >= 0 ? 'emerald' : 'rose'],
                ['Cash collected', $metrics['collections'], 'Approved payments in period', 'cyan'],
                ['Outstanding', $metrics['outstanding'], 'Open customer invoice balances', 'amber'],
            ] as [$label,$value,$note,$tone])
                <article class="erp-card p-5"><div class="flex items-center justify-between"><p class="text-xs font-black uppercase tracking-[0.16em] text-slate-400">{{ $label }}</p><span class="h-2.5 w-2.5 rounded-full bg-{{ $tone }}-500"></span></div><p class="mt-3 text-2xl font-black text-[#071a3b]">AED {{ number_format((float)$value, 2) }}</p><p class="mt-1 text-xs text-slate-500">{{ $note }}</p></article>
            @endforeach
        </section>

        <section class="grid gap-3 md:grid-cols-4">
            @foreach($alerts as $alert)<a href="{{ route($alert['route']) }}" class="erp-card flex items-center justify-between p-4 transition hover:-translate-y-0.5"><div><p class="text-xs font-bold text-slate-500">{{ $alert['label'] }}</p><p class="mt-1 text-2xl font-black text-[#071a3b]">{{ $alert['value'] }}</p></div><span class="grid h-9 w-9 place-items-center rounded-xl bg-{{ $alert['tone'] }}-50 text-{{ $alert['tone'] }}-600">→</span></a>@endforeach
        </section>

        <section class="grid gap-5 xl:grid-cols-[1.5fr_1fr]">
            <article class="erp-card p-5"><div class="flex items-start justify-between"><div><h2 class="text-lg font-black text-[#071a3b]">Six-month performance</h2><p class="text-xs text-slate-500">Accrual revenue against recorded expenses</p></div><a href="{{ route('reports.index', ['from'=>$from->format('Y-m-d'),'to'=>$to->format('Y-m-d')]) }}" class="text-xs font-black text-blue-600">Full P&amp;L →</a></div><div class="mt-8 flex h-64 items-end gap-4 border-b border-slate-200 px-2">@foreach($months as $month)<div class="flex h-full flex-1 flex-col justify-end"><div class="flex flex-1 items-end justify-center gap-1"><span class="w-2/5 rounded-t-lg bg-blue-500" style="height: {{ max(3, ($month['revenue']/$maxChart)*100) }}%"></span><span class="w-2/5 rounded-t-lg bg-amber-300" style="height: {{ max(3, ($month['expenses']/$maxChart)*100) }}%"></span></div><p class="py-2 text-center text-[10px] font-bold text-slate-400">{{ $month['label'] }}</p></div>@endforeach</div><div class="mt-3 flex gap-4 text-xs font-bold text-slate-500"><span>● <b class="text-blue-600">Revenue</b></span><span>● <b class="text-amber-500">Expenses</b></span></div></article>
            <article class="erp-card p-5"><h2 class="text-lg font-black text-[#071a3b]">Financial position</h2><dl class="mt-4 space-y-3">@foreach([['Rent revenue',$metrics['rentRevenue']],['Service revenue',$metrics['serviceRevenue']],['Operating expenses',-$metrics['expenses']],['VAT payable',$metrics['vatLiability']],['Refundable deposits',$metrics['depositLiability']],['Occupancy',$metrics['occupancy'].'%']] as [$label,$value])<div class="flex items-center justify-between rounded-2xl bg-slate-50 px-4 py-3"><dt class="text-sm font-bold text-slate-500">{{ $label }}</dt><dd class="text-sm font-black {{ is_numeric($value) && $value < 0 ? 'text-rose-600' : 'text-[#071a3b]' }}">{{ is_numeric($value) ? 'AED '.number_format((float)$value,2) : $value }}</dd></div>@endforeach</dl><p class="mt-4 rounded-2xl bg-blue-50 p-3 text-xs leading-5 text-blue-800">VAT and refundable deposits are shown as liabilities, not company revenue.</p></article>
        </section>

        <section class="grid gap-5 xl:grid-cols-2"><article class="erp-card overflow-hidden"><div class="border-b border-slate-100 p-5"><h2 class="font-black text-[#071a3b]">Recent expenses</h2></div><div class="divide-y divide-slate-100">@forelse($recentExpenses as $expense)<div class="flex items-center justify-between px-5 py-3"><div><p class="text-sm font-bold text-[#071a3b]">{{ $expense->name }}</p><p class="text-xs text-slate-400">{{ $expense->incurred_on?->format('M d, Y') }} · {{ $expense->unit?->unit_no ?? 'Company' }}</p></div><p class="text-sm font-black text-rose-600">AED {{ number_format((float)$expense->amount,2) }}</p></div>@empty<p class="p-6 text-sm text-slate-400">No expenses in the ledger.</p>@endforelse</div></article><article class="erp-card p-5"><h2 class="font-black text-[#071a3b]">Executive shortcuts</h2><div class="mt-4 grid gap-3 sm:grid-cols-2">@foreach([['Profit & loss','reports.index'],['Owner payouts','owner-payouts.index'],['Invoices','invoices.index'],['Planning sheet','planning-sheet.index']] as [$label,$route])<a href="{{ route($route) }}" class="rounded-2xl border border-slate-200 p-4 text-sm font-black text-[#071a3b] transition hover:border-blue-300 hover:bg-blue-50">{{ $label }} <span class="float-right text-blue-600">→</span></a>@endforeach</div></article></section>
    </div>
</x-app-layout>
