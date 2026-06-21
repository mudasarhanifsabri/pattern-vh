<x-app-layout>
    <x-slot name="header">{{ __('Profile settings') }}</x-slot>

    <p class="-mt-4 mb-6 max-w-2xl text-sm leading-6 text-slate-500">Manage your account details, security credentials, and account access.</p>

    <div class="grid gap-5 xl:grid-cols-[1.15fr_0.85fr]">
        <div class="space-y-5">
            <div class="erp-card p-5 sm:p-7">@include('profile.partials.update-profile-information-form')</div>
            <div class="erp-card p-5 sm:p-7">@include('profile.partials.update-password-form')</div>
        </div>
        <div class="space-y-5">
            <section class="erp-card p-5 sm:p-6">
                <h2 class="text-base font-bold text-[#071a3b]">Account summary</h2>
                <div class="mt-5 flex items-center gap-4 rounded-xl bg-[#f7f9fc] p-4">
                    <span class="grid h-14 w-14 place-items-center rounded-xl bg-blue-100 text-base font-bold text-blue-700">{{ strtoupper(substr($user->name, 0, 2)) }}</span>
                    <div class="min-w-0"><p class="truncate text-sm font-bold text-[#071a3b]">{{ $user->name }}</p><p class="truncate text-xs text-slate-500">{{ $user->email }}</p></div>
                </div>
                <dl class="mt-4 divide-y divide-slate-100 text-sm">
                    <div class="flex justify-between py-3"><dt class="text-slate-500">Role</dt><dd class="font-semibold text-[#071a3b]">{{ $user->getRoleNames()->first() ?? 'User' }}</dd></div>
                    <div class="flex justify-between py-3"><dt class="text-slate-500">Member since</dt><dd class="font-semibold text-[#071a3b]">{{ $user->created_at->format('M Y') }}</dd></div>
                </dl>
            </section>
            <div class="erp-card border-rose-100 p-5 sm:p-7">@include('profile.partials.delete-user-form')</div>
        </div>
    </div>
</x-app-layout>
