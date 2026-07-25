<x-dynamic-component component="layouts.maintainer-pwa">
    <main class="app-scroll px-5 py-6">
        <section class="rounded-3xl bg-white p-6 text-center shadow-sm">
            <div class="mx-auto grid h-20 w-20 place-items-center rounded-full bg-[#6d3be8] text-2xl font-black text-white">{{ str($member->full_name)->substr(0,1) }}</div>
            <h1 class="mt-4 text-2xl font-black text-[#071a3b]">{{ $member->full_name }}</h1>
            <p class="text-sm text-slate-500">{{ str($member->team_role)->headline() }} · {{ $member->email }}</p>
            <div class="mt-5 rounded-2xl bg-slate-50 p-4 text-left text-sm"><p><strong>Phone:</strong> {{ $member->mobile_no ?? '-' }}</p><p><strong>Specialty:</strong> {{ $member->specialty ?? '-' }}</p><p><strong>Status:</strong> {{ str($member->availability_status ?? 'active')->headline() }}</p></div>
            <div class="mt-5" data-push-panel><button data-push-enable class="w-full rounded-xl bg-[#6d3be8] px-4 py-3 font-black text-white">Enable Notifications</button><p data-push-status class="mt-2 text-xs text-slate-500">Notifications help you receive new jobs immediately.</p><button data-push-test class="mt-2 hidden w-full rounded-xl border border-slate-200 px-4 py-3 font-black text-[#071a3b]">Test Notification</button></div>
        </section>
    </main>
    @include('maintainer.partials.bottom-nav')
</x-dynamic-component>
