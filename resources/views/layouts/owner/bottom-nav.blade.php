<nav class="tenant-bottom-nav {{ request()->routeIs('support.*') ? 'hidden' : 'grid' }} fixed inset-x-0 bottom-0 z-40 mx-auto max-w-[430px] grid-cols-4 border-t border-slate-100 bg-white/95 px-3 pt-2 shadow-[0_-12px_30px_rgba(15,23,42,0.08)] backdrop-blur">
    @foreach ([
        ['route' => 'dashboard', 'label' => 'Owner', 'icon' => 'M4 19V5m0 14h16M8 16v-5m4 5V8m4 8v-8'],
        ['route' => 'owner-statements.index', 'label' => 'Statement', 'icon' => 'M6 3h12v18H6zM9 8h6M9 12h6M9 16h4'],
        ['route' => 'owner-payouts.index', 'label' => 'Payouts', 'icon' => 'M4 7h16M6 7V5h12v2M5 11h14v8H5zM9 15h6'],
        ['route' => 'profile.edit', 'label' => 'Profile', 'icon' => 'M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM4 21a8 8 0 0 1 16 0'],
    ] as $tab)
        @php($active = request()->routeIs($tab['route']) || ($tab['route'] === 'owner-statements.index' && request()->routeIs('owner-statements.*')) || ($tab['route'] === 'owner-payouts.index' && request()->routeIs('owner-payouts.*')))
        <a href="{{ route($tab['route']) }}" class="tenant-nav-item pressable flex flex-col items-center justify-center gap-1 px-1 py-2 text-[11px] font-bold {{ $active ? 'tenant-nav-item-active text-blue-600' : 'text-slate-500' }}">
            <span class="tenant-nav-icon {{ $active ? 'bg-blue-50' : '' }} grid h-9 w-9 place-items-center rounded-2xl">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="{{ $tab['icon'] }}"/></svg>
            </span>
            {{ $tab['label'] }}
        </a>
    @endforeach
</nav>

<div id="tenant-install-prompt" class="tenant-install-card {{ request()->routeIs('support.*') ? 'hidden' : '' }} fixed inset-x-3 bottom-24 z-50 hidden rounded-[1.5rem] border border-blue-100 bg-white p-4 shadow-2xl shadow-slate-950/20 sm:left-auto sm:w-[390px]">
    <div class="flex gap-3">
        <span class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-blue-600 text-sm font-black text-white">P</span>
        <div class="min-w-0 flex-1">
            <h2 class="text-sm font-black text-[#071a3b]">Install Pattern owner app</h2>
            <p id="tenant-install-copy" class="mt-1 text-xs leading-5 text-slate-500">Add Pattern to your phone for quick statements, payouts, property status, and owner support.</p>
            <div class="mt-3 flex gap-2">
                <button id="tenant-install-button" class="pressable touch-target rounded-xl bg-blue-600 px-4 py-2 text-xs font-black text-white">Install app</button>
                <button id="tenant-install-dismiss" class="pressable touch-target rounded-xl border border-slate-200 px-4 py-2 text-xs font-black text-slate-600">Later</button>
            </div>
        </div>
    </div>
</div>
