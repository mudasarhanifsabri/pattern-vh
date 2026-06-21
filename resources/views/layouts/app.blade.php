<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#061a38">
    <meta name="description" content="Pattern RMS mobile-friendly vacation homes operations and tenant app.">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Pattern">
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <link rel="icon" href="{{ asset('icons/erp-icon.svg') }}" type="image/svg+xml">
    <title>{{ config('app.name', 'ERP Base') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased">
    @php
        $tenantOnly = auth()->user()?->can('portal.tenant') && ! auth()->user()?->can('bookings.manage');
    @endphp

    <div
        x-data="{
            sidebarOpen: false,
            sidebarCollapsed: localStorage.getItem('patternSidebarCollapsed') === '1',
            toggleSidebarCollapsed() {
                this.sidebarCollapsed = ! this.sidebarCollapsed;
                localStorage.setItem('patternSidebarCollapsed', this.sidebarCollapsed ? '1' : '0');
            }
        }"
        class="min-h-screen {{ $tenantOnly ? '' : 'lg:flex' }}"
    >
        @unless ($tenantOnly)
            <div x-show="sidebarOpen" x-transition.opacity class="fixed inset-0 z-30 bg-slate-950/60 lg:hidden" @click="sidebarOpen = false"></div>
            @include('layouts.sidebar')
        @endunless

        <div class="flex min-h-screen min-w-0 flex-1 flex-col {{ $tenantOnly ? '' : 'transition-[padding] duration-300 ease-out' }}" @unless($tenantOnly) :class="sidebarCollapsed ? 'lg:pl-[92px]' : 'lg:pl-[292px]'" @endunless>
            @include('layouts.topbar', ['tenantOnly' => $tenantOnly])
            <main class="flex-1 p-3 pb-24 sm:p-6 {{ $tenantOnly ? 'mx-auto w-full max-w-6xl mobile-app-safe' : 'lg:p-8 xl:p-9' }}">
                @isset($header)
                    <div class="mb-5 md:mb-7">
                        @php($headerHtml = trim($header->toHtml()))
                        @if (str_contains($headerHtml, '<'))
                            {!! $headerHtml !!}
                        @else
                            <h1 class="text-2xl font-black tracking-[-0.04em] text-[#071a3b] sm:text-[38px] sm:leading-none">{{ $header }}</h1>
                        @endif
                    </div>
                @endisset
                {{ $slot }}
            </main>
            <div class="@can('portal.tenant') hidden md:block @endcan">
                @include('layouts.footer')
            </div>
        </div>
    </div>
    @can('portal.tenant')
        <nav class="fixed inset-x-3 bottom-3 z-40 grid grid-cols-5 rounded-[1.35rem] border border-slate-200 bg-white/95 p-2 shadow-2xl shadow-slate-950/20 backdrop-blur md:hidden">
            @foreach ([
                ['route' => 'dashboard', 'label' => 'Home', 'icon' => 'M4 12 12 4l8 8M6 10v10h12V10'],
                ['route' => 'bookings.index', 'label' => 'Stays', 'icon' => 'M8 2v4M16 2v4M3 10h18M5 4h14a2 2 0 0 1 2 2v14H3V6a2 2 0 0 1 2-2z'],
                ['route' => 'tenant.payment-requests.index', 'label' => 'Pay', 'icon' => 'M3 7h18v10H3zM7 14h.01M11 14h3'],
                ['route' => 'support.index', 'label' => 'Support', 'icon' => 'M4 5h16v11H8l-4 4V5zm4 4h8m-8 3h5'],
                ['route' => 'profile.edit', 'label' => 'Profile', 'icon' => 'M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM4 21a8 8 0 0 1 16 0'],
            ] as $tab)
                <a href="{{ route($tab['route']) }}" class="flex flex-col items-center gap-1 rounded-2xl px-2 py-2 text-[10px] font-black {{ request()->routeIs($tab['route']) || ($tab['route'] === 'bookings.index' && request()->routeIs('bookings.*')) ? 'bg-blue-600 text-white' : 'text-slate-500' }}">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="{{ $tab['icon'] }}"/></svg>
                    {{ $tab['label'] }}
                </a>
            @endforeach
        </nav>
        <div id="tenant-install-prompt" class="fixed inset-x-3 bottom-24 z-50 hidden rounded-[1.5rem] border border-blue-100 bg-white p-4 shadow-2xl shadow-slate-950/20 sm:left-auto sm:w-[390px]">
            <div class="flex gap-3">
                <span class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-blue-600 text-sm font-black text-white">P</span>
                <div class="min-w-0 flex-1">
                    <h2 class="text-sm font-black text-[#071a3b]">Install Pattern app</h2>
                    <p id="tenant-install-copy" class="mt-1 text-xs leading-5 text-slate-500">Add Pattern to your phone for quick payment, extension, checkout, and refund updates.</p>
                    <div class="mt-3 flex gap-2">
                        <button id="tenant-install-button" class="rounded-xl bg-blue-600 px-4 py-2 text-xs font-black text-white">Install app</button>
                        <button id="tenant-install-dismiss" class="rounded-xl border border-slate-200 px-4 py-2 text-xs font-black text-slate-600">Later</button>
                    </div>
                </div>
            </div>
        </div>
    @endcan
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => navigator.serviceWorker.register('{{ asset('service-worker.js') }}'));
        }
    </script>
</body>
</html>
