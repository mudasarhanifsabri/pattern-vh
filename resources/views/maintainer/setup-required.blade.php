<x-dynamic-component component="layouts.maintainer-pwa">
    <main class="flex min-h-screen flex-col bg-slate-100 px-5 py-8">
        <div class="rounded-[2rem] bg-[#6d3be8] p-6 text-white shadow-xl">
            <p class="text-xs font-black uppercase tracking-[0.18em] text-white/70">Maintainer PWA</p>
            <h1 class="mt-3 text-2xl font-black">Link this login to a team member</h1>
            <p class="mt-3 text-sm leading-6 text-white/80">This mobile app opens only for cleaners and maintainers with an Operations Team profile.</p>
        </div>

        <section class="mt-5 rounded-2xl bg-white p-5 shadow-sm">
            <h2 class="font-black text-[#071a3b]">What to check</h2>
            <div class="mt-4 space-y-3 text-sm text-slate-600">
                <p class="rounded-xl bg-slate-50 p-3">Create or edit an Operations Team member from the admin side.</p>
                <p class="rounded-xl bg-slate-50 p-3">Use the same email as this login: <span class="font-black text-[#071a3b]">{{ $user?->email ?: 'no email found' }}</span></p>
                <p class="rounded-xl bg-slate-50 p-3">Save the member. Pattern VH will now link the app user automatically.</p>
            </div>
        </section>

        <a href="{{ route('dashboard') }}" class="mt-5 rounded-2xl bg-white px-5 py-4 text-center text-sm font-black text-[#6d3be8] shadow-sm">Back to dashboard</a>
    </main>
</x-dynamic-component>
