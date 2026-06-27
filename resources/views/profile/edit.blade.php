<x-app-layout>
    @php
        $tenantPortal = auth()->user()->can('portal.tenant') && ! auth()->user()->can('bookings.manage');
    @endphp

    @if($tenantPortal)
        <div class="space-y-5">
            @if (session('status') === 'profile-updated')
                <div class="rounded-[1.35rem] border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">Profile updated successfully.</div>
            @endif

            <section class="rounded-[1.8rem] bg-white p-5 shadow-[0_16px_40px_rgba(15,23,42,0.08)] ring-1 ring-slate-100">
                <div class="flex items-center gap-4">
                    <span class="grid h-20 w-20 shrink-0 place-items-center rounded-full bg-gradient-to-br from-blue-100 to-slate-100 text-xl font-black text-blue-700">{{ strtoupper(substr($user->name, 0, 2)) }}</span>
                    <div class="min-w-0">
                        <h2 class="truncate text-xl font-black text-[#0b1736]">{{ $user->name }}</h2>
                        <p class="truncate text-sm font-semibold text-slate-500">{{ $user->email }}</p>
                        <p class="mt-1 text-sm font-semibold text-slate-500">{{ $user->phone ?? '+971 50 123 4567' }}</p>
                        <a href="#edit-profile" class="mt-3 inline-flex rounded-xl bg-blue-50 px-4 py-2 text-sm font-black text-blue-600">Edit Profile</a>
                    </div>
                </div>
            </section>

            <section class="rounded-[1.8rem] bg-white p-5 shadow-sm ring-1 ring-slate-100">
                <h2 class="text-lg font-black text-[#0b1736]">Account</h2>
                <div class="mt-3 divide-y divide-slate-100">
                    @foreach([
                        ['Personal Information', 'M16 7a4 4 0 1 1-8 0 4 4 0 0 1 8 0zM12 14a7 7 0 0 0-7 7h14a7 7 0 0 0-7-7z', '#edit-profile'],
                        ['Payment Methods', 'M3 10h18M7 15h.01M11 15h2M5 6h14a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2z', route('tenant.payment-requests.index')],
                        ['My Documents', 'M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l5 5v11a2 2 0 0 1-2 2z', '#documents'],
                        ['Change Password', 'M12 15v2m-6 4h12a2 2 0 0 0 2-2v-6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2zm10-10V7a4 4 0 0 0-8 0v4h8z', '#change-password'],
                    ] as [$label, $icon, $href])
                        <a href="{{ $href }}" class="flex items-center gap-3 py-4">
                            <span class="grid h-10 w-10 place-items-center rounded-2xl bg-slate-50 text-slate-600"><svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="{{ $icon }}" /></svg></span>
                            <span class="text-sm font-black text-slate-700">{{ $label }}</span>
                            <svg class="ml-auto h-5 w-5 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 5l7 7-7 7" /></svg>
                        </a>
                    @endforeach
                </div>
            </section>

            <section class="rounded-[1.8rem] bg-white p-5 shadow-sm ring-1 ring-slate-100">
                <h2 class="text-lg font-black text-[#0b1736]">Preferences</h2>
                <div class="mt-3 divide-y divide-slate-100">
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-3 py-4"><span class="grid h-10 w-10 place-items-center rounded-2xl bg-slate-50 text-slate-600"><svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.2V11a6 6 0 1 0-12 0v3.2a2 2 0 0 1-.6 1.4L4 17h5m6 0a3 3 0 0 1-6 0" /></svg></span><span class="text-sm font-black text-slate-700">Notification Settings</span><svg class="ml-auto h-5 w-5 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 5l7 7-7 7" /></svg></a>
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-3 py-4"><span class="grid h-10 w-10 place-items-center rounded-2xl bg-slate-50 text-slate-600"><svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 5h12M9 3v2m1 13l4-9 4 9m-7-3h6M5 8c.5 2 2 4 5 5M4 13c3-.5 5-2.5 6-5" /></svg></span><span class="text-sm font-black text-slate-700">Language</span><span class="ml-auto text-sm font-semibold text-slate-500">English</span><svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 5l7 7-7 7" /></svg></a>
                </div>
            </section>

            <section class="rounded-[1.8rem] bg-white p-5 shadow-sm ring-1 ring-slate-100">
                <h2 class="text-lg font-black text-[#0b1736]">Support</h2>
                <div class="mt-3 divide-y divide-slate-100">
                    <a href="{{ route('support.index') }}" class="flex items-center gap-3 py-4"><span class="grid h-10 w-10 place-items-center rounded-2xl bg-slate-50 text-slate-600">?</span><span class="text-sm font-black text-slate-700">Help Center</span><svg class="ml-auto h-5 w-5 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 5l7 7-7 7" /></svg></a>
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-3 py-4"><span class="grid h-10 w-10 place-items-center rounded-2xl bg-slate-50 text-slate-600">i</span><span class="text-sm font-black text-slate-700">Terms & Conditions</span><svg class="ml-auto h-5 w-5 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 5l7 7-7 7" /></svg></a>
                </div>
            </section>

            <details id="edit-profile" class="rounded-[1.8rem] bg-white p-5 shadow-sm ring-1 ring-slate-100">
                <summary class="cursor-pointer text-lg font-black text-[#0b1736]">Personal Information</summary>
                <div class="mt-5">@include('profile.partials.update-profile-information-form')</div>
            </details>

            <details id="change-password" class="rounded-[1.8rem] bg-white p-5 shadow-sm ring-1 ring-slate-100">
                <summary class="cursor-pointer text-lg font-black text-[#0b1736]">Change Password</summary>
                <div class="mt-5">@include('profile.partials.update-password-form')</div>
            </details>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="flex w-full items-center justify-center rounded-[1.35rem] bg-white px-4 py-4 text-sm font-black text-rose-600 shadow-sm ring-1 ring-rose-100">Logout</button>
            </form>
        </div>
    @else
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
    @endif
</x-app-layout>
