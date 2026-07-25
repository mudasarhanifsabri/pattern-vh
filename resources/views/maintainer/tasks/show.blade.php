<x-dynamic-component component="layouts.maintainer-pwa">
    <header class="app-topbar">
        <a href="{{ route('maintainer.tasks.index') }}" class="grid h-10 w-10 place-items-center rounded-xl text-[#071a3b]" aria-label="Back">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
        </a>
        <h1 class="font-black text-[#071a3b]">Task Details</h1>
        <a href="{{ route('maintainer.tasks.timeline', $task) }}" class="grid h-10 w-10 place-items-center rounded-xl text-[#071a3b]" aria-label="Timeline">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 12h.01M19 12h.01M5 12h.01"/></svg>
        </a>
    </header>

    <main class="app-scroll px-5 pt-5">
        @if(session('status'))<div class="mb-3 rounded-2xl bg-emerald-50 p-3 text-sm font-bold text-emerald-700">{{ session('status') }}</div>@endif
        @if($errors->any())<div class="mb-3 rounded-2xl bg-red-50 p-3 text-sm font-bold text-red-700">{{ $errors->first() }}</div>@endif

        @php
            $priorityClass = in_array($task->priority, ['urgent', 'high'], true) ? 'bg-red-50 text-red-600' : 'bg-amber-50 text-amber-700';
            $taskPhotos = collect($task->pictures ?? [])->filter();
        @endphp
        <section class="space-y-5">
            <div>
                <div class="flex justify-between">
                    <span class="rounded-lg bg-[#6d3be8] px-2 py-1 text-xs font-black text-white">{{ $task->task_display_number }}</span>
                    <span class="rounded-lg px-3 py-1 text-xs font-black {{ $priorityClass }}">{{ $task->priority_label }}</span>
                </div>
                <h2 class="mt-4 text-xl font-black text-[#071a3b]">{{ $task->title }}</h2>
                <p class="mt-1 text-sm text-slate-500">{{ $task->unit?->building?->name }} - Unit {{ $task->unit?->unit_no }}</p>
                <dl class="mt-5 grid gap-3 text-sm">
                    @foreach([['Category',$task->type_label],['Assigned By',$task->events->last()?->user?->name ?? 'Admin User'],['Assigned To',$task->assignee?->full_name ?? '-'],['Due Date',$task->due_at?->format('d M, Y') ?? '-'],['Status',$task->status_label],['Estimated Cost','AED '.number_format((float)$task->total_cost,2)]] as [$label,$value])
                        <div class="grid grid-cols-[1fr_1.1fr] gap-3"><dt class="text-slate-500">{{ $label }}</dt><dd class="font-semibold text-[#071a3b]">{{ $value }}</dd></div>
                    @endforeach
                </dl>
            </div>

            <div>
                <h3 class="text-base font-black text-[#071a3b]">Description</h3>
                <p class="mt-2 text-sm leading-6 text-slate-600">{{ $task->description ?: $task->notes ?: 'No description.' }}</p>
            </div>

            <div>
                <h3 class="text-base font-black text-[#071a3b]">Photos ({{ $taskPhotos->count() }})</h3>
                <div class="photo-grid mt-3">
                    @forelse($taskPhotos as $photo)
                        <img src="{{ asset($photo) }}" alt="Task photo" class="photo-tile">
                    @empty
                        <p class="col-span-3 rounded-2xl border border-dashed border-slate-200 p-5 text-center text-sm text-slate-500">No task photos.</p>
                    @endforelse
                </div>
            </div>

            <div class="space-y-3">
                @if(in_array($task->status, ['open','assigned'], true))
                    <a href="{{ route('maintainer.tasks.accept.form', $task) }}" class="app-button bg-emerald-600 text-white shadow-lg shadow-emerald-600/20">Accept Task</a>
                @elseif(! in_array($task->status, ['completed','closed','cancelled'], true))
                    <form method="POST" action="{{ route('maintainer.tasks.start', $task) }}">@csrf<button class="app-button bg-[#071a3b] text-white">Start / In Progress</button></form>
                @endif
                <a href="{{ route('maintainer.tasks.remarks.create', $task) }}" class="app-button border border-slate-200 bg-white text-[#071a3b]">Add Remark</a>
                <a href="{{ route('maintainer.tasks.costs.create', $task) }}" class="app-button border border-slate-200 bg-white text-[#071a3b]">Add Cost</a>
                <a href="{{ route('maintainer.tasks.complete.form', $task) }}" class="app-button bg-emerald-600 text-white">Complete Task</a>
            </div>
        </section>
    </main>

    @include('maintainer.partials.bottom-nav')
</x-dynamic-component>
