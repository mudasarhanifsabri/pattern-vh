<x-app-layout>
<x-slot name="header"><div><p class="text-[11px] font-bold uppercase tracking-[0.22em] text-blue-600">Accounting</p><h1 class="text-2xl font-bold text-[#071a3b]">Owner Account Statement</h1></div></x-slot>
<div class="space-y-6">
    <section class="erp-card p-5">
        <form method="GET" class="grid gap-3 lg:grid-cols-[1fr_160px_160px_auto_auto] lg:items-end">
            @can('owner-statements.manage')<div><x-input-label for="owner_id" value="Owner" /><select id="owner_id" name="owner_id" class="erp-focus mt-1 h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm">@foreach($owners as $ownerOption)<option value="{{ $ownerOption->id }}" @selected($owner?->id === $ownerOption->id)>{{ $ownerOption->full_name }}</option>@endforeach</select></div>@endcan
            <div><x-input-label for="from" value="From" /><x-text-input id="from" name="from" type="date" class="mt-1 block w-full" :value="$from->format('Y-m-d')" /></div>
            <div><x-input-label for="to" value="To" /><x-text-input id="to" name="to" type="date" class="mt-1 block w-full" :value="$to->format('Y-m-d')" /></div>
            <button class="h-11 rounded-xl bg-slate-900 px-4 text-sm font-bold text-white">Filter</button>
            @if($owner)<a href="{{ route('owner-statements.pdf', request()->query()) }}" target="_blank" class="inline-flex h-11 items-center justify-center rounded-xl bg-slate-900 px-4 text-sm font-bold text-white">Statement PDF</a><a href="{{ route('owner-statements.index', array_merge(request()->query(), ['export' => 1])) }}" class="inline-flex h-11 items-center justify-center rounded-xl bg-blue-600 px-4 text-sm font-bold text-white">Export CSV</a>@endif
        </form>
    </section>

    @if($owner && $statement)
        <section class="grid gap-4 md:grid-cols-4">
            @foreach([
                ['label' => 'Gross rent share', 'value' => $statement['gross']],
                ['label' => 'Management fee', 'value' => $statement['management_fee']],
                ['label' => 'Owner expenses', 'value' => $statement['expenses']],
                ['label' => 'Net payable', 'value' => $statement['net']],
            ] as $card)
                <article class="erp-card p-5"><p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">{{ $card['label'] }}</p><p class="mt-3 text-2xl font-black text-[#071a3b]">AED {{ number_format((float)$card['value'], 2) }}</p></article>
            @endforeach
        </section>

        <section class="erp-card overflow-hidden">
            <div class="border-b border-slate-100 p-5"><h2 class="text-lg font-bold text-[#071a3b]">{{ $owner->full_name }}</h2><p class="mt-1 text-sm text-slate-500">{{ $from->format('M d, Y') }} to {{ $to->format('M d, Y') }}</p></div>
            <table class="min-w-full divide-y divide-slate-200 text-sm"><thead class="bg-slate-50 text-left text-[11px] font-bold uppercase tracking-[0.16em] text-slate-500"><tr><th class="px-4 py-3">Date</th><th class="px-4 py-3">Description</th><th class="px-4 py-3">Gross</th><th class="px-4 py-3">Mgmt fee</th><th class="px-4 py-3">Expense</th><th class="px-4 py-3">Net</th></tr></thead><tbody class="divide-y divide-slate-100 bg-white">@forelse($statement['rows'] as $row)<tr><td class="px-4 py-4">{{ $row['date']->format('M d, Y') }}</td><td class="px-4 py-4 font-bold text-[#071a3b]">{{ $row['description'] }}</td><td class="px-4 py-4">AED {{ number_format($row['gross'], 2) }}</td><td class="px-4 py-4">AED {{ number_format($row['management_fee'], 2) }}</td><td class="px-4 py-4">AED {{ number_format($row['owner_expense'], 2) }}</td><td class="px-4 py-4 font-black text-[#071a3b]">AED {{ number_format($row['net'], 2) }}</td></tr>@empty<tr><td colspan="6" class="px-4 py-10 text-center text-sm text-slate-500">No statement rows for this period.</td></tr>@endforelse</tbody></table>
        </section>
    @else
        <div class="rounded-2xl border border-dashed border-slate-200 bg-white px-4 py-10 text-center text-sm text-slate-500">Select an owner to view the account statement.</div>
    @endif
</div>
</x-app-layout>
