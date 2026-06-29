<x-app-layout>
    <div class="space-y-5">
        @if (session('status'))
            <div class="rounded-3xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-700">{{ session('status') }}</div>
        @endif

        <section class="overflow-hidden rounded-[1.6rem] bg-gradient-to-br from-slate-950 via-slate-800 to-amber-500 p-5 text-white shadow-[0_18px_45px_rgba(15,23,42,0.12)]">
            <p class="text-xs font-black uppercase tracking-[0.18em] text-amber-100">Tenant payments</p>
            <h1 class="mt-3 text-[2rem] font-black leading-tight tracking-[-0.04em]">Invoices & Receipts</h1>
            <p class="mt-2 text-sm font-semibold leading-6 text-white/75">View pending dues, approved payments, and receipt PDFs for your stay.</p>
            <div class="mt-5 grid grid-cols-2 gap-3">
                <div class="rounded-2xl bg-white/12 p-4 backdrop-blur">
                    <p class="text-[10px] font-black uppercase text-white/60">Balance due</p>
                    <p class="mt-1 text-xl font-black">AED {{ number_format($balanceDue, 0) }}</p>
                </div>
                <div class="rounded-2xl bg-white/12 p-4 backdrop-blur">
                    <p class="text-[10px] font-black uppercase text-white/60">Paid</p>
                    <p class="mt-1 text-xl font-black">AED {{ number_format($paidTotal, 0) }}</p>
                </div>
            </div>
        </section>

        <section class="space-y-3">
            @forelse($invoices as $invoice)
                @php
                    $isDue = $invoice->balance_amount > 0;
                    $receiptCount = $invoice->payments->filter(fn ($payment) => $payment->receipt)->count();
                @endphp
                <a href="{{ route('tenant.invoices.show', $invoice) }}" class="block rounded-[1.45rem] bg-white p-4 shadow-[0_14px_32px_rgba(15,23,42,0.07)] ring-1 ring-slate-100 active:scale-[0.99]">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-xs font-black uppercase tracking-[0.16em] text-blue-600">{{ $invoice->invoice_no }}</p>
                            <h2 class="mt-1 text-lg font-black text-[#071a3b]">AED {{ number_format((float) $invoice->total_amount, 2) }}</h2>
                            <p class="mt-1 text-sm font-semibold leading-5 text-slate-500">{{ $invoice->booking?->unit?->building?->name }} / Unit {{ $invoice->booking?->unit?->unit_no }}</p>
                        </div>
                        <span class="shrink-0 rounded-full {{ $isDue ? 'bg-amber-50 text-amber-700' : 'bg-emerald-50 text-emerald-700' }} px-3 py-1 text-[11px] font-black">{{ $isDue ? 'Pending' : 'Paid' }}</span>
                    </div>
                    <div class="mt-4 grid grid-cols-3 gap-2 text-center">
                        <div class="rounded-2xl bg-slate-50 p-3">
                            <p class="text-[10px] font-black uppercase text-slate-400">Paid</p>
                            <p class="mt-1 text-sm font-black text-emerald-700">AED {{ number_format((float) $invoice->paid_amount, 0) }}</p>
                        </div>
                        <div class="rounded-2xl bg-amber-50 p-3">
                            <p class="text-[10px] font-black uppercase text-amber-500">Due</p>
                            <p class="mt-1 text-sm font-black text-amber-700">AED {{ number_format((float) $invoice->balance_amount, 0) }}</p>
                        </div>
                        <div class="rounded-2xl bg-blue-50 p-3">
                            <p class="text-[10px] font-black uppercase text-blue-500">Receipts</p>
                            <p class="mt-1 text-sm font-black text-blue-700">{{ $receiptCount }}</p>
                        </div>
                    </div>
                    <div class="mt-4 flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-500">Due {{ $invoice->due_date?->format('M d, Y') ?? 'on receipt' }}</span>
                        <span class="rounded-xl bg-slate-900 px-4 py-2 text-xs font-black text-white">Open</span>
                    </div>
                </a>
            @empty
                <div class="rounded-[1.45rem] border border-dashed border-slate-200 bg-white px-4 py-10 text-center shadow-sm">
                    <h2 class="text-lg font-black text-[#071a3b]">No invoices yet</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-500">Your invoices and receipt PDFs will appear here once your booking payment is recorded.</p>
                </div>
            @endforelse
        </section>

        <div>{{ $invoices->links() }}</div>
    </div>
</x-app-layout>
