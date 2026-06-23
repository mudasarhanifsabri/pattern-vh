<x-app-layout>
<x-slot name="header"><div><p class="text-[11px] font-bold uppercase tracking-[0.22em] text-blue-600">Accounting</p><h1 class="text-2xl font-bold text-[#071a3b]">Accounting command center</h1></div></x-slot>

<div class="space-y-6">
    <div class="grid gap-4 md:grid-cols-4">
        @foreach ([
            ['label' => 'Approved revenue', 'value' => 'AED '.number_format((float) $stats['revenue'], 2)],
            ['label' => 'Open receivables', 'value' => 'AED '.number_format((float) $stats['open_balance'], 2)],
            ['label' => 'Expenses', 'value' => 'AED '.number_format((float) $stats['expenses'], 2)],
            ['label' => 'Owner units', 'value' => $stats['owner_units']],
        ] as $card)
            <article class="erp-card p-5"><p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">{{ $card['label'] }}</p><p class="mt-3 text-2xl font-black tracking-[-0.04em] text-[#071a3b]">{{ $card['value'] }}</p></article>
        @endforeach
    </div>

    <section class="grid gap-5 xl:grid-cols-[1fr_360px]">
        <div class="erp-card p-5">
            <div class="flex items-center justify-between"><div><h2 class="text-lg font-bold text-[#071a3b]">Accounting workflows</h2><p class="mt-1 text-sm text-slate-500">Expenses, owner statements, reports, invoices, payments, and deposits.</p></div></div>
            <div class="mt-5 grid gap-3 md:grid-cols-3">
                @foreach ([
                    ['label' => 'Add expense', 'route' => 'expenses.create', 'can' => 'expenses.manage'],
                    ['label' => 'Bank reconciliation', 'route' => 'bank-reconciliation.index', 'can' => 'bank-reconciliation.view'],
                    ['label' => 'Owner statements', 'route' => 'owner-statements.index', 'can' => 'owner-statements.view'],
                    ['label' => 'Owner payouts', 'route' => 'owner-payouts.index', 'can' => 'owner-payouts.view'],
                    ['label' => 'Reports & export', 'route' => 'reports.index', 'can' => 'reports.view'],
                    ['label' => 'Invoices', 'route' => 'invoices.index', 'can' => 'invoices.view'],
                    ['label' => 'Payments', 'route' => 'payments.index', 'can' => 'payments.view'],
                    ['label' => 'Security deposits', 'route' => 'security-deposits.index', 'can' => 'security-deposits.view'],
                ] as $action)
                    @can($action['can'])
                        <a href="{{ route($action['route']) }}" class="rounded-3xl border border-slate-200 bg-white p-5 text-sm font-black text-[#071a3b] shadow-sm hover:border-blue-200 hover:bg-blue-50">{{ $action['label'] }}</a>
                    @endcan
                @endforeach
            </div>
        </div>

        <aside class="erp-card p-5">
            <h2 class="text-lg font-bold text-[#071a3b]">Recent expenses</h2>
            <div class="mt-4 space-y-3">
                @forelse($recentExpenses as $expense)
                    <a href="{{ route('expenses.show', $expense) }}" class="block rounded-2xl border border-slate-200 p-4 hover:bg-slate-50"><div class="flex items-center justify-between gap-3"><div><p class="font-bold text-[#071a3b]">{{ $expense->name }}</p><p class="mt-1 text-xs text-slate-500">{{ $expense->owner?->full_name ?? str($expense->expense_to_role)->headline() }}</p></div><span class="text-sm font-black text-rose-700">AED {{ number_format((float) $expense->amount, 2) }}</span></div></a>
                @empty
                    <p class="rounded-2xl border border-dashed border-slate-200 px-4 py-8 text-center text-sm text-slate-500">No expenses yet.</p>
                @endforelse
            </div>
        </aside>
    </section>
</div>
</x-app-layout>
