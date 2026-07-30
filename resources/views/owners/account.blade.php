<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-[11px] font-bold uppercase tracking-[0.22em] text-blue-600">Owner account</p>
            <h1 class="text-2xl font-bold text-[#071a3b]">{{ $owner->full_name }} — Statement of Account</h1>
        </div>
    </x-slot>

    <div class="{{ $ownerPortal ? 'tenant-app-screen' : '' }} space-y-6">
        @if(session('status'))<div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-700">{{ session('status') }}</div>@endif
        @if($errors->any())<div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ $errors->first() }}</div>@endif

        <div class="flex flex-wrap items-center justify-between gap-3">
            <a href="{{ $ownerPortal ? route('owner-statements.index') : route('owners.show', $owner) }}" class="text-sm font-bold text-slate-600 hover:text-blue-700">← {{ $ownerPortal ? 'Statement summary' : 'Owner details' }}</a>
            @can('owners.manage')<button type="button" data-modal-open="account-entry-modal" class="rounded-xl bg-blue-600 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-blue-600/20">Add entry</button>@endcan
        </div>

        <section class="grid gap-4 md:grid-cols-3">
            @foreach([['Credits', $totals['credits'], 'text-emerald-700'], ['Debits', $totals['debits'], 'text-rose-700'], ['Current balance', $totals['balance'], $totals['balance'] >= 0 ? 'text-blue-700' : 'text-rose-700']] as [$label,$value,$colour])
                <div class="erp-card p-5"><p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">{{ $label }}</p><p class="mt-2 text-2xl font-black {{ $colour }}">AED {{ number_format((float)$value, 2) }}</p></div>
            @endforeach
        </section>

        <section class="erp-card p-5">
            <form method="GET" class="grid gap-3 md:grid-cols-7">
                <input name="search" value="{{ request('search') }}" placeholder="Search description or reference" class="erp-focus h-11 rounded-xl border border-slate-200 px-3 text-sm md:col-span-2">
                <select name="unit_id" class="erp-focus h-11 rounded-xl border border-slate-200 bg-white px-3 text-sm"><option value="">All owner units</option>@foreach($owner->units as $unit)<option value="{{ $unit->id }}" @selected($selectedUnitId===$unit->id)>{{ $unit->building?->name }} / {{ $unit->unit_no }}</option>@endforeach</select>
                <select name="type" class="erp-focus h-11 rounded-xl border border-slate-200 bg-white px-3 text-sm"><option value="">All types</option>@foreach($types as $value=>$label)<option value="{{ $value }}" @selected(request('type')===$value)>{{ $label }}</option>@endforeach</select>
                <input type="date" name="from" value="{{ request('from') }}" class="erp-focus h-11 rounded-xl border border-slate-200 px-3 text-sm">
                <input type="date" name="to" value="{{ request('to') }}" class="erp-focus h-11 rounded-xl border border-slate-200 px-3 text-sm">
                <button class="h-11 rounded-xl bg-[#111827] px-4 text-sm font-bold text-white">Filter</button>
            </form>
        </section>

        <section class="erp-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-[11px] font-bold uppercase tracking-[0.14em] text-slate-500"><tr><th class="px-4 py-3">Date</th><th class="px-4 py-3">Type / source</th><th class="px-4 py-3">Description</th><th class="px-4 py-3">Reference</th><th class="px-4 py-3 text-right">Debit</th><th class="px-4 py-3 text-right">Credit</th><th class="px-4 py-3 text-right">Balance</th></tr></thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse($entries as $entry)
                            <tr>
                                <td class="whitespace-nowrap px-4 py-4 font-semibold text-slate-600">{{ $entry['date']->format('M d, Y') }}</td>
                                <td class="px-4 py-4"><span class="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-bold text-blue-700">{{ $entry['type_label'] }}</span><p class="mt-2 text-[11px] text-slate-400">{{ $entry['source'] }}</p></td>
                                <td class="min-w-64 px-4 py-4"><p class="font-bold text-[#071a3b]">{{ $entry['description'] }}</p>@if($entry['unit'])<p class="mt-1 text-xs font-bold text-blue-600">{{ $entry['unit']->building?->name }} / {{ $entry['unit']->unit_no }}</p>@endif @if($entry['notes'])<p class="mt-1 text-xs text-slate-500">{{ $entry['notes'] }}</p>@endif</td>
                                <td class="whitespace-nowrap px-4 py-4 text-slate-500">{{ $entry['reference'] ?: '—' }}</td>
                                <td class="whitespace-nowrap px-4 py-4 text-right font-bold text-rose-700">{{ $entry['debit'] ? 'AED '.number_format($entry['debit'],2) : '—' }}</td>
                                <td class="whitespace-nowrap px-4 py-4 text-right font-bold text-emerald-700">{{ $entry['credit'] ? 'AED '.number_format($entry['credit'],2) : '—' }}</td>
                                <td class="whitespace-nowrap px-4 py-4 text-right font-black {{ $entry['balance'] >= 0 ? 'text-[#071a3b]' : 'text-rose-700' }}">AED {{ number_format($entry['balance'],2) }}</td>
                            </tr>
                        @empty<tr><td colspan="7" class="px-4 py-12 text-center text-slate-500">No account entries match these filters.</td></tr>@endforelse
                    </tbody>
                </table>
            </div>
            @if($entries->hasPages())<div class="border-t border-slate-100 p-4">{{ $entries->links() }}</div>@endif
        </section>
    </div>

    @can('owners.manage')
        <div id="account-entry-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/40 p-4" data-modal>
            <div class="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-2xl bg-white p-6 shadow-2xl">
                <div class="flex items-center justify-between"><div><h2 class="text-xl font-black text-[#071a3b]">Add account entry</h2><p class="mt-1 text-sm text-slate-500">Credit increases the owner balance; debit reduces it.</p></div><button type="button" data-modal-close class="rounded-lg px-3 py-2 text-sm font-bold text-slate-500 hover:bg-slate-100">Close</button></div>
                <form method="POST" action="{{ route('owners.account.store', $owner) }}" class="mt-6 grid gap-4 md:grid-cols-2">
                    @csrf
                    <div><x-input-label for="entry_date" value="Entry date"/><x-text-input id="entry_date" name="entry_date" type="date" class="mt-1 block w-full" :value="old('entry_date', now()->toDateString())" required/></div>
                    <div><x-input-label for="unit_id" value="Unit"/><select id="unit_id" name="unit_id" class="erp-focus mt-1 h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm"><option value="">Owner account — no specific unit</option>@foreach($owner->units as $unit)<option value="{{ $unit->id }}" @selected(old('unit_id')==$unit->id)>{{ $unit->building?->name }} / {{ $unit->unit_no }}</option>@endforeach</select></div>
                    <div><x-input-label for="type" value="Entry type"/><select id="type" name="type" class="erp-focus mt-1 h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm" required>@foreach($types as $value=>$label)<option value="{{ $value }}" @selected(old('type')===$value)>{{ $label }}</option>@endforeach</select></div>
                    <div><x-input-label for="direction" value="Account side"/><select id="direction" name="direction" class="erp-focus mt-1 h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm" required><option value="credit" @selected(old('direction')==='credit')>Credit — amount owed to owner</option><option value="debit" @selected(old('direction')==='debit')>Debit — deduction / amount paid</option></select></div>
                    <div><x-input-label for="amount" value="Amount (AED)"/><x-text-input id="amount" name="amount" type="number" min="0.01" step="0.01" class="mt-1 block w-full" :value="old('amount')" required/></div>
                    <div class="md:col-span-2"><x-input-label for="description" value="Description"/><x-text-input id="description" name="description" class="mt-1 block w-full" :value="old('description')" required/></div>
                    <div><x-input-label for="reference_no" value="Reference number"/><x-text-input id="reference_no" name="reference_no" class="mt-1 block w-full" :value="old('reference_no')"/></div>
                    <div><x-input-label for="notes" value="Notes"/><textarea id="notes" name="notes" rows="3" class="erp-focus mt-1 block w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">{{ old('notes') }}</textarea></div>
                    <div class="flex justify-end gap-3 md:col-span-2"><button type="button" data-modal-close class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-bold text-slate-600">Cancel</button><button class="rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-bold text-white">Save entry</button></div>
                </form>
            </div>
        </div>
    @endcan

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('[data-modal-open]').forEach(button => button.addEventListener('click', () => {
                const modal = document.getElementById(button.dataset.modalOpen); modal?.classList.remove('hidden'); modal?.classList.add('flex');
            }));
            document.querySelectorAll('[data-modal]').forEach(modal => modal.addEventListener('click', event => {
                if (event.target === modal || event.target.closest('[data-modal-close]')) { modal.classList.add('hidden'); modal.classList.remove('flex'); }
            }));
            @if($errors->any()) document.querySelector('[data-modal]')?.classList.remove('hidden'); document.querySelector('[data-modal]')?.classList.add('flex'); @endif
        });
    </script>
</x-app-layout>
