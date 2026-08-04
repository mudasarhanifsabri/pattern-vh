<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#061a38">
    <meta name="description" content="Pattern owner portal for property status, statements, payouts, and support.">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Pattern Owner">
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="icon" href="{{ asset('icons/pattern-192.png') }}" type="image/png">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    <title>Pattern Owner Portal</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="owner-portal-app min-h-screen bg-[#f7f9fe] font-sans text-[#071a3b] antialiased">
    <div class="min-h-screen">
        @include('layouts.tenant.topbar')

        <main class="mobile-app-main mobile-app-safe mx-auto w-full max-w-[430px] px-4 pb-28 pt-2 max-[380px]:px-3 lg:max-w-3xl lg:px-6 lg:pb-12 lg:pt-6">
            @if(session('portal_preview_admin_id'))
                @php($previewRecord = session('portal_preview_record', []))
                <div class="mb-4 rounded-2xl border border-amber-200 bg-amber-50 px-3 py-2.5 text-xs font-bold text-amber-900 shadow-sm">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <span>Viewing as {{ $previewRecord['name'] ?? auth()->user()?->name }}{{ isset($previewRecord['role']) ? ' ('.$previewRecord['role'].')' : '' }}.</span>
                        <a href="{{ route('admin.portal-preview.stop') }}" class="inline-flex h-10 items-center justify-center rounded-xl bg-amber-900 px-4 text-xs font-black uppercase tracking-[0.12em] text-white">Return to Super Admin</a>
                    </div>
                </div>
            @endif

            {{ $slot }}
        </main>
    </div>

    @include('layouts.owner.bottom-nav')

    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => navigator.serviceWorker.register('{{ asset('service-worker.js') }}'));
        }

        document.addEventListener('submit', (event) => {
            const form = event.target.closest('form[data-single-submit]');
            if (!form || form.dataset.submitted === '1') return;

            form.dataset.submitted = '1';
            form.querySelectorAll('[data-upload-progress]').forEach((progress) => {
                const bar = progress.querySelector('[data-upload-progress-bar]');
                progress.classList.remove('hidden');
                if (bar) bar.style.width = '100%';
            });
            form.querySelectorAll('button[type="submit"], input[type="submit"]').forEach((button) => {
                button.disabled = true;
                if (button.tagName === 'BUTTON') {
                    button.dataset.originalText = button.textContent.trim();
                    button.textContent = 'Saving...';
                }
            });
        }, true);
    </script>
</body>
</html>
