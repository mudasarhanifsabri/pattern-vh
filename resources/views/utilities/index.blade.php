<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-[11px] font-bold uppercase tracking-[0.22em] text-blue-600">Recurring services</p>
            <h1 class="text-3xl font-black tracking-[-0.04em] text-[#071a3b]">Utility management</h1>
            <p class="mt-2 text-sm text-slate-500">Track responsibility, provider credentials, billing cycles, due dates, and receipts.</p>
        </div>
    </x-slot>

    @php
        $weekStart = now()->startOfWeek();
        $days = collect(range(0, 6))->map(fn ($day) => $weekStart->copy()->addDays($day));
    @endphp

    <div class="space-y-6">
        @if (session('status'))<div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">{{ session('status') }}</div>@endif

        <div class="erp-card p-2">
            <div class="flex flex-wrap gap-2 text-sm font-bold">
                <a href="#utilities" class="rounded-xl bg-blue-50 px-4 py-2.5 text-blue-700">Utilities</a>
                <a href="#company-paid" class="rounded-xl px-4 py-2.5 text-slate-500 hover:bg-slate-50">Company-paid details</a>
                <a href="#due-calendar" class="rounded-xl px-4 py-2.5 text-slate-500 hover:bg-slate-50">Due calendar</a>
                <a href="#receipts" class="rounded-xl px-4 py-2.5 text-slate-500 hover:bg-slate-50">Receipts</a>
            </div>
        </div>

        <div class="space-y-5">
                <section id="due-calendar" class="erp-card overflow-hidden">
                    <div class="flex items-center justify-between gap-3 border-b border-slate-100 p-5">
                        <div>
                            <h2 class="text-lg font-bold text-[#071a3b]">Due calendar</h2>
                            <p class="mt-1 text-sm text-slate-500">{{ $days->first()->format('M d') }} - {{ $days->last()->format('M d, Y') }}</p>
                        </div>
                        <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-bold text-blue-700">{{ $bills->where('status', 'pending')->count() }} pending</span>
                    </div>
                    <div class="grid min-h-[360px] md:grid-cols-7">
                        @foreach ($days as $day)
                            <div class="border-b border-r border-slate-100 p-3 last:border-r-0">
                                <p class="text-xs font-black text-[#071a3b]">{{ $day->format('D d') }}</p>
                                <div class="mt-4 space-y-2">
                                    @forelse ($bills->filter(fn ($bill) => $bill->due_date?->isSameDay($day)) as $bill)
                                        @php
                                            $tone = match ($bill->utilityAccount->provider_type) {
                                                'dewa' => 'bg-blue-50 text-blue-700 border-blue-500',
                                                'gas' => 'bg-amber-50 text-amber-700 border-amber-500',
                                                'internet' => 'bg-violet-50 text-violet-700 border-violet-500',
                                                default => 'bg-emerald-50 text-emerald-700 border-emerald-500',
                                            };
                                        @endphp
                                        <div class="rounded-xl border-l-4 px-3 py-2 text-xs font-bold {{ $tone }}">
                                            {{ $bill->due_date->format('H:i') }} · {{ $bill->utilityAccount->unit->building?->name }} {{ $bill->utilityAccount->unit->unit_no }}
                                            <span class="block font-medium">AED {{ number_format((float) $bill->amount, 2) }} · {{ str($bill->status)->headline() }}</span>
                                        </div>
                                    @empty
                                        <div class="rounded-xl border border-dashed border-slate-200 px-3 py-2 text-xs text-slate-400">Available</div>
                                    @endforelse
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>

                <section id="utilities" class="erp-card p-5">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h2 class="text-lg font-bold text-[#071a3b]">Utility accounts</h2>
                            <p class="mt-1 text-sm text-slate-500">DEWA, gas, internet, cooling, and other recurring services.</p>
                        </div>
                        <div class="flex items-center gap-2"><span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600">{{ $accounts->count() }} accounts</span>@can('utilities.manage')<button type="button" x-data x-on:click="$dispatch('open-modal', 'add-utility-bill')" class="rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-bold text-white">+ Add bill</button>@endcan</div>
                    </div>
                    <div class="mt-5 grid gap-4 lg:grid-cols-2">
                        @forelse ($accounts as $account)
                            <div class="rounded-3xl border border-slate-200 p-4">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">{{ str($account->provider_type)->headline() }}</p>
                                        <h3 class="mt-1 font-black text-[#071a3b]">{{ $account->provider_name }}</h3>
                                        <p class="mt-1 text-sm text-slate-500">{{ $account->unit->building?->name }} / Unit {{ $account->unit->unit_no }}</p>
                                    </div>
                                    <span class="rounded-full {{ $account->paid_by_company ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }} px-3 py-1 text-xs font-bold">{{ $account->paid_by_company ? 'Pattern pays' : 'Owner pays' }}</span>
                                </div>
                                <dl class="mt-4 grid gap-3 sm:grid-cols-3">
                                    <div class="rounded-2xl bg-slate-50 p-3"><dt class="text-[10px] font-bold uppercase text-slate-400">Account</dt><dd class="mt-1 text-sm font-bold text-[#071a3b]">{{ $account->account_no ?: 'N/A' }}</dd></div>
                                    <div class="rounded-2xl bg-slate-50 p-3"><dt class="text-[10px] font-bold uppercase text-slate-400">Billing day</dt><dd class="mt-1 text-sm font-bold text-[#071a3b]">{{ $account->billing_day ?: '-' }}</dd></div>
                                    <div class="rounded-2xl bg-slate-50 p-3"><dt class="text-[10px] font-bold uppercase text-slate-400">Next due</dt><dd class="mt-1 text-sm font-bold text-[#071a3b]">{{ $account->next_due_date?->format('M d') ?? '-' }}</dd></div>
                                </dl>
                            </div>
                        @empty
                            <p class="rounded-3xl border border-dashed border-slate-200 p-8 text-center text-sm text-slate-500 lg:col-span-2">No utility accounts yet.</p>
                        @endforelse
                    </div>
                </section>
                    @can('utilities.manage')
                    <div class="erp-card p-5">
                        <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-600">Unit registration</p>
                        <h2 class="mt-1 text-lg font-bold text-[#071a3b]">Add utility accounts inside Units</h2>
                        <p class="mt-2 text-sm leading-6 text-slate-500">Create or edit a unit to add DEWA, internet, gas, cooling, billing day, due date, Pattern-paid status, and credentials. This keeps every account attached to the correct apartment from day one.</p>
                        <div class="mt-4 grid gap-2">
                            <a href="{{ route('units.create') }}" class="rounded-xl bg-blue-600 px-4 py-2.5 text-center text-sm font-bold text-white shadow-lg shadow-blue-600/20">Register unit</a>
                            <a href="{{ route('units.index') }}" class="rounded-xl border border-slate-200 px-4 py-2.5 text-center text-sm font-bold text-[#071a3b] hover:bg-slate-50">Open units</a>
                        </div>
                    </div>

                    @endcan
        </div>

        @can('utilities.manage')
            <x-modal name="add-utility-bill" maxWidth="lg" focusable>
                    <form id="receipts" method="POST" action="{{ route('utilities.bills.store') }}" enctype="multipart/form-data" class="p-6">
                        @csrf
                        <div class="flex items-center justify-between"><h2 class="text-lg font-bold text-[#071a3b]">Add bill / receipt</h2><button type="button" x-on:click="$dispatch('close')" class="rounded-xl bg-slate-100 px-3 py-2 text-sm font-bold">Close</button></div>
                        <div class="mt-4 space-y-3">
                            <select name="utility_account_id" class="erp-focus h-11 w-full rounded-xl border border-slate-200 px-3 text-sm" required><option value="">Select utility account</option>@foreach($accounts as $account)<option value="{{ $account->id }}">{{ $account->provider_name }} · {{ $account->unit->unit_no }}</option>@endforeach</select>
                            <input name="due_date" type="date" class="erp-focus h-11 w-full rounded-xl border border-slate-200 px-3 text-sm" required>
                            <input name="amount" class="erp-focus h-11 w-full rounded-xl border border-slate-200 px-3 text-sm" placeholder="Amount" required>
                            <select name="status" class="erp-focus h-11 w-full rounded-xl border border-slate-200 px-3 text-sm">@foreach($statuses as $status)<option value="{{ $status }}">{{ str($status)->headline() }}</option>@endforeach</select>
                            <input name="receipt" type="file" class="block w-full rounded-xl border border-dashed border-blue-200 bg-blue-50/50 p-3 text-sm">
                            <button class="w-full rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-bold text-white">Save bill</button>
                        </div>
                    </form>
            </x-modal>
        @endcan
    </div>
</x-app-layout>
