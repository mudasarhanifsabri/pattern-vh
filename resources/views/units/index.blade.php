<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-[11px] font-black uppercase tracking-[0.22em] text-blue-600">Portfolio</p>
                <h1 class="mt-2 text-3xl font-black tracking-[-0.04em] text-[#071a3b]">Properties</h1>
                <p class="mt-2 text-sm text-slate-500">Manage your unit portfolio, owners, permits, utilities, locks, and media.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('units.index') }}" class="inline-flex h-11 items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-600 shadow-sm">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20 12a8 8 0 1 1-2.3-5.7M20 4v6h-6"/></svg>
                    Refresh
                </a>
                @can('units.manage')
                    <a href="{{ route('units.create') }}" class="inline-flex h-11 items-center gap-2 rounded-xl bg-blue-600 px-4 text-sm font-black text-white shadow-lg shadow-blue-600/20">
                        <span class="text-lg">+</span> Add Property
                    </a>
                @endcan
            </div>
        </div>
    </x-slot>

    <div class="space-y-5">
        @if (session('status'))<div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">{{ session('status') }}</div>@endif

        <section class="grid gap-4 md:grid-cols-4">
            @foreach([
                ['label' => 'Total Properties', 'value' => $stats['total'], 'note' => 'All property listings', 'tone' => 'rose'],
                ['label' => 'Available Properties', 'value' => $stats['available'], 'note' => 'Ready for rent', 'tone' => 'emerald'],
                ['label' => 'Occupied Properties', 'value' => $stats['occupied'], 'note' => 'Currently rented', 'tone' => 'cyan'],
                ['label' => 'Under Maintenance', 'value' => $stats['maintenance'], 'note' => 'Needs attention', 'tone' => 'amber'],
            ] as $card)
                <article class="erp-card p-5">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-sm font-bold text-slate-600">{{ $card['label'] }}</p>
                            <p class="mt-3 text-3xl font-black tracking-[-0.05em] text-[#071a3b]">{{ $card['value'] }}</p>
                            <p class="mt-1 text-sm text-slate-500">{{ $card['note'] }}</p>
                        </div>
                        <span class="grid h-11 w-11 place-items-center rounded-2xl {{ $card['tone'] === 'emerald' ? 'bg-emerald-50 text-emerald-700' : ($card['tone'] === 'cyan' ? 'bg-cyan-50 text-cyan-700' : ($card['tone'] === 'amber' ? 'bg-amber-50 text-amber-700' : 'bg-rose-50 text-rose-700')) }}">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 21V7l8-4 8 4v14M8 21v-6h8v6M9 10h.01M12 10h.01M15 10h.01"/></svg>
                        </span>
                    </div>
                </article>
            @endforeach
        </section>

        <section class="erp-card p-5">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex items-center gap-3">
                    <span class="grid h-12 w-12 place-items-center rounded-2xl bg-blue-50 text-blue-700">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 21V7l8-4 8 4v14M8 21v-6h8v6"/></svg>
                    </span>
                    <div>
                        <h2 class="text-xl font-black text-[#071a3b]">Properties</h2>
                        <span class="sr-only">Apartment registry</span>
                        <p class="text-sm text-slate-500">Showing {{ $units->firstItem() ?? 0 }} to {{ $units->lastItem() ?? 0 }} of {{ $units->total() }} properties</p>
                    </div>
                </div>
            </div>

            <form method="GET" class="mt-5 grid gap-3 rounded-[1.5rem] border border-slate-200 bg-slate-50 p-4 lg:grid-cols-[1fr_180px_180px_auto]">
                <div class="relative">
                    <svg class="absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m21 21-4.3-4.3M10 18a8 8 0 1 1 0-16 8 8 0 0 1 0 16z"/></svg>
                    <input name="search" value="{{ request('search') }}" placeholder="Search properties..." class="erp-focus h-12 w-full rounded-xl border border-slate-200 bg-white pl-12 pr-4 text-sm shadow-sm">
                </div>
                <select name="type" class="erp-focus h-12 rounded-xl border border-slate-200 bg-white px-3 text-sm shadow-sm">
                    <option value="">All Types</option>
                    @foreach(App\Models\Unit::TYPES as $type)<option value="{{ $type }}" @selected(request('type') === $type)>{{ $type }}</option>@endforeach
                </select>
                <select name="status" class="erp-focus h-12 rounded-xl border border-slate-200 bg-white px-3 text-sm shadow-sm">
                    <option value="">All Statuses</option>
                    @foreach(App\Models\Unit::AVAILABILITY_STATUSES as $status)<option value="{{ $status }}" @selected(request('status') === $status)>{{ str($status)->headline() }}</option>@endforeach
                </select>
                <button class="rounded-xl bg-slate-900 px-5 text-sm font-black text-white">Filter</button>
            </form>

            <div class="mt-5 grid gap-5 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">
                @forelse($units as $unit)
                    @php($picture = collect($unit->pictures ?? [])->first())
                    <article class="group overflow-hidden rounded-[1.5rem] border border-slate-200 bg-white shadow-sm transition hover:-translate-y-1 hover:shadow-xl hover:shadow-slate-950/10">
                        <div class="relative h-52 bg-slate-100">
                            @if($picture)
                                <img src="{{ route('units.picture', [$unit, 0]) }}" alt="Unit {{ $unit->unit_no }}" class="h-full w-full object-cover">
                            @else
                                <div class="grid h-full place-items-center bg-slate-100 text-slate-400">
                                    <svg class="h-20 w-20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M4 21V7l8-4 8 4v14M8 21v-6h8v6M9 10h6M9 13h6"/></svg>
                                </div>
                            @endif
                            <div class="absolute left-4 top-4 flex gap-2">
                                <span class="rounded-full {{ $unit->availability_status === 'available' ? 'bg-emerald-100 text-emerald-700' : ($unit->availability_status === 'occupied' ? 'bg-blue-100 text-blue-700' : 'bg-amber-100 text-amber-700') }} px-3 py-1 text-xs font-black">{{ str($unit->availability_status)->headline() }}</span>
                            </div>
                            <span class="absolute right-4 top-4 rounded-full bg-white/90 px-3 py-1 text-xs font-black text-slate-700">{{ $unit->unit_type }}</span>
                            <div class="absolute inset-0 grid place-items-center bg-slate-950/0 opacity-0 transition group-hover:bg-slate-950/25 group-hover:opacity-100">
                                <div class="flex gap-2">
                                    <a href="{{ route('units.show', $unit) }}" class="grid h-12 w-12 place-items-center rounded-2xl bg-white text-[#071a3b] shadow-xl">
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg>
                                    </a>
                                    @can('units.manage')
                                        <a href="{{ route('units.edit', $unit) }}" class="grid h-12 w-12 place-items-center rounded-2xl bg-white text-[#071a3b] shadow-xl">
                                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 20h9M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/></svg>
                                        </a>
                                    @endcan
                                </div>
                            </div>
                        </div>
                        <div class="p-5">
                            <h3 class="truncate text-xl font-black text-[#071a3b]">{{ $unit->building->name }} / {{ $unit->unit_no }}</h3>
                            <p class="mt-2 line-clamp-1 text-sm text-slate-500">{{ $unit->view ?: 'No view details added' }}</p>
                            <p class="mt-3 text-sm text-slate-600">{{ $unit->parking_no ? 'Parking '.$unit->parking_no : 'No parking set' }} / {{ $unit->rent_amount ? 'AED '.number_format((float) $unit->rent_amount, 0) : 'Rent not set' }}</p>
                            <div class="mt-4 rounded-2xl border border-slate-200 bg-slate-50 p-3">
                                <div class="flex items-center justify-between text-xs">
                                    <span class="font-bold text-slate-500">{{ $unit->owners->count() }} owner{{ $unit->owners->count() === 1 ? '' : 's' }}</span>
                                    <span class="rounded-full bg-blue-100 px-2 py-0.5 font-bold text-blue-700">{{ $unit->bookings_count }} bookings</span>
                                </div>
                                <p class="mt-2 truncate text-xs text-slate-500">{{ $unit->owners->pluck('full_name')->implode(', ') ?: 'No owner assigned' }}</p>
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="col-span-full rounded-[1.5rem] border border-dashed border-slate-200 px-4 py-16 text-center text-slate-500">No properties found.</div>
                @endforelse
            </div>
            <div class="mt-5">{{ $units->links() }}</div>
        </section>
    </div>
</x-app-layout>
