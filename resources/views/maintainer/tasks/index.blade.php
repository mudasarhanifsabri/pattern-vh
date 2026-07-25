<x-dynamic-component component="layouts.maintainer-pwa">
    <div class="purple-head px-5 pb-7 pt-5 text-white">
        <div class="flex items-center justify-between">
            <button class="grid h-10 w-10 place-items-center rounded-2xl bg-white/10" aria-label="Menu">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
            </button>
            <a href="{{ route('maintainer.notifications') }}" class="relative grid h-10 w-10 place-items-center rounded-2xl bg-white/10" aria-label="Notifications">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></svg>
                @if($stats['overdue'] > 0)<span class="absolute -right-1 -top-1 grid h-5 min-w-5 place-items-center rounded-full bg-red-500 px-1 text-[10px] font-black">{{ $stats['overdue'] }}</span>@endif
            </a>
        </div>
        <p class="mt-5 text-sm font-semibold opacity-90">Good Morning</p>
        <h1 class="mt-1 text-2xl font-black">{{ $member->full_name }}</h1>

        <div class="mt-5 grid grid-cols-2 overflow-hidden rounded-2xl bg-white text-[#071a3b] shadow-xl">
            @foreach([['My Tasks',$stats['total'],'M9 11l2 2 4-4','text-blue-600','bg-blue-50'],['In Progress',$stats['in_progress'],'M8 3v4m8-4v4M4 11h16M6 7h12v14H6z','text-amber-600','bg-amber-50'],['Completed',$stats['completed'],'M20 6 9 17l-5-5','text-emerald-600','bg-emerald-50'],['Overdue',$stats['overdue'],'M12 8v5l3 2M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0','text-red-600','bg-red-50']] as [$label,$value,$icon,$color,$bg])
                <div class="flex items-center gap-3 border border-slate-100 p-4">
                    <span class="grid h-10 w-10 place-items-center rounded-full {{ $bg }} {{ $color }}"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="{{ $icon }}"/></svg></span>
                    <span><span class="block text-xl font-black {{ $color }}">{{ $value }}</span><span class="text-xs text-slate-500">{{ $label }}</span></span>
                </div>
            @endforeach
        </div>
    </div>

    <main class="app-scroll px-5 pt-5">
        @if(session('status'))<div class="mb-3 rounded-2xl bg-emerald-50 p-3 text-sm font-bold text-emerald-700">{{ session('status') }}</div>@endif
        <form method="GET" action="{{ route('maintainer.tasks.index') }}" class="mb-4 flex gap-2">
            <label class="relative min-w-0 flex-1">
                <svg class="absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m21 21-4.3-4.3M10 18a8 8 0 1 1 0-16 8 8 0 0 1 0 16"/></svg>
                <input name="q" value="{{ request('q') }}" placeholder="Search tasks..." class="h-12 w-full rounded-xl border border-slate-200 bg-white pl-11 pr-4 text-sm">
            </label>
            <button class="grid h-12 w-12 place-items-center rounded-xl border border-slate-200 bg-white text-slate-700" aria-label="Filter">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 5h18M7 12h10M10 19h4"/></svg>
            </button>
        </form>
        <div class="mb-4 flex gap-5 overflow-x-auto text-sm font-bold">
            @foreach(['' => 'All','assigned'=>'Assigned','in_progress'=>'In Progress','completed'=>'Completed'] as $key => $label)
                <a href="{{ route('maintainer.tasks.index', $key ? ['status'=>$key] : []) }}" class="{{ request('status','')===$key ? 'border-b-2 border-[#6d3be8] text-[#6d3be8]' : 'text-slate-500' }} shrink-0 pb-2">{{ $label }}</a>
            @endforeach
        </div>
        <div class="space-y-3">
            @forelse($tasks as $task)
                @php
                    $priorityClass = match ($task->priority) {
                        'urgent', 'high' => 'text-red-600',
                        'medium' => 'text-orange-500',
                        default => 'text-emerald-600',
                    };
                    $statusClass = in_array($task->status, ['completed', 'closed'], true) ? 'bg-emerald-50 text-emerald-700' : ($task->status === 'in_progress' ? 'bg-blue-50 text-blue-700' : 'bg-slate-100 text-slate-700');
                @endphp
                <a href="{{ route('maintainer.tasks.show', $task) }}" class="app-card block p-4">
                    <div class="flex items-start justify-between gap-3">
                        <span class="rounded-lg bg-red-50 px-2 py-1 text-xs font-black text-red-600">{{ $task->task_display_number }}</span>
                        <span class="rounded-lg px-2 py-1 text-xs font-black {{ $statusClass }}">{{ $task->status_label }}</span>
                    </div>
                    <h2 class="mt-3 text-base font-black text-[#071a3b]">{{ $task->title }}</h2>
                    <p class="mt-1 text-sm text-slate-500">{{ $task->unit?->building?->name }} - Unit {{ $task->unit?->unit_no }}</p>
                    <div class="mt-3 grid grid-cols-2 text-xs">
                        <div><p class="text-slate-400">Priority</p><p class="font-black {{ $priorityClass }}">{{ $task->priority_label }}</p></div>
                        <div><p class="text-slate-400">Due Date</p><p class="font-black text-[#071a3b]">{{ $task->due_at?->format('d M, Y') ?? '-' }}</p></div>
                    </div>
                </a>
            @empty
                <p class="rounded-2xl border border-dashed border-slate-300 p-8 text-center text-sm text-slate-500">No assigned tasks.</p>
            @endforelse
        </div>
    </main>

    @include('maintainer.partials.bottom-nav')
    @push('scripts')
    <script>
        let lastTask = 0;
        async function pollTasks(){
            try {
                const res = await fetch('{{ route('maintainer.tasks.live') }}');
                const data = await res.json();
                const latest = data.tasks?.[0];
                if (latest && latest.updated_at > lastTask) {
                    if (lastTask) alert('New task received: ' + latest.title);
                    lastTask = latest.updated_at;
                }
            } catch(e) {}
        }
        setInterval(pollTasks, 12000); pollTasks();
    </script>
    @endpush
</x-dynamic-component>
