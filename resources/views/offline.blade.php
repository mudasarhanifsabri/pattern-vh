<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0f172a">
    <title>Offline | {{ config('app.name') }}</title>
    @vite('resources/css/app.css')
</head>
<body class="grid min-h-screen place-items-center bg-[#f4f7fb] p-6 text-[#071a3b]">
    <main class="erp-card w-full max-w-md p-8 text-center">
        <div class="mx-auto grid h-16 w-16 place-items-center rounded-2xl bg-blue-600 font-bold text-white shadow-lg shadow-blue-600/20">ERP</div>
        <p class="mt-6 text-[10px] font-bold uppercase tracking-[0.18em] text-blue-600">Connection unavailable</p>
        <h1 class="mt-3 text-3xl font-bold tracking-[-0.03em]">You are offline</h1>
        <p class="mt-3 text-sm leading-6 text-slate-500">Reconnect to continue securely. ERP data is never stored in the offline cache.</p>
        <button onclick="location.reload()" class="mt-7 min-h-11 rounded-xl bg-blue-600 px-6 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-100">Try again</button>
    </main>
</body>
</html>
