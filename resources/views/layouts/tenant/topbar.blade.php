@php
    $tenantTopbarTitle = request()->routeIs('bookings.show') ? 'Booking Details' : (request()->routeIs('bookings.*') ? 'Bookings' : (request()->routeIs('support.*') ? 'Messages' : (request()->routeIs('profile.*') ? 'Profile' : 'My Stay')));
    $tenantTopbarBackRoute = request()->routeIs('bookings.show') ? route('bookings.index') : null;
@endphp

<header class="tenant-topbar sticky top-0 z-20 mx-auto flex h-[76px] w-full max-w-[430px] items-center gap-3 border-b-0 bg-[#f7f9fe]/95 px-4 pt-3 backdrop-blur max-[380px]:px-3">
    <a href="{{ $tenantTopbarBackRoute ?: route('dashboard') }}" class="pressable touch-target grid h-11 w-11 shrink-0 place-items-center overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200" aria-label="{{ $tenantTopbarBackRoute ? 'Back to bookings' : 'Pattern home' }}">
        @if($tenantTopbarBackRoute)
            <svg class="h-5 w-5 text-[#071a3b]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M15 19l-7-7 7-7" /></svg>
        @else
            <img src="{{ asset('icons/pattern-48.png') }}" alt="" class="h-8 w-8 object-contain">
        @endif
    </a>
    <div class="absolute left-1/2 -translate-x-1/2 text-lg font-black text-[#071a3b] max-[380px]:text-base">
        {{ $tenantTopbarTitle }}
    </div>

    <div class="ml-auto flex items-center gap-2">
        <x-dropdown align="right" width="w-[22rem] sm:w-[26rem]" contentClasses="bg-white">
            <x-slot name="trigger">
                <button data-notification-trigger class="pressable touch-target relative grid h-11 w-11 place-items-center rounded-2xl border border-slate-200 bg-white text-slate-700 shadow-sm hover:bg-slate-50" type="button" aria-label="Notifications">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></svg>
                    <span data-notification-dot class="{{ $topbarNotificationCount ? 'block' : 'hidden' }} absolute right-2.5 top-2.5 h-2 w-2 rounded-full border-2 border-white bg-rose-500"></span>
                    <span data-notification-count class="{{ $topbarNotificationCount ? 'grid' : 'hidden' }} absolute -right-1 -top-1 h-5 min-w-5 place-items-center rounded-full bg-rose-500 px-1 text-[10px] font-black text-white">{{ $topbarNotificationCount > 99 ? '99+' : $topbarNotificationCount }}</span>
                </button>
            </x-slot>
            <x-slot name="content">
                @include('layouts.partials.notifications')
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
            let previousUnreadCount = Number(document.querySelector('[data-notification-count]')?.textContent?.replace(/\D/g, '') || 0);
            let notificationPollReady = false;
            let notificationAudioContext = null;
            let notificationSoundUnlocked = false;
            const unlockNotificationSound = () => {
                if (notificationSoundUnlocked) return;
                try {
                    const AudioContextClass = window.AudioContext || window.webkitAudioContext;
                    if (!AudioContextClass) return;
                    notificationAudioContext = notificationAudioContext || new AudioContextClass();
                    if (notificationAudioContext.state === 'suspended') notificationAudioContext.resume();
                    notificationSoundUnlocked = true;
                } catch (error) {}
            };
            window.addEventListener('pointerdown', unlockNotificationSound, { once: true, passive: true });
            window.addEventListener('keydown', unlockNotificationSound, { once: true });
            const playNotificationBeep = () => {
                try {
                    const AudioContextClass = window.AudioContext || window.webkitAudioContext;
                    if (!AudioContextClass) return;
                    notificationAudioContext = notificationAudioContext || new AudioContextClass();
                    if (notificationAudioContext.state === 'suspended') notificationAudioContext.resume();
                    const now = notificationAudioContext.currentTime;
                    const gain = notificationAudioContext.createGain();
                    gain.gain.setValueAtTime(0.0001, now);
                    gain.gain.exponentialRampToValueAtTime(0.075, now + 0.015);
                    gain.gain.exponentialRampToValueAtTime(0.0001, now + 0.42);
                    gain.connect(notificationAudioContext.destination);

                    [660, 880].forEach((frequency, index) => {
                        const osc = notificationAudioContext.createOscillator();
                        osc.type = 'sine';
                        osc.frequency.setValueAtTime(frequency, now + (index * 0.12));
                        osc.connect(gain);
                        osc.start(now + (index * 0.12));
                        osc.stop(now + 0.18 + (index * 0.12));
                    });
                } catch (error) {}
            };
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
                    if (notificationPollReady && unread > previousUnreadCount) {
                        playNotificationBeep();
                    }
                    previousUnreadCount = unread;
                    notificationPollReady = true;
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
