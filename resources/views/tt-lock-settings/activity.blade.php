<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-[11px] font-black uppercase tracking-[0.22em] text-blue-600">Smart access</p>
                <h1 class="text-3xl font-black tracking-[-0.04em] text-[#071a3b]">TTLock activity logs</h1>
                <p class="mt-2 text-sm text-slate-500">Paginated access history from API sync and TTLock callbacks.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('tt-lock-settings.index') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-black text-[#071a3b]">API groups</a>
                <a href="{{ route('tt-lock-settings.locks.index') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-black text-[#071a3b]">Locks list</a>
            </div>
        </div>
    </x-slot>

    <div class="space-y-5">
        @if(session('status'))<div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-700">{{ session('status') }}</div>@endif
        @if($errors->any())<div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-bold text-rose-700">{{ $errors->first() }}</div>@endif
        @if(! $schemaReady)
            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-bold leading-6 text-amber-800">TTLock activity table is not ready yet.</div>
        @endif

        <section class="erp-card p-5">
            <form method="GET" class="grid gap-3 lg:grid-cols-[1fr_260px_auto] lg:items-end">
                <label class="text-xs font-black uppercase tracking-[0.14em] text-slate-400">Search
                    <input name="search" value="{{ request('search') }}" class="erp-focus mt-1 h-11 w-full rounded-xl border border-slate-200 px-3 text-sm" placeholder="Lock, unit, operator, record">
                </label>
                <label class="text-xs font-black uppercase tracking-[0.14em] text-slate-400">Event type
                    <select name="event_type" class="erp-focus mt-1 h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm">
                        <option value="">All event types</option>
                        @foreach($eventTypes as $type)
                            <option value="{{ $type }}" @selected(request('event_type') === $type)>{{ str($type)->replace('_', ' ')->headline() }}</option>
                        @endforeach
                    </select>
                </label>
                <button class="h-11 rounded-xl bg-slate-900 px-4 text-sm font-black text-white">Filter</button>
            </form>
        </section>

        <section class="erp-card overflow-hidden">
            <div class="flex flex-col gap-2 border-b border-slate-100 p-5 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-lg font-black text-[#071a3b]">Access activity</h2>
                    <p class="mt-1 text-sm text-slate-500">{{ method_exists($events, 'total') ? $events->total() : $events->count() }} record(s) found.</p>
                </div>
            </div>

            <div class="hidden overflow-x-auto md:block">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-[11px] font-black uppercase tracking-[0.16em] text-slate-500">
                        <tr><th class="px-5 py-3">Time</th><th class="px-5 py-3">Lock / unit</th><th class="px-5 py-3">Event</th><th class="px-5 py-3">Operator</th><th class="px-5 py-3">Record</th><th class="px-5 py-3">Source</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse($events as $event)
                            @php($eventUnit = $event->unit ?: $event->ttLock?->unit)
                            <tr class="hover:bg-slate-50/70">
                                <td class="whitespace-nowrap px-5 py-4 text-xs font-bold text-[#071a3b]">{{ $event->event_at?->format('M d, Y H:i') ?? $event->created_at->format('M d, Y H:i') }}</td>
                                <td class="px-5 py-4"><p class="font-black text-[#071a3b]">{{ $event->lock_name ?: $event->ttLock?->lock_name ?: 'Unknown lock' }}</p><p class="mt-1 text-xs text-slate-500">{{ $eventUnit ? ($eventUnit->building?->name.' / Unit '.$eventUnit->unit_no) : 'No unit attached' }}</p></td>
                                <td class="px-5 py-4"><span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-700">{{ str($event->event_type)->replace('_', ' ')->headline() }}</span></td>
                                <td class="px-5 py-4 text-sm text-slate-600">{{ $event->operator_name ?: 'Not provided' }}</td>
                                <td class="px-5 py-4 text-xs text-slate-500">{{ $event->record_id ?: ($event->keyboard_pwd ? 'Code '.$event->keyboard_pwd : 'Callback') }}</td>
                                <td class="px-5 py-4 text-xs font-bold text-slate-500">{{ $event->source ?: 'unknown' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-5 py-12 text-center text-slate-500">No activity records found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="grid gap-3 p-4 md:hidden">
                @forelse($events as $event)
                    @php($eventUnit = $event->unit ?: $event->ttLock?->unit)
                    <article class="rounded-2xl border border-slate-200 p-4">
                        <div class="flex items-start justify-between gap-3"><div><h3 class="font-black text-[#071a3b]">{{ str($event->event_type)->replace('_', ' ')->headline() }}</h3><p class="mt-1 text-xs text-slate-500">{{ $event->event_at?->format('M d, Y H:i') ?? $event->created_at->format('M d, Y H:i') }}</p></div><span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold">{{ $event->source ?: 'sync' }}</span></div>
                        <p class="mt-3 text-sm font-bold text-[#071a3b]">{{ $event->lock_name ?: $event->ttLock?->lock_name ?: 'Unknown lock' }}</p>
                        <p class="mt-1 text-sm text-slate-600">{{ $eventUnit ? ($eventUnit->building?->name.' / Unit '.$eventUnit->unit_no) : 'No unit attached' }}</p>
                        <p class="mt-2 text-xs text-slate-500">{{ $event->operator_name ?: 'Operator not provided' }} / {{ $event->record_id ?: 'No record id' }}</p>
                    </article>
                @empty
                    <p class="py-8 text-center text-sm text-slate-500">No activity records found.</p>
                @endforelse
            </div>

            @if(method_exists($events, 'links'))
                <div class="border-t border-slate-100 px-5 py-4">{{ $events->links() }}</div>
            @endif
        </section>
    </div>
</x-app-layout>
