<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-[11px] font-black uppercase tracking-[0.22em] text-blue-600">Smart access</p>
                <h1 class="text-3xl font-black tracking-[-0.04em] text-[#071a3b]">TT Lock management</h1>
                <p class="mt-2 text-sm text-slate-500">Credential groups and installed locks, kept separate from unit assignment.</p>
            </div>
            <div class="flex gap-2">
                <button type="button" x-data x-on:click="$dispatch('open-modal', 'add-lock')" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-black text-[#071a3b]">Add lock</button>
                <button type="button" x-data x-on:click="$dispatch('open-modal', 'add-lock-group')" class="rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-black text-white shadow-lg shadow-blue-600/20">Add API group</button>
            </div>
        </div>
    </x-slot>

    @php
        $availableLocks = $locks->filter(fn ($lock) => ! $lock->unit)->count();
        $lowBattery = $locks->filter(fn ($lock) => $lock->battery_level !== null && $lock->battery_level < 25)->count();
    @endphp

    <div class="space-y-5">
        @if(session('status'))<div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-700">{{ session('status') }}</div>@endif
        @if($errors->any())<div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-bold text-rose-700">{{ $errors->first() }}</div>@endif

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach([
                ['label' => 'Installed locks', 'value' => $locks->count(), 'tone' => 'text-[#071a3b]'],
                ['label' => 'Available to assign', 'value' => $availableLocks, 'tone' => 'text-emerald-600'],
                ['label' => 'Low battery', 'value' => $lowBattery, 'tone' => 'text-amber-600'],
                ['label' => 'API groups', 'value' => $settings->count(), 'tone' => 'text-blue-600'],
            ] as $stat)
                <div class="erp-card p-5"><p class="text-[11px] font-black uppercase tracking-[0.16em] text-slate-400">{{ $stat['label'] }}</p><p class="mt-2 text-2xl font-black {{ $stat['tone'] }}">{{ $stat['value'] }}</p></div>
            @endforeach
        </div>

        <nav class="erp-card flex gap-2 overflow-x-auto p-2 text-sm font-black">
            <a href="#locks" class="whitespace-nowrap rounded-xl bg-blue-50 px-4 py-2.5 text-blue-700">Installed locks</a>
            <a href="#api-groups" class="whitespace-nowrap rounded-xl px-4 py-2.5 text-slate-500 hover:bg-slate-50">API credential groups</a>
        </nav>

        <section id="locks" class="erp-card overflow-hidden">
            <div class="flex items-center justify-between gap-3 border-b border-slate-100 p-5">
                <div><h2 class="text-lg font-black text-[#071a3b]">Installed locks</h2><p class="mt-1 text-sm text-slate-500">Select an available lock from the unit create or edit page.</p></div>
                <button type="button" x-data x-on:click="$dispatch('open-modal', 'add-lock')" class="rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-black text-white">+ Add lock</button>
            </div>
            <div class="hidden overflow-x-auto md:block">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-[11px] font-black uppercase tracking-[0.16em] text-slate-500"><tr><th class="px-5 py-3">Lock</th><th class="px-5 py-3">Connection</th><th class="px-5 py-3">Health</th><th class="px-5 py-3">Assigned unit</th><th class="px-5 py-3">Last sync</th><th class="px-5 py-3 text-right">Actions</th></tr></thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse($locks as $lock)
                            <tr class="hover:bg-slate-50/70">
                                <td class="px-5 py-4"><p class="font-black text-[#071a3b]">{{ $lock->lock_name }}</p><p class="mt-1 text-xs text-slate-500">{{ $lock->lock_alias ?: 'No alias' }} · {{ $lock->lock_id }}</p></td>
                                <td class="px-5 py-4 text-xs text-slate-600"><p>{{ $lock->setting?->name ?: 'No API group' }}</p><p class="mt-1">Gateway {{ $lock->gateway_id ?: 'not set' }}</p></td>
                                <td class="px-5 py-4"><div class="flex items-center gap-2"><span class="font-black {{ ($lock->battery_level ?? 100) < 25 ? 'text-amber-600' : 'text-[#071a3b]' }}">{{ $lock->battery_level === null ? '—' : $lock->battery_level.'%' }}</span><span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-600">{{ str($lock->status)->replace('_', ' ')->headline() }}</span></div></td>
                                <td class="px-5 py-4 text-sm">@if($lock->unit)<span class="font-bold text-[#071a3b]">{{ $lock->unit->building?->name }}</span><span class="block text-xs text-slate-500">Unit {{ $lock->unit->unit_no }}</span>@else<span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-700">Available</span>@endif</td>
                                <td class="px-5 py-4 text-xs text-slate-500">{{ $lock->last_synced_at?->diffForHumans() ?? 'Never' }}</td>
                                <td class="px-5 py-4"><div class="flex justify-end gap-2"><button type="button" x-data x-on:click="$dispatch('open-modal', 'edit-lock-{{ $lock->id }}')" class="rounded-xl border border-slate-200 px-3 py-2 text-xs font-black text-[#071a3b]">Edit</button><form method="POST" action="{{ route('tt-lock-settings.locks.destroy', $lock) }}" onsubmit="return confirm('Delete this TT Lock?')">@csrf @method('DELETE')<button class="rounded-xl border border-rose-200 px-3 py-2 text-xs font-black text-rose-600">Delete</button></form></div></td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-5 py-12 text-center text-slate-500">No installed locks yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="grid gap-3 p-4 md:hidden">
                @forelse($locks as $lock)
                    <article class="rounded-2xl border border-slate-200 p-4"><div class="flex items-start justify-between gap-3"><div><h3 class="font-black text-[#071a3b]">{{ $lock->lock_name }}</h3><p class="mt-1 text-xs text-slate-500">{{ $lock->lock_id }} · {{ $lock->lock_alias ?: 'No alias' }}</p></div><span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold">{{ $lock->battery_level === null ? '—' : $lock->battery_level.'%' }}</span></div><p class="mt-3 text-sm text-slate-600">{{ $lock->unit ? ($lock->unit->building?->name.' / '.$lock->unit->unit_no) : 'Available to assign' }}</p><button type="button" x-data x-on:click="$dispatch('open-modal', 'edit-lock-{{ $lock->id }}')" class="mt-4 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm font-black">Edit lock</button></article>
                @empty<p class="py-8 text-center text-sm text-slate-500">No installed locks yet.</p>@endforelse
            </div>
        </section>

        <section id="api-groups" class="erp-card overflow-hidden">
            <div class="flex items-center justify-between gap-3 border-b border-slate-100 p-5"><div><h2 class="text-lg font-black text-[#071a3b]">API credential groups</h2><p class="mt-1 text-sm text-slate-500">Credentials are masked on this page.</p></div><button type="button" x-data x-on:click="$dispatch('open-modal', 'add-lock-group')" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-black">+ Add group</button></div>
            <div class="grid gap-4 p-5 lg:grid-cols-2">
                @forelse($settings as $setting)
                    <article class="rounded-2xl border border-slate-200 p-4"><div class="flex items-start justify-between gap-3"><div><h3 class="font-black text-[#071a3b]">{{ $setting->name }}</h3><p class="mt-1 text-sm text-slate-500">{{ $setting->username }}</p></div><span class="rounded-full {{ $setting->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }} px-2.5 py-1 text-xs font-bold">{{ $setting->is_active ? 'Active' : 'Inactive' }}</span></div><dl class="mt-4 grid gap-3 sm:grid-cols-2"><div class="rounded-xl bg-slate-50 p-3"><dt class="text-[10px] font-black uppercase text-slate-400">Client ID</dt><dd class="mt-1 truncate text-sm font-bold text-[#071a3b]">•••• {{ str($setting->client_id)->substr(-4) }}</dd></div><div class="rounded-xl bg-slate-50 p-3"><dt class="text-[10px] font-black uppercase text-slate-400">Locks</dt><dd class="mt-1 text-sm font-bold text-[#071a3b]">{{ $setting->locks_count }}</dd></div></dl><div class="mt-4 flex justify-end gap-2"><button type="button" x-data x-on:click="$dispatch('open-modal', 'edit-lock-group-{{ $setting->id }}')" class="rounded-xl border border-slate-200 px-3 py-2 text-xs font-black">Edit</button><form method="POST" action="{{ route('tt-lock-settings.groups.destroy', $setting) }}" onsubmit="return confirm('Delete this credential group?')">@csrf @method('DELETE')<button class="rounded-xl border border-rose-200 px-3 py-2 text-xs font-black text-rose-600">Delete</button></form></div></article>
                @empty<p class="py-8 text-center text-sm text-slate-500 lg:col-span-2">No API credential groups yet.</p>@endforelse
            </div>
        </section>
    </div>

    <x-modal name="add-lock-group" maxWidth="lg" focusable><form method="POST" action="{{ route('tt-lock-settings.groups.store') }}" class="p-6">@csrf @include('tt-lock-settings.partials.group-form', ['setting' => null, 'title' => 'Add API credential group'])</form></x-modal>
    <x-modal name="add-lock" maxWidth="xl" focusable><form method="POST" action="{{ route('tt-lock-settings.locks.store') }}" class="p-6">@csrf @include('tt-lock-settings.partials.lock-form', ['lock' => null, 'title' => 'Add installed lock'])</form></x-modal>
    @foreach($settings as $setting)<x-modal name="edit-lock-group-{{ $setting->id }}" maxWidth="lg" focusable><form method="POST" action="{{ route('tt-lock-settings.groups.update', $setting) }}" class="p-6">@csrf @method('PATCH') @include('tt-lock-settings.partials.group-form', ['setting' => $setting, 'title' => 'Edit API credential group'])</form></x-modal>@endforeach
    @foreach($locks as $lock)<x-modal name="edit-lock-{{ $lock->id }}" maxWidth="xl" focusable><form method="POST" action="{{ route('tt-lock-settings.locks.update', $lock) }}" class="p-6">@csrf @method('PATCH') @include('tt-lock-settings.partials.lock-form', ['lock' => $lock, 'title' => 'Edit installed lock'])</form></x-modal>@endforeach
</x-app-layout>
