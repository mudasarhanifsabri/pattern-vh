<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-[11px] font-black uppercase tracking-[0.22em] text-blue-600">Administration</p>
                <h1 class="text-3xl font-black tracking-[-0.04em] text-[#071a3b]">Software updates</h1>
                <p class="mt-2 text-sm text-slate-500">Update the ERP from the panel without touching owners, bookings, payments, or real records.</p>
            </div>
            <span class="inline-flex w-fit items-center rounded-full {{ $enabled ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' }} px-3 py-1.5 text-xs font-black">
                {{ $enabled ? 'Updater enabled' : 'Updater disabled' }}
            </span>
        </div>
    </x-slot>

    <div class="space-y-5">
        @if(session('status'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-700">{{ session('status') }}</div>
        @endif
        @if(session('warning'))
            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-bold text-amber-700">{{ session('warning') }}</div>
        @endif
        @if($errors->any())
            <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-bold text-rose-700">{{ $errors->first() }}</div>
        @endif

        <section class="grid gap-5 xl:grid-cols-[1fr_420px]">
            <form method="POST" action="{{ route('software-updates.run') }}" class="erp-card overflow-hidden">
                @csrf
                <div class="border-b border-slate-100 p-5">
                    <p class="text-[11px] font-black uppercase tracking-[0.18em] text-blue-600">Safe deploy workflow</p>
                    <h2 class="mt-1 text-xl font-black text-[#071a3b]">Run update</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-500">This runs fixed deployment steps only. It never runs seeders, never uses <code>migrate:fresh</code>, and never deletes data.</p>
                </div>

                <div class="grid gap-3 p-5 md:grid-cols-2">
                    @foreach([
                        'git_pull' => ['Download latest code', 'Runs git pull --ff-only from your connected GitHub/cPanel repository.', true],
                        'composer_install' => ['Install PHP dependencies', 'Runs composer install --no-dev --optimize-autoloader.', true],
                        'clear_cache' => ['Clear old cache', 'Clears stale config, routes, views, and app cache before migration.', true],
                        'migrate' => ['Run database migrations', 'Applies new tables/columns safely while preserving existing records.', true],
                        'build_cache' => ['Rebuild Laravel cache', 'Optimizes config, routes, events, and views for production speed.', true],
                        'npm_build' => ['Build frontend assets', 'Use this only if Node/npm is installed on cPanel. Otherwise build locally before upload.', false],
                    ] as $name => [$label, $copy, $checked])
                        <label class="flex gap-3 rounded-2xl border border-slate-200 bg-white p-4 transition hover:border-blue-200 hover:bg-blue-50/40">
                            <input type="checkbox" name="{{ $name }}" value="1" @checked($checked) class="mt-1 h-4 w-4 rounded border-slate-300 text-blue-600">
                            <span>
                                <span class="block text-sm font-black text-[#071a3b]">{{ $label }}</span>
                                <span class="mt-1 block text-xs leading-5 text-slate-500">{{ $copy }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>

                <div class="flex flex-col gap-3 border-t border-slate-100 bg-slate-50 p-5 sm:flex-row sm:items-center sm:justify-between">
                    <div class="space-y-1 text-xs font-bold text-slate-500">
                        <p>PHP binary: <span class="text-slate-700">{{ $phpBinary }}</span></p>
                        <p>Git: <span class="text-slate-700">{{ $gitBinary }}</span> / Composer: <span class="text-slate-700">{{ $composerBinary }}</span></p>
                    </div>
                    <button @disabled(! $enabled) class="rounded-2xl bg-blue-600 px-5 py-3 text-sm font-black text-white shadow-lg shadow-blue-600/20 transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-slate-300">
                        Run software update
                    </button>
                </div>
            </form>

            <aside class="space-y-5">
                <div class="erp-card p-5">
                    <h2 class="text-lg font-black text-[#071a3b]">Production rules</h2>
                    <div class="mt-4 space-y-3 text-sm leading-6 text-slate-600">
                        <p class="rounded-2xl bg-emerald-50 p-4 text-emerald-800"><strong>Real data safe:</strong> updates run migrations only, so records stay in place.</p>
                        <p class="rounded-2xl bg-blue-50 p-4 text-blue-800"><strong>Before first cPanel use:</strong> set <code>SEED_DEMO_DATA=false</code> in production.</p>
                        <p class="rounded-2xl bg-amber-50 p-4 text-amber-800"><strong>Never use:</strong> <code>php artisan migrate:fresh</code> on live data.</p>
                    </div>
                </div>

                <div class="erp-card p-5">
                    <h2 class="text-lg font-black text-[#071a3b]">When to click</h2>
                    <ul class="mt-4 space-y-3 text-sm leading-6 text-slate-600">
                        <li>After I push a new version to GitHub.</li>
                        <li>After cPanel shows your repository has new commits.</li>
                        <li>After uploading changed files manually.</li>
                    </ul>
                </div>

                <div class="erp-card p-5">
                    <h2 class="text-lg font-black text-[#071a3b]">Share production logs</h2>
                    <p class="mt-1 text-sm leading-6 text-slate-500">Download or create a safe copy of recent logs when you need to send an error to support.</p>
                    <div class="mt-4 space-y-3">
                        @forelse($productionLogs as $log)
                            <div class="rounded-2xl border border-slate-200 p-3">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="text-sm font-black text-[#071a3b]">{{ $log['label'] }}</p>
                                        <p class="mt-1 text-xs text-slate-500">{{ $log['updated_at'] }} / {{ $log['size'] }}</p>
                                    </div>
                                </div>
                                <div class="mt-3 flex flex-wrap gap-2">
                                    <a href="{{ route('software-updates.logs.download', $log['type']) }}" class="rounded-xl bg-blue-600 px-3 py-2 text-xs font-black text-white">Download</a>
                                    <form method="POST" action="{{ route('software-updates.logs.copy', $log['type']) }}">
                                        @csrf
                                        <button class="rounded-xl border border-slate-200 px-3 py-2 text-xs font-black text-slate-600">Create copy</button>
                                    </form>
                                </div>
                            </div>
                        @empty
                            <p class="rounded-2xl border border-dashed border-slate-200 px-4 py-6 text-center text-sm text-slate-500">No production logs found yet.</p>
                        @endforelse
                    </div>
                </div>
            </aside>
        </section>

        <section class="erp-card overflow-hidden">
            <div class="flex flex-col gap-2 border-b border-slate-100 p-5 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-lg font-black text-[#071a3b]">Latest update log</h2>
                    <p class="mt-1 text-sm text-slate-500">{{ $latestLog ? $latestLog['name'].' - '.$latestLog['updated_at'] : 'No software update has been run from the panel yet.' }}</p>
                </div>
            </div>
            <pre class="max-h-[520px] overflow-auto bg-[#071a3b] p-5 text-xs leading-6 text-blue-50">{{ $latestLog['content'] ?? 'The update log will appear here after you run the first update.' }}</pre>
        </section>
    </div>
</x-app-layout>
