<x-app-layout>
<x-slot name="header"><div><p class="text-[11px] font-bold uppercase tracking-[0.22em] text-blue-600">Accounting</p><h1 class="text-2xl font-bold text-[#071a3b]">Bank reconciliation</h1></div></x-slot>

<div class="space-y-6">
    @if(session('status'))<div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-700">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-bold text-rose-700">{{ $errors->first() }}</div>@endif

    <div class="grid gap-4 md:grid-cols-5">
        @foreach([
            ['Unmatched', $stats['unmatched'], 'amber'],
            ['Suggestions', $stats['suggested'], 'blue'],
            ['Matched', $stats['matched'], 'emerald'],
            ['Credits', 'AED '.number_format((float)$stats['credits'], 2), 'emerald'],
            ['Debits', 'AED '.number_format((float)$stats['debits'], 2), 'rose'],
        ] as [$label, $value, $tone])
            <article class="erp-card p-5"><p class="text-xs font-black uppercase tracking-[0.16em] text-slate-400">{{ $label }}</p><p class="mt-3 text-2xl font-black tracking-[-0.04em] text-[#071a3b]">{{ $value }}</p></article>
        @endforeach
    </div>

    <section class="grid gap-5 xl:grid-cols-[1fr_340px]">
        <div class="erp-card overflow-hidden">
            <div class="border-b border-slate-100 p-5">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div><h2 class="text-lg font-black text-[#071a3b]">Bank transactions</h2><p class="mt-1 text-sm text-slate-500">Match bank movements with payments, expenses, and owner transfers.</p></div>
                    <div class="flex flex-wrap gap-2">
                        @can('bank-reconciliation.manage')
                            <button type="button" x-data x-on:click="$dispatch('open-modal', 'import-bank-statement')" class="inline-flex h-11 items-center justify-center rounded-xl bg-blue-600 px-4 text-sm font-black text-white shadow-lg shadow-blue-600/20">Import statement</button>
                            <button type="button" x-data x-on:click="$dispatch('open-modal', 'add-bank-account')" class="inline-flex h-11 items-center justify-center rounded-xl border border-slate-200 bg-white px-4 text-sm font-black text-slate-700">Add account</button>
                        @endcan
                    </div>
                </div>
                <form method="GET" class="mt-5 grid gap-2 md:grid-cols-[1fr_170px_150px_110px]">
                    <input name="search" value="{{ request('search') }}" placeholder="Search description or reference..." class="erp-focus h-11 rounded-xl border border-slate-200 px-3 text-sm">
                    <select name="status" class="erp-focus h-11 rounded-xl border border-slate-200 px-3 text-sm"><option value="">All status</option>@foreach(\App\Models\BankTransaction::STATUSES as $status)<option value="{{ $status }}" @selected(request('status')===$status)>{{ str($status)->headline() }}</option>@endforeach</select>
                    <select name="type" class="erp-focus h-11 rounded-xl border border-slate-200 px-3 text-sm"><option value="">All type</option><option value="credit" @selected(request('type')==='credit')>Credit</option><option value="debit" @selected(request('type')==='debit')>Debit</option></select>
                    <button class="rounded-xl bg-slate-900 px-3 text-sm font-black text-white">Filter</button>
                </form>
            </div>

            <div class="divide-y divide-slate-100">
                @forelse($transactions as $transaction)
                    <article class="p-5">
                        <div class="grid gap-4 lg:grid-cols-[1fr_180px_170px]">
                            <div>
                                <div class="flex flex-wrap items-center gap-2"><span class="rounded-full {{ $transaction->type === 'credit' ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' }} px-2.5 py-1 text-xs font-black">{{ str($transaction->type)->headline() }}</span><span class="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-black text-blue-700">{{ str($transaction->status)->headline() }}</span></div>
                                <p class="mt-2 font-black text-[#071a3b]">{{ $transaction->description ?: 'No description' }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $transaction->transaction_date?->format('M d, Y') }} / {{ $transaction->reference_no ?: 'No reference' }} / {{ $transaction->bankAccount->name }}</p>
                            </div>
                            <div class="text-left lg:text-right"><p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">Amount</p><p class="mt-1 text-lg font-black text-[#071a3b]">AED {{ number_format((float)$transaction->amount, 2) }}</p>@if($transaction->balance !== null)<p class="text-xs text-slate-500">Bal AED {{ number_format((float)$transaction->balance, 2) }}</p>@endif</div>
                            @can('bank-reconciliation.manage')
                                <div class="space-y-2">
                                    @if($transaction->status !== 'matched')
                                        <form method="POST" action="{{ route('bank-reconciliation.ignore', $transaction) }}">@csrf<button class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs font-black text-slate-500">Ignore</button></form>
                                    @else
                                        <p class="rounded-xl bg-emerald-50 px-3 py-2 text-xs font-black text-emerald-700">Matched {{ $transaction->matched_at?->format('M d, H:i') }}</p>
                                    @endif
                                </div>
                            @endcan
                        </div>

                        @if($transaction->matches->isNotEmpty() && $transaction->status !== 'matched')
                            <div class="mt-4 grid gap-3 md:grid-cols-2">
                                @foreach($transaction->matches->where('status', 'suggested') as $match)
                                    <div class="rounded-2xl border border-blue-100 bg-blue-50/50 p-4">
                                        <div class="flex items-start justify-between gap-3"><div><p class="text-sm font-black text-[#071a3b]">{{ class_basename($match->matchable_type) }} #{{ $match->matchable_id }}</p><p class="mt-1 text-xs text-slate-500">{{ $match->reason }}</p></div><span class="rounded-full bg-white px-2.5 py-1 text-xs font-black text-blue-700">{{ $match->confidence }}%</span></div>
                                        @can('bank-reconciliation.manage')
                                            <div class="mt-3 flex gap-2"><form method="POST" action="{{ route('bank-reconciliation.confirm', $transaction) }}" class="flex-1">@csrf<input type="hidden" name="match_id" value="{{ $match->id }}"><button class="w-full rounded-xl bg-blue-600 px-3 py-2 text-xs font-black text-white">Confirm</button></form><form method="POST" action="{{ route('bank-reconciliation.reject', $match) }}">@csrf<button class="rounded-xl border border-rose-200 px-3 py-2 text-xs font-black text-rose-600">Reject</button></form></div>
                                        @endcan
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        @can('bank-reconciliation.manage')
                            @if($transaction->status !== 'matched')
                                <button type="button" x-data x-on:click="$dispatch('open-modal', 'manual-bank-match-{{ $transaction->id }}')" class="mt-4 inline-flex rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-black text-slate-700">Manual match</button>
                                <x-modal name="manual-bank-match-{{ $transaction->id }}" maxWidth="lg">
                                    <form method="POST" action="{{ route('bank-reconciliation.manual-match', $transaction) }}" class="p-6">
                                        @csrf
                                        <div class="flex items-start justify-between gap-4">
                                            <div>
                                                <p class="text-[11px] font-black uppercase tracking-[0.2em] text-blue-600">Manual match</p>
                                                <h2 class="mt-1 text-xl font-black text-[#071a3b]">Match bank transaction</h2>
                                                <p class="mt-1 text-sm text-slate-500">AED {{ number_format((float)$transaction->amount, 2) }} / {{ $transaction->reference_no ?: 'No reference' }}</p>
                                            </div>
                                            <button type="button" x-on:click="$dispatch('close-modal', 'manual-bank-match-{{ $transaction->id }}')" class="rounded-xl border border-slate-200 px-3 py-2 text-xs font-black text-slate-500">Close</button>
                                        </div>
                                        <div class="mt-5 grid gap-3">
                                            <label class="text-xs font-black uppercase tracking-[0.12em] text-slate-500">Match with
                                                <select name="match_type" class="erp-focus mt-2 h-11 w-full rounded-xl border border-slate-200 px-3 text-sm">
                                                    <option value="payment">Payment</option>
                                                    <option value="expense">Expense</option>
                                                    <option value="owner_payout">Owner payout</option>
                                                </select>
                                            </label>
                                            <label class="text-xs font-black uppercase tracking-[0.12em] text-slate-500">Record ID
                                                <input name="match_id" placeholder="Example: payment ID, expense ID, or payout transfer ID" class="erp-focus mt-2 h-11 w-full rounded-xl border border-slate-200 px-3 text-sm" required>
                                            </label>
                                            <div class="rounded-2xl bg-amber-50 p-4 text-xs font-bold leading-5 text-amber-800">Use manual match only when the suggestion is missing but finance has verified the exact record.</div>
                                        </div>
                                        <div class="mt-6 flex justify-end gap-2">
                                            <button type="button" x-on:click="$dispatch('close-modal', 'manual-bank-match-{{ $transaction->id }}')" class="rounded-xl border border-slate-200 px-5 py-2.5 text-sm font-black text-slate-600">Cancel</button>
                                            <button class="rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-black text-white">Confirm match</button>
                                        </div>
                                    </form>
                                </x-modal>
                            @endif
                        @endcan
                    </article>
                @empty
                    <p class="p-10 text-center text-sm text-slate-500">No bank transactions imported yet.</p>
                @endforelse
            </div>
            <div class="p-5">{{ $transactions->links() }}</div>
        </div>

        <aside class="space-y-5">
            <div class="erp-card p-5"><h2 class="text-lg font-black text-[#071a3b]">Recent imports</h2><div class="mt-4 space-y-3">@forelse($imports as $import)<div class="rounded-2xl border border-slate-200 p-4"><p class="font-black text-[#071a3b]">{{ $import->original_name }}</p><p class="mt-1 text-xs text-slate-500">{{ $import->bankAccount->name }} / {{ $import->created_at->format('M d, Y H:i') }}</p><p class="mt-2 text-xs font-bold text-slate-600">{{ $import->rows_imported }} imported / {{ $import->rows_duplicate }} duplicate</p></div>@empty<p class="text-sm text-slate-500">No imports yet.</p>@endforelse</div></div>
            <div class="erp-card p-5"><h2 class="text-lg font-black text-[#071a3b]">How matching works</h2><div class="mt-4 space-y-3 text-sm text-slate-600"><p class="rounded-2xl bg-emerald-50 p-4">Credits are matched with tenant payments by amount, date, invoice, reference, and tenant name.</p><p class="rounded-2xl bg-rose-50 p-4">Debits are matched with expenses and owner payout transfers.</p><p class="rounded-2xl bg-blue-50 p-4">Confirming a pending payment match approves the payment and can issue the receipt automatically.</p></div></div>
        </aside>
    </section>

    @can('bank-reconciliation.manage')
        <x-modal name="add-bank-account" maxWidth="2xl" focusable>
            <form method="POST" action="{{ route('bank-reconciliation.accounts.store') }}" class="p-6">
                @csrf
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-[11px] font-black uppercase tracking-[0.2em] text-blue-600">Bank account</p>
                        <h2 class="mt-1 text-xl font-black text-[#071a3b]">Add collection bank account</h2>
                        <p class="mt-1 text-sm text-slate-500">Create Pattern bank accounts for statement imports and matching.</p>
                    </div>
                    <button type="button" x-on:click="$dispatch('close-modal', 'add-bank-account')" class="rounded-xl border border-slate-200 px-3 py-2 text-xs font-black text-slate-500">Close</button>
                </div>
                <div class="mt-6 grid gap-3 md:grid-cols-2">
                    <input name="name" placeholder="Account name e.g. Main collections" class="erp-focus h-11 rounded-xl border border-slate-200 px-3 text-sm" required>
                    <input name="bank_name" placeholder="Bank name" class="erp-focus h-11 rounded-xl border border-slate-200 px-3 text-sm">
                    <input name="account_no" placeholder="Account no" class="erp-focus h-11 rounded-xl border border-slate-200 px-3 text-sm">
                    <input name="iban" placeholder="IBAN" class="erp-focus h-11 rounded-xl border border-slate-200 px-3 text-sm">
                    <input name="currency" value="AED" class="erp-focus h-11 rounded-xl border border-slate-200 px-3 text-sm">
                </div>
                <div class="mt-6 flex justify-end gap-2">
                    <button type="button" x-on:click="$dispatch('close-modal', 'add-bank-account')" class="rounded-xl border border-slate-200 px-5 py-2.5 text-sm font-black text-slate-600">Cancel</button>
                    <button class="rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-black text-white">Save account</button>
                </div>
            </form>
        </x-modal>

        <x-modal name="import-bank-statement" maxWidth="2xl" focusable>
            <form method="POST" action="{{ route('bank-reconciliation.import') }}" enctype="multipart/form-data" class="p-6">
                @csrf
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-[11px] font-black uppercase tracking-[0.2em] text-blue-600">Statement import</p>
                        <h2 class="mt-1 text-xl font-black text-[#071a3b]">Upload bank statement CSV</h2>
                        <p class="mt-1 text-sm text-slate-500">Accepted columns: date, description/narration, reference, debit, credit, amount, balance.</p>
                    </div>
                    <button type="button" x-on:click="$dispatch('close-modal', 'import-bank-statement')" class="rounded-xl border border-slate-200 px-3 py-2 text-xs font-black text-slate-500">Close</button>
                </div>
                <div class="mt-6 grid gap-3 md:grid-cols-2">
                    <select name="bank_account_id" class="erp-focus h-11 rounded-xl border border-slate-200 px-3 text-sm" required>
                        <option value="">Select bank account</option>
                        @foreach($accounts as $account)
                            <option value="{{ $account->id }}">{{ $account->name }} / {{ $account->bank_name ?: 'Bank' }}</option>
                        @endforeach
                    </select>
                    <input name="statement" type="file" accept=".csv,.txt" class="erp-focus h-11 rounded-xl border border-dashed border-blue-200 bg-blue-50 px-3 py-2 text-sm" required>
                    <input name="statement_from" type="date" class="erp-focus h-11 rounded-xl border border-slate-200 px-3 text-sm">
                    <input name="statement_to" type="date" class="erp-focus h-11 rounded-xl border border-slate-200 px-3 text-sm">
                    <textarea name="notes" rows="3" placeholder="Notes" class="erp-focus rounded-xl border border-slate-200 px-3 py-2 text-sm md:col-span-2"></textarea>
                </div>
                <div class="mt-5 rounded-2xl bg-blue-50 p-4 text-xs font-bold leading-5 text-blue-800">After import, the system will suggest matches against payments, expenses, and owner payout transfers. No payment is approved until finance confirms the match.</div>
                <div class="mt-6 flex justify-end gap-2">
                    <button type="button" x-on:click="$dispatch('close-modal', 'import-bank-statement')" class="rounded-xl border border-slate-200 px-5 py-2.5 text-sm font-black text-slate-600">Cancel</button>
                    <button class="rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-black text-white">Import and auto-match</button>
                </div>
            </form>
        </x-modal>
    @endcan
</div>
</x-app-layout>
