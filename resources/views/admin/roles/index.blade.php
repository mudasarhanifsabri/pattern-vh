<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <p class="text-[11px] font-bold uppercase tracking-[0.22em] text-blue-600">Access control</p>
            <h1 class="text-2xl font-bold text-[#071a3b]">Roles</h1>
        </div>
    </x-slot>

    <div class="space-y-6">
        @if (session('status'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">{{ session('status') }}</div>
        @endif

        <div class="erp-card p-5">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h2 class="text-lg font-bold text-[#071a3b]">Portal roles</h2>
                    <p class="mt-1 text-sm text-slate-500">Owner, Tenant, Operations Team, Cleaner, and Technician roles are ready for future portals.</p>
                </div>
                <a href="{{ route('admin.roles.create') }}" class="inline-flex h-11 items-center justify-center rounded-xl bg-blue-600 px-4 text-sm font-bold text-white shadow-lg shadow-blue-600/20 hover:bg-blue-700">Add role</a>
            </div>

            <div class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($roles as $role)
                    <div class="rounded-2xl border border-slate-200 bg-white p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h3 class="font-bold text-[#071a3b]">{{ $role->name }}</h3>
                                <p class="mt-1 text-xs text-slate-500">{{ $role->users_count }} users assigned</p>
                            </div>
                            <a href="{{ route('admin.roles.edit', $role) }}" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-bold text-slate-600 hover:bg-slate-50">Edit</a>
                        </div>

                        <div class="mt-4 flex flex-wrap gap-2">
                            @forelse ($role->permissions->take(6) as $permission)
                                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-bold text-slate-600">{{ $permission->name }}</span>
                            @empty
                                <span class="text-xs text-slate-400">No permissions assigned.</span>
                            @endforelse
                            @if ($role->permissions->count() > 6)
                                <span class="rounded-full bg-blue-50 px-2.5 py-1 text-[11px] font-bold text-blue-700">+{{ $role->permissions->count() - 6 }} more</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>
