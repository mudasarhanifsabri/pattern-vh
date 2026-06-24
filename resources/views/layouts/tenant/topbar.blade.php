<header class="tenant-topbar sticky top-0 z-20 mx-auto flex h-[76px] w-full max-w-[430px] items-center gap-3 border-b-0 bg-[#f7f9fe] px-4 pt-3 backdrop-blur max-[380px]:px-3">
    <button class="grid h-10 w-10 shrink-0 place-items-center rounded-2xl text-[#071a3b]" type="button" aria-label="Menu">
        <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7h16M4 12h12M4 17h16"/></svg>
    </button>
    <a href="{{ route('dashboard') }}" class="absolute left-1/2 -translate-x-1/2 text-lg font-black text-[#071a3b] max-[380px]:text-base">
        {{ request()->routeIs('bookings.*') ? 'Bookings' : (request()->routeIs('support.*') ? 'Messages' : (request()->routeIs('profile.*') ? 'Profile' : 'My Stay')) }}
    </a>

    <div class="ml-auto flex items-center gap-2">
        <x-dropdown align="right" width="w-[22rem] sm:w-[26rem]" contentClasses="bg-white">
            <x-slot name="trigger">
                <button data-notification-trigger class="relative grid h-11 w-11 place-items-center rounded-2xl border border-slate-200 bg-white text-slate-700 shadow-sm hover:bg-slate-50" type="button" aria-label="Notifications">
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
