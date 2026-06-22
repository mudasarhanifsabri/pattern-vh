<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, viewport-fit=cover">
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
        class="min-h-screen {{ $tenantOnly ? 'bg-[#f7f9fe]' : 'lg:flex' }}"
    >
        @unless ($tenantOnly)
            <div x-show="sidebarOpen" x-transition.opacity class="fixed inset-0 z-30 bg-slate-950/60 lg:hidden" @click="sidebarOpen = false"></div>
            @include('layouts.sidebar')
        @endunless

        <div class="flex min-h-screen min-w-0 flex-1 flex-col {{ $tenantOnly ? '' : 'transition-[padding] duration-300 ease-out' }}" @unless($tenantOnly) :class="sidebarCollapsed ? 'lg:pl-[92px]' : 'lg:pl-[292px]'" @endunless>
            @if($tenantOnly)
                @include('layouts.tenant.topbar')
            @else
                @include('layouts.topbar')
            @endif
            <main class="flex-1 {{ $tenantOnly ? 'mx-auto w-full max-w-[430px] px-4 pb-28 pt-2 mobile-app-safe max-[380px]:px-3' : 'p-3 pb-24 sm:p-6 lg:p-8 xl:p-9' }}">
                @if(! $tenantOnly)
                @isset($header)
                    <div class="mb-5 md:mb-7">
                        @php
                            $headerHtml = trim($header->toHtml());
                        @endphp
                        @if (str_contains($headerHtml, '<'))
                            {!! $headerHtml !!}
                        @else
                            <h1 class="text-2xl font-black tracking-[-0.04em] text-[#071a3b] sm:text-[38px] sm:leading-none">{{ $header }}</h1>
                        @endif
                    </div>
                @endisset
                @endif
                {{ $slot }}
            </main>
            <div class="{{ $tenantOnly ? 'hidden md:block' : '' }}">
                @include('layouts.footer')
            </div>
        </div>
    </div>
    @if($tenantOnly)
        @include('layouts.tenant.bottom-nav')
    @endif
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => navigator.serviceWorker.register('{{ asset('service-worker.js') }}'));
        }
    </script>
</body>
</html>
