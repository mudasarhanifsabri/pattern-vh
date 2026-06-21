@php
    $menuGroups = [
        [
            'label' => 'Support Center',
            'caption' => 'Chat and tickets',
            'icon' => 'M4 5h16v11H8l-4 4V5zm4 4h8m-8 3h5',
            'active' => ['support.*'],
            'permissions' => ['support.view', 'support.manage', 'support.reports'],
            'items' => [
                ['label' => 'Inbox', 'route' => 'support.index', 'active' => 'support.index', 'permissions' => ['support.view', 'support.manage']],
                ['label' => 'New request', 'route' => 'support.create', 'active' => 'support.create', 'permissions' => ['support.view', 'support.manage']],
                ['label' => 'Support reports', 'route' => 'support.reports', 'active' => 'support.reports', 'permissions' => ['support.reports']],
                ['label' => 'Quick replies', 'route' => 'support.quick-replies.index', 'active' => 'support.quick-replies.*', 'permissions' => ['support.manage']],
                ['label' => 'Auto reply rules', 'route' => 'support.auto-reply-rules.index', 'active' => 'support.auto-reply-rules.*', 'permissions' => ['support.manage']],
            ],
        ],
        [
            'label' => 'Portfolio',
            'caption' => 'Properties and owners',
            'icon' => 'M4 21V7l8-4 8 4v14M8 21v-6h8v6',
            'active' => ['buildings.*', 'units.*', 'owners.*', 'owner-contracts.*'],
            'permissions' => ['buildings.view', 'buildings.manage', 'units.view', 'units.manage', 'owners.view', 'owners.manage', 'owner-contracts.view', 'owner-contracts.manage'],
            'items' => [
                ['label' => 'Buildings', 'route' => 'buildings.index', 'active' => 'buildings.*', 'permissions' => ['buildings.view', 'buildings.manage']],
                ['label' => 'Units / Properties', 'route' => 'units.index', 'active' => 'units.*', 'permissions' => ['units.view', 'units.manage']],
                ['label' => 'Owners', 'route' => 'owners.index', 'active' => 'owners.*', 'permissions' => ['owners.view', 'owners.manage']],
                ['label' => 'Owner contracts', 'route' => 'owner-contracts.index', 'active' => 'owner-contracts.*', 'permissions' => ['owner-contracts.view', 'owner-contracts.manage']],
            ],
        ],
        [
            'label' => 'Reservations',
            'caption' => 'Bookings and calendar',
            'icon' => 'M8 2v4m8-4v4M3 10h18M5 5h14v16H5z',
            'active' => ['bookings.*', 'availability-calendar.*', 'planning-sheet.*', 'dtcm-checkins.*'],
            'permissions' => ['bookings.view', 'bookings.manage', 'availability-calendar.view', 'booking-tasks.view', 'booking-tasks.manage', 'dtcm-checkins.view', 'dtcm-checkins.manage'],
            'items' => [
                ['label' => 'Bookings', 'route' => 'bookings.index', 'active' => 'bookings.*', 'permissions' => ['bookings.view', 'bookings.manage']],
                ['label' => 'Availability calendar', 'route' => 'availability-calendar.index', 'active' => 'availability-calendar.*', 'permissions' => ['availability-calendar.view', 'bookings.view', 'bookings.manage']],
                ['label' => 'Planning sheet', 'route' => 'planning-sheet.index', 'active' => 'planning-sheet.*', 'permissions' => ['bookings.view', 'bookings.manage', 'booking-tasks.view', 'booking-tasks.manage']],
                ['label' => 'DTCM check-ins', 'route' => 'dtcm-checkins.index', 'active' => 'dtcm-checkins.*', 'permissions' => ['dtcm-checkins.view', 'dtcm-checkins.manage']],
            ],
        ],
        [
            'label' => 'People',
            'caption' => 'Role portals',
            'icon' => 'M16 11a4 4 0 1 0-8 0M4 21a8 8 0 0 1 16 0',
            'active' => ['tenants.*', 'agents.*', 'operations-team.*'],
            'permissions' => ['tenants.view', 'tenants.manage', 'agents.view', 'agents.manage', 'operations-team.view', 'operations-team.manage'],
            'items' => [
                ['label' => 'Tenants', 'route' => 'tenants.index', 'active' => 'tenants.*', 'permissions' => ['tenants.view', 'tenants.manage']],
                ['label' => 'Agents', 'route' => 'agents.index', 'active' => 'agents.*', 'permissions' => ['agents.view', 'agents.manage']],
                ['label' => 'Operations team', 'route' => 'operations-team.index', 'active' => 'operations-team.*', 'permissions' => ['operations-team.view', 'operations-team.manage']],
            ],
        ],
        [
            'label' => 'Field Operations',
            'caption' => 'Tasks and assets',
            'icon' => 'M9 11l2 2 4-4M5 7h14M5 17h14',
            'active' => ['tasks.*', 'utilities.*', 'vehicles.*', 'inventory.*'],
            'permissions' => ['booking-tasks.view', 'booking-tasks.manage', 'utilities.view', 'utilities.manage', 'vehicles.view', 'vehicles.manage', 'inventory.view', 'inventory.manage'],
            'items' => [
                ['label' => 'Task management', 'route' => 'tasks.index', 'active' => 'tasks.*', 'permissions' => ['booking-tasks.view', 'booking-tasks.manage']],
                ['label' => 'Utilities', 'route' => 'utilities.index', 'active' => 'utilities.*', 'permissions' => ['utilities.view', 'utilities.manage']],
                ['label' => 'Vehicles', 'route' => 'vehicles.index', 'active' => 'vehicles.*', 'permissions' => ['vehicles.view', 'vehicles.manage']],
                ['label' => 'Inventory', 'route' => 'inventory.index', 'active' => 'inventory.*', 'permissions' => ['inventory.view', 'inventory.manage']],
            ],
        ],
        [
            'label' => 'Accounting',
            'caption' => 'Money and reports',
            'icon' => 'M4 19V5m0 14h16M8 16v-5m4 5V8m4 8v-8',
            'active' => ['accounting.*', 'invoices.*', 'payments.*', 'expenses.*', 'owner-payouts.*', 'owner-statements.*', 'payment-collection-requests.*', 'security-deposits.*', 'reports.*'],
            'permissions' => ['accounting.view', 'accounting.manage', 'invoices.view', 'invoices.manage', 'payments.view', 'payments.manage', 'expenses.view', 'expenses.manage', 'owner-payouts.view', 'owner-payouts.manage', 'owner-statements.view', 'owner-statements.manage', 'payment-collection-requests.view', 'payment-collection-requests.manage', 'security-deposits.view', 'security-deposits.manage', 'reports.view', 'reports.export'],
            'items' => [
                ['label' => 'Accounting dashboard', 'route' => 'accounting.index', 'active' => 'accounting.*', 'permissions' => ['accounting.view', 'accounting.manage']],
                ['label' => 'Invoices', 'route' => 'invoices.index', 'active' => 'invoices.*', 'permissions' => ['invoices.view', 'invoices.manage']],
                ['label' => 'Payments', 'route' => 'payments.index', 'active' => 'payments.*', 'permissions' => ['payments.view', 'payments.manage']],
                ['label' => 'Expenses', 'route' => 'expenses.index', 'active' => 'expenses.*', 'permissions' => ['expenses.view', 'expenses.manage']],
                ['label' => 'Owner account manager', 'route' => 'owner-payouts.index', 'active' => 'owner-payouts.*', 'permissions' => ['owner-payouts.view', 'owner-payouts.manage']],
                ['label' => 'Owner statements', 'route' => 'owner-statements.index', 'active' => 'owner-statements.*', 'permissions' => ['owner-statements.view', 'owner-statements.manage', 'portal.owner']],
                ['label' => 'Collection requests', 'route' => 'payment-collection-requests.index', 'active' => 'payment-collection-requests.*', 'permissions' => ['payment-collection-requests.view', 'payment-collection-requests.manage']],
                ['label' => 'Security deposits', 'route' => 'security-deposits.index', 'active' => 'security-deposits.*', 'permissions' => ['security-deposits.view', 'security-deposits.manage']],
                ['label' => 'Reports', 'route' => 'reports.index', 'active' => 'reports.*', 'permissions' => ['reports.view', 'reports.export']],
            ],
        ],
        [
            'label' => 'Administration',
            'caption' => 'Users and setup',
            'icon' => 'M12 8a4 4 0 1 0 0 8 4 4 0 0 0 0-8zM4 12h2m12 0h2M12 4v2m0 12v2',
            'active' => ['admin.users.*', 'admin.roles.*', 'admin.permissions.*', 'admin.activity-logs.*', 'settings.*', 'software-updates.*', 'tt-lock-settings.*', 'profile.*'],
            'permissions' => ['users.manage', 'roles.manage', 'permissions.manage', 'activity.view', 'software-updates.manage'],
            'items' => [
                ['label' => 'Users', 'route' => 'admin.users.index', 'active' => 'admin.users.*', 'permissions' => ['users.manage']],
                ['label' => 'Roles', 'route' => 'admin.roles.index', 'active' => 'admin.roles.*', 'permissions' => ['roles.manage']],
                ['label' => 'Permissions', 'route' => 'admin.permissions.index', 'active' => 'admin.permissions.*', 'permissions' => ['permissions.manage']],
                ['label' => 'Activity log', 'route' => 'admin.activity-logs.index', 'active' => 'admin.activity-logs.*', 'permissions' => ['activity.view']],
                ['label' => 'Settings', 'route' => 'settings.index', 'active' => 'settings.*', 'permissions' => ['users.manage', 'roles.manage']],
                ['label' => 'Software updates', 'route' => 'software-updates.index', 'active' => 'software-updates.*', 'permissions' => ['software-updates.manage', 'users.manage']],
                ['label' => 'TT Lock settings', 'route' => 'tt-lock-settings.index', 'active' => 'tt-lock-settings.*', 'permissions' => ['users.manage', 'roles.manage']],
                ['label' => 'Profile', 'route' => 'profile.edit', 'active' => 'profile.*', 'permissions' => []],
            ],
        ],
    ];

    $isActive = fn (array $patterns): bool => collect($patterns)->contains(fn (string $pattern): bool => request()->routeIs($pattern));
    $canAny = fn (array $permissions): bool => empty($permissions) || (auth()->check() && auth()->user()->hasAnyPermission($permissions));
@endphp

<aside
    :class="[sidebarOpen ? 'translate-x-0' : '-translate-x-full', sidebarCollapsed ? 'sidebar-collapsed lg:w-[84px]' : 'lg:w-[292px]']"
    class="fixed inset-y-0 left-0 z-40 flex w-[292px] flex-col overflow-hidden border-r border-slate-200 bg-white text-slate-700 shadow-2xl shadow-slate-950/10 transition-[width,transform] duration-300 ease-out lg:translate-x-0 lg:shadow-none"
>
    <div class="relative flex h-20 items-center justify-between border-b border-slate-200 px-5 sidebar-head">
        <a href="{{ route('dashboard') }}" class="flex min-w-0 items-center gap-3 sidebar-logo-link">
            <span class="sidebar-logo flex h-12 w-[200px] items-center rounded-2xl bg-white px-3 shadow-sm ring-1 ring-slate-200">
                <img src="{{ asset('brand/pattern-logo.jpeg') }}" alt="Pattern Vacation Homes Rental" class="max-h-9 w-full object-contain">
            </span>
        </a>
        <button class="hidden rounded-xl border border-slate-200 bg-white p-2 text-slate-500 shadow-sm transition hover:bg-slate-50 lg:grid" @click="toggleSidebarCollapsed()" aria-label="Toggle sidebar">
            <svg class="h-4 w-4 transition" :class="sidebarCollapsed ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
        </button>
        <button class="rounded-lg p-2 text-slate-400 hover:bg-slate-100 lg:hidden" @click="sidebarOpen = false" aria-label="Close menu">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 6 12 12M18 6 6 18"/></svg>
        </button>
    </div>

    <nav class="erp-sidebar-scroll flex-1 space-y-2 overflow-y-auto px-3 py-5" aria-label="Main navigation">
        @can('ceo.dashboard')
            <a href="{{ route('ceo.dashboard') }}" class="group flex items-center gap-3 rounded-2xl px-3 py-3 text-sm font-black transition {{ request()->routeIs('ceo.*') ? 'bg-[#071a3b] text-white shadow-lg' : 'text-slate-600 hover:bg-slate-50 hover:text-[#071a3b]' }}">
                <span class="grid h-9 w-9 place-items-center rounded-xl {{ request()->routeIs('ceo.*') ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-500 group-hover:text-blue-600' }}">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 19V5m0 14h16M8 15l3-3 3 2 4-7"/></svg>
                </span>
                <span class="sidebar-copy min-w-0 flex-1 truncate">CEO Overview</span>
            </a>
        @endcan
        <a href="{{ route('dashboard') }}" class="group flex items-center gap-3 rounded-2xl px-3 py-3 text-sm font-black transition {{ request()->routeIs('dashboard') ? 'bg-blue-50 text-blue-700' : 'text-slate-600 hover:bg-slate-50 hover:text-[#071a3b]' }}">
            <span class="grid h-9 w-9 place-items-center rounded-xl {{ request()->routeIs('dashboard') ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-500 group-hover:text-blue-600' }}">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 4h6v7H4zM14 4h6v4h-6zM14 12h6v8h-6zM4 15h6v5H4z"/></svg>
            </span>
            <span class="sidebar-copy min-w-0 flex-1 truncate">Dashboard</span>
        </a>

        @foreach($menuGroups as $group)
            @if($canAny($group['permissions']))
                @php($open = $isActive($group['active']))
                <details class="erp-sidebar-group rounded-2xl border transition {{ $open ? 'border-blue-100 bg-blue-50/70' : 'border-transparent hover:border-slate-200 hover:bg-slate-50' }}" @if($open) open @endif>
                    <summary class="group flex cursor-pointer list-none items-center gap-3 rounded-2xl px-3 py-3 text-sm font-black {{ $open ? 'text-blue-700' : 'text-slate-700' }}">
                        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl {{ $open ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-500 group-hover:text-blue-600' }}">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="{{ $group['icon'] }}"/></svg>
                        </span>
                        <span class="sidebar-copy min-w-0 flex-1">
                            <span class="block truncate">{{ $group['label'] }}</span>
                            <span class="block truncate text-[10px] font-bold uppercase tracking-[0.12em] {{ $open ? 'text-blue-400' : 'text-slate-400' }}">{{ $group['caption'] }}</span>
                        </span>
                        <svg class="sidebar-copy h-4 w-4 shrink-0 text-slate-400 transition group-open:rotate-90" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 6 6 6-6 6"/></svg>
                    </summary>

                    <div class="sidebar-subitems space-y-1 px-3 pb-3">
                        @foreach($group['items'] as $item)
                            @if(Route::has($item['route']) && $canAny($item['permissions']))
                                <a href="{{ route($item['route']) }}" class="relative ml-5 flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-bold transition {{ request()->routeIs($item['active']) ? 'bg-white text-blue-700 shadow-sm ring-1 ring-blue-100' : 'text-slate-500 hover:bg-white hover:text-[#071a3b]' }}">
                                    <span class="absolute -left-3 top-0 h-full w-px bg-slate-200"></span>
                                    <span class="h-1.5 w-1.5 shrink-0 rounded-full {{ request()->routeIs($item['active']) ? 'bg-blue-600' : 'bg-slate-300' }}"></span>
                                    <span class="sidebar-copy min-w-0 flex-1 truncate">{{ $item['label'] }}</span>
                                </a>
                            @endif
                        @endforeach
                    </div>
                </details>
            @endif
        @endforeach
    </nav>

    <div class="sidebar-footer border-t border-slate-200 px-5 py-4">
        <div class="flex items-center gap-3">
            <span class="grid h-10 w-10 place-items-center rounded-full bg-blue-100 text-xs font-black text-blue-700">{{ str(auth()->user()?->name ?? 'User')->explode(' ')->map(fn($part) => str($part)->substr(0, 1))->take(2)->implode('') }}</span>
            <div class="sidebar-copy min-w-0">
                <p class="truncate text-sm font-black text-[#071a3b]">{{ auth()->user()?->name }}</p>
                <p class="truncate text-xs text-slate-500">{{ auth()->user()?->getRoleNames()->first() ?? 'Team member' }}</p>
            </div>
        </div>
    </div>
</aside>
