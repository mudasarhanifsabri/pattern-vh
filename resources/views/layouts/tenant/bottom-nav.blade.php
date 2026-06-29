<nav class="tenant-bottom-nav {{ request()->routeIs('support.*') ? 'hidden' : 'grid' }} fixed inset-x-0 bottom-0 z-40 mx-auto max-w-[430px] grid-cols-5 border-t border-slate-100 bg-white/95 px-2 pt-2 shadow-[0_-12px_30px_rgba(15,23,42,0.08)] backdrop-blur">
    @foreach ([
        ['route' => 'dashboard', 'label' => 'My Stay', 'icon' => 'M4 12 12 4l8 8M6 10v10h12V10'],
        ['route' => 'bookings.index', 'label' => 'Bookings', 'icon' => 'M8 2v4M16 2v4M3 10h18M5 4h14a2 2 0 0 1 2 2v14H3V6a2 2 0 0 1 2-2z'],
        ['route' => 'invoices.index', 'label' => 'Payments', 'icon' => 'M6 3h12v18H6zM9 7h6M9 11h6M9 15h3'],
        ['route' => 'support.index', 'label' => 'Messages', 'icon' => 'M4 5h16v11H8l-4 4V5zm4 4h8m-8 3h5'],
        ['route' => 'profile.edit', 'label' => 'Profile', 'icon' => 'M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM4 21a8 8 0 0 1 16 0'],
    ] as $tab)
        @php($active = request()->routeIs($tab['route']) || ($tab['route'] === 'bookings.index' && request()->routeIs('bookings.*')) || ($tab['route'] === 'invoices.index' && request()->routeIs('invoices.*')) || ($tab['route'] === 'support.index' && request()->routeIs('support.*')))
        <a href="{{ route($tab['route']) }}" class="tenant-nav-item pressable flex flex-col items-center justify-center gap-1 px-0.5 py-2 text-[10px] font-bold {{ $active ? 'tenant-nav-item-active text-blue-600' : 'text-slate-500' }}">
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
            <h2 class="text-sm font-black text-[#071a3b]">Install Pattern app</h2>
            <p id="tenant-install-copy" class="mt-1 text-xs leading-5 text-slate-500">Add Pattern to your phone for quick payment, extension, checkout, and refund updates.</p>
            <div class="mt-3 flex gap-2">
                <button id="tenant-install-button" class="pressable touch-target rounded-xl bg-blue-600 px-4 py-2 text-xs font-black text-white">Install app</button>
                <button id="tenant-install-dismiss" class="pressable touch-target rounded-xl border border-slate-200 px-4 py-2 text-xs font-black text-slate-600">Later</button>
            </div>
        </div>
    </div>
</div>
