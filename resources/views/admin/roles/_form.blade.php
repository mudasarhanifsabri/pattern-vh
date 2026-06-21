@csrf

<div class="grid gap-5 lg:grid-cols-[360px_1fr]">
    <div class="erp-card p-5">
        <h2 class="text-lg font-bold text-[#071a3b]">Role details</h2>
        <p class="mt-1 text-sm text-slate-500">Use clear names like Owner, Tenant, Operations Team, Cleaner, or Technician.</p>

        <div class="mt-5">
            <x-input-label for="name" value="Role name" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $role->name ?? '')" required />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>
    </div>

    <div class="erp-card p-5">
        <h2 class="text-lg font-bold text-[#071a3b]">Permissions</h2>
        <p class="mt-1 text-sm text-slate-500">These permissions drive menus, portals, and management screens.</p>

        <div class="mt-5 grid gap-4 md:grid-cols-2">
            @foreach ($permissions as $group => $groupPermissions)
                <div class="rounded-2xl border border-slate-200 p-4">
                    <h3 class="text-sm font-bold text-[#071a3b]">{{ $group }}</h3>
                    <div class="mt-3 space-y-2">
                        @foreach ($groupPermissions as $permission)
                            <label class="flex cursor-pointer items-center gap-3 rounded-xl px-2 py-2 hover:bg-slate-50">
                                <input type="checkbox" name="permissions[]" value="{{ $permission->name }}" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500" @checked(collect(old('permissions', $rolePermissions ?? []))->contains($permission->name))>
                                <span class="text-sm font-medium text-slate-700">{{ $permission->name }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

<div class="mt-6 flex items-center justify-end gap-3">
    <a href="{{ route('admin.roles.index') }}" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-bold text-slate-600 hover:bg-slate-50">Cancel</a>
    @isset($role)
        @if ($role->name !== 'Super Admin')
            <button form="delete-role-form" class="rounded-xl border border-rose-200 px-4 py-2.5 text-sm font-bold text-rose-600 hover:bg-rose-50" type="submit">Delete</button>
        @endif
    @endisset
    <x-primary-button>{{ $submitLabel }}</x-primary-button>
</div>
