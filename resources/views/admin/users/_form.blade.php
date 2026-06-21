@csrf

<div class="grid gap-5 lg:grid-cols-[1fr_320px]">
    <div class="erp-card p-5">
        <h2 class="text-lg font-bold text-[#071a3b]">Account details</h2>
        <p class="mt-1 text-sm text-slate-500">Set the basic login information for this user.</p>

        <div class="mt-5 grid gap-4">
            <div>
                <x-input-label for="name" value="Full name" />
                <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name ?? '')" required autofocus />
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="email" value="Email address" />
                <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email ?? '')" required />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <x-input-label for="password" :value="isset($user) ? 'New password' : 'Password'" />
                    <x-text-input id="password" name="password" type="password" class="mt-1 block w-full" />
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    @if (! isset($user))
                        <p class="mt-2 text-xs text-slate-500">Leave blank if you will send the password setup email now.</p>
                    @endif
                </div>
                <div>
                    <x-input-label for="password_confirmation" value="Confirm password" />
                    <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" />
                </div>
            </div>

            @if (! isset($user))
                <label class="flex cursor-pointer items-center justify-between rounded-xl border border-blue-100 bg-blue-50/60 px-4 py-3">
                    <span>
                        <span class="block text-sm font-bold text-[#071a3b]">Send password setup email now</span>
                        <span class="block text-xs text-slate-500">The user will receive an email link to set their own password.</span>
                    </span>
                    <input type="checkbox" name="send_password_setup" value="1" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500" @checked(old('send_password_setup', true))>
                </label>
                <x-input-error :messages="$errors->get('send_password_setup')" class="mt-2" />
            @endif
        </div>
    </div>

    <div class="erp-card p-5">
        <h2 class="text-lg font-bold text-[#071a3b]">Portal role</h2>
        <p class="mt-1 text-sm text-slate-500">Assign one or more roles. Cleaner and Technician are operations team portals.</p>

        <div class="mt-5 space-y-2">
            @foreach ($roles as $role)
                <label class="flex cursor-pointer items-center justify-between rounded-xl border border-slate-200 px-3 py-3 hover:bg-slate-50">
                    <span class="text-sm font-bold text-[#071a3b]">{{ $role->name }}</span>
                    <input type="checkbox" name="roles[]" value="{{ $role->name }}" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500" @checked(collect(old('roles', $userRoles ?? []))->contains($role->name))>
                </label>
            @endforeach
            <x-input-error :messages="$errors->get('roles')" class="mt-2" />
        </div>
    </div>
</div>

<div class="mt-6 flex items-center justify-end gap-3">
    <a href="{{ route('admin.users.index') }}" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-bold text-slate-600 hover:bg-slate-50">Cancel</a>
    <x-primary-button>{{ $submitLabel }}</x-primary-button>
</div>
