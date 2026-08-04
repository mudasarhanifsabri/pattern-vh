<x-app-layout>
@php
    $ownerOnly = auth()->user()?->can('portal.owner')
        && ! auth()->user()?->can('accounting.view')
        && ! auth()->user()?->can('accounting.manage')
        && ! auth()->user()?->can('users.manage');
    $statementQuery = array_filter([
        'owner_id' => $owner?->id,
        'unit_id' => $unit?->id,
        'from' => $from->format('Y-m-d'),
        'to' => $to->format('Y-m-d'),
    ], fn ($value) => filled($value));
@endphp

<x-slot name="header"><div><p class="text-[11px] font-bold uppercase tracking-[0.22em] text-blue-600">Accounting</p><h1 class="text-2xl font-bold text-[#071a3b]">Owner Account Statement</h1></div></x-slot>

<div class="{{ $ownerOnly ? 'tenant-app-screen' : '' }} space-y-5">
    @if(session('status'))<div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-700">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-bold text-rose-700">{{ $errors->first() }}</div>@endif
    <section class="{{ $ownerOnly ? 'rounded-[1.6rem] bg-white p-5 shadow-[0_18px_45px_rgba(15,23,42,0.08)]' : 'erp-card p-5' }}">
        <form method="GET" class="grid gap-3 {{ $ownerOnly ? '' : 'lg:grid-cols-[1fr_1fr_150px_150px_auto] lg:items-end' }}">
            @can('owner-statements.manage')
                <div><x-input-label for="owner_id" value="Owner" /><select id="owner_id" name="owner_id" class="erp-focus mt-1 h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm">@foreach($owners as $ownerOption)<option value="{{ $ownerOption->id }}" @selected($owner?->id === $ownerOption->id)>{{ $ownerOption->full_name }}</option>@endforeach</select></div>
            @endcan
            <div>
                <x-input-label for="unit_id" value="Unit" />
                <select id="unit_id" name="unit_id" class="erp-focus mt-1 h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm">
                    <option value="">All units</option>
                    @foreach($owners as $unitOwner)
                        @foreach($unitOwner->units as $unitOption)
                            <option value="{{ $unitOption->id }}" data-owner-id="{{ $unitOwner->id }}" @selected($unit?->id === $unitOption->id)>{{ $unitOption->building?->name }} / Unit {{ $unitOption->unit_no }}</option>
                        @endforeach
                    @endforeach
                </select>
            </div>
            <div><x-input-label for="from" value="From" /><x-text-input id="from" name="from" type="date" class="mt-1 block h-12 w-full rounded-2xl" :value="$from->format('Y-m-d')" /></div>
            <div><x-input-label for="to" value="To" /><x-text-input id="to" name="to" type="date" class="mt-1 block h-12 w-full rounded-2xl" :value="$to->format('Y-m-d')" /></div>
            <button class="h-12 rounded-2xl bg-slate-900 px-4 text-sm font-black text-white">Filter</button>
        </form>
        @if($owner)
            <div class="mt-3 grid gap-2 sm:grid-cols-3">
                <a href="{{ route('owner-statements.pdf-preview', $statementQuery) }}" class="inline-flex h-12 items-center justify-center rounded-2xl bg-slate-900 px-4 text-sm font-black text-white">Preview PDF</a>
                <a href="{{ route('owner-statements.pdf', array_merge($statementQuery, ['download' => 1])) }}" class="inline-flex h-12 items-center justify-center rounded-2xl bg-blue-600 px-4 text-sm font-black text-white">Download PDF</a>
                <a href="{{ route('owner-statements.index', array_merge($statementQuery, ['export' => 1])) }}" class="inline-flex h-12 items-center justify-center rounded-2xl border border-blue-200 bg-blue-50 px-4 text-sm font-black text-blue-700">Download CSV</a>
            </div>
        @endif
    </section>

    @can('owner-statements.manage')
        @if($owner)
            <section class="erp-card p-5">
                <div><h2 class="text-lg font-black text-[#071a3b]">Add Zoho opening balance</h2><p class="mt-1 text-sm text-slate-500">Add the previous balance separately for each unit. Credit means Pattern owes the owner; Debit means the owner owes Pattern.</p></div>
                <form method="POST" action="{{ route('owner-statements.opening-balance.store') }}" class="mt-5 grid gap-3 md:grid-cols-2 xl:grid-cols-[1fr_150px_140px_170px_1fr_auto] xl:items-end">
                    @csrf
                    <input type="hidden" name="owner_id" value="{{ $owner->id }}"><input type="hidden" name="statement_from" value="{{ $from->format('Y-m-d') }}"><input type="hidden" name="statement_to" value="{{ $to->format('Y-m-d') }}">
                    <div><x-input-label for="opening_unit" value="Owner unit" /><select id="opening_unit" name="unit_id" class="erp-focus mt-1 h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm" required><option value="">Select unit</option>@foreach($owner->units as $openingUnit)<option value="{{ $openingUnit->id }}" @selected(old('unit_id', $unit?->id) == $openingUnit->id)>{{ $openingUnit->building?->name }} / Unit {{ $openingUnit->unit_no }}</option>@endforeach</select></div>
                    <div><x-input-label for="opening_date" value="Balance date" /><input id="opening_date" name="entry_date" type="date" value="{{ old('entry_date', $from->copy()->subDay()->format('Y-m-d')) }}" class="erp-focus mt-1 h-11 w-full rounded-xl border border-slate-200 px-3 text-sm" required></div>
                    <div><x-input-label for="opening_direction" value="Balance type" /><select id="opening_direction" name="direction" class="erp-focus mt-1 h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm"><option value="credit">Credit</option><option value="debit" @selected(old('direction') === 'debit')>Debit</option></select></div>
                    <div><x-input-label for="opening_amount" value="Amount" /><input id="opening_amount" name="amount" type="number" min="0.01" step="0.01" value="{{ old('amount') }}" placeholder="0.00" class="erp-focus mt-1 h-11 w-full rounded-xl border border-slate-200 px-3 text-sm" required></div>
                    <div><x-input-label for="opening_description" value="Description" /><input id="opening_description" name="description" value="{{ old('description', 'Zoho opening balance') }}" class="erp-focus mt-1 h-11 w-full rounded-xl border border-slate-200 px-3 text-sm"></div>
                    <button class="h-11 rounded-xl bg-blue-600 px-5 text-sm font-black text-white">Add balance</button>
                </form>
            </section>
        @endif
    @endcan

    @if($owner && $statement)
        <div class="flex justify-end">
            <a href="{{ route('owners.account.index', $owner) }}" class="inline-flex h-11 items-center rounded-2xl border border-blue-200 bg-blue-50 px-4 text-sm font-black text-blue-700">Detailed account ledger →</a>
        </div>
        @if($ownerOnly)
            <section class="overflow-hidden rounded-[1.6rem] bg-white shadow-[0_18px_45px_rgba(15,23,42,0.08)]">
                <div class="bg-gradient-to-br from-slate-950 via-slate-800 to-blue-700 p-5 text-white">
                    <p class="text-xs font-black uppercase tracking-[0.16em] text-blue-100">Statement</p>
                    <h2 class="mt-2 text-2xl font-black leading-tight">{{ $owner->full_name }}</h2>
                    <p class="mt-1 text-sm font-semibold text-white/70">{{ $from->format('M d, Y') }} to {{ $to->format('M d, Y') }}</p>
                </div>
                <div class="grid grid-cols-2 gap-3 p-4">
                    @foreach([
                        ['label' => 'Opening balance', 'value' => $statement['opening_balance']],
                        ['label' => 'Gross rent share', 'value' => $statement['gross']],
                        ['label' => 'Management fee', 'value' => $statement['management_fee']],
                        ['label' => 'Owner expenses', 'value' => $statement['expenses']],
                        ['label' => 'Closing balance', 'value' => $statement['net']],
                    ] as $card)
                        <div class="rounded-2xl {{ $card['label'] === 'Closing balance' ? 'bg-blue-50' : 'bg-slate-50' }} p-3">
                            <p class="text-[10px] font-bold uppercase text-slate-400">{{ $card['label'] }}</p>
                            <p class="mt-1 text-lg font-black {{ $card['value'] < 0 ? 'text-rose-600' : 'text-[#071a3b]' }}">AED {{ number_format((float) $card['value'], 0) }}</p>
                        </div>
                    @endforeach
                </div>
            </section>
        @else
            <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                @foreach([
                    ['label' => 'Opening balance', 'value' => $statement['opening_balance']],
                    ['label' => 'Gross rent share', 'value' => $statement['gross']],
                    ['label' => 'Management fee', 'value' => $statement['management_fee']],
                    ['label' => 'Owner expenses', 'value' => $statement['expenses']],
                    ['label' => 'Closing balance', 'value' => $statement['net']],
                ] as $card)
                    <article class="erp-card p-5"><p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">{{ $card['label'] }}</p><p class="mt-3 text-2xl font-black text-[#071a3b]">AED {{ number_format((float)$card['value'], 2) }}</p></article>
                @endforeach
            </section>
        @endif

        <section class="{{ $ownerOnly ? 'rounded-[1.6rem] bg-white p-5 shadow-[0_18px_45px_rgba(15,23,42,0.08)]' : 'erp-card overflow-hidden' }}">
            <div class="{{ $ownerOnly ? '' : 'border-b border-slate-100 p-5' }}">
                <h2 class="text-lg font-black text-[#071a3b]">Statement activity</h2>
                <p class="mt-1 text-sm text-slate-500">Rent collected and owner entitlement are included by each invoice period. Owner expenses use their incurred date; security deposits and Pattern top-up remain excluded.</p>
            </div>

            <div class="mt-5 space-y-3 md:hidden">
                @forelse($statement['rows'] as $row)
                    <article class="rounded-3xl border border-slate-200 p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-[0.14em] text-blue-600">{{ $row['date']->format('M d, Y') }}</p>
                                <h3 class="mt-1 text-sm font-black leading-5 text-[#071a3b]">{{ $row['description'] }}</h3>
                                @if($row['booking_duration'])
                                    <p class="mt-2 text-xs font-bold text-slate-500">Owner rent entitlement: AED {{ number_format($row['rent_collected'], 2) }}</p>
                                    <p class="mt-1 text-xs font-semibold text-slate-500">{{ $row['booking_duration'] }}</p>
                                @endif
                            </div>
                            <span class="text-sm font-black {{ $row['net'] < 0 ? 'text-rose-600' : 'text-emerald-700' }}">AED {{ number_format($row['net'], 2) }}</span>
                        </div>
                        <div class="mt-4 grid grid-cols-3 gap-2 text-xs">
                            <div class="rounded-2xl bg-slate-50 p-3"><p class="font-bold text-slate-400">Rent</p><p class="mt-1 font-black text-[#071a3b]">{{ number_format($row['gross'], 0) }}</p></div>
                            <div class="rounded-2xl bg-slate-50 p-3"><p class="font-bold text-slate-400">Mgmt</p><p class="mt-1 font-black text-[#071a3b]">{{ number_format($row['management_fee'], 0) }}</p></div>
                            <div class="rounded-2xl bg-slate-50 p-3"><p class="font-bold text-slate-400">Expense</p><p class="mt-1 font-black text-[#071a3b]">{{ number_format($row['owner_expense'], 0) }}</p></div>
                        </div>
                    </article>
                @empty
                    <p class="rounded-2xl border border-dashed border-slate-200 px-4 py-8 text-center text-sm text-slate-500">No statement rows for this period.</p>
                @endforelse
            </div>

            <div class="hidden overflow-x-auto md:block">
                <table class="min-w-full divide-y divide-slate-200 text-sm"><thead class="bg-slate-50 text-left text-[11px] font-bold uppercase tracking-[0.16em] text-slate-500"><tr><th class="px-4 py-3">Checkout date</th><th class="px-4 py-3">Invoice / Booking</th><th class="px-4 py-3">Stay dates</th><th class="px-4 py-3">Owner rent entitlement</th><th class="px-4 py-3">Rent share</th><th class="px-4 py-3">Mgmt fee</th><th class="px-4 py-3">Expense</th><th class="px-4 py-3">Net payable</th></tr></thead><tbody class="divide-y divide-slate-100 bg-white">@forelse($statement['rows'] as $row)<tr><td class="px-4 py-4">{{ $row['date']->format('M d, Y') }}</td><td class="px-4 py-4"><p class="font-bold text-[#071a3b]">{{ $row['description'] }}</p></td><td class="px-4 py-4 text-xs font-semibold text-slate-500">{{ $row['booking_duration'] ?? '-' }}</td><td class="px-4 py-4">{{ $row['rent_collected'] !== null ? 'AED '.number_format($row['rent_collected'], 2) : '-' }}</td><td class="px-4 py-4">AED {{ number_format($row['gross'], 2) }}</td><td class="px-4 py-4">AED {{ number_format($row['management_fee'], 2) }}</td><td class="px-4 py-4">AED {{ number_format($row['owner_expense'], 2) }}</td><td class="px-4 py-4 font-black text-[#071a3b]">AED {{ number_format($row['net'], 2) }}</td></tr>@empty<tr><td colspan="8" class="px-4 py-10 text-center text-sm text-slate-500">No statement rows for this period.</td></tr>@endforelse</tbody></table>
            </div>
        </section>
    @else
        <div class="rounded-2xl border border-dashed border-slate-200 bg-white px-4 py-10 text-center text-sm text-slate-500">Select an owner to view the account statement.</div>
    @endif
</div>
@can('owner-statements.manage')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const ownerSelect = document.getElementById('owner_id');
    const unitSelect = document.getElementById('unit_id');
    if (!ownerSelect || !unitSelect) return;

    const filterUnits = (reset = false) => {
        const ownerId = ownerSelect.value;
        Array.from(unitSelect.options).forEach((option) => {
            if (!option.value) return;
            option.hidden = option.dataset.ownerId !== ownerId;
        });
        if (reset && unitSelect.selectedOptions[0]?.dataset.ownerId !== ownerId) unitSelect.value = '';
    };

    ownerSelect.addEventListener('change', () => filterUnits(true));
    filterUnits();
});
</script>
@endcan
</x-app-layout>
