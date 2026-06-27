<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-[11px] font-black uppercase tracking-[0.22em] text-blue-600">Smart access</p>
                <h1 class="text-3xl font-black tracking-[-0.04em] text-[#071a3b]">TTLock API groups</h1>
                <p class="mt-2 text-sm text-slate-500">Manage TTLock credentials, sync locks, and sync access history.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('tt-lock-settings.locks.index') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-black text-[#071a3b]">Locks list</a>
                <a href="{{ route('tt-lock-settings.activity') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-black text-[#071a3b]">Activity logs</a>
                <button type="button" x-data x-on:click="$dispatch('open-modal', 'add-lock-group')" class="rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-black text-white shadow-lg shadow-blue-600/20">Add API group</button>
            </div>
        </div>
    </x-slot>

    <div class="space-y-5">
        @if(session('status'))<div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-700">{{ session('status') }}</div>@endif
        @if($errors->any())<div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-bold text-rose-700">{{ $errors->first() }}</div>@endif
        @if(! $schemaReady)
            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-bold leading-6 text-amber-800">
                TTLock database tables are not fully ready yet. Run migrations, then refresh this page.
            </div>
        @endif

        <div class="grid gap-4 sm:grid-cols-3">
            <article class="erp-card p-5"><p class="text-[11px] font-black uppercase tracking-[0.16em] text-slate-400">API groups</p><p class="mt-2 text-2xl font-black text-blue-600">{{ method_exists($settings, 'total') ? $settings->total() : $settings->count() }}</p></article>
            <article class="erp-card p-5"><p class="text-[11px] font-black uppercase tracking-[0.16em] text-slate-400">Synced locks</p><p class="mt-2 text-2xl font-black text-[#071a3b]">{{ $lockCount }}</p></article>
            <article class="erp-card p-5"><p class="text-[11px] font-black uppercase tracking-[0.16em] text-slate-400">Activity records</p><p class="mt-2 text-2xl font-black text-emerald-600">{{ $eventCount }}</p></article>
        </div>

        <section class="erp-card p-5">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <p class="text-[11px] font-black uppercase tracking-[0.18em] text-blue-600">TTLock callback URL</p>
                    <h2 class="mt-1 text-lg font-black text-[#071a3b]">Paste this in TTLock Callback URL</h2>
                    <p class="mt-1 text-sm text-slate-500">TTLock can push unlock records to this endpoint. Use the live HTTPS domain in production.</p>
                </div>
                <code class="break-all rounded-2xl bg-slate-50 px-4 py-3 text-xs font-bold text-slate-700">{{ $callbackUrl }}</code>
            </div>
        </section>

        <section class="erp-card overflow-hidden">
            <div class="flex items-center justify-between gap-3 border-b border-slate-100 p-5">
                <div>
                    <h2 class="text-lg font-black text-[#071a3b]">API credential groups</h2>
                    <p class="mt-1 text-sm text-slate-500">Credentials are masked on this page.</p>
                </div>
                <button type="button" x-data x-on:click="$dispatch('open-modal', 'add-lock-group')" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-black">Add group</button>
            </div>
            <div class="grid gap-4 p-5 lg:grid-cols-2">
                @forelse($settings as $setting)
                    <article class="rounded-2xl border border-slate-200 p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h3 class="font-black text-[#071a3b]">{{ $setting->name }}</h3>
                                <p class="mt-1 text-sm text-slate-500">{{ $setting->username }}</p>
                            </div>
                            <span class="rounded-full {{ $setting->last_error ? 'bg-rose-50 text-rose-700' : ($setting->access_token ? 'bg-emerald-50 text-emerald-700' : ($setting->is_active ? 'bg-blue-50 text-blue-700' : 'bg-slate-100 text-slate-600')) }} px-2.5 py-1 text-xs font-bold">
                                {{ $setting->last_error ? 'Needs review' : ($setting->access_token ? 'Connected' : ($setting->is_active ? 'Ready to test' : 'Inactive')) }}
                            </span>
                        </div>
                        <dl class="mt-4 grid gap-3 sm:grid-cols-2">
                            <div class="rounded-xl bg-slate-50 p-3"><dt class="text-[10px] font-black uppercase text-slate-400">Client ID</dt><dd class="mt-1 truncate text-sm font-bold text-[#071a3b]">**** {{ str($setting->client_id)->substr(-4) }}</dd></div>
                            <div class="rounded-xl bg-slate-50 p-3"><dt class="text-[10px] font-black uppercase text-slate-400">Locks</dt><dd class="mt-1 text-sm font-bold text-[#071a3b]">{{ $setting->locks_count ?? 0 }}</dd></div>
                            <div class="rounded-xl bg-slate-50 p-3"><dt class="text-[10px] font-black uppercase text-slate-400">Last tested</dt><dd class="mt-1 text-sm font-bold text-[#071a3b]">{{ $setting->last_tested_at?->format('M d, H:i') ?? 'Not tested' }}</dd></div>
                            <div class="rounded-xl bg-slate-50 p-3"><dt class="text-[10px] font-black uppercase text-slate-400">Token expires</dt><dd class="mt-1 text-sm font-bold text-[#071a3b]">{{ $setting->token_expires_at?->diffForHumans() ?? 'No token' }}</dd></div>
                        </dl>
                        @if($setting->last_error)
                            <p class="mt-3 rounded-2xl bg-rose-50 p-3 text-xs font-bold leading-5 text-rose-700">{{ $setting->last_error }}</p>
                        @endif
                        <div class="mt-4 flex flex-wrap justify-end gap-2">
                            <form method="POST" action="{{ route('tt-lock-settings.groups.test', $setting) }}">@csrf<button class="rounded-xl bg-emerald-600 px-3 py-2 text-xs font-black text-white">Test connection</button></form>
                            <form method="POST" action="{{ route('tt-lock-settings.groups.sync-locks', $setting) }}">@csrf<button class="rounded-xl bg-blue-600 px-3 py-2 text-xs font-black text-white">Sync locks</button></form>
                            <form method="POST" action="{{ route('tt-lock-settings.groups.sync-history', $setting) }}">@csrf<input type="hidden" name="days" value="30"><button class="rounded-xl bg-indigo-600 px-3 py-2 text-xs font-black text-white">Sync history</button></form>
                            <button type="button" x-data x-on:click="$dispatch('open-modal', 'edit-lock-group-{{ $setting->id }}')" class="rounded-xl border border-slate-200 px-3 py-2 text-xs font-black">Edit</button>
                            <form method="POST" action="{{ route('tt-lock-settings.groups.destroy', $setting) }}" onsubmit="return confirm('Delete this credential group?')">@csrf @method('DELETE')<button class="rounded-xl border border-rose-200 px-3 py-2 text-xs font-black text-rose-600">Delete</button></form>
                        </div>
                    </article>
                @empty
                    <p class="py-8 text-center text-sm text-slate-500 lg:col-span-2">No API credential groups yet.</p>
                @endforelse
            </div>
            @if(method_exists($settings, 'links'))
                <div class="border-t border-slate-100 px-5 py-4">{{ $settings->links() }}</div>
            @endif
        </section>
    </div>

    <x-modal name="add-lock-group" maxWidth="lg" focusable>
        <form method="POST" action="{{ route('tt-lock-settings.groups.store') }}" class="p-6">@csrf @include('tt-lock-settings.partials.group-form', ['setting' => null, 'title' => 'Add API credential group'])</form>
    </x-modal>
    @foreach($settings as $setting)
        <x-modal name="edit-lock-group-{{ $setting->id }}" maxWidth="lg" focusable>
            <form method="POST" action="{{ route('tt-lock-settings.groups.update', $setting) }}" class="p-6">@csrf @method('PATCH') @include('tt-lock-settings.partials.group-form', ['setting' => $setting, 'title' => 'Edit API credential group'])</form>
        </x-modal>
    @endforeach
</x-app-layout>
