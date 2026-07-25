<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-[11px] font-bold uppercase tracking-[0.22em] text-blue-600">Field operations</p>
                <h1 class="text-3xl font-black tracking-[-0.04em] text-[#071a3b]">Task Manager</h1>
                <p class="mt-1 text-sm text-slate-500">Admin task list, grid-style cards, details tracking, cost, remarks, and maintainer assignment.</p>
            </div>
            @can('booking-tasks.manage')
                <button type="button" onclick="document.getElementById('createTaskModal').showModal()" class="rounded-2xl bg-blue-600 px-5 py-3 text-sm font-black text-white shadow-lg shadow-blue-600/20">Create Task</button>
            @endcan
        </div>
    </x-slot>

    <div class="space-y-5">
        <span class="sr-only">Task management Checkout cleaning Timeline</span>
        @if(session('status'))<div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-700">{{ session('status') }}</div>@endif
        @if($errors->any())<div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-bold text-rose-700">{{ $errors->first() }}</div>@endif

        <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-6">
            @foreach(['total'=>'All','pending'=>'Pending','accepted'=>'Accepted','in_progress'=>'In Progress','completed'=>'Completed','overdue'=>'Overdue'] as $key => $label)
                <a href="{{ $key === 'total' ? route('tasks.index') : route('tasks.index', ['status' => $key]) }}" class="erp-card p-4 {{ request('status') === $key || ($key === 'total' && request()->missing('status')) ? 'ring-2 ring-blue-500' : '' }}">
                    <p class="text-[10px] font-black uppercase tracking-[0.14em] text-slate-400">{{ $label }}</p>
                    <p class="mt-2 text-2xl font-black text-[#071a3b]">{{ $stats[$key] }}</p>
                </a>
            @endforeach
        </section>

        <section class="erp-card p-4">
            <form class="grid gap-3 md:grid-cols-[1fr_180px_180px_160px]">
                <input name="q" value="{{ request('q') }}" placeholder="Search task no, title..." class="erp-focus rounded-2xl border-slate-200 text-sm">
                <select name="task_type" class="erp-focus rounded-2xl border-slate-200 text-sm"><option value="">All categories</option>@foreach($types as $value => $label)<option value="{{ $value }}" @selected(request('task_type')===$value)>{{ $label }}</option>@endforeach</select>
                <select name="assigned_to_id" class="erp-focus rounded-2xl border-slate-200 text-sm"><option value="">All staff</option>@foreach($teamMembers as $member)<option value="{{ $member->id }}" @selected(request('assigned_to_id')==$member->id)>{{ $member->full_name }}</option>@endforeach</select>
                <button class="rounded-2xl bg-[#071a3b] px-4 py-2.5 text-sm font-black text-white">Filter</button>
            </form>
        </section>

        <section class="erp-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full text-left">
                    <thead class="bg-slate-50 text-[10px] font-black uppercase tracking-[0.15em] text-slate-400">
                    <tr><th class="px-5 py-3">Task ID</th><th class="px-4 py-3">Title</th><th class="px-4 py-3">Unit</th><th class="px-4 py-3">Category</th><th class="px-4 py-3">Priority</th><th class="px-4 py-3">Assigned To</th><th class="px-4 py-3">Due Date</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Progress</th><th class="px-5 py-3 text-right">Action</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                    @forelse($tasks as $task)
                        <tr class="hover:bg-slate-50/70">
                            <td class="px-5 py-4 text-xs font-black text-blue-700">{{ $task->task_display_number }}</td>
                            <td class="px-4 py-4"><p class="text-sm font-black text-[#071a3b]">{{ $task->title }}</p><p class="text-xs text-slate-400">{{ $task->booking?->booking_no ?? 'Property task' }}</p></td>
                            <td class="px-4 py-4 text-sm font-bold text-slate-600">{{ $task->unit?->building?->name }} / {{ $task->unit?->unit_no }}</td>
                            <td class="px-4 py-4 text-xs font-black text-slate-500">{{ $task->type_label }}</td>
                            <td class="px-4 py-4 text-xs font-black {{ in_array($task->priority, ['urgent','high']) ? 'text-rose-600' : 'text-slate-500' }}">{{ $task->priority_label }}</td>
                            <td class="px-4 py-4 text-sm font-bold text-slate-600">{{ $task->assignee?->full_name ?? 'Unassigned' }}</td>
                            <td class="px-4 py-4 text-xs font-bold {{ $task->is_overdue ? 'text-rose-600' : 'text-slate-500' }}">{{ $task->due_at?->format('d M Y H:i') ?? '-' }}</td>
                            <td class="px-4 py-4"><span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-black text-slate-600">{{ $task->status_label }}</span></td>
                            <td class="px-4 py-4"><div class="h-2 w-24 overflow-hidden rounded-full bg-slate-100"><span class="block h-full bg-blue-600" style="width:{{ (int) $task->progress }}%"></span></div><p class="mt-1 text-[10px] font-bold text-slate-400">{{ (int) $task->progress }}%</p></td>
                            <td class="px-5 py-4 text-right"><a href="{{ route('tasks.show', $task) }}" class="rounded-xl border border-slate-200 px-3 py-2 text-xs font-black text-blue-600 hover:bg-blue-50">Details</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="p-10 text-center text-sm text-slate-400">No tasks found.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 p-4">{{ $tasks->links() }}</div>
        </section>
    </div>

    @can('booking-tasks.manage')
        <dialog id="createTaskModal" class="w-full max-w-3xl rounded-3xl p-0 backdrop:bg-slate-950/60">
            <form method="POST" action="{{ route('tasks.store') }}" enctype="multipart/form-data" class="bg-white" data-single-submit>
                @csrf
                <div class="border-b border-slate-100 p-5"><h2 class="text-xl font-black text-[#071a3b]">Create Task</h2><p class="text-sm text-slate-500">Create cleaning, maintenance, inspection, or custom work orders.</p></div>
                <div class="grid gap-4 p-5 sm:grid-cols-2">
                    <label class="text-xs font-bold text-slate-500">Booking<select name="booking_id" class="erp-focus mt-1 w-full rounded-xl border-slate-200 text-sm"><option value="">No booking</option>@foreach($bookings as $booking)<option value="{{ $booking->id }}">{{ $booking->booking_no }} - {{ $booking->unit?->unit_no }}</option>@endforeach</select></label>
                    <label class="text-xs font-bold text-slate-500">Unit<select name="unit_id" class="erp-focus mt-1 w-full rounded-xl border-slate-200 text-sm"><option value="">Select unit</option>@foreach($units as $unit)<option value="{{ $unit->id }}">{{ $unit->building?->name }} / {{ $unit->unit_no }}</option>@endforeach</select></label>
                    <label class="text-xs font-bold text-slate-500">Category<select name="task_type" class="erp-focus mt-1 w-full rounded-xl border-slate-200 text-sm">@foreach($types as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></label>
                    <label class="text-xs font-bold text-slate-500">Assigned To<select name="assigned_to_id" class="erp-focus mt-1 w-full rounded-xl border-slate-200 text-sm"><option value="">Unassigned</option>@foreach($teamMembers as $member)<option value="{{ $member->id }}">{{ $member->full_name }} - {{ str($member->team_role)->headline() }}</option>@endforeach</select></label>
                    <label class="text-xs font-bold text-slate-500">Priority<select name="priority" class="erp-focus mt-1 w-full rounded-xl border-slate-200 text-sm">@foreach($priorities as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></label>
                    <label class="text-xs font-bold text-slate-500">Due Date<input type="datetime-local" name="due_at" class="erp-focus mt-1 w-full rounded-xl border-slate-200 text-sm"></label>
                    <label class="sm:col-span-2 text-xs font-bold text-slate-500">Title<input name="title" required class="erp-focus mt-1 w-full rounded-xl border-slate-200 text-sm"></label>
                    <label class="sm:col-span-2 text-xs font-bold text-slate-500">Description<textarea name="description" rows="3" class="erp-focus mt-1 w-full rounded-xl border-slate-200 text-sm"></textarea></label>
                    <label class="sm:col-span-2 text-xs font-bold text-slate-500">Pictures<input type="file" name="pictures[]" multiple accept="image/*" class="mt-1 w-full rounded-xl border border-slate-200 text-sm"></label>
                </div>
                <div class="flex justify-end gap-2 border-t border-slate-100 p-5"><button type="button" onclick="document.getElementById('createTaskModal').close()" class="rounded-xl border px-4 py-2 text-sm font-black">Cancel</button><button class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-black text-white">Create Task</button></div>
            </form>
        </dialog>
    @endcan
</x-app-layout>
