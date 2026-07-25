<x-app-layout>
    <x-slot name="header"><div><p class="text-[11px] font-bold uppercase tracking-[0.22em] text-blue-600">Task details</p><h1 class="text-3xl font-black tracking-[-0.04em] text-[#071a3b]">{{ $task->task_display_number }}</h1></div></x-slot>
    <div class="grid gap-5 xl:grid-cols-[1fr_380px]">
        <section class="space-y-5">
            @if(session('status'))<div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-700">{{ session('status') }}</div>@endif
            @if($errors->any())<div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-bold text-rose-700">{{ $errors->first() }}</div>@endif
            <div class="erp-card p-5">
                <div class="flex flex-wrap items-start justify-between gap-3"><div><h2 class="text-2xl font-black text-[#071a3b]">{{ $task->title }}</h2><p class="mt-1 text-sm text-slate-500">{{ $task->unit?->building?->name }} / Unit {{ $task->unit?->unit_no }}</p></div><span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-black text-blue-700">{{ $task->status_label }}</span></div>
                <div class="mt-5 grid gap-3 md:grid-cols-3">@foreach([['Category',$task->type_label],['Priority',$task->priority_label],['Assigned',$task->assignee?->full_name ?? 'Unassigned'],['Due',$task->due_at?->format('d M Y H:i') ?? '-'],['Progress',(int)$task->progress.'%'],['Cost','AED '.number_format((float)$task->total_cost,2)]] as [$label,$value])<div class="rounded-2xl bg-slate-50 p-4"><p class="text-[10px] font-black uppercase tracking-[0.14em] text-slate-400">{{ $label }}</p><p class="mt-1 text-sm font-black text-[#071a3b]">{{ $value }}</p></div>@endforeach</div>
                <p class="mt-5 whitespace-pre-line text-sm leading-6 text-slate-600">{{ $task->description ?: $task->notes ?: 'No description.' }}</p>
            </div>
            @can('booking-tasks.manage')
                <form method="POST" action="{{ route('tasks.update', $task) }}" class="erp-card grid gap-4 p-5 md:grid-cols-2">@csrf @method('PATCH')
                    <label class="text-xs font-bold text-slate-500">Status<select name="status" class="erp-focus mt-1 w-full rounded-xl border-slate-200 text-sm">@foreach(\App\Models\BookingTask::STATUSES as $status)<option value="{{ $status }}" @selected($task->status===$status)>{{ str($status)->replace('_',' ')->headline() }}</option>@endforeach</select></label>
                    <label class="text-xs font-bold text-slate-500">Assigned<select name="assigned_to_id" class="erp-focus mt-1 w-full rounded-xl border-slate-200 text-sm"><option value="">Unassigned</option>@foreach($teamMembers as $member)<option value="{{ $member->id }}" @selected($task->assigned_to_id===$member->id)>{{ $member->full_name }}</option>@endforeach</select></label>
                    <label class="text-xs font-bold text-slate-500">Priority<select name="priority" class="erp-focus mt-1 w-full rounded-xl border-slate-200 text-sm">@foreach(\App\Models\BookingTask::PRIORITIES as $value => $label)<option value="{{ $value }}" @selected($task->priority===$value)>{{ $label }}</option>@endforeach</select></label>
                    <label class="text-xs font-bold text-slate-500">Due<input type="datetime-local" name="due_at" value="{{ $task->due_at?->format('Y-m-d\TH:i') }}" class="erp-focus mt-1 w-full rounded-xl border-slate-200 text-sm"></label>
                    <label class="text-xs font-bold text-slate-500">Progress<input type="number" min="0" max="100" name="progress" value="{{ (int) $task->progress }}" class="erp-focus mt-1 w-full rounded-xl border-slate-200 text-sm"></label>
                    <label class="text-xs font-bold text-slate-500">Timeline note<input name="timeline_note" class="erp-focus mt-1 w-full rounded-xl border-slate-200 text-sm"></label>
                    <label class="md:col-span-2 text-xs font-bold text-slate-500">Completion notes<textarea name="completion_notes" rows="3" class="erp-focus mt-1 w-full rounded-xl border-slate-200 text-sm">{{ $task->completion_notes }}</textarea></label>
                    <button class="md:col-span-2 rounded-xl bg-blue-600 px-4 py-3 text-sm font-black text-white">Save Task</button>
                </form>
            @endcan
            <div class="grid gap-5 lg:grid-cols-2">
                <form method="POST" action="{{ route('tasks.remarks.store', $task) }}" enctype="multipart/form-data" class="erp-card space-y-3 p-5">@csrf
                    <h3 class="font-black text-[#071a3b]">Add Remark</h3>
                    <textarea name="remark" required rows="3" class="erp-focus w-full rounded-xl border-slate-200 text-sm"></textarea>
                    <select name="status_update" class="erp-focus w-full rounded-xl border-slate-200 text-sm"><option value="">No status change</option><option value="accepted">Accepted</option><option value="in_progress">In Progress</option><option value="waiting_approval">Waiting Approval</option><option value="completed">Completed</option></select>
                    <input type="file" name="pictures[]" multiple accept="image/*" class="w-full rounded-xl border border-slate-200 text-sm">
                    <button class="w-full rounded-xl bg-[#071a3b] px-4 py-3 text-sm font-black text-white">Add Remark</button>
                </form>
                <form method="POST" action="{{ route('tasks.costs.store', $task) }}" class="erp-card space-y-3 p-5">@csrf
                    <h3 class="font-black text-[#071a3b]">Add Cost</h3>
                    <select name="type" class="erp-focus w-full rounded-xl border-slate-200 text-sm"><option value="labor">Labor</option><option value="material">Material</option><option value="other">Other</option></select>
                    <input name="label" required placeholder="Worker / item / expense" class="erp-focus w-full rounded-xl border-slate-200 text-sm">
                    <div class="grid grid-cols-2 gap-2"><input name="hours" type="number" step="0.01" placeholder="Hours" class="erp-focus rounded-xl border-slate-200 text-sm"><input name="rate" type="number" step="0.01" placeholder="Rate" class="erp-focus rounded-xl border-slate-200 text-sm"><input name="quantity" type="number" step="0.01" placeholder="Qty" class="erp-focus rounded-xl border-slate-200 text-sm"><input name="unit_price" type="number" step="0.01" placeholder="Unit price" class="erp-focus rounded-xl border-slate-200 text-sm"><input name="amount" type="number" step="0.01" placeholder="Other amount" class="erp-focus col-span-2 rounded-xl border-slate-200 text-sm"></div>
                    <button class="w-full rounded-xl bg-[#071a3b] px-4 py-3 text-sm font-black text-white">Save Cost</button>
                </form>
            </div>
        </section>
        <aside class="space-y-5">
            <form method="POST" action="{{ route('tasks.complete', $task) }}" enctype="multipart/form-data" class="erp-card space-y-3 p-5">@csrf
                <h3 class="font-black text-[#071a3b]">Complete Task</h3>
                <textarea name="completion_notes" required rows="3" class="erp-focus w-full rounded-xl border-slate-200 text-sm" placeholder="Completion notes"></textarea>
                <input type="file" name="final_images[]" multiple accept="image/*" class="w-full rounded-xl border border-slate-200 text-sm">
                <input type="file" name="invoice_attachment" class="w-full rounded-xl border border-slate-200 text-sm">
                <input type="file" name="receipt_attachment" class="w-full rounded-xl border border-slate-200 text-sm">
                <button class="w-full rounded-xl bg-emerald-600 px-4 py-3 text-sm font-black text-white">Complete Task</button>
            </form>
            <div class="erp-card p-5"><h3 class="font-black text-[#071a3b]">Timeline</h3><div class="mt-3 space-y-3">@foreach($task->events as $event)<div class="rounded-2xl bg-slate-50 p-3"><p class="text-xs font-black text-[#071a3b]">{{ str($event->event_type)->replace('_',' ')->headline() }}</p><p class="mt-1 text-xs text-slate-500">{{ $event->description }}</p><p class="mt-1 text-[10px] text-slate-400">{{ $event->user?->name ?? 'System' }} - {{ $event->created_at->format('d M H:i') }}</p></div>@endforeach @foreach($task->remarks as $remark)<div class="rounded-2xl bg-blue-50 p-3"><p class="text-xs font-black text-blue-700">Remark Added</p><p class="mt-1 text-xs text-slate-600">{{ $remark->remark }}</p><p class="mt-1 text-[10px] text-slate-400">{{ $remark->user?->name ?? 'Team' }} - {{ $remark->created_at->format('d M H:i') }}</p></div>@endforeach</div></div>
        </aside>
    </div>
</x-app-layout>
