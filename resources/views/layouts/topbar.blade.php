@php
    $tenantOnly = false;
@endphp

<header class="sticky top-0 z-20 flex h-20 w-full items-center gap-3 border-b border-[#dfe7f1] bg-white/95 px-4 backdrop-blur sm:px-6 lg:px-8">
    @unless ($tenantOnly)
        <button class="grid h-11 w-11 shrink-0 place-items-center rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 lg:hidden" @click="sidebarOpen = true" aria-label="Open menu">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
        </button>
    @endunless

    @if ($tenantOnly)
        <button class="grid h-10 w-10 shrink-0 place-items-center rounded-2xl text-[#071a3b]" type="button" aria-label="Menu">
            <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7h16M4 12h12M4 17h16"/></svg>
        </button>
        <a href="{{ route('dashboard') }}" class="absolute left-1/2 -translate-x-1/2 text-lg font-black text-[#071a3b]">
            {{ request()->routeIs('bookings.*') ? 'Bookings' : (request()->routeIs('support.*') ? 'Messages' : (request()->routeIs('profile.*') ? 'Profile' : 'My Stay')) }}
        </a>
    @else
        <div class="relative hidden w-full max-w-[405px] sm:block">
            <svg class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
            <input type="search" placeholder="Search this workspace..." class="erp-focus h-11 w-full rounded-xl border border-slate-200 bg-[#f8faff] py-2 pl-11 pr-16 text-sm text-slate-700 placeholder:text-slate-400">
            <span class="absolute right-3 top-1/2 -translate-y-1/2 rounded-md border border-slate-200 bg-white px-2 py-1 text-[10px] font-semibold text-slate-400">Ctrl K</span>
        </div>
    @endif

    <div class="ml-auto flex items-center gap-2">
        <button class="{{ $tenantOnly ? 'hidden sm:flex' : 'hidden md:flex' }} h-11 items-center gap-2 rounded-xl border border-slate-200 px-3 text-sm font-medium text-slate-600 hover:bg-slate-50" type="button">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a15 15 0 0 1 0 18M12 3a15 15 0 0 0 0 18"/></svg>
            English
        </button>
        <x-dropdown align="right" width="w-[22rem] sm:w-[26rem]" contentClasses="bg-white">
            <x-slot name="trigger">
                <button data-notification-trigger class="relative grid h-11 w-11 place-items-center rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50" type="button" aria-label="Notifications">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></svg>
                    <span data-notification-dot class="{{ $topbarNotificationCount ? 'block' : 'hidden' }} absolute right-2.5 top-2.5 h-2 w-2 rounded-full border-2 border-white bg-rose-500"></span>
                    <span data-notification-count class="{{ $topbarNotificationCount ? 'grid' : 'hidden' }} absolute -right-1 -top-1 h-5 min-w-5 place-items-center rounded-full bg-rose-500 px-1 text-[10px] font-black text-white">{{ $topbarNotificationCount > 99 ? '99+' : $topbarNotificationCount }}</span>
                </button>
            </x-slot>
            <x-slot name="content">
                <div class="border-b border-slate-100 p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h2 class="text-sm font-black text-[#071a3b]">Notifications</h2>
                            <p data-notification-summary class="mt-1 text-xs text-slate-500">{{ $topbarNotificationCount ? $topbarNotificationCount.' unread workspace update'.($topbarNotificationCount === 1 ? '' : 's') : 'You are all caught up.' }}</p>
                        </div>
                    <form method="POST" action="{{ route('notifications.read-all') }}">
                        @csrf
                        <button class="rounded-xl bg-blue-50 px-3 py-2 text-[11px] font-black text-blue-700">Mark all read</button>
                    </form>
                </div>
                @if(config('services.webpush.public_key'))
                    <div class="mt-3 rounded-2xl bg-blue-50 p-3">
                        <div class="flex items-center gap-3">
                            <div class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-white text-blue-600">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.2V11a6 6 0 1 0-12 0v3.2a2 2 0 0 1-.6 1.4L4 17h5m6 0a3 3 0 0 1-6 0" /></svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-xs font-black text-[#071a3b]">Device push alerts</p>
                                <p data-push-status class="mt-0.5 text-[11px] font-semibold text-slate-500">Enable alerts for this browser/app.</p>
                            </div>
                            <button type="button" data-push-enable class="rounded-xl bg-blue-600 px-3 py-2 text-[11px] font-black text-white">Enable</button>
                            <button type="button" data-push-test class="hidden rounded-xl bg-white px-3 py-2 text-[11px] font-black text-blue-700">Test</button>
                        </div>
                    </div>
                @else
                    <p class="mt-3 rounded-2xl bg-amber-50 px-3 py-2 text-[11px] font-bold text-amber-700">Push keys are not configured yet. Run <span class="font-black">php artisan webpush:vapid</span>.</p>
                @endif
            </div>
                <div data-notification-list class="max-h-[420px] overflow-y-auto p-2">
                    @php
                        $safeTopbarNotifications = collect($topbarNotifications ?? [])->filter(
                            fn ($item) => $item instanceof \App\Models\NotificationLog
                        );
                    @endphp
                    @forelse($safeTopbarNotifications as $notification)
                        @php
                            $displayStatus = $notification->sent_at ? 'sent' : $notification->status;
                            $isRead = (bool) $notification->is_read;
                        @endphp
                        <form method="POST" action="{{ route('notifications.read', $notification) }}" class="block">
                            @csrf
                            <button class="w-full rounded-2xl p-3 text-left transition {{ $isRead ? 'hover:bg-slate-50' : 'bg-blue-50 hover:bg-blue-100' }}">
                                <span class="flex items-start gap-3">
                                    <span class="mt-1 grid h-9 w-9 shrink-0 place-items-center rounded-xl {{ $isRead ? 'bg-slate-100 text-slate-500' : 'bg-blue-600 text-white' }}">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></svg>
                                    </span>
                                    <span class="min-w-0 flex-1">
                                        <span class="flex items-start justify-between gap-2">
                                            <span class="line-clamp-1 text-xs font-black text-[#071a3b]">{{ $notification->subject ?: str($notification->channel)->replace('_', ' ')->headline() }}</span>
                                            <span class="shrink-0 rounded-full {{ $displayStatus === 'sent' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }} px-2 py-0.5 text-[10px] font-black">{{ str($displayStatus)->headline() }}</span>
                                        </span>
                                        <span class="mt-1 block line-clamp-2 text-xs leading-5 text-slate-500">{{ $notification->message ?: 'System notification' }}</span>
                                        <span class="mt-2 block text-[10px] font-bold text-slate-400">{{ $notification->created_at->diffForHumans() }}</span>
                                    </span>
                                </span>
                            </button>
                        </form>
                    @empty
                        <div class="px-4 py-10 text-center">
                            <div class="mx-auto grid h-12 w-12 place-items-center rounded-2xl bg-slate-100 text-slate-400">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></svg>
                            </div>
                            <p class="mt-3 text-sm font-bold text-slate-500">No notifications yet.</p>
                        </div>
                    @endforelse
                </div>
            </x-slot>
        </x-dropdown>

        <x-dropdown align="right" width="48" contentClasses="py-1.5 bg-white">
            <x-slot name="trigger">
                <button class="flex h-12 items-center gap-3 rounded-xl border border-slate-200 px-2.5 text-left hover:bg-slate-50 sm:min-w-[185px]">
                    <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-blue-100 text-xs font-bold text-blue-700">{{ strtoupper(substr(auth()->user()->name, 0, 2)) }}</span>
                    <span class="hidden min-w-0 flex-1 sm:block"><span class="block truncate text-xs font-bold text-[#071a3b]">{{ auth()->user()->name }}</span><span class="block truncate text-[10px] text-slate-500">{{ auth()->user()->getRoleNames()->first() ?? 'User' }}</span></span>
                    <svg class="hidden h-4 w-4 text-slate-500 sm:block" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m6 9 6 6 6-6"/></svg>
                </button>
            </x-slot>
            <x-slot name="content">
                <x-dropdown-link :href="route('profile.edit')">{{ __('Profile settings') }}</x-dropdown-link>
                <form method="POST" action="{{ route('logout') }}">@csrf
                    <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">{{ __('Log out') }}</x-dropdown-link>
                </form>
            </x-slot>
        </x-dropdown>
    </div>
</header>

@auth
    <script>
        window.patternNotifications = window.patternNotifications || {};
        if (!window.patternNotifications.started) {
            window.patternNotifications.started = true;
            const escapeNotificationHtml = (value) => String(value || '')
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#039;');
            const updateNotificationBell = async () => {
                const countEl = document.querySelector('[data-notification-count]');
                const dotEl = document.querySelector('[data-notification-dot]');
                const listEl = document.querySelector('[data-notification-list]');
                const summaryEl = document.querySelector('[data-notification-summary]');
                if (!countEl || !dotEl || !listEl || !summaryEl) return;

                try {
                    const response = await fetch('{{ route('notifications.feed') }}', { headers: { 'Accept': 'application/json' } });
                    if (!response.ok) return;
                    const data = await response.json();
                    const unread = Number(data.unread_count || 0);
                    countEl.textContent = unread > 99 ? '99+' : unread;
                    countEl.classList.toggle('hidden', unread === 0);
                    countEl.classList.toggle('grid', unread > 0);
                    dotEl.classList.toggle('hidden', unread === 0);
                    dotEl.classList.toggle('block', unread > 0);
                    summaryEl.textContent = unread ? `${unread} unread workspace update${unread === 1 ? '' : 's'}` : 'You are all caught up.';

                    listEl.innerHTML = (data.items || []).length
                        ? data.items.map((item) => {
                            const title = escapeNotificationHtml(item.title);
                            const message = escapeNotificationHtml(item.message);
                            const createdAt = escapeNotificationHtml(item.created_at);
                            const status = escapeNotificationHtml(String(item.status || '').replaceAll('_', ' '));

                            return `
                            <form method="POST" action="${item.url}" class="block">
                                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                <button class="w-full rounded-2xl p-3 text-left transition ${item.is_read ? 'hover:bg-slate-50' : 'bg-blue-50 hover:bg-blue-100'}">
                                    <span class="flex items-start gap-3">
                                        <span class="mt-1 grid h-9 w-9 shrink-0 place-items-center rounded-xl ${item.is_read ? 'bg-slate-100 text-slate-500' : 'bg-blue-600 text-white'}">
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></svg>
                                        </span>
                                        <span class="min-w-0 flex-1">
                                            <span class="flex items-start justify-between gap-2">
                                                <span class="line-clamp-1 text-xs font-black text-[#071a3b]">${title}</span>
                                                <span class="shrink-0 rounded-full px-2 py-0.5 text-[10px] font-black ${item.status === 'sent' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700'}">${status}</span>
                                            </span>
                                            <span class="mt-1 block line-clamp-2 text-xs leading-5 text-slate-500">${message}</span>
                                            <span class="mt-2 block text-[10px] font-bold text-slate-400">${createdAt}</span>
                                        </span>
                                    </span>
                                </button>
                            </form>
                        `}).join('')
                        : `<div class="px-4 py-10 text-center"><div class="mx-auto grid h-12 w-12 place-items-center rounded-2xl bg-slate-100 text-slate-400"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></svg></div><p class="mt-3 text-sm font-bold text-slate-500">No notifications yet.</p></div>`;
                } catch (error) {}
            };

            setTimeout(updateNotificationBell, 1500);
            setInterval(updateNotificationBell, 30000);
        }
    </script>
@endauth
