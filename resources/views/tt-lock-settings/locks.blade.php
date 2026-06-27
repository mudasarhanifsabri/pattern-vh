<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-[11px] font-black uppercase tracking-[0.22em] text-blue-600">Smart access</p>
                <h1 class="text-3xl font-black tracking-[-0.04em] text-[#071a3b]">TTLock locks list</h1>
                <p class="mt-2 text-sm text-slate-500">Paginated lock inventory, health, assigned units, and recent activity.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('tt-lock-settings.index') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-black text-[#071a3b]">API groups</a>
                <a href="{{ route('tt-lock-settings.activity') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-black text-[#071a3b]">Activity logs</a>
                <button type="button" x-data x-on:click="$dispatch('open-modal', 'add-lock')" class="rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-black text-white shadow-lg shadow-blue-600/20">Add lock</button>
            </div>
        </div>
    </x-slot>

    <div class="space-y-5">
        @if(session('status'))<div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-700">{{ session('status') }}</div>@endif
        @if($errors->any())<div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-bold text-rose-700">{{ $errors->first() }}</div>@endif
        @if(! $schemaReady)
            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-bold leading-6 text-amber-800">TTLock database tables are not fully ready yet.</div>
        @endif

        <section class="erp-card p-5">
            <form method="GET" class="grid gap-3 lg:grid-cols-[1fr_220px_auto] lg:items-end">
                <label class="text-xs font-black uppercase tracking-[0.14em] text-slate-400">Search
                    <input name="search" value="{{ request('search') }}" class="erp-focus mt-1 h-11 w-full rounded-xl border border-slate-200 px-3 text-sm" placeholder="Lock, alias, ID, unit">
                </label>
                <label class="text-xs font-black uppercase tracking-[0.14em] text-slate-400">Status
                    <select name="status" class="erp-focus mt-1 h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm">
                        <option value="">All statuses</option>
                        @foreach($statuses as $status)
                            <option value="{{ $status }}" @selected(request('status') === $status)>{{ str($status)->replace('_', ' ')->headline() }}</option>
                        @endforeach
                    </select>
                </label>
                <button class="h-11 rounded-xl bg-slate-900 px-4 text-sm font-black text-white">Filter</button>
            </form>
        </section>

        <section class="erp-card overflow-hidden">
            <div class="flex items-center justify-between gap-3 border-b border-slate-100 p-5">
                <div>
                    <h2 class="text-lg font-black text-[#071a3b]">Installed locks</h2>
                    <p class="mt-1 text-sm text-slate-500">{{ method_exists($locks, 'total') ? $locks->total() : $locks->count() }} lock(s) found.</p>
                </div>
                <button type="button" x-data x-on:click="$dispatch('open-modal', 'add-lock')" class="rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-black text-white">Add lock</button>
            </div>

            <div class="hidden overflow-x-auto md:block">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-[11px] font-black uppercase tracking-[0.16em] text-slate-500">
                        <tr><th class="px-5 py-3">Lock</th><th class="px-5 py-3">Connection</th><th class="px-5 py-3">Health</th><th class="px-5 py-3">Assigned unit</th><th class="px-5 py-3">Recent access</th><th class="px-5 py-3 text-right">Actions</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse($locks as $lock)
                            @php($recent = $lock->events->first())
                            <tr class="hover:bg-slate-50/70">
                                <td class="px-5 py-4"><p class="font-black text-[#071a3b]">{{ $lock->lock_name }}</p><p class="mt-1 text-xs text-slate-500">{{ $lock->lock_alias ?: 'No alias' }} / {{ $lock->lock_id }}</p></td>
                                <td class="px-5 py-4 text-xs text-slate-600"><p>{{ $lock->setting?->name ?: 'No API group' }}</p><p class="mt-1">{{ $lock->gateway_id ? 'Gateway '.$lock->gateway_id : 'Bluetooth only / no gateway' }}</p></td>
                                <td class="px-5 py-4"><div class="flex flex-wrap items-center gap-2"><span class="font-black {{ ($lock->battery_level ?? 100) < 25 ? 'text-amber-600' : 'text-[#071a3b]' }}">{{ $lock->battery_level === null ? '-' : $lock->battery_level.'%' }}</span><span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-600">{{ str($lock->status)->replace('_', ' ')->headline() }}</span></div><p class="mt-1 text-xs text-slate-500">{{ $lock->last_synced_at?->diffForHumans() ?? 'Not synced' }}</p></td>
                                <td class="px-5 py-4">@if($lock->unit)<span class="font-bold text-[#071a3b]">{{ $lock->unit->building?->name }}</span><span class="block text-xs text-slate-500">Unit {{ $lock->unit->unit_no }}</span>@else<span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-700">Available</span>@endif</td>
                                <td class="px-5 py-4 text-xs text-slate-500">@if($recent)<span class="font-black text-[#071a3b]">{{ str($recent->event_type)->replace('_', ' ')->headline() }}</span><span class="block">{{ $recent->event_at?->diffForHumans() ?? $recent->created_at->diffForHumans() }}</span>@else No history yet @endif</td>
                                <td class="px-5 py-4"><div class="flex justify-end gap-2"><a href="{{ route('tt-lock-settings.activity', ['search' => $lock->lock_id]) }}" class="rounded-xl bg-indigo-50 px-3 py-2 text-xs font-black text-indigo-700">Activity</a><button type="button" x-data x-on:click="$dispatch('open-modal', 'edit-lock-{{ $lock->id }}')" class="rounded-xl border border-slate-200 px-3 py-2 text-xs font-black text-[#071a3b]">Edit</button><form method="POST" action="{{ route('tt-lock-settings.locks.destroy', $lock) }}" onsubmit="return confirm('Delete this TT Lock?')">@csrf @method('DELETE')<button class="rounded-xl border border-rose-200 px-3 py-2 text-xs font-black text-rose-600">Delete</button></form></div></td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-5 py-12 text-center text-slate-500">No locks found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="grid gap-3 p-4 md:hidden">
                @forelse($locks as $lock)
                    <article class="rounded-2xl border border-slate-200 p-4">
                        <div class="flex items-start justify-between gap-3"><div><h3 class="font-black text-[#071a3b]">{{ $lock->lock_name }}</h3><p class="mt-1 text-xs text-slate-500">{{ $lock->lock_id }} / {{ $lock->lock_alias ?: 'No alias' }}</p></div><span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold">{{ $lock->battery_level === null ? '-' : $lock->battery_level.'%' }}</span></div>
                        <p class="mt-3 text-sm text-slate-600">{{ $lock->unit ? ($lock->unit->building?->name.' / Unit '.$lock->unit->unit_no) : 'Available to assign' }}</p>
                        <div class="mt-4 grid grid-cols-2 gap-2"><a href="{{ route('tt-lock-settings.activity', ['search' => $lock->lock_id]) }}" class="rounded-xl bg-indigo-50 px-3 py-2 text-center text-sm font-black text-indigo-700">Activity</a><button type="button" x-data x-on:click="$dispatch('open-modal', 'edit-lock-{{ $lock->id }}')" class="rounded-xl border border-slate-200 px-3 py-2 text-sm font-black">Edit</button></div>
                    </article>
                @empty
                    <p class="py-8 text-center text-sm text-slate-500">No locks found.</p>
                @endforelse
            </div>

            @if(method_exists($locks, 'links'))
                <div class="border-t border-slate-100 px-5 py-4">{{ $locks->links() }}</div>
            @endif
        </section>
    </div>

    <x-modal name="add-lock" maxWidth="xl" focusable><form method="POST" action="{{ route('tt-lock-settings.locks.store') }}" class="p-6">@csrf @include('tt-lock-settings.partials.lock-form', ['lock' => null, 'title' => 'Add installed lock'])</form></x-modal>
    @foreach($locks as $lock)
        <x-modal name="edit-lock-{{ $lock->id }}" maxWidth="xl" focusable><form method="POST" action="{{ route('tt-lock-settings.locks.update', $lock) }}" class="p-6">@csrf @method('PATCH') @include('tt-lock-settings.partials.lock-form', ['lock' => $lock, 'title' => 'Edit installed lock'])</form></x-modal>
    @endforeach
</x-app-layout>
