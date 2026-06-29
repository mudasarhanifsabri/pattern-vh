<x-app-layout>
    @php
        $isDue = (float) $invoice->balance_amount > 0;
        $booking = $invoice->booking;
    @endphp

    <div class="space-y-5">
        <section class="rounded-[1.6rem] bg-white p-5 shadow-[0_18px_45px_rgba(15,23,42,0.08)] ring-1 ring-slate-100">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-xs font-black uppercase tracking-[0.18em] text-blue-600">{{ $invoice->invoice_no }}</p>
                    <h1 class="mt-2 text-2xl font-black tracking-[-0.04em] text-[#071a3b]">AED {{ number_format((float) $invoice->total_amount, 2) }}</h1>
                    <p class="mt-1 text-sm font-semibold leading-5 text-slate-500">{{ $booking?->unit?->building?->name }} / Unit {{ $booking?->unit?->unit_no }}</p>
                </div>
                <span class="shrink-0 rounded-full {{ $isDue ? 'bg-amber-50 text-amber-700' : 'bg-emerald-50 text-emerald-700' }} px-3 py-1 text-xs font-black">{{ $isDue ? 'Pending' : 'Paid' }}</span>
            </div>
            <div class="mt-5 grid grid-cols-3 gap-2 text-center">
                <div class="rounded-2xl bg-slate-50 p-3">
                    <p class="text-[10px] font-black uppercase text-slate-400">Paid</p>
                    <p class="mt-1 text-sm font-black text-emerald-700">AED {{ number_format((float) $invoice->paid_amount, 0) }}</p>
                </div>
                <div class="rounded-2xl bg-amber-50 p-3">
                    <p class="text-[10px] font-black uppercase text-amber-500">Due</p>
                    <p class="mt-1 text-sm font-black text-amber-700">AED {{ number_format((float) $invoice->balance_amount, 0) }}</p>
                </div>
                <div class="rounded-2xl bg-blue-50 p-3">
                    <p class="text-[10px] font-black uppercase text-blue-500">Due date</p>
                    <p class="mt-1 text-xs font-black text-blue-700">{{ $invoice->due_date?->format('M d') ?? 'Now' }}</p>
                </div>
            </div>
            <div class="mt-4 grid grid-cols-2 gap-3">
                <a href="{{ route('invoices.pdf', $invoice) }}" target="_blank" class="rounded-2xl bg-blue-600 px-4 py-3 text-center text-sm font-black text-white">Invoice PDF</a>
                <a href="{{ route('tenant.payment-requests.index') }}" class="rounded-2xl bg-slate-900 px-4 py-3 text-center text-sm font-black text-white">Request collection</a>
            </div>
        </section>

        <section class="rounded-[1.6rem] bg-white p-5 shadow-[0_18px_45px_rgba(15,23,42,0.08)] ring-1 ring-slate-100">
            <h2 class="text-lg font-black text-[#071a3b]">Charges</h2>
            <div class="mt-4 divide-y divide-slate-100 text-sm">
                @foreach([
                    'Rent' => $invoice->rent_amount,
                    'VAT 5% on rent' => $invoice->vat_amount,
                    'Security deposit' => $invoice->deposit_amount,
                    'DTCM fee' => $invoice->dtcm_fee,
                    'Cleaning fee' => $invoice->cleaning_fee,
                    'Agency fee' => $invoice->agency_fee,
                ] as $label => $amount)
                    @if((float) $amount > 0)
                        <div class="flex items-center justify-between gap-3 py-3">
                            <span class="font-semibold text-slate-500">{{ $label }}</span>
                            <span class="font-black text-[#071a3b]">AED {{ number_format((float) $amount, 2) }}</span>
                        </div>
                    @endif
                @endforeach
            </div>
        </section>

        <section class="rounded-[1.6rem] bg-white p-5 shadow-[0_18px_45px_rgba(15,23,42,0.08)] ring-1 ring-slate-100">
            <h2 class="text-lg font-black text-[#071a3b]">Payments & Receipts</h2>
            <div class="mt-4 space-y-3">
                @forelse($invoice->payments as $payment)
                    <div class="rounded-2xl border border-slate-100 bg-slate-50 p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-black text-[#071a3b]">{{ $payment->payment_no }}</p>
                                <p class="mt-1 text-xs font-semibold text-slate-500">AED {{ number_format((float) $payment->amount, 2) }} / {{ $payment->paid_at?->format('M d, Y') }}</p>
                            </div>
                            <span class="rounded-full {{ $payment->status === 'approved' ? 'bg-emerald-50 text-emerald-700' : ($payment->status === 'rejected' ? 'bg-rose-50 text-rose-700' : 'bg-amber-50 text-amber-700') }} px-3 py-1 text-xs font-black">{{ str($payment->status)->headline() }}</span>
                        </div>
                        @if($payment->receipt)
                            <a href="{{ route('receipts.pdf', $payment->receipt) }}" target="_blank" class="mt-3 flex items-center justify-between rounded-2xl bg-emerald-600 px-4 py-3 text-sm font-black text-white">
                                <span>Download receipt</span>
                                <span>{{ $payment->receipt->receipt_no }}</span>
                            </a>
                        @else
                            <p class="mt-3 rounded-2xl bg-white px-4 py-3 text-xs font-bold text-slate-500">Receipt will appear here after finance approves the payment.</p>
                        @endif
                    </div>
                @empty
                    <p class="rounded-2xl border border-dashed border-slate-200 px-4 py-8 text-center text-sm text-slate-500">No payments recorded yet.</p>
                @endforelse
            </div>
        </section>
    </div>
</x-app-layout>
