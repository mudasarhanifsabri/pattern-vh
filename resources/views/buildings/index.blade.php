<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-[11px] font-bold uppercase tracking-[0.22em] text-blue-600">Portfolio</p>
            <h1 class="text-2xl font-bold text-[#071a3b]">Buildings</h1>
        </div>
    </x-slot>

    <div class="space-y-6">
        @if (session('status'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">{{ session('status') }}</div>
        @endif

        <div class="erp-card p-5">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h2 class="text-lg font-bold text-[#071a3b]">Building registry</h2>
                    <p class="mt-1 text-sm text-slate-500">Security emails, amenities, map coordinates, and building-level notes.</p>
                </div>
                @can('buildings.manage')
                    <a href="{{ route('buildings.create') }}" class="inline-flex h-11 items-center rounded-xl bg-blue-600 px-4 text-sm font-bold text-white hover:bg-blue-700">Add building</a>
                @endcan
            </div>
            <form method="GET" class="mt-5">
                <input name="search" value="{{ request('search') }}" placeholder="Search buildings or area..." class="erp-focus h-11 w-full rounded-xl border border-slate-200 bg-[#f8faff] px-4 text-sm">
            </form>
            <div class="mt-5 overflow-hidden rounded-2xl border border-slate-200">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-[11px] font-bold uppercase tracking-[0.16em] text-slate-500">
                        <tr><th class="px-4 py-3">Building</th><th class="px-4 py-3">Security emails</th><th class="px-4 py-3">Units</th><th class="px-4 py-3 text-right">Actions</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($buildings as $building)
                            <tr>
                                <td class="px-4 py-4"><div class="font-bold text-[#071a3b]">{{ $building->name }}</div><div class="text-xs text-slate-500">{{ $building->area ?: 'No area' }}</div></td>
                                <td class="px-4 py-4 text-xs text-slate-600">{{ implode(', ', $building->security_emails ?? []) ?: 'Not added' }}</td>
                                <td class="px-4 py-4 text-sm font-bold text-[#071a3b]">{{ $building->units_count }}</td>
                                <td class="px-4 py-4"><div class="flex justify-end gap-2"><a href="{{ route('buildings.show', $building) }}" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-bold text-slate-600 hover:bg-slate-50">View</a>@can('buildings.manage')<a href="{{ route('buildings.edit', $building) }}" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-bold text-slate-600 hover:bg-slate-50">Edit</a>@endcan</div></td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-4 py-10 text-center text-slate-500">No buildings found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-5">{{ $buildings->links() }}</div>
        </div>
    </div>
</x-app-layout>
