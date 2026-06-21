<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#061a38">
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <link rel="icon" href="{{ asset('icons/erp-icon.svg') }}" type="image/svg+xml">
    <title>{{ config('app.name', 'ERP Base') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased">
    @php
        [$title, $subtitle] = match (true) {
            request()->routeIs('login') => ['Welcome back', 'Sign in to continue to your operations workspace.'],
            request()->routeIs('register') => ['Create your account', 'Set up secure access to the ERP workspace.'],
            request()->routeIs('password.request') => ['Reset your password', 'We will send a secure reset link to your email.'],
            request()->routeIs('password.reset') => ['Choose a new password', 'Create a strong password for your account.'],
            request()->routeIs('password.confirm') => ['Confirm your identity', 'Re-enter your password to continue securely.'],
            request()->routeIs('verification.notice') => ['Verify your email', 'Complete verification to activate your workspace.'],
            default => ['Secure workspace', 'Continue to your ERP account.'],
        };
    @endphp
    <div class="grid min-h-screen bg-white lg:grid-cols-[minmax(360px,0.82fr)_1.18fr]">
        <aside class="relative hidden overflow-hidden bg-[#061a38] p-10 text-white lg:flex lg:flex-col xl:p-14">
            <div class="absolute -right-32 -top-32 h-96 w-96 rounded-full border border-blue-400/10"></div>
            <div class="absolute -right-12 -top-12 h-56 w-56 rounded-full bg-blue-600/10 blur-3xl"></div>
            <a href="/" class="relative flex w-fit items-center rounded-xl bg-white px-4 py-3 shadow-lg shadow-blue-950/20">
                <img src="{{ asset('brand/pattern-logo.jpeg') }}" alt="Pattern Vacation Homes Rental" class="h-11 w-auto object-contain">
            </a>
            <div class="relative my-auto max-w-lg">
                <span class="inline-flex items-center gap-2 rounded-full border border-blue-300/20 bg-blue-500/10 px-3 py-1.5 text-[11px] font-semibold text-blue-200"><span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span> Secure ERP foundation</span>
                <h1 class="mt-7 text-4xl font-bold leading-tight tracking-[-0.04em] xl:text-5xl">Run operations from one clear workspace.</h1>
                <p class="mt-5 max-w-md text-sm leading-7 text-blue-100/65">Authentication, role-based access, cloud documents, and responsive tools built for focused business operations.</p>
                <div class="mt-10 grid grid-cols-2 gap-3">
                    <div class="rounded-2xl border border-white/10 bg-white/[0.05] p-4"><p class="text-xl font-bold">S3</p><p class="mt-1 text-xs text-blue-200/60">Cloud media storage</p></div>
                    <div class="rounded-2xl border border-white/10 bg-white/[0.05] p-4"><p class="text-xl font-bold">PWA</p><p class="mt-1 text-xs text-blue-200/60">Mobile-ready access</p></div>
                </div>
            </div>
            <p class="relative text-[11px] text-blue-200/45">&copy; {{ date('Y') }} {{ config('app.name') }}. Secure by design.</p>
        </aside>

        <main class="flex min-h-screen items-center justify-center bg-[#f7f9fc] px-5 py-10 sm:px-8">
            <div class="w-full max-w-[460px]">
                <a href="/" class="mx-auto mb-8 flex w-fit items-center rounded-xl bg-white px-4 py-3 shadow-sm"><img src="{{ asset('brand/pattern-logo.jpeg') }}" alt="Pattern Vacation Homes Rental" class="h-11 w-auto object-contain"></a>
                <div class="erp-card p-6 sm:p-8">
                    <div class="mb-7">
                        <div class="flex items-center gap-2 text-[10px] font-bold uppercase tracking-[0.18em] text-blue-600"><span class="h-0.5 w-4 bg-blue-600"></span> Account access</div>
                        <h1 class="mt-4 text-2xl font-bold tracking-[-0.03em] text-[#071a3b]">{{ $title }}</h1>
                        <p class="mt-2 text-sm leading-6 text-slate-500">{{ $subtitle }}</p>
                    </div>
                    {{ $slot }}
                </div>
            </div>
        </main>
    </div>
    <script>
        if ('serviceWorker' in navigator) window.addEventListener('load', () => navigator.serviceWorker.register('{{ asset('service-worker.js') }}'));
    </script>
</body>
</html>
