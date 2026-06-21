<x-app-layout>
<x-slot name="header"><div><p class="text-[11px] font-bold uppercase tracking-[0.22em] text-blue-600">Finance</p><h1 class="text-2xl font-bold text-[#071a3b]">Payments</h1></div></x-slot>

@php
    $statusClasses = [
        'pending' => 'bg-amber-50 text-amber-700',
        'approved' => 'bg-emerald-50 text-emerald-700',
        'rejected' => 'bg-rose-50 text-rose-700',
    ];
@endphp

<div class="space-y-6">
    <div class="grid gap-4 md:grid-cols-4">
        @foreach ([
            ['label' => 'Approved received', 'value' => 'AED '.number_format((float) $stats['approved'], 2), 'tone' => 'emerald'],
            ['label' => 'Pending verification', 'value' => 'AED '.number_format((float) $stats['pending'], 2), 'tone' => 'amber'],
            ['label' => 'Open balance', 'value' => 'AED '.number_format((float) $stats['open_balance'], 2), 'tone' => 'blue'],
            ['label' => 'Overdue', 'value' => 'AED '.number_format((float) $stats['overdue'], 2), 'tone' => 'rose'],
        ] as $card)
            <div class="erp-card p-5">
                <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">{{ $card['label'] }}</p>
                <p class="mt-3 text-2xl font-black tracking-[-0.04em] text-[#071a3b]">{{ $card['value'] }}</p>
            </div>
        @endforeach
    </div>

    <div class="grid gap-5 xl:grid-cols-[1fr_360px]">
        <section class="erp-card p-5">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div><h2 class="text-lg font-bold text-[#071a3b]">Payment registry</h2><p class="mt-1 text-sm text-slate-500">Cash, bank transfer, card machine, and Stripe placeholder payments.</p></div>
                @can('invoices.manage')<a href="{{ route('invoices.index') }}" class="inline-flex h-11 items-center justify-center rounded-xl bg-blue-600 px-4 text-sm font-bold text-white">Open invoices</a>@endcan
            </div>

            <form method="GET" class="mt-5 grid gap-3 lg:grid-cols-[1fr_160px_190px_auto]">
                <input name="search" value="{{ request('search') }}" placeholder="Search payment, invoice, tenant, reference..." class="erp-focus h-11 rounded-xl border border-slate-200 bg-[#f8faff] px-4 text-sm">
                <select name="status" class="erp-focus h-11 rounded-xl border border-slate-200 bg-white px-3 text-sm">
                    <option value="">All statuses</option>
                    @foreach (\App\Models\Payment::STATUSES as $status)<option value="{{ $status }}" @selected(request('status') === $status)>{{ str($status)->headline() }}</option>@endforeach
                </select>
                <select name="method" class="erp-focus h-11 rounded-xl border border-slate-200 bg-white px-3 text-sm">
                    <option value="">All methods</option>
                    @foreach (\App\Models\Payment::METHODS as $method)<option value="{{ $method }}" @selected(request('method') === $method)>{{ $method === 'stripe_placeholder' ? 'Stripe placeholder' : str($method)->replace('_', ' ')->headline() }}</option>@endforeach
                </select>
                <button class="rounded-xl bg-slate-900 px-4 text-sm font-bold text-white">Filter</button>
            </form>

            <div class="mt-5 space-y-3 md:hidden">
                @forelse ($payments as $payment)
                    <a href="{{ route('invoices.show', $payment->invoice) }}" class="block rounded-[1.5rem] border border-slate-200 bg-white p-4 shadow-sm">
                        <div class="flex items-start justify-between gap-3"><div><p class="text-xs font-bold uppercase tracking-[0.16em] text-blue-600">{{ $payment->payment_no }}</p><h3 class="mt-1 text-lg font-black text-[#071a3b]">AED {{ number_format((float) $payment->amount, 2) }}</h3><p class="mt-1 text-sm text-slate-500">{{ $payment->invoice->tenant->full_name }}</p></div><span class="rounded-full px-2.5 py-1 text-[11px] font-bold {{ $statusClasses[$payment->status] ?? 'bg-slate-50 text-slate-600' }}">{{ str($payment->status)->headline() }}</span></div>
                        <p class="mt-3 text-xs text-slate-500">{{ $payment->paid_at->format('M d, Y H:i') }} / {{ $payment->method === 'stripe_placeholder' ? 'Stripe placeholder' : str($payment->method)->replace('_', ' ')->headline() }}</p>
                    </a>
                @empty
                    <p class="rounded-2xl border border-dashed border-slate-200 px-4 py-10 text-center text-sm text-slate-500">No payments found.</p>
                @endforelse
            </div>

            <div class="mt-5 hidden overflow-hidden rounded-2xl border border-slate-200 md:block">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-[11px] font-bold uppercase tracking-[0.16em] text-slate-500"><tr><th class="px-4 py-3">Payment</th><th class="px-4 py-3">Invoice / Tenant</th><th class="px-4 py-3">Method</th><th class="px-4 py-3">Amount</th><th class="px-4 py-3">Status</th><th class="px-4 py-3 text-right">Actions</th></tr></thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($payments as $payment)
                            <tr>
                                <td class="px-4 py-4"><div class="font-bold text-[#071a3b]">{{ $payment->payment_no }}</div><div class="text-xs text-slate-500">{{ $payment->paid_at->format('M d, Y H:i') }}</div></td>
                                <td class="px-4 py-4"><div class="font-bold text-[#071a3b]">{{ $payment->invoice->invoice_no }}</div><div class="text-xs text-slate-500">{{ $payment->invoice->tenant->full_name }}</div></td>
                                <td class="px-4 py-4 text-slate-600">{{ $payment->method === 'stripe_placeholder' ? 'Stripe placeholder' : str($payment->method)->replace('_', ' ')->headline() }}</td>
                                <td class="px-4 py-4 font-bold text-[#071a3b]">AED {{ number_format((float) $payment->amount, 2) }}</td>
                                <td class="px-4 py-4"><span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $statusClasses[$payment->status] ?? 'bg-slate-50 text-slate-600' }}">{{ str($payment->status)->headline() }}</span></td>
                                <td class="px-4 py-4"><div class="flex justify-end gap-2">@if($payment->proof_path)<a href="{{ route('payments.proof', $payment) }}" target="_blank" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-bold text-slate-600">Proof</a>@endif @if($payment->receipt)<a href="{{ route('receipts.pdf', $payment->receipt) }}" target="_blank" class="rounded-lg bg-emerald-600 px-3 py-2 text-xs font-bold text-white">Receipt</a>@endif<a href="{{ route('invoices.show', $payment->invoice) }}" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-bold text-slate-600">Invoice</a></div></td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-10 text-center text-sm text-slate-500">No payments found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-5">{{ $payments->links() }}</div>
        </section>

        <aside class="space-y-5">
            <div class="erp-card p-5">
                <h2 class="text-lg font-bold text-[#071a3b]">Stripe placeholder</h2>
                <p class="mt-2 text-sm leading-6 text-slate-500">Stripe is reserved for the future online payment link flow. For now, mark these as pending until real gateway integration is connected.</p>
            </div>
            <div class="erp-card p-5">
                <h2 class="text-lg font-bold text-[#071a3b]">Unpaid and overdue</h2>
                <form method="GET" class="mt-4"><select name="invoice_status" class="erp-focus h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm" onchange="this.form.submit()"><option value="">Open invoices</option><option value="unpaid" @selected(request('invoice_status') === 'unpaid')>Unpaid</option><option value="partially_paid" @selected(request('invoice_status') === 'partially_paid')>Partially paid</option><option value="overdue" @selected(request('invoice_status') === 'overdue')>Overdue</option></select></form>
                <div class="mt-4 space-y-3">
                    @forelse($openInvoices as $invoice)
                        <a href="{{ route('invoices.show', $invoice) }}" class="block rounded-2xl border border-slate-200 p-4 hover:bg-slate-50"><div class="flex items-center justify-between gap-3"><div><p class="font-bold text-[#071a3b]">{{ $invoice->invoice_no }}</p><p class="mt-1 text-xs text-slate-500">{{ $invoice->tenant->full_name }}</p></div><span class="text-sm font-black text-amber-700">AED {{ number_format((float) $invoice->balance_amount, 2) }}</span></div><p class="mt-2 text-xs text-slate-500">Due {{ $invoice->due_date?->format('M d, Y') ?? 'on receipt' }}</p></a>
                    @empty
                        <p class="rounded-2xl border border-dashed border-slate-200 px-4 py-8 text-center text-sm text-slate-500">No open invoices.</p>
                    @endforelse
                </div>
            </div>
        </aside>
    </div>
</div>
</x-app-layout>
