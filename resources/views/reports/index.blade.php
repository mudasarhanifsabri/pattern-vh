<x-app-layout>
@php
    $ownerOnly = auth()->user()?->can('portal.owner')
        && ! auth()->user()?->can('accounting.view')
        && ! auth()->user()?->can('accounting.manage')
        && ! auth()->user()?->can('users.manage');
@endphp
<x-slot name="header">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-[11px] font-bold uppercase tracking-[0.22em] text-blue-600">
                {{ $ownerReport ? 'Owner portal' : 'Accounting intelligence' }}
            </p>
            <h1 class="text-3xl font-black tracking-[-0.04em] text-[#071a3b]">
                {{ $ownerReport ? 'Owner Income Report' : 'Reports & Profit/Loss' }}
            </h1>
            <p class="mt-1 text-sm text-slate-500">
                {{ $ownerReport ? 'Income shows collected rent only for your linked properties.' : 'Revenue excludes VAT and refundable security deposits.' }}
            </p>
        </div>
        <form method="GET" class="flex flex-wrap items-end gap-2">
            <label class="text-xs font-bold text-slate-500">
                From
                <input type="date" name="from" value="{{ $from->format('Y-m-d') }}" class="erp-focus mt-1 block rounded-xl border-slate-200 text-sm">
            </label>
            <label class="text-xs font-bold text-slate-500">
                To
                <input type="date" name="to" value="{{ $to->format('Y-m-d') }}" class="erp-focus mt-1 block rounded-xl border-slate-200 text-sm">
            </label>
            <button class="rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-black text-white">Run report</button>
        </form>
    </div>
</x-slot>

@php
    $profitRows = $ownerReport
        ? [
            ['Collected rent income', $profitLoss['rent']],
            ['Owner expenses', -$profitLoss['expenses']],
            ['Net owner income', $profitLoss['net']],
        ]
        : [
            ['Rent revenue', $profitLoss['rent']],
            ['Service and operating fees', $profitLoss['fees']],
            ['Total operating revenue', $profitLoss['revenue']],
            ['Operating expenses', -$profitLoss['expenses']],
            ['Net profit / loss', $profitLoss['net']],
        ];
@endphp

<div class="{{ $ownerOnly ? 'tenant-app-screen' : '' }} space-y-5">
    @if($ownerReport)
        <section class="overflow-hidden rounded-[1.6rem] bg-white shadow-[0_18px_45px_rgba(15,23,42,0.08)] md:hidden">
            <div class="bg-gradient-to-br from-slate-950 via-slate-800 to-blue-700 p-5 text-white">
                <p class="text-xs font-black uppercase tracking-[0.16em] text-blue-100">Owner income</p>
                <h2 class="mt-2 text-2xl font-black leading-tight">AED {{ number_format((float) $profitLoss['net'], 0) }}</h2>
                <p class="mt-1 text-sm font-semibold text-white/70">{{ $from->format('M d') }} to {{ $to->format('M d, Y') }}</p>
            </div>
            <form method="GET" class="grid gap-3 p-4">
                <div class="grid grid-cols-2 gap-2">
                    <label class="text-xs font-bold text-slate-500">From<input type="date" name="from" value="{{ $from->format('Y-m-d') }}" class="erp-focus mt-1 block h-12 w-full rounded-2xl border-slate-200 text-sm"></label>
                    <label class="text-xs font-bold text-slate-500">To<input type="date" name="to" value="{{ $to->format('Y-m-d') }}" class="erp-focus mt-1 block h-12 w-full rounded-2xl border-slate-200 text-sm"></label>
                </div>
                <button class="h-12 rounded-2xl bg-blue-600 px-4 text-sm font-black text-white">Run report</button>
            </form>
        </section>
    @endif

    <section class="grid gap-3 {{ $ownerOnly ? 'grid-cols-2' : 'md:grid-cols-4' }}">
        @foreach($cards as $card)
            <article class="{{ $ownerOnly ? 'rounded-[1.35rem] bg-white p-4 shadow-[0_14px_30px_rgba(15,23,42,0.07)]' : 'erp-card p-5' }}">
                <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">{{ $card['name'] }}</p>
                <p class="mt-3 text-xl font-black {{ $card['value'] < 0 ? 'text-rose-600' : 'text-[#071a3b]' }}">AED {{ number_format((float) $card['value'], $ownerOnly ? 0 : 2) }}</p>
                <p class="mt-1 text-xs text-slate-500">{{ $card['note'] }}</p>
                @if($ownerReport || auth()->user()?->can('reports.export'))
                    <a href="{{ route('reports.export', ['type' => $card['type'], 'from' => $from->format('Y-m-d'), 'to' => $to->format('Y-m-d')]) }}" class="mt-3 inline-flex text-xs font-black text-blue-600">CSV</a>
                @endif
            </article>
        @endforeach
    </section>

    <section class="grid gap-5 xl:grid-cols-[1.4fr_1fr]">
        <article class="{{ $ownerOnly ? 'overflow-hidden rounded-[1.6rem] bg-white shadow-[0_18px_45px_rgba(15,23,42,0.08)]' : 'erp-card overflow-hidden' }}">
            <div class="flex items-center justify-between border-b border-slate-100 p-5">
                <div>
                    <h2 class="text-lg font-black text-[#071a3b]">{{ $ownerReport ? 'Owner income statement' : 'Profit & loss statement' }}</h2>
                    <p class="text-xs text-slate-500">{{ $from->format('M d, Y') }} to {{ $to->format('M d, Y') }}</p>
                </div>
                @if($ownerReport || auth()->user()?->can('reports.export'))
                    <a class="rounded-xl bg-[#071a3b] px-4 py-2 text-xs font-black text-white" href="{{ route('reports.export', ['type' => 'profit_loss', 'from' => $from->format('Y-m-d'), 'to' => $to->format('Y-m-d')]) }}">
                        {{ $ownerReport ? 'Download income' : 'Download P&L' }}
                    </a>
                @endif
            </div>

            <dl class="divide-y divide-slate-100">
                @foreach($profitRows as [$label, $value])
                    <div class="flex items-center justify-between px-5 py-4 {{ str_starts_with($label, 'Net') ? 'bg-slate-50' : '' }}">
                        <dt class="text-sm {{ str_starts_with($label, 'Total') || str_starts_with($label, 'Net') ? 'font-black text-[#071a3b]' : 'font-bold text-slate-500' }}">{{ $label }}</dt>
                        <dd class="text-sm font-black {{ $value < 0 ? 'text-rose-600' : 'text-[#071a3b]' }}">AED {{ number_format((float) $value, 2) }}</dd>
                    </div>
                @endforeach
            </dl>

            @unless($ownerReport)
                <div class="grid gap-3 border-t border-slate-100 p-5 sm:grid-cols-2">
                    <div class="rounded-2xl bg-blue-50 p-4">
                        <p class="text-xs font-bold text-blue-700">VAT payable</p>
                        <p class="mt-1 text-xl font-black text-[#071a3b]">AED {{ number_format($profitLoss['vat'], 2) }}</p>
                        <p class="text-xs text-slate-500">5% on rent, excluded from income</p>
                    </div>
                    <div class="rounded-2xl bg-violet-50 p-4">
                        <p class="text-xs font-bold text-violet-700">Refundable deposits</p>
                        <p class="mt-1 text-xl font-black text-[#071a3b]">AED {{ number_format($profitLoss['deposits'], 2) }}</p>
                        <p class="text-xs text-slate-500">Customer liability, excluded from income</p>
                    </div>
                </div>
            @else
                <p class="border-t border-slate-100 p-5 text-xs leading-5 text-slate-500">
                    Service fees, VAT, and security deposits are company or tenant liability amounts and are not shown as owner income.
                </p>
            @endunless
        </article>

        <article class="{{ $ownerOnly ? 'rounded-[1.6rem] bg-white p-5 shadow-[0_18px_45px_rgba(15,23,42,0.08)]' : 'erp-card p-5' }}">
            <h2 class="text-lg font-black text-[#071a3b]">{{ $ownerReport ? 'Owner expense breakdown' : 'Expense breakdown' }}</h2>
            <div class="mt-4 space-y-3">
                @forelse($expenseBreakdown as $row)
                    <div class="flex items-center justify-between rounded-2xl bg-slate-50 px-4 py-3">
                        <span class="text-sm font-bold text-slate-600">{{ str($row->type)->replace('_', ' ')->headline() }}</span>
                        <span class="text-sm font-black text-[#071a3b]">AED {{ number_format((float) $row->total, 2) }}</span>
                    </div>
                @empty
                    <p class="rounded-2xl border border-dashed border-slate-200 p-6 text-center text-sm text-slate-400">No expenses in this period.</p>
                @endforelse
            </div>
        </article>
    </section>

    <section class="{{ $ownerOnly ? 'rounded-[1.6rem] bg-white p-5 shadow-[0_18px_45px_rgba(15,23,42,0.08)]' : 'erp-card p-5' }}">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-lg font-black text-[#071a3b]">Detailed exports</h2>
                <p class="text-xs text-slate-500">{{ $ownerReport ? 'Exports are limited to your linked properties.' : 'Filtered by the selected reporting period.' }}</p>
            </div>
            <div class="grid w-full grid-cols-2 gap-2 sm:flex sm:w-auto sm:flex-wrap">
                @foreach(['bookings' => 'Bookings', 'invoices' => 'Invoices', 'payments' => 'Payments', 'expenses' => 'Expenses'] as $type => $label)
                    <a href="{{ route('reports.export', ['type' => $type, 'from' => $from->format('Y-m-d'), 'to' => $to->format('Y-m-d')]) }}" class="rounded-xl border border-slate-200 px-4 py-3 text-center text-xs font-black text-slate-600 hover:bg-slate-50">{{ $label }} CSV</a>
                @endforeach
            </div>
        </div>
    </section>
</div>
</x-app-layout>
