<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-[11px] font-bold uppercase tracking-[0.22em] text-blue-600">{{ $tenantPortal ? 'My stay inspection' : 'Apartment inspection' }}</p>
            <h1 class="text-2xl font-bold text-[#071a3b]">{{ $booking->unit->building->name }} / Unit {{ $booking->unit->unit_no }}</h1>
            <p class="mt-2 text-sm text-slate-500">{{ $booking->booking_no }} / {{ $booking->tenant->full_name }} / {{ $booking->check_in_date->format('M d, Y') }} to {{ $booking->check_out_date->format('M d, Y') }}</p>
        </div>
    </x-slot>

    @php
        $flatItems = collect($groups)->flatMap(fn ($items, $area) => collect($items)->map(fn ($item) => [$area, $item]));
        $completed = $booking->checkInInspectionItems->count();
        $total = max($flatItems->count(), 1);
        $completion = min(100, round(($completed / $total) * 100));
        $openIssues = $booking->checkInInspectionItems->whereIn('condition_status', ['damaged', 'missing', 'needs_attention'])->count();
    @endphp

    <div class="space-y-5">
        @if (session('status'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ $errors->first() }}</div>
        @endif

        <section class="overflow-hidden rounded-[2rem] bg-gradient-to-br from-[#061a38] via-[#0d2b5c] to-[#2563eb] p-5 text-white shadow-2xl shadow-blue-950/20">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.22em] text-blue-200">{{ str($booking->unit->unit_type)->headline() }} inspection</p>
                    <h2 class="mt-3 text-3xl font-black tracking-[-0.04em]">Full apartment condition report</h2>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-blue-100">Use this for check-in condition, checkout comparison, damage notes, and deposit refund evidence.</p>
                </div>
                <div class="grid grid-cols-3 gap-2 text-center sm:min-w-[380px]">
                    <div class="rounded-2xl bg-white/10 p-3"><p class="text-2xl font-black">{{ $completion }}%</p><p class="text-[11px] text-blue-100">Complete</p></div>
                    <div class="rounded-2xl bg-white/10 p-3"><p class="text-2xl font-black">{{ $completed }}</p><p class="text-[11px] text-blue-100">Items</p></div>
                    <div class="rounded-2xl bg-white/10 p-3"><p class="text-2xl font-black">{{ $openIssues }}</p><p class="text-[11px] text-blue-100">Issues</p></div>
                </div>
            </div>
        </section>

        <div class="grid gap-5 xl:grid-cols-[1fr_340px]">
            <section class="erp-card overflow-hidden">
                <div class="border-b border-slate-100 p-5">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h2 class="text-lg font-black text-[#071a3b]">Inspection checklist</h2>
                            <p class="mt-1 text-sm text-slate-500">Grouped by unit type. Mark every item and add notes where needed.</p>
                        </div>
                        <a href="{{ route('bookings.show', $booking) }}" class="rounded-xl border border-slate-200 px-4 py-2.5 text-xs font-bold text-slate-600">Back to booking</a>
                    </div>
                </div>

                <form method="POST" action="{{ $tenantPortal ? route('bookings.tenant-check-in-report', $booking) : route('bookings.inspection.store', $booking) }}" class="p-5">
                    @csrf

                    <div class="space-y-5">
                        @php($index = 0)
                        @foreach($groups as $area => $items)
                            <div class="rounded-[1.5rem] border border-slate-200 bg-slate-50 p-4">
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <p class="text-xs font-black uppercase tracking-[0.16em] text-blue-600">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</p>
                                        <h3 class="mt-1 text-lg font-black text-[#071a3b]">{{ $area }}</h3>
                                    </div>
                                    <span class="rounded-full bg-white px-3 py-1 text-xs font-bold text-slate-500">{{ count($items) }} checks</span>
                                </div>

                                <div class="mt-4 grid gap-3 md:grid-cols-2">
                                    @foreach($items as $item)
                                        @php($existing = $existingItems->get($area.'|'.$item))
                                        <div class="rounded-2xl border border-slate-200 bg-white p-3">
                                            <input type="hidden" name="items[{{ $index }}][area]" value="{{ $area }}">
                                            <input type="hidden" name="items[{{ $index }}][item]" value="{{ $item }}">
                                            <div class="flex items-start justify-between gap-3">
                                                <div>
                                                    <p class="font-bold text-[#071a3b]">{{ $item }}</p>
                                                    @if($existing)
                                                        <p class="mt-1 text-[11px] font-bold text-slate-400">Saved as {{ str($existing->condition_status)->replace('_', ' ')->headline() }}</p>
                                                    @endif
                                                </div>
                                                <select name="items[{{ $index }}][condition_status]" class="erp-focus h-10 rounded-xl border border-slate-200 bg-white px-2 text-xs font-bold">
                                                    @foreach($conditionOptions as $option)
                                                        <option value="{{ $option }}" @selected(($existing->condition_status ?? 'good') === $option)>{{ str($option)->replace('_', ' ')->headline() }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <input name="items[{{ $index }}][notes]" value="{{ $existing->notes ?? '' }}" class="erp-focus mt-3 h-10 w-full rounded-xl border border-slate-200 px-3 text-xs" placeholder="Remarks, damage note, missing item details">
                                            <div class="mt-3 rounded-xl border border-dashed border-slate-200 bg-slate-50 px-3 py-3 text-center text-[11px] font-bold text-slate-400">Photo upload placeholder</div>
                                        </div>
                                        @php($index++)
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-5 rounded-[1.5rem] border border-blue-100 bg-blue-50 p-4">
                        <label class="text-sm font-black text-[#071a3b]" for="completion_notes">Inspection summary</label>
                        <textarea id="completion_notes" name="completion_notes" rows="4" class="erp-focus mt-2 w-full rounded-xl border border-blue-100 bg-white px-3 py-2 text-sm" placeholder="Overall apartment condition, urgent repair notes, or checkout damage summary."></textarea>
                        <button class="mt-3 rounded-xl bg-blue-600 px-5 py-3 text-sm font-black text-white">{{ $tenantPortal ? 'Submit check-in inspection' : 'Save full inspection' }}</button>
                    </div>
                </form>
            </section>

            <aside class="space-y-5">
                <div class="erp-card p-5">
                    <h2 class="text-lg font-black text-[#071a3b]">Inspection guide</h2>
                    <div class="mt-4 space-y-3 text-sm text-slate-600">
                        <p class="rounded-2xl bg-blue-50 p-4">Use <strong>Good</strong> only when item is clean, working, and not damaged.</p>
                        <p class="rounded-2xl bg-amber-50 p-4">Use <strong>Needs attention</strong> for maintenance or unclear cases.</p>
                        <p class="rounded-2xl bg-rose-50 p-4">Use <strong>Damaged</strong> or <strong>Missing</strong> when deposit deduction may apply.</p>
                    </div>
                </div>

                <div class="erp-card p-5">
                    <h2 class="text-lg font-black text-[#071a3b]">Saved issues</h2>
                    <div class="mt-4 space-y-3">
                        @forelse($booking->checkInInspectionItems->whereIn('condition_status', ['damaged', 'missing', 'needs_attention']) as $issue)
                            <div class="rounded-2xl border border-slate-200 p-3">
                                <p class="text-sm font-black text-[#071a3b]">{{ $issue->area }} / {{ $issue->item }}</p>
                                <p class="mt-1 text-xs font-bold text-amber-700">{{ str($issue->condition_status)->replace('_', ' ')->headline() }}</p>
                                <p class="mt-2 text-xs text-slate-500">{{ $issue->notes ?: 'No notes added.' }}</p>
                            </div>
                        @empty
                            <p class="rounded-2xl border border-dashed border-slate-200 px-4 py-8 text-center text-sm text-slate-500">No issues saved yet.</p>
                        @endforelse
                    </div>
                </div>

                @unless($tenantPortal)
                    <div class="erp-card p-5">
                        <h2 class="text-lg font-black text-[#071a3b]">Task timeline</h2>
                        <div class="mt-4 space-y-3">
                            @forelse($booking->tasks->flatMap->events->sortByDesc('created_at')->take(6) as $event)
                                <div class="rounded-2xl bg-slate-50 p-3 text-xs">
                                    <p class="font-black text-[#071a3b]">{{ str($event->event_type)->replace('_', ' ')->headline() }}</p>
                                    <p class="mt-1 text-slate-500">{{ $event->description }}</p>
                                    <p class="mt-1 text-slate-400">{{ $event->created_at->format('M d, H:i') }}</p>
                                </div>
                            @empty
                                <p class="rounded-2xl border border-dashed border-slate-200 px-4 py-8 text-center text-sm text-slate-500">No timeline yet.</p>
                            @endforelse
                        </div>
                    </div>
                @endunless
            </aside>
        </div>
    </div>
</x-app-layout>
