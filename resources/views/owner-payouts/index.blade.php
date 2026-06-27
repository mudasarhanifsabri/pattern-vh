<x-app-layout>
    @php
        $ownerOnly = auth()->user()?->can('portal.owner')
            && ! auth()->user()?->can('accounting.view')
            && ! auth()->user()?->can('accounting.manage')
            && ! auth()->user()?->can('users.manage');
    @endphp

    <x-slot name="header">
        <div>
            <p class="text-[11px] font-bold uppercase tracking-[0.22em] text-blue-600">Accounting</p>
            <h1 class="text-3xl font-black tracking-[-0.04em] text-[#071a3b]">Owner Account Manager</h1>
            <p class="mt-2 text-sm text-slate-500">Owner payouts are calculated from approved rent collections only. Security deposits stay with the company until tenant refund workflow is completed.</p>
        </div>
    </x-slot>

    <div class="{{ $ownerOnly ? 'tenant-app-screen' : '' }} space-y-5">
        @if($ownerOnly)
            <section class="overflow-hidden rounded-[1.6rem] bg-white shadow-[0_18px_45px_rgba(15,23,42,0.08)]">
                <div class="bg-gradient-to-br from-slate-950 via-slate-800 to-blue-700 p-5 text-white">
                    <p class="text-xs font-black uppercase tracking-[0.16em] text-blue-100">Payouts</p>
                    <h2 class="mt-2 text-2xl font-black leading-tight">AED {{ number_format((float) $stats['ready'], 0) }}</h2>
                    <p class="mt-1 text-sm font-semibold text-white/70">Ready to transfer from approved rent collections.</p>
                </div>
                <div class="grid grid-cols-3 divide-x divide-slate-100 p-4 text-center">
                    <div><p class="text-[10px] font-bold uppercase text-slate-400">Upcoming</p><p class="mt-1 text-sm font-black text-[#071a3b]">{{ number_format((float) $stats['upcoming'], 0) }}</p></div>
                    <div><p class="text-[10px] font-bold uppercase text-slate-400">Transferred</p><p class="mt-1 text-sm font-black text-[#071a3b]">{{ number_format((float) $stats['transferred'], 0) }}</p></div>
                    <div><p class="text-[10px] font-bold uppercase text-slate-400">Items</p><p class="mt-1 text-sm font-black text-[#071a3b]">{{ $stats['count'] }}</p></div>
                </div>
            </section>
        @endif

        <section class="{{ $ownerOnly ? 'hidden' : 'erp-card p-5' }}">
            <form method="GET" class="grid gap-3 lg:grid-cols-[1fr_auto] lg:items-end">
                @can('owner-payouts.manage')
                    <div>
                        <x-input-label for="owner_id" value="Owner" />
                        <select id="owner_id" name="owner_id" class="erp-focus mt-1 h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm">
                            <option value="">All owners</option>
                            @foreach($owners as $ownerOption)
                                <option value="{{ $ownerOption->id }}" @selected($owner?->id === $ownerOption->id)>{{ $ownerOption->full_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button class="h-11 rounded-xl bg-slate-900 px-4 text-sm font-bold text-white">Filter</button>
                @else
                    <div class="rounded-2xl bg-blue-50 p-4 text-sm font-bold text-blue-700">Showing payouts for {{ $owner?->full_name ?? 'your owner account' }}.</div>
                @endcan
            </form>
        </section>

        @if (session('status'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-700">{{ session('status') }}</div>
        @endif

        @if (isset($errors) && $errors->any())
            <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-bold text-rose-700">{{ $errors->first() }}</div>
        @endif

        <section class="grid gap-3 {{ $ownerOnly ? 'grid-cols-2' : 'md:grid-cols-5' }}">
            @foreach([
                ['label' => 'Upcoming payouts', 'value' => 'AED '.number_format((float) $stats['upcoming'], 2), 'tone' => 'blue'],
                ['label' => 'Ready to transfer', 'value' => 'AED '.number_format((float) $stats['ready'], 2), 'tone' => 'emerald'],
                ['label' => 'Transferred', 'value' => 'AED '.number_format((float) $stats['transferred'], 2), 'tone' => 'violet'],
                ['label' => 'Forecast total', 'value' => 'AED '.number_format((float) $stats['total'], 2), 'tone' => 'slate'],
                ['label' => 'Payout items', 'value' => $stats['count'], 'tone' => 'amber'],
            ] as $card)
                <article class="{{ $ownerOnly ? 'rounded-[1.35rem] bg-white p-4 shadow-[0_14px_30px_rgba(15,23,42,0.07)]' : 'erp-card p-5' }}">
                    <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">{{ $card['label'] }}</p>
                    <p class="mt-3 text-xl font-black tracking-[-0.04em] text-[#071a3b]">{{ $card['value'] }}</p>
                </article>
            @endforeach
        </section>

        <section class="{{ $ownerOnly ? 'overflow-hidden rounded-[1.6rem] bg-white shadow-[0_18px_45px_rgba(15,23,42,0.08)]' : 'erp-card overflow-hidden' }}">
            <div class="border-b border-slate-100 p-5">
                <h2 class="text-lg font-bold text-[#071a3b]">Payout and transfer schedule</h2>
                <p class="mt-1 text-sm text-slate-500">Owners see rent payout landing dates. Deposits are excluded from owner payable amounts.</p>
            </div>

            <div class="hidden overflow-x-auto {{ $ownerOnly ? '' : 'md:block' }}">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-[11px] font-bold uppercase tracking-[0.16em] text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Owner</th>
                            <th class="px-4 py-3">Booking / Unit</th>
                            <th class="px-4 py-3">Collection</th>
                            <th class="px-4 py-3">Payable date</th>
                            <th class="px-4 py-3">Gross share</th>
                            <th class="px-4 py-3">Mgmt fee</th>
                            <th class="px-4 py-3">Net payout</th>
                            <th class="px-4 py-3">Transfer</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse($rows as $row)
                            <tr>
                                <td class="px-4 py-4 font-bold text-[#071a3b]">{{ $row['owner']->full_name }}</td>
                                <td class="px-4 py-4"><div class="font-bold text-[#071a3b]">{{ $row['booking']?->booking_no }}</div><div class="text-xs text-slate-500">{{ $row['unit']->building?->name }} / Unit {{ $row['unit']->unit_no }}</div></td>
                                <td class="px-4 py-4">{{ $row['collection_date']?->format('M d, Y') ?? '-' }}</td>
                                <td class="px-4 py-4 font-bold text-[#071a3b]">{{ $row['payable_on']?->format('M d, Y') ?? '-' }}</td>
                                <td class="px-4 py-4">AED {{ number_format($row['gross_share'], 2) }}<span class="block text-xs text-slate-400">{{ number_format($row['share_percent'], 2) }}% share</span></td>
                                <td class="px-4 py-4">AED {{ number_format($row['management_fee'], 2) }}</td>
                                <td class="px-4 py-4 font-black text-[#071a3b]">AED {{ number_format($row['net_payout'], 2) }}</td>
                                <td class="px-4 py-4">
                                    <span class="rounded-full {{ $row['status'] === 'transferred' ? 'bg-violet-50 text-violet-700' : ($row['status'] === 'ready' ? 'bg-emerald-50 text-emerald-700' : 'bg-blue-50 text-blue-700') }} px-2.5 py-1 text-xs font-bold">{{ str($row['status'])->headline() }}</span>
                                    @if($row['transfer'])
                                        <p class="mt-2 text-xs text-slate-500">{{ $row['transfer']->transferred_at->format('M d, Y') }} @if($row['transfer']->reference_no) / {{ $row['transfer']->reference_no }} @endif</p>
                                    @elseif($row['status'] === 'ready')
                                        @can('owner-payouts.manage')
                                            <form method="POST" action="{{ route('owner-payouts.transfers.store') }}" class="mt-3 grid gap-2">
                                                @csrf
                                                <input type="hidden" name="owner_id" value="{{ $row['owner']->id }}">
                                                <input type="hidden" name="payment_id" value="{{ $row['payment']->id }}">
                                                <input type="date" name="transferred_at" value="{{ now()->toDateString() }}" class="erp-focus h-9 rounded-xl border border-slate-200 px-3 text-xs">
                                                <input name="reference_no" class="erp-focus h-9 rounded-xl border border-slate-200 px-3 text-xs" placeholder="Bank ref">
                                                <button class="rounded-xl bg-emerald-600 px-3 py-2 text-xs font-black text-white">Mark transferred</button>
                                            </form>
                                        @endcan
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="px-4 py-10 text-center text-sm text-slate-500">No approved rent collections found for payout.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="space-y-3 p-4 {{ $ownerOnly ? '' : 'md:hidden' }}">
                @forelse($rows as $row)
                    <article class="rounded-3xl border border-slate-200 bg-white p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-[0.16em] text-blue-600">{{ $row['booking']?->booking_no }}</p>
                                <h3 class="mt-1 text-lg font-black text-[#071a3b]">AED {{ number_format($row['net_payout'], 2) }}</h3>
                                <p class="mt-1 text-sm text-slate-500">Will land on {{ $row['payable_on']?->format('M d, Y') ?? '-' }}</p>
                            </div>
                            <span class="rounded-full {{ $row['status'] === 'transferred' ? 'bg-violet-50 text-violet-700' : ($row['status'] === 'ready' ? 'bg-emerald-50 text-emerald-700' : 'bg-blue-50 text-blue-700') }} px-2.5 py-1 text-xs font-bold">{{ str($row['status'])->headline() }}</span>
                        </div>
                        <div class="mt-4 grid grid-cols-2 gap-3 text-xs">
                            <div class="rounded-2xl bg-slate-50 p-3"><p class="font-bold text-slate-400">Unit</p><p class="mt-1 font-bold text-[#071a3b]">{{ $row['unit']->unit_no }}</p></div>
                            <div class="rounded-2xl bg-slate-50 p-3"><p class="font-bold text-slate-400">Collected</p><p class="mt-1 font-bold text-[#071a3b]">{{ $row['collection_date']?->format('M d') }}</p></div>
                        </div>
                    </article>
                @empty
                    <p class="rounded-2xl border border-dashed border-slate-200 px-4 py-10 text-center text-sm text-slate-500">No approved rent collections found for payout.</p>
                @endforelse
            </div>
        </section>
    </div>
</x-app-layout>
