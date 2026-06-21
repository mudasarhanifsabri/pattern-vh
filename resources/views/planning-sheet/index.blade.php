<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-[11px] font-black uppercase tracking-[0.22em] text-blue-600">Operations planner</p>
                <h1 class="text-3xl font-black tracking-[-0.04em] text-[#071a3b]">Planning sheet</h1>
                <p class="mt-2 text-sm text-slate-500">Check-ins, check-outs, cleaning, maintenance, pending invoices, DTCM, collections, and utility due dates in one schedule.</p>
            </div>
            <a href="{{ route('planning-sheet.pdf', request()->query()) }}" target="_blank" class="inline-flex items-center justify-center rounded-2xl bg-blue-600 px-5 py-3 text-sm font-black text-white shadow-lg shadow-blue-600/20">Download PDF</a>
        </div>
    </x-slot>

    <div class="space-y-5">
        <form method="GET" class="erp-card grid gap-3 p-4 md:grid-cols-[180px_180px_180px_1fr_auto] md:items-end">
            <label class="block">
                <span class="text-xs font-black text-slate-500">Range</span>
                <select name="preset" class="erp-focus mt-1 h-12 w-full rounded-2xl border border-slate-200 bg-white px-4 text-sm font-bold">
                    @foreach(['2_days' => 'Next 2 days', '7_days' => 'Next 7 days', '14_days' => 'Next 2 weeks', 'month' => 'This month', 'custom' => 'Custom'] as $value => $label)
                        <option value="{{ $value }}" @selected($preset === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block">
                <span class="text-xs font-black text-slate-500">Start date</span>
                <input type="date" name="start" value="{{ $start->toDateString() }}" class="erp-focus mt-1 h-12 w-full rounded-2xl border border-slate-200 px-4 text-sm font-bold">
            </label>
            <label class="block">
                <span class="text-xs font-black text-slate-500">End date</span>
                <input type="date" name="end" value="{{ $end->toDateString() }}" class="erp-focus mt-1 h-12 w-full rounded-2xl border border-slate-200 px-4 text-sm font-bold">
            </label>
            <div class="rounded-2xl bg-blue-50 px-4 py-3 text-xs font-bold leading-5 text-blue-700">
                Showing {{ $start->format('M d, Y') }} to {{ $end->format('M d, Y') }}. Custom ranges are capped at 63 days for fast PDF generation.
            </div>
            <button class="rounded-2xl bg-slate-900 px-5 py-3 text-sm font-black text-white">Apply</button>
        </form>

        <div class="grid gap-4 md:grid-cols-3 xl:grid-cols-6">
            @foreach([
                ['Check-ins', $stats['check_ins'], 'blue'],
                ['Check-outs', $stats['check_outs'], 'slate'],
                ['Tasks', $stats['tasks'], 'emerald'],
                ['Pending invoices', $stats['pending_invoices'], 'rose'],
                ['Collections', $stats['collections'], 'violet'],
                ['Utilities', $stats['utilities'], 'amber'],
            ] as [$label, $value, $tone])
                <div class="erp-card p-5">
                    <p class="text-[11px] font-black uppercase tracking-[0.16em] text-slate-400">{{ $label }}</p>
                    <p class="mt-3 text-3xl font-black text-[#071a3b]">{{ $value }}</p>
                </div>
            @endforeach
        </div>

        <section class="erp-card overflow-hidden">
            <div class="border-b border-slate-200 p-5">
                <p class="text-[11px] font-black uppercase tracking-[0.22em] text-blue-600">Daily operations</p>
                <h2 class="mt-1 text-xl font-black text-[#071a3b]">Schedule board</h2>
            </div>
            <div class="grid divide-y divide-slate-100 lg:grid-cols-2 lg:divide-x lg:divide-y-0 xl:grid-cols-3">
                @foreach($days as $day)
                    <article class="min-h-[260px] p-5">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="text-lg font-black text-[#071a3b]">{{ $day['date']->format('D d') }}</p>
                                <p class="text-xs font-bold text-slate-400">{{ $day['date']->format('F Y') }}</p>
                            </div>
                            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-600">{{ $day['events']->count() }} items</span>
                        </div>

                        <div class="mt-4 space-y-3">
                            @forelse($day['events'] as $event)
                                @php($tone = [
                                    'blue' => 'border-blue-200 bg-blue-50 text-blue-700',
                                    'emerald' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
                                    'amber' => 'border-amber-200 bg-amber-50 text-amber-700',
                                    'rose' => 'border-rose-200 bg-rose-50 text-rose-700',
                                    'violet' => 'border-violet-200 bg-violet-50 text-violet-700',
                                    'cyan' => 'border-cyan-200 bg-cyan-50 text-cyan-700',
                                    'slate' => 'border-slate-200 bg-slate-50 text-slate-700',
                                ][$event['tone']] ?? 'border-slate-200 bg-slate-50 text-slate-700')
                                <a href="{{ $event['url'] }}" class="block rounded-2xl border p-4 transition hover:-translate-y-0.5 hover:shadow-lg {{ $tone }}">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <p class="text-[10px] font-black uppercase tracking-[0.16em] opacity-70">{{ $event['type'] }} / {{ $event['time'] }}</p>
                                            <p class="mt-1 text-sm font-black text-[#071a3b]">{{ $event['title'] }}</p>
                                            <p class="mt-1 text-xs font-bold opacity-80">{{ $event['subtitle'] }}</p>
                                        </div>
                                        <span class="rounded-full bg-white/80 px-2.5 py-1 text-[10px] font-black">{{ $event['status'] }}</span>
                                    </div>
                                </a>
                            @empty
                                <div class="rounded-2xl border border-dashed border-slate-200 p-6 text-center text-sm font-bold text-slate-400">No planned work.</div>
                            @endforelse
                        </div>
                    </article>
                @endforeach
            </div>
        </section>
    </div>
</x-app-layout>
