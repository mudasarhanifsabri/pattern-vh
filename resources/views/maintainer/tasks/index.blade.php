<x-dynamic-component component="layouts.maintainer-pwa">
    <div class="purple-head px-6 pb-7 pt-7 text-white">
        <div class="flex items-center justify-between">
            <button class="text-3xl leading-none">≡</button>
            <a href="{{ route('maintainer.notifications') }}" class="relative text-2xl">♢<span class="absolute -right-2 -top-2 rounded-full bg-red-500 px-1.5 text-[10px]">{{ $stats['total'] }}</span></a>
        </div>
        <p class="mt-6 text-sm font-semibold opacity-90">Good Morning</p>
        <h1 class="text-2xl font-black">{{ $member->full_name }} 👋</h1>
        <div class="mt-4 grid grid-cols-2 overflow-hidden rounded-2xl bg-white text-[#071a3b] shadow-xl">
            @foreach([['My Tasks',$stats['total'],'text-blue-600'],['In Progress',$stats['in_progress'],'text-amber-600'],['Completed',$stats['completed'],'text-emerald-600'],['Overdue',$stats['overdue'],'text-red-600']] as [$label,$value,$color])
                <div class="border border-slate-100 p-4"><p class="text-xl font-black {{ $color }}">{{ $value }}</p><p class="text-xs text-slate-500">{{ $label }}</p></div>
            @endforeach
        </div>
    </div>
    <main class="app-scroll px-5 pt-5">
        @if(session('status'))<div class="mb-3 rounded-2xl bg-emerald-50 p-3 text-sm font-bold text-emerald-700">{{ session('status') }}</div>@endif
        <form class="mb-4 flex gap-2">
            <input name="q" value="{{ request('q') }}" placeholder="Search tasks..." class="h-12 min-w-0 flex-1 rounded-xl border border-slate-200 bg-white px-4 text-sm">
            <button class="grid h-12 w-12 place-items-center rounded-xl border border-slate-200 bg-white">≡</button>
        </form>
        <div class="mb-4 flex gap-5 overflow-x-auto text-sm font-bold">
            @foreach(['' => 'All','assigned'=>'Assigned','in_progress'=>'In Progress','completed'=>'Completed'] as $key => $label)
                <a href="{{ route('maintainer.tasks.index', $key ? ['status'=>$key] : []) }}" class="{{ request('status','')===$key ? 'border-b-2 border-[#6d3be8] text-[#6d3be8]' : 'text-slate-500' }} pb-2">{{ $label }}</a>
            @endforeach
        </div>
        <div class="space-y-3">
            @forelse($tasks as $task)
                <a href="{{ route('maintainer.tasks.show', $task) }}" class="block rounded-2xl bg-white p-4 shadow-sm">
                    <div class="flex items-start justify-between gap-3"><span class="rounded-lg bg-[#6d3be8] px-2 py-1 text-xs font-black text-white">{{ $task->task_display_number }}</span><span class="rounded-lg bg-blue-50 px-2 py-1 text-xs font-black text-blue-700">{{ $task->status_label }}</span></div>
                    <h2 class="mt-3 text-base font-black text-[#071a3b]">{{ $task->title }}</h2>
                    <p class="mt-1 text-sm text-slate-500">{{ $task->unit?->building?->name }} · {{ $task->unit?->unit_no }}</p>
                    <div class="mt-3 grid grid-cols-2 text-xs"><div><p class="text-slate-400">Priority</p><p class="font-black {{ in_array($task->priority,['high','urgent']) ? 'text-red-600' : 'text-orange-500' }}">{{ $task->priority_label }}</p></div><div><p class="text-slate-400">Due Date</p><p class="font-black text-[#071a3b]">{{ $task->due_at?->format('d M, Y') ?? '-' }}</p></div></div>
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
                    if (lastTask) alert('🔔 New task received: ' + latest.title);
                    lastTask = latest.updated_at;
                }
            } catch(e) {}
        }
        setInterval(pollTasks, 12000); pollTasks();
    </script>
    @endpush
</x-dynamic-component>
