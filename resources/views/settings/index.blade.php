<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-[11px] font-black uppercase tracking-[0.22em] text-blue-600">Administration</p>
            <h1 class="text-3xl font-black tracking-[-0.04em] text-[#071a3b]">System settings</h1>
            <p class="mt-2 text-sm text-slate-500">Environment, mail, queue, S3, and connection health.</p>
        </div>
    </x-slot>

    <div class="space-y-5">
        @if(session('status'))<div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-700">{{ session('status') }}</div>@endif
        @if($errors->any())<div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-bold text-rose-700">{{ $errors->first() }}</div>@endif

        <section class="grid gap-4 md:grid-cols-4">
            @foreach($statuses as $label => $status)
                <article class="erp-card p-5">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-black text-[#071a3b]">{{ $label }}</p>
                            <p class="mt-2 text-xs leading-5 text-slate-500">{{ str($status['message'])->limit(90) }}</p>
                        </div>
                        <span class="h-3 w-3 rounded-full {{ $status['ok'] ? 'bg-emerald-400' : 'bg-rose-400' }}"></span>
                    </div>
                </article>
            @endforeach
        </section>

        <section class="erp-card p-5">
            <h2 class="text-lg font-black text-[#071a3b]">Connection tests</h2>
            <div class="mt-4 flex flex-wrap gap-2">
                @foreach(['database' => 'Database', 's3' => 'AWS S3', 'mail' => 'Mail', 'queue' => 'Queue'] as $type => $label)
                    <form method="POST" action="{{ route('settings.test') }}">@csrf<input type="hidden" name="type" value="{{ $type }}"><button class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-bold text-slate-600">{{ $label }} test</button></form>
                @endforeach
            </div>
            <p class="mt-4 rounded-2xl bg-blue-50 p-4 text-sm text-blue-700">For fast emails, keep <strong>QUEUE_CONNECTION=database</strong> and run <strong>php artisan queue:work</strong> in a separate terminal.</p>
        </section>

        <form method="POST" action="{{ route('settings.update') }}" class="erp-card overflow-hidden">
            @csrf
            @method('PATCH')
            <div class="border-b border-slate-100 p-5">
                <h2 class="text-lg font-black text-[#071a3b]">Environment values</h2>
                <p class="mt-1 text-sm text-slate-500">Sensitive values are editable here. After changing production config, clear config cache.</p>
            </div>
            <div class="grid gap-4 p-5 md:grid-cols-2 xl:grid-cols-3">
                @foreach($values as $key => $value)
                    <label>
                        <span class="text-xs font-black uppercase tracking-[0.14em] text-slate-400">{{ $key }}</span>
                        <input name="{{ $key }}" value="{{ $value }}" class="erp-focus mt-1 h-11 w-full rounded-xl border border-slate-200 px-3 text-sm" autocomplete="off">
                    </label>
                @endforeach
            </div>
            <div class="flex justify-end border-t border-slate-100 bg-slate-50 p-5">
                <button class="rounded-xl bg-blue-600 px-5 py-3 text-sm font-black text-white">Save settings</button>
            </div>
        </form>
    </div>
</x-app-layout>
