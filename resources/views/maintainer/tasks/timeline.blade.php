<x-dynamic-component component="layouts.maintainer-pwa">
    <header class="app-topbar">
        <a href="{{ route('maintainer.tasks.show', $task) }}" class="grid h-10 w-10 place-items-center rounded-xl" aria-label="Back"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg></a>
        <h1 class="font-black text-[#071a3b]">Timeline</h1>
        <span class="h-10 w-10"></span>
    </header>

    <main class="app-scroll px-5 pt-5">
        <div class="space-y-5">
            @foreach($task->events->sortBy('created_at') as $event)
                <div class="relative pl-8">
                    <span class="absolute left-0 top-1 grid h-5 w-5 place-items-center rounded-full bg-[#6d3be8] text-white"><span class="h-1.5 w-1.5 rounded-full bg-white"></span></span>
                    <div class="absolute bottom-[-22px] left-[9px] top-7 w-px bg-purple-100"></div>
                    <p class="text-sm font-black text-[#071a3b]">{{ str($event->event_type)->replace('_',' ')->headline() }}</p>
                    <p class="mt-1 text-xs font-semibold text-slate-500">{{ $event->user?->name ?? 'System' }}</p>
                    <p class="mt-1 text-xs text-slate-500">{{ $event->created_at?->format('d M Y - h:i A') }}</p>
                    @if($event->description)<p class="mt-2 text-sm leading-5 text-slate-600">{{ $event->description }}</p>@endif
                </div>
            @endforeach
            @foreach($task->remarks->sortBy('created_at') as $remark)
                <div class="relative pl-8">
                    <span class="absolute left-0 top-1 grid h-5 w-5 place-items-center rounded-full bg-blue-500 text-white"><span class="h-1.5 w-1.5 rounded-full bg-white"></span></span>
                    <div class="absolute bottom-[-22px] left-[9px] top-7 w-px bg-blue-100"></div>
                    <p class="text-sm font-black text-[#071a3b]">Remark Added</p>
                    <p class="mt-1 text-xs font-semibold text-slate-500">{{ $remark->user?->name ?? 'Maintainer' }}</p>
                    <p class="mt-1 text-xs text-slate-500">{{ $remark->created_at?->format('d M Y - h:i A') }}</p>
                    <p class="mt-2 text-sm leading-5 text-slate-600">{{ $remark->remark }}</p>
                    @if(collect($remark->pictures ?? [])->isNotEmpty())
                        <div class="mt-2 flex gap-2">@foreach(collect($remark->pictures)->take(3) as $photo)<img src="{{ asset($photo) }}" alt="Remark photo" class="h-12 w-12 rounded-lg object-cover">@endforeach</div>
                    @endif
                    {{-- Manager responses are visible to the maintainer in the same update timeline. --}}
                    @if($remark->replies->isNotEmpty())
                        <div class="mt-3 space-y-2 border-l-2 border-blue-100 pl-3">@foreach($remark->replies as $reply)<div class="rounded-xl bg-blue-50 p-3"><p class="text-xs font-black text-blue-800">{{ $reply->user?->name ?? 'Operations Team' }}</p><p class="mt-1 text-sm leading-5 text-slate-700">{{ $reply->reply }}</p><p class="mt-1 text-[10px] text-slate-400">{{ $reply->created_at?->format('d M Y - h:i A') }}</p></div>@endforeach</div>
                    @endif
                </div>
            @endforeach
        </div>
    </main>
</x-dynamic-component>
