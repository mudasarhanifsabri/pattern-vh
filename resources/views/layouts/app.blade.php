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
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="icon" href="{{ asset('icons/pattern-192.png') }}" type="image/png">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
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
            <main class="flex-1 {{ $tenantOnly ? 'mobile-app-main mx-auto w-full max-w-[430px] px-4 pb-28 pt-2 mobile-app-safe max-[380px]:px-3' : 'p-3 pb-24 sm:p-6 lg:p-8 xl:p-9' }}">
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
    <script>
        document.addEventListener('submit', (event) => {
            const form = event.target.closest('form[data-single-submit]');
            if (!form || form.dataset.submitted === '1') {
                return;
            }

            form.dataset.submitted = '1';
            form.querySelectorAll('[data-upload-progress]').forEach((progress) => {
                const bar = progress.querySelector('[data-upload-progress-bar]');
                progress.classList.remove('hidden');
                if (bar) {
                    bar.style.width = '100%';
                }
            });
            const submitters = form.querySelectorAll('button[type="submit"], input[type="submit"]');
            submitters.forEach((button) => {
                button.disabled = true;
                if (button.tagName === 'BUTTON') {
                    button.dataset.originalText = button.textContent.trim();
                    button.textContent = 'Saving...';
                }
            });
        }, true);
    </script>
    @auth
        <script>
            window.patternPush = window.patternPush || {};
            if (!window.patternPush.started) {
                window.patternPush.started = true;
                window.patternPush.vapidPublicKey = @js(config('services.webpush.public_key'));

                const pushButtons = () => Array.from(document.querySelectorAll('[data-push-enable]'));
                const pushTestButtons = () => Array.from(document.querySelectorAll('[data-push-test]'));
                const pushStatusNodes = () => Array.from(document.querySelectorAll('[data-push-status]'));
                const setPushStatus = (message, enabled = false) => {
                    pushStatusNodes().forEach((node) => { node.textContent = message; });
                    pushButtons().forEach((button) => {
                        button.textContent = enabled ? 'Enabled' : 'Enable';
                        button.disabled = enabled;
                        button.classList.toggle('opacity-60', enabled);
                    });
                    pushTestButtons().forEach((button) => button.classList.toggle('hidden', !enabled));
                };
                const urlBase64ToUint8Array = (base64String) => {
                    const padding = '='.repeat((4 - base64String.length % 4) % 4);
                    const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
                    const rawData = window.atob(base64);
                    const outputArray = new Uint8Array(rawData.length);
                    for (let i = 0; i < rawData.length; ++i) outputArray[i] = rawData.charCodeAt(i);
                    return outputArray;
                };
                const registration = async () => {
                    if (!('serviceWorker' in navigator)) return null;
                    return await navigator.serviceWorker.ready;
                };
                const saveSubscription = async (subscription) => {
                    await fetch('{{ route('push-subscriptions.store') }}', {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: JSON.stringify(subscription.toJSON()),
                    });
                };
                const enablePush = async () => {
                    if (!window.patternPush.vapidPublicKey) {
                        setPushStatus('Push keys are not configured yet.');
                        return;
                    }
                    if (!('Notification' in window) || !('PushManager' in window)) {
                        setPushStatus('This browser does not support web push.');
                        return;
                    }

                    const permission = await Notification.requestPermission();
                    if (permission !== 'granted') {
                        setPushStatus('Notifications were not allowed on this device.');
                        return;
                    }

                    const sw = await registration();
                    if (!sw) {
                        setPushStatus('Service worker is not ready yet.');
                        return;
                    }

                    const existing = await sw.pushManager.getSubscription();
                    const subscription = existing || await sw.pushManager.subscribe({
                        userVisibleOnly: true,
                        applicationServerKey: urlBase64ToUint8Array(window.patternPush.vapidPublicKey),
                    });

                    await saveSubscription(subscription);
                    setPushStatus('Enabled on this device.', true);
                };
                const testPush = async () => {
                    await fetch('{{ route('notifications.test-push') }}', {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                    });
                };

                window.addEventListener('load', async () => {
                    if (!window.patternPush.vapidPublicKey) return;
                    try {
                        const sw = await registration();
                        const existing = sw ? await sw.pushManager.getSubscription() : null;
                        if (existing && Notification.permission === 'granted') {
                            await saveSubscription(existing);
                            setPushStatus('Enabled on this device.', true);
                        }
                    } catch (error) {}
                });

                document.addEventListener('click', (event) => {
                    if (event.target.closest('[data-push-enable]')) enablePush();
                    if (event.target.closest('[data-push-test]')) testPush();
                });
            }
        </script>
    @endauth
</body>
</html>
