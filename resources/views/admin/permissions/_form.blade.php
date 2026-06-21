@csrf

<div class="erp-card max-w-2xl p-5">
    <h2 class="text-lg font-bold text-[#071a3b]">Permission details</h2>
    <p class="mt-1 text-sm text-slate-500">Permissions should describe one access decision, for example users.manage or portal.technician.</p>

    <div class="mt-5">
        <x-input-label for="name" value="Permission name" />
        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $permission->name ?? '')" required />
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
    </div>
</div>

<div class="mt-6 flex max-w-2xl items-center justify-end gap-3">
    <a href="{{ route('admin.permissions.index') }}" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-bold text-slate-600 hover:bg-slate-50">Cancel</a>
    @isset($permission)
        <button form="delete-permission-form" class="rounded-xl border border-rose-200 px-4 py-2.5 text-sm font-bold text-rose-600 hover:bg-rose-50" type="submit">Delete</button>
    @endisset
    <x-primary-button>{{ $submitLabel }}</x-primary-button>
</div>
