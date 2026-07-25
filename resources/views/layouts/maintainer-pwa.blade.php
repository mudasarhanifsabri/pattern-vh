<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, minimum-scale=1, user-scalable=no, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#6d3be8">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <link rel="manifest" href="{{ asset('manifest-maintainer.webmanifest') }}">
    <title>{{ config('app.name', 'Pattern') }} Maintainer</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        html,body{overscroll-behavior:none;touch-action:manipulation;background:#eef2f7}
        .phone-shell{max-width:430px;margin:0 auto;min-height:100dvh;background:#eef2f7;position:relative;overflow:hidden}
        .app-scroll{height:calc(100dvh - 82px);overflow-y:auto;-webkit-overflow-scrolling:touch;padding-bottom:96px}
        .purple-head{background:linear-gradient(145deg,#6d3be8,#542bd3);border-bottom-left-radius:28px;border-bottom-right-radius:28px}
        .bottom-nav{position:fixed;left:50%;bottom:0;transform:translateX(-50%);width:100%;max-width:430px;background:#fff;border-top:1px solid #e5e7eb;display:grid;grid-template-columns:repeat(5,1fr);min-height:76px;z-index:40}
        dialog::backdrop{background:rgba(15,23,42,.55)}
    </style>
</head>
<body>
<div class="phone-shell">
    {{ $slot }}
</div>
<script>
    if ('serviceWorker' in navigator) window.addEventListener('load', () => navigator.serviceWorker.register('{{ asset('service-worker.js') }}'));
    document.addEventListener('gesturestart', event => event.preventDefault());
</script>
@auth
<script>
    window.addEventListener('load', () => {
        const enableButtons = document.querySelectorAll('[data-push-enable]');
        const statusNodes = document.querySelectorAll('[data-push-status]');
        const testButtons = document.querySelectorAll('[data-push-test]');
        const setStatus = (text, enabled = false) => {
            statusNodes.forEach(node => node.textContent = text);
            enableButtons.forEach(button => { button.textContent = enabled ? 'Enabled' : 'Enable Notifications'; button.disabled = enabled; });
            testButtons.forEach(button => button.classList.toggle('hidden', !enabled));
        };
        const key = @js(config('services.webpush.public_key'));
        const toUint8 = (base64) => {
            const padding = '='.repeat((4 - base64.length % 4) % 4);
            const raw = atob((base64 + padding).replace(/-/g, '+').replace(/_/g, '/'));
            return Uint8Array.from([...raw].map(char => char.charCodeAt(0)));
        };
        enableButtons.forEach(button => button.addEventListener('click', async () => {
            if (!key || !('Notification' in window) || !('PushManager' in window)) return setStatus('Push is not configured on this browser.');
            const permission = await Notification.requestPermission();
            if (permission !== 'granted') return setStatus('Notifications were not allowed.');
            const registration = await navigator.serviceWorker.ready;
            const existing = await registration.pushManager.getSubscription();
            const subscription = existing || await registration.pushManager.subscribe({userVisibleOnly: true, applicationServerKey: toUint8(key)});
            await fetch('{{ route('push-subscriptions.store') }}', {method:'POST',headers:{'Accept':'application/json','Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content},body:JSON.stringify(subscription.toJSON())});
            setStatus('Enabled on this device.', true);
        }));
        testButtons.forEach(button => button.addEventListener('click', () => fetch('{{ route('notifications.test-push') }}', {method:'POST',headers:{'Accept':'application/json','Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content}})));
    });
</script>
@endauth
@stack('scripts')
</body>
</html>
