<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <p class="text-[11px] font-bold uppercase tracking-[0.22em] text-blue-600">Administration</p>
            <h1 class="text-2xl font-bold text-[#071a3b]">Activity log</h1>
        </div>
    </x-slot>

    <div class="erp-card p-5">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 class="text-lg font-bold text-[#071a3b]">Recent system activity</h2>
                <p class="mt-1 text-sm text-slate-500">Basic audit trail for users, roles, and permissions.</p>
            </div>
            <form method="GET" class="flex gap-2">
                <select name="action" class="erp-focus h-11 rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-700">
                    <option value="">All actions</option>
                    @foreach ($actions as $action)
                        <option value="{{ $action }}" @selected(request('action') === $action)>{{ $action }}</option>
                    @endforeach
                </select>
                <button class="rounded-xl bg-blue-600 px-4 text-sm font-bold text-white hover:bg-blue-700">Filter</button>
            </form>
        </div>

        <div class="mt-5 overflow-hidden rounded-2xl border border-slate-200">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-[11px] font-bold uppercase tracking-[0.16em] text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Action</th>
                        <th class="px-4 py-3">Description</th>
                        <th class="px-4 py-3">User</th>
                        <th class="px-4 py-3">When</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse ($logs as $log)
                        <tr>
                            <td class="px-4 py-4"><span class="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-bold text-blue-700">{{ $log->action }}</span></td>
                            <td class="px-4 py-4">
                                <div class="font-medium text-[#071a3b]">{{ $log->description }}</div>
                                <div class="mt-1 text-xs text-slate-400">{{ class_basename($log->subject_type) }} #{{ $log->subject_id }}</div>
                            </td>
                            <td class="px-4 py-4 text-sm text-slate-600">{{ $log->user?->name ?? 'System' }}</td>
                            <td class="px-4 py-4 text-xs text-slate-500">{{ $log->created_at->format('M d, Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-10 text-center text-sm text-slate-500">No activity has been recorded yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-5">{{ $logs->links() }}</div>
    </div>
</x-app-layout>
