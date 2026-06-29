<x-app-layout>
<x-slot name="header">
    <div>
        <p class="text-[11px] font-bold uppercase tracking-[0.22em] text-blue-600">Payments</p>
        <h1 class="text-2xl font-bold text-[#071a3b]">Request payment collection</h1>
    </div>
</x-slot>

@php
    $statusClasses = [
        'requested' => 'bg-blue-50 text-blue-700',
        'scheduled' => 'bg-amber-50 text-amber-700',
        'collected_pending_verification' => 'bg-purple-50 text-purple-700',
        'approved' => 'bg-emerald-50 text-emerald-700',
        'rejected' => 'bg-rose-50 text-rose-700',
        'cancelled' => 'bg-slate-100 text-slate-600',
    ];
@endphp

<div class="space-y-5">
    @if (session('status'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ $errors->first() }}</div>
    @endif

    <div class="flex items-center justify-between">
        <a href="{{ route('dashboard') }}" class="grid h-11 w-11 place-items-center rounded-2xl bg-white text-slate-700 shadow-sm ring-1 ring-slate-200">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M15 19l-7-7 7-7" /></svg>
        </a>
        <h1 class="text-base font-black text-[#0b1736]">Payment Collection</h1>
        <span class="grid h-11 w-11 place-items-center rounded-2xl bg-white text-blue-600 shadow-sm ring-1 ring-slate-200">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 10h18M7 15h.01M11 15h2M5 6h14a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2z" /></svg>
        </span>
    </div>

    <section class="overflow-hidden rounded-[1.8rem] bg-gradient-to-br from-blue-600 via-blue-600 to-[#061a38] p-5 text-white shadow-2xl shadow-blue-600/20">
        <p class="text-xs font-bold uppercase tracking-[0.22em] text-blue-100">Tenant payments</p>
        <h2 class="mt-3 text-2xl font-black tracking-[-0.04em]">Request doorstep collection</h2>
        <p class="mt-2 text-sm leading-6 text-blue-100">Cash or debit card machine collection from your apartment. Finance will approve after payment proof is verified.</p>
    </section>

    <div class="space-y-5">
        <div class="rounded-[1.8rem] bg-white p-5 shadow-[0_16px_40px_rgba(15,23,42,0.08)] ring-1 ring-slate-100">
            <h2 class="text-lg font-black text-[#0b1736]">Collection request</h2>
            <p class="mt-1 text-sm font-semibold text-slate-500">Choose invoice, method, and your preferred visit time.</p>

            <form method="POST" action="{{ route('tenant.payment-requests.store') }}" class="mt-5 space-y-4">
                @csrf
                <div>
                    <x-input-label for="invoice_id" value="Invoice" />
                    <select id="invoice_id" name="invoice_id" class="erp-focus mt-1 h-12 w-full rounded-2xl border border-slate-200 bg-white px-4 text-sm font-semibold" required>
                        <option value="">Select unpaid invoice</option>
                        @foreach ($invoices as $invoice)
                            <option value="{{ $invoice->id }}" @selected(old('invoice_id') == $invoice->id)>
                                {{ $invoice->invoice_no }} - AED {{ number_format((float) $invoice->balance_amount, 2) }} - {{ $invoice->booking->unit->building->name }} / {{ $invoice->booking->unit->unit_no }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="grid gap-4">
                    <div>
                        <x-input-label for="collection_method" value="Collection method" />
                        <select id="collection_method" name="collection_method" class="erp-focus mt-1 h-12 w-full rounded-2xl border border-slate-200 bg-white px-4 text-sm font-semibold" required>
                            <option value="cash">Cash from doorstep</option>
                            <option value="card_machine">Debit/card machine at doorstep</option>
                        </select>
                    </div>
                    <div>
                        <x-input-label for="amount" value="Amount" />
                        <x-text-input id="amount" name="amount" class="mt-1 block h-12 w-full" :value="old('amount')" placeholder="AED amount" required />
                    </div>
                    <div>
                        <x-input-label for="preferred_date" value="Preferred date" />
                        <x-text-input id="preferred_date" name="preferred_date" type="date" class="mt-1 block h-12 w-full" :value="old('preferred_date')" />
                    </div>
                    <div>
                        <x-input-label for="preferred_time_window" value="Preferred time" />
                        <x-text-input id="preferred_time_window" name="preferred_time_window" class="mt-1 block h-12 w-full" :value="old('preferred_time_window')" placeholder="Example: 6 PM - 8 PM" />
                    </div>
                </div>

                <div class="grid gap-4">
                    <div>
                        <x-input-label for="contact_mobile" value="Mobile / WhatsApp" />
                        <x-text-input id="contact_mobile" name="contact_mobile" class="mt-1 block h-12 w-full" :value="old('contact_mobile', $tenant->mobile_no)" required />
                    </div>
                    <label class="mt-7 flex h-12 items-center gap-2 rounded-xl border border-slate-200 px-3 text-sm font-semibold text-slate-600">
                        <input type="checkbox" name="contact_has_whatsapp" value="1" class="rounded border-slate-300 text-blue-600" checked>
                        WhatsApp available
                    </label>
                </div>

                <div>
                    <x-input-label for="collection_address" value="Collection address / apartment" />
                    <textarea id="collection_address" name="collection_address" rows="3" class="erp-focus mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" required>{{ old('collection_address') }}</textarea>
                </div>

                <div>
                    <x-input-label for="tenant_notes" value="Notes for collection team" />
                    <textarea id="tenant_notes" name="tenant_notes" rows="3" class="erp-focus mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">{{ old('tenant_notes') }}</textarea>
                </div>

                <button class="h-14 w-full rounded-2xl bg-blue-600 px-4 py-3 text-sm font-black text-white shadow-xl shadow-blue-600/20 active:scale-[0.99]">Send collection request</button>
            </form>
        </div>

        <div class="rounded-[1.8rem] bg-white p-5 shadow-sm ring-1 ring-slate-100">
            <h2 class="text-lg font-black text-[#0b1736]">How it works</h2>
            <div class="mt-4 space-y-3 text-sm font-semibold text-slate-600">
                <p class="rounded-2xl bg-blue-50 p-4">1. Submit request from your tenant app.</p>
                <p class="rounded-2xl bg-amber-50 p-4">2. Pattern schedules a team member with cash/card machine.</p>
                <p class="rounded-2xl bg-emerald-50 p-4">3. Receipt is issued after finance verifies collection.</p>
            </div>
        </div>
    </div>

    <div class="overflow-hidden rounded-[1.8rem] bg-white shadow-sm ring-1 ring-slate-100">
        <div class="border-b border-slate-100 p-5">
            <h2 class="text-lg font-black text-[#0b1736]">My requests</h2>
        </div>
        <div class="space-y-3 p-4">
            @forelse ($requests as $requestRecord)
                <div class="rounded-[1.5rem] border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h3 class="text-base font-black text-[#0b1736]">{{ $requestRecord->request_no }}</h3>
                            <p class="mt-1 text-sm text-slate-500">{{ str($requestRecord->collection_method)->replace('_', ' ')->headline() }} / AED {{ number_format((float) $requestRecord->amount, 2) }}</p>
                        </div>
                        <span class="w-fit rounded-full px-2.5 py-1 text-xs font-bold {{ $statusClasses[$requestRecord->status] ?? 'bg-slate-100 text-slate-600' }}">{{ str($requestRecord->status)->replace('_', ' ')->headline() }}</span>
                    </div>
                    <div class="mt-4 grid grid-cols-2 gap-3">
                        <div class="rounded-2xl bg-slate-50 p-3"><p class="text-[10px] font-bold uppercase text-slate-400">Invoice</p><p class="mt-1 text-sm font-bold text-[#071a3b]">{{ $requestRecord->invoice->invoice_no }}</p></div>
                        <div class="rounded-2xl bg-slate-50 p-3"><p class="text-[10px] font-bold uppercase text-slate-400">Unit</p><p class="mt-1 text-sm font-bold text-[#071a3b]">{{ $requestRecord->booking->unit->unit_no }}</p></div>
                    </div>
                    @if ($requestRecord->payment?->receipt)
                        <a href="{{ route('receipts.pdf', ['receipt' => $requestRecord->payment->receipt, 'download' => 1]) }}" download class="mt-3 inline-flex rounded-xl bg-emerald-600 px-3 py-2 text-xs font-bold text-white">Download receipt / {{ $requestRecord->payment->receipt->check_in_code }}</a>
                    @endif
                </div>
            @empty
                <p class="px-4 py-10 text-center text-sm text-slate-500">No requests yet.</p>
            @endforelse
        </div>
    </div>
</div>
</x-app-layout>
