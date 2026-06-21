<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <p class="text-[11px] font-bold uppercase tracking-[0.22em] text-blue-600">Access control</p>
            <h1 class="text-2xl font-bold text-[#071a3b]">Permissions</h1>
        </div>
    </x-slot>

    <div class="space-y-6">
        @if (session('status'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">{{ session('status') }}</div>
        @endif

        <div class="erp-card p-5">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h2 class="text-lg font-bold text-[#071a3b]">Permission catalog</h2>
                    <p class="mt-1 text-sm text-slate-500">Use dot names such as users.manage, portal.owner, or activity.view.</p>
                </div>
                <a href="{{ route('admin.permissions.create') }}" class="inline-flex h-11 items-center justify-center rounded-xl bg-blue-600 px-4 text-sm font-bold text-white shadow-lg shadow-blue-600/20 hover:bg-blue-700">Add permission</a>
            </div>

            <div class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($permissions as $group => $groupPermissions)
                    <div class="rounded-2xl border border-slate-200 bg-white p-4">
                        <h3 class="font-bold text-[#071a3b]">{{ $group }}</h3>
                        <div class="mt-3 space-y-2">
                            @foreach ($groupPermissions as $permission)
                                <div class="flex items-center justify-between gap-3 rounded-xl bg-slate-50 px-3 py-2">
                                    <span class="text-sm font-medium text-slate-700">{{ $permission->name }}</span>
                                    <a href="{{ route('admin.permissions.edit', $permission) }}" class="text-xs font-bold text-blue-700">Edit</a>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>
