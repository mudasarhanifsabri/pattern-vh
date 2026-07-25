<x-dynamic-component component="layouts.maintainer-pwa">
    <header class="sticky top-0 z-30 flex h-16 items-center justify-between bg-white px-5 shadow-sm">
        <a href="{{ route('maintainer.tasks.index') }}" class="text-sm font-black text-[#6d3be8]">Back</a>
        <h1 class="font-black text-[#071a3b]">Notifications</h1>
        <span></span>
    </header>
    <main class="app-scroll space-y-3 px-5 pt-5">
        @forelse($tasks as $task)
            <a href="{{ route('maintainer.tasks.show', $task) }}" class="block rounded-2xl bg-white p-4 shadow-sm"><p class="text-xs font-black text-[#6d3be8]">{{ $task->task_display_number }}</p><h2 class="mt-1 font-black text-[#071a3b]">{{ $task->title }}</h2><p class="mt-1 text-xs text-slate-500">{{ $task->updated_at->diffForHumans() }}</p></a>
        @empty
            <p class="rounded-2xl border border-dashed border-slate-300 p-8 text-center text-sm text-slate-500">No new jobs.</p>
        @endforelse
    </main>
    @include('maintainer.partials.bottom-nav')
</x-dynamic-component>
