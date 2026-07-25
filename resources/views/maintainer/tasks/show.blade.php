<x-dynamic-component component="layouts.maintainer-pwa">
    <header class="sticky top-0 z-30 flex h-16 items-center justify-between bg-white px-5 shadow-sm">
        <a href="{{ route('maintainer.tasks.index') }}" class="text-sm font-black text-[#6d3be8]">Back</a>
        <h1 class="font-black text-[#071a3b]">Task Details</h1>
        <a href="{{ route('maintainer.profile') }}" class="text-sm font-black text-slate-500">Profile</a>
    </header>

    <main class="app-scroll space-y-4 px-5 pt-5">
        @if(session('status'))<div class="rounded-2xl bg-emerald-50 p-3 text-sm font-bold text-emerald-700">{{ session('status') }}</div>@endif
        @if($errors->any())<div class="rounded-2xl bg-red-50 p-3 text-sm font-bold text-red-700">{{ $errors->first() }}</div>@endif
        <section class="rounded-2xl bg-white p-5 shadow-sm">
            <div class="flex justify-between"><span class="rounded-lg bg-[#6d3be8] px-2 py-1 text-xs font-black text-white">{{ $task->task_display_number }}</span><span class="rounded-lg bg-red-50 px-2 py-1 text-xs font-black text-red-600">{{ $task->priority_label }}</span></div>
            <h2 class="mt-4 text-xl font-black text-[#071a3b]">{{ $task->title }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ $task->unit?->building?->name }} - Unit {{ $task->unit?->unit_no }}</p>
            <div class="mt-4 grid gap-2 text-sm">
                @foreach([['Category',$task->type_label],['Assigned By',$task->events->last()?->user?->name ?? 'Admin'],['Due Date',$task->due_at?->format('d M Y') ?? '-'],['Status',$task->status_label],['Estimated Cost','AED '.number_format((float)$task->total_cost,2)]] as [$label,$value])
                    <div class="flex justify-between gap-3"><span class="text-slate-500">{{ $label }}</span><strong class="text-right text-[#071a3b]">{{ $value }}</strong></div>
                @endforeach
            </div>
            <h3 class="mt-5 font-black text-[#071a3b]">Description</h3>
            <p class="mt-2 text-sm leading-6 text-slate-600">{{ $task->description ?: $task->notes ?: 'No description.' }}</p>
        </section>

        <section class="grid gap-3">
            @if(in_array($task->status, ['open','assigned']))
                <form method="POST" action="{{ route('maintainer.tasks.accept', $task) }}" enctype="multipart/form-data" class="rounded-2xl bg-white p-4 shadow-sm">@csrf
                    <label class="text-xs font-bold text-slate-500">Expected Completion Date<input type="date" name="expected_completion_date" required class="mt-1 w-full rounded-xl border-slate-200"></label>
                    <textarea name="initial_remark" rows="3" class="mt-3 w-full rounded-xl border-slate-200 text-sm" placeholder="Initial remark"></textarea>
                    <input type="file" name="pictures[]" multiple accept="image/*" capture="environment" class="mt-3 w-full rounded-xl border border-slate-200 text-sm">
                    <button class="mt-3 w-full rounded-xl bg-emerald-600 px-4 py-3 font-black text-white">Accept Task</button>
                </form>
            @endif

            <form method="POST" action="{{ route('maintainer.tasks.start', $task) }}">@csrf<button class="w-full rounded-xl bg-[#071a3b] px-4 py-3 font-black text-white">Start / In Progress</button></form>

            <form method="POST" action="{{ route('maintainer.tasks.remarks.store', $task) }}" enctype="multipart/form-data" class="rounded-2xl bg-white p-4 shadow-sm">@csrf
                <h3 class="font-black text-[#071a3b]">Add Remark</h3>
                <textarea name="remark" required rows="3" class="mt-2 w-full rounded-xl border-slate-200 text-sm"></textarea>
                <select name="status_update" class="mt-2 w-full rounded-xl border-slate-200 text-sm"><option value="">No status change</option><option value="in_progress">In Progress</option><option value="waiting_approval">Waiting Approval</option></select>
                <input type="file" name="pictures[]" multiple accept="image/*" capture="environment" class="mt-2 w-full rounded-xl border border-slate-200 text-sm">
                <button class="mt-3 w-full rounded-xl bg-[#6d3be8] px-4 py-3 font-black text-white">Add Remark</button>
            </form>

            <form method="POST" action="{{ route('maintainer.tasks.costs.store', $task) }}" class="rounded-2xl bg-white p-4 shadow-sm">@csrf
                <h3 class="font-black text-[#071a3b]">Add Labor / Material Cost</h3>
                <select name="type" class="mt-2 w-full rounded-xl border-slate-200 text-sm"><option value="labor">Labor</option><option value="material">Material</option><option value="other">Other</option></select>
                <input name="label" required placeholder="Worker / item" class="mt-2 w-full rounded-xl border-slate-200 text-sm">
                <div class="mt-2 grid grid-cols-2 gap-2"><input name="hours" type="number" step="0.01" placeholder="Hours" class="rounded-xl border-slate-200 text-sm"><input name="rate" type="number" step="0.01" placeholder="Rate" class="rounded-xl border-slate-200 text-sm"><input name="quantity" type="number" step="0.01" placeholder="Qty" class="rounded-xl border-slate-200 text-sm"><input name="unit_price" type="number" step="0.01" placeholder="Unit price" class="rounded-xl border-slate-200 text-sm"><input name="amount" type="number" step="0.01" placeholder="Other amount" class="col-span-2 rounded-xl border-slate-200 text-sm"></div>
                <button class="mt-3 w-full rounded-xl bg-[#6d3be8] px-4 py-3 font-black text-white">Save Cost</button>
            </form>

            <form method="POST" action="{{ route('maintainer.tasks.complete', $task) }}" enctype="multipart/form-data" class="rounded-2xl bg-white p-4 shadow-sm">@csrf
                <h3 class="font-black text-[#071a3b]">Complete Task</h3>
                <textarea name="completion_notes" required rows="3" class="mt-2 w-full rounded-xl border-slate-200 text-sm"></textarea>
                <input type="file" name="final_images[]" multiple accept="image/*" capture="environment" class="mt-2 w-full rounded-xl border border-slate-200 text-sm">
                <input type="file" name="invoice_attachment" class="mt-2 w-full rounded-xl border border-slate-200 text-sm">
                <input type="file" name="receipt_attachment" class="mt-2 w-full rounded-xl border border-slate-200 text-sm">
                <button class="mt-3 w-full rounded-xl bg-emerald-600 px-4 py-3 font-black text-white">Complete Task</button>
            </form>

            <section class="rounded-2xl bg-white p-4 shadow-sm">
                <h3 class="font-black text-[#071a3b]">Timeline</h3>
                <div class="mt-3 space-y-3">@foreach($task->events as $event)<div class="border-l-4 border-[#6d3be8] pl-3"><p class="text-sm font-black text-[#071a3b]">{{ str($event->event_type)->replace('_',' ')->headline() }}</p><p class="text-xs text-slate-500">{{ $event->description }}</p></div>@endforeach @foreach($task->remarks as $remark)<div class="border-l-4 border-emerald-500 pl-3"><p class="text-sm font-black text-[#071a3b]">Remark</p><p class="text-xs text-slate-500">{{ $remark->remark }}</p></div>@endforeach</div>
            </section>
        </section>
    </main>

    @include('maintainer.partials.bottom-nav')
</x-dynamic-component>
