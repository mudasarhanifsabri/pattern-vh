<x-app-layout>
<x-slot name="header">
    <div>
        <p class="text-[11px] font-bold uppercase tracking-[0.22em] text-blue-600">Invoice profile</p>
        <h1 class="text-2xl font-bold text-[#071a3b]">{{ $invoice->invoice_no }}</h1>
    </div>
</x-slot>

@php
    $paymentStatusClasses = [
        'pending' => 'bg-amber-50 text-amber-700 ring-amber-100',
        'approved' => 'bg-emerald-50 text-emerald-700 ring-emerald-100',
        'rejected' => 'bg-rose-50 text-rose-700 ring-rose-100',
    ];
@endphp

<div class="space-y-6" x-data="{ paymentModal: {{ $errors->hasAny(['method', 'amount', 'paid_at', 'reference_no', 'notes', 'payment_proof']) ? 'true' : 'false' }} }">
    @if (session('status'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ $errors->first() }}</div>
    @endif

    <div class="grid gap-5 xl:grid-cols-[1fr_380px]">
        <div class="space-y-5">
            <div class="erp-card p-5">
                <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-600">{{ str($invoice->status)->headline() }}</p>
                        <h2 class="mt-1 text-2xl font-black text-[#071a3b]">AED {{ number_format((float) $invoice->total_amount, 2) }}</h2>
                        <p class="mt-1 text-sm text-slate-500">{{ $invoice->tenant->full_name }} / {{ $invoice->booking->booking_no }}</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('invoices.pdf', $invoice) }}" target="_blank" class="rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-bold text-white">Invoice PDF</a>
                        @can('payments.manage')
                            <button type="button" x-on:click="paymentModal = true" class="rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-bold text-white">Record payment</button>
                        @endcan
                        @can('invoices.manage')
                            <a href="{{ route('invoices.edit', $invoice) }}" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-bold text-slate-600">Edit</a>
                        @endcan
                    </div>
                </div>
                <dl class="mt-6 grid gap-4 md:grid-cols-3">
                    <div class="rounded-2xl bg-slate-50 p-4">
                        <dt class="text-xs font-bold uppercase text-slate-400">Approved paid</dt>
                        <dd class="font-bold text-[#071a3b]">AED {{ number_format((float) $invoice->paid_amount, 2) }}</dd>
                    </div>
                    <div class="rounded-2xl bg-slate-50 p-4">
                        <dt class="text-xs font-bold uppercase text-slate-400">Balance</dt>
                        <dd class="font-bold text-[#071a3b]">AED {{ number_format((float) $invoice->balance_amount, 2) }}</dd>
                    </div>
                    <div class="rounded-2xl bg-slate-50 p-4">
                        <dt class="text-xs font-bold uppercase text-slate-400">Due</dt>
                        <dd class="font-bold text-[#071a3b]">{{ $invoice->due_date?->format('M d, Y') ?? 'On receipt' }}</dd>
                    </div>
                </dl>
            </div>

            <div class="erp-card p-5">
                <h2 class="text-lg font-bold text-[#071a3b]">Invoice charges</h2>
                <div class="mt-4 overflow-hidden rounded-2xl border border-slate-200">
                    <table class="min-w-full divide-y divide-slate-100 text-sm">
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @foreach([
                                'Rent' => $invoice->rent_amount,
                                'VAT 5% on rent only' => $invoice->vat_amount,
                                'Security deposit' => $invoice->deposit_amount,
                                'DTCM fee' => $invoice->dtcm_fee,
                                'Cleaning fee' => $invoice->cleaning_fee,
                                'Agency fee' => $invoice->agency_fee,
                            ] as $label => $amount)
                                <tr>
                                    <td class="px-4 py-3 font-bold text-slate-600">{{ $label }}</td>
                                    <td class="px-4 py-3 text-right font-black text-[#071a3b]">AED {{ number_format((float) $amount, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <p class="mt-3 rounded-2xl bg-blue-50 px-4 py-3 text-xs font-bold text-blue-700">VAT is charged only on rent. Deposit, DTCM, cleaning, and agency fee are not included in VAT calculation.</p>
            </div>

            <div class="erp-card overflow-hidden">
                <div class="border-b border-slate-100 p-5">
                    <h2 class="text-lg font-bold text-[#071a3b]">Payments, proof, and receipts</h2>
                    <p class="mt-1 text-sm text-slate-500">Recorded payments stay pending until finance verifies that money arrived.</p>
                </div>

                <div class="divide-y divide-slate-100">
                    @forelse ($invoice->payments as $payment)
                        <div class="p-5">
                            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h3 class="font-bold text-[#071a3b]">{{ $payment->payment_no }}</h3>
                                        <span class="rounded-full px-2.5 py-1 text-xs font-bold ring-1 {{ $paymentStatusClasses[$payment->status] ?? 'bg-slate-50 text-slate-600 ring-slate-100' }}">{{ str($payment->status)->headline() }}</span>
                                        <span class="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-bold text-blue-700">{{ str($payment->method)->replace('_', ' ')->headline() }}</span>
                                    </div>
                                    <p class="mt-1 text-xs text-slate-500">AED {{ number_format((float) $payment->amount, 2) }} / {{ $payment->paid_at->format('M d, Y H:i') }}</p>
                                    @if ($payment->reference_no)
                                        <p class="mt-1 text-xs text-slate-500">Reference: {{ $payment->reference_no }}</p>
                                    @endif
                                    @if ($payment->verification_notes)
                                        <p class="mt-3 rounded-2xl bg-slate-50 p-3 text-xs text-slate-600">{{ $payment->verification_notes }}</p>
                                    @endif
                                </div>

                                <div class="flex flex-wrap justify-start gap-2 lg:justify-end">
                                    @can('payments.view')
                                        @if ($payment->proof_path)
                                            <a href="{{ route('payments.proof', $payment) }}" target="_blank" class="rounded-xl border border-slate-200 px-3 py-2 text-xs font-bold text-slate-600">View proof</a>
                                        @else
                                            <span class="rounded-xl border border-dashed border-slate-200 px-3 py-2 text-xs font-bold text-slate-400">No proof attached</span>
                                        @endif
                                    @endcan
                                    @if ($payment->receipt)
                                        <a href="{{ route('receipts.pdf', $payment->receipt) }}" target="_blank" class="rounded-xl bg-emerald-600 px-3 py-2 text-xs font-bold text-white">Receipt PDF / {{ $payment->receipt->check_in_code }}</a>
                                    @endif
                                </div>
                            </div>

                            @can('payments.manage')
                                @if ($payment->status === 'pending')
                                    <div class="mt-4 grid gap-3 lg:grid-cols-2">
                                        <form method="POST" action="{{ route('payments.approve', $payment) }}" class="rounded-2xl border border-emerald-100 bg-emerald-50 p-3">
                                            @csrf
                                            <textarea name="verification_notes" rows="2" class="erp-focus w-full rounded-xl border border-emerald-100 bg-white px-3 py-2 text-xs" placeholder="Approval note, e.g. bank credit confirmed"></textarea>
                                            <button class="mt-2 w-full rounded-xl bg-emerald-600 px-4 py-2.5 text-xs font-bold text-white">Approve payment</button>
                                        </form>
                                        <form method="POST" action="{{ route('payments.reject', $payment) }}" class="rounded-2xl border border-rose-100 bg-rose-50 p-3">
                                            @csrf
                                            <textarea name="verification_notes" rows="2" class="erp-focus w-full rounded-xl border border-rose-100 bg-white px-3 py-2 text-xs" placeholder="Reason, e.g. proof unclear or amount not received"></textarea>
                                            <button class="mt-2 w-full rounded-xl bg-rose-600 px-4 py-2.5 text-xs font-bold text-white">Reject payment</button>
                                        </form>
                                    </div>
                                @endif
                            @endcan
                        </div>
                    @empty
                        <p class="px-4 py-10 text-center text-sm text-slate-500">No payments yet.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="space-y-5">
            @can('payments.manage')
                <div class="erp-card p-5">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h2 class="text-lg font-bold text-[#071a3b]">Payment action</h2>
                            <p class="mt-1 text-sm leading-6 text-slate-500">Cash, bank transfer, and card machine payments are recorded first, then approved by finance.</p>
                        </div>
                        <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-bold text-blue-700">Popup</span>
                    </div>
                    <button type="button" x-on:click="paymentModal = true" class="mt-5 flex w-full items-center justify-center gap-2 rounded-2xl bg-blue-600 px-4 py-3 text-sm font-black text-white shadow-lg shadow-blue-600/20">
                        <span class="text-lg leading-none">+</span>
                        Record payment
                    </button>
                    <div class="mt-4 grid grid-cols-2 gap-3 text-xs">
                        <div class="rounded-2xl bg-slate-50 p-3">
                            <p class="font-bold uppercase text-slate-400">Balance</p>
                            <p class="mt-1 font-black text-[#071a3b]">AED {{ number_format((float) $invoice->balance_amount, 2) }}</p>
                        </div>
                        <div class="rounded-2xl bg-slate-50 p-3">
                            <p class="font-bold uppercase text-slate-400">Status</p>
                            <p class="mt-1 font-black text-[#071a3b]">{{ str($invoice->status)->headline() }}</p>
                        </div>
                    </div>
                </div>
            @endcan

            <div class="erp-card p-5">
                <h2 class="text-lg font-bold text-[#071a3b]">Approval workflow</h2>
                <div class="mt-4 space-y-3 text-sm text-slate-600">
                    <p class="rounded-2xl bg-amber-50 p-4">1. Team records payment and attaches proof.</p>
                    <p class="rounded-2xl bg-blue-50 p-4">2. Finance checks bank/card/cash collection and approves.</p>
                    <p class="rounded-2xl bg-emerald-50 p-4">3. If invoice becomes fully paid, receipt code email queues, booking confirms, and DTCM check-in is prepared.</p>
                    <p class="rounded-2xl bg-slate-50 p-4">4. Booking becomes checked-in only after the guest is registered in the DTCM portal.</p>
                </div>
            </div>
        </div>
    </div>

    @can('payments.manage')
        <div x-cloak x-show="paymentModal" x-transition.opacity class="fixed inset-0 z-50 grid place-items-center bg-slate-950/60 p-4 backdrop-blur-sm">
            <div x-show="paymentModal" x-transition x-on:click.outside="paymentModal = false" class="max-h-[92vh] w-full max-w-2xl overflow-y-auto rounded-[1.6rem] bg-white shadow-2xl shadow-slate-950/30">
                <div class="sticky top-0 z-10 flex items-start justify-between gap-4 border-b border-slate-100 bg-white/95 p-5 backdrop-blur">
                    <div>
                        <p class="text-[11px] font-black uppercase tracking-[0.18em] text-blue-600">Payment verification</p>
                        <h2 class="mt-1 text-xl font-black text-[#071a3b]">Record payment</h2>
                        <p class="mt-1 text-sm text-slate-500">{{ $invoice->invoice_no }} / Balance AED {{ number_format((float) $invoice->balance_amount, 2) }}</p>
                    </div>
                    <button type="button" x-on:click="paymentModal = false" class="grid h-10 w-10 place-items-center rounded-2xl border border-slate-200 text-slate-500 hover:bg-slate-50">
                        <span class="text-xl leading-none">&times;</span>
                    </button>
                </div>

                <form method="POST" action="{{ route('invoices.payments.store', $invoice) }}" enctype="multipart/form-data" class="p-5">
                    @csrf
                    <div class="grid gap-4 md:grid-cols-2">
                        <label>
                            <span class="text-xs font-black uppercase tracking-[0.14em] text-slate-400">Payment method</span>
                            <select name="method" class="erp-focus mt-1 h-12 w-full rounded-2xl border border-slate-200 bg-white px-3 text-sm">
                                @foreach (\App\Models\Payment::METHODS as $method)
                                    <option value="{{ $method }}" @selected(old('method') === $method)>{{ str($method)->replace('_', ' ')->headline() }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label>
                            <span class="text-xs font-black uppercase tracking-[0.14em] text-slate-400">Amount</span>
                            <x-text-input name="amount" class="mt-1 block h-12 w-full rounded-2xl" :value="old('amount', $invoice->balance_amount)" placeholder="Amount" />
                        </label>
                        <label>
                            <span class="text-xs font-black uppercase tracking-[0.14em] text-slate-400">Paid at</span>
                            <x-text-input name="paid_at" type="datetime-local" class="mt-1 block h-12 w-full rounded-2xl" :value="old('paid_at', now()->format('Y-m-d\\TH:i'))" />
                        </label>
                        <label>
                            <span class="text-xs font-black uppercase tracking-[0.14em] text-slate-400">Reference no</span>
                            <x-text-input name="reference_no" class="mt-1 block h-12 w-full rounded-2xl" :value="old('reference_no')" placeholder="Bank/Card/Cash reference" />
                        </label>
                    </div>

                    <label class="mt-4 block rounded-[1.25rem] border border-dashed border-blue-200 bg-blue-50/60 px-4 py-6 text-center">
                        <span class="block text-sm font-black text-[#071a3b]">Attach payment proof</span>
                        <span class="mt-1 block text-xs leading-5 text-slate-500">Bank slip, card receipt, cash collection photo, PDF/JPG/PNG up to 10 MB</span>
                        <input type="file" name="payment_proof" accept=".pdf,.jpg,.jpeg,.png,.webp" class="mt-4 w-full rounded-xl border border-slate-200 bg-white p-2 text-sm">
                    </label>

                    <label class="mt-4 block">
                        <span class="text-xs font-black uppercase tracking-[0.14em] text-slate-400">Payment notes</span>
                        <textarea name="notes" rows="3" class="erp-focus mt-1 w-full rounded-2xl border border-slate-200 px-3 py-2 text-sm" placeholder="Payment notes">{{ old('notes') }}</textarea>
                    </label>

                    <div class="mt-5 rounded-2xl bg-amber-50 p-4 text-sm leading-6 text-amber-800">
                        This payment will be saved as <strong>pending verification</strong>. Receipt is issued only after finance approves it.
                    </div>

                    <div class="sticky bottom-0 -mx-5 mt-5 flex justify-end gap-3 border-t border-slate-100 bg-white/95 p-5 backdrop-blur">
                        <button type="button" x-on:click="paymentModal = false" class="rounded-xl border border-slate-200 px-5 py-3 text-sm font-black text-slate-600">Cancel</button>
                        <button class="rounded-xl bg-blue-600 px-5 py-3 text-sm font-black text-white">Save as pending verification</button>
                    </div>
                </form>
            </div>
        </div>
    @endcan
</div>
</x-app-layout>
