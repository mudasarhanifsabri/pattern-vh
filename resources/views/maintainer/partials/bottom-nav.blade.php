@php($items = [
    ['label' => 'Home', 'route' => 'maintainer.tasks.index', 'icon' => 'H', 'active' => 'maintainer.tasks.index'],
    ['label' => 'Tasks', 'route' => 'maintainer.tasks.index', 'icon' => 'T', 'active' => 'maintainer.tasks.*'],
    ['label' => '+', 'route' => 'maintainer.tasks.index', 'icon' => '+', 'active' => 'none'],
    ['label' => 'Alerts', 'route' => 'maintainer.notifications', 'icon' => 'N', 'active' => 'maintainer.notifications'],
    ['label' => 'Profile', 'route' => 'maintainer.profile', 'icon' => 'P', 'active' => 'maintainer.profile'],
])
<nav class="bottom-nav">
    @foreach($items as $item)
        @php($active = request()->routeIs($item['active']))
        <a href="{{ route($item['route']) }}" class="flex flex-col items-center justify-center gap-1 text-[10px] font-black {{ $active ? 'text-[#6d3be8]' : 'text-slate-500' }}">
            <span class="{{ $item['label'] === '+' ? 'grid h-12 w-12 place-items-center rounded-full bg-[#6d3be8] text-3xl leading-none text-white shadow-lg shadow-purple-500/30' : 'grid h-7 w-7 place-items-center rounded-full bg-slate-100 text-xs' }}">{{ $item['icon'] }}</span>
            <span>{{ $item['label'] }}</span>
        </a>
    @endforeach
</nav>
