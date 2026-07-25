@php($items = [
    ['label' => 'Home', 'route' => 'maintainer.tasks.index', 'icon' => 'M3 11l9-8 9 8M5 10v10h14V10', 'active' => 'maintainer.tasks.index'],
    ['label' => 'Tasks', 'route' => 'maintainer.tasks.index', 'icon' => 'M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01', 'active' => 'maintainer.tasks.*'],
    ['label' => '+', 'route' => 'maintainer.tasks.index', 'icon' => 'M12 5v14M5 12h14', 'active' => 'none'],
    ['label' => 'Alerts', 'route' => 'maintainer.notifications', 'icon' => 'M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4', 'active' => 'maintainer.notifications'],
    ['label' => 'Profile', 'route' => 'maintainer.profile', 'icon' => 'M20 21a8 8 0 0 0-16 0M12 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8', 'active' => 'maintainer.profile'],
])
<nav class="bottom-nav">
    @foreach($items as $item)
        @php($active = request()->routeIs($item['active']))
        <a href="{{ route($item['route']) }}" class="flex flex-col items-center justify-center gap-1 text-[10px] font-black {{ $active ? 'text-[#6d3be8]' : 'text-slate-500' }}">
            <span class="{{ $item['label'] === '+' ? 'grid h-12 w-12 place-items-center rounded-full bg-[#6d3be8] text-white shadow-lg shadow-purple-500/30' : 'grid h-7 w-7 place-items-center rounded-full bg-slate-100' }}">
                <svg class="{{ $item['label'] === '+' ? 'h-6 w-6' : 'h-4 w-4' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="{{ $item['icon'] }}"/></svg>
            </span>
            <span>{{ $item['label'] }}</span>
        </a>
    @endforeach
</nav>
