<x-app-layout>
    <x-slot name="header"><div><p class="text-[11px] font-bold uppercase tracking-[0.22em] text-blue-600">Building profile</p><h1 class="text-2xl font-bold text-[#071a3b]">{{ $building->name }}</h1></div></x-slot>
    <div class="space-y-6">
        @if (session('status'))<div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">{{ session('status') }}</div>@endif
        <div class="grid gap-5 xl:grid-cols-[1fr_360px]">
            <div class="erp-card p-5">
                <div class="flex justify-between gap-4"><div><h2 class="text-xl font-bold text-[#071a3b]">{{ $building->name }}</h2><p class="mt-1 text-sm text-slate-500">{{ $building->address ?: 'No address added' }}</p></div>@can('buildings.manage')<div class="flex gap-2"><a href="{{ route('buildings.edit', $building) }}" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-bold text-slate-600">Edit</a><form method="POST" action="{{ route('buildings.destroy', $building) }}" onsubmit="return confirm('Delete this building?')">@csrf @method('DELETE')<button class="rounded-xl border border-rose-200 px-4 py-2.5 text-sm font-bold text-rose-600">Delete</button></form></div>@endcan</div>
                <dl class="mt-6 grid gap-4 md:grid-cols-2"><div class="rounded-2xl bg-slate-50 p-4"><dt class="text-xs font-bold uppercase text-slate-400">Area</dt><dd class="font-bold text-[#071a3b]">{{ $building->area ?: 'Not added' }}</dd></div><div class="rounded-2xl bg-slate-50 p-4"><dt class="text-xs font-bold uppercase text-slate-400">Coordinates</dt><dd class="font-bold text-[#071a3b]">{{ $building->latitude }}, {{ $building->longitude }}</dd></div></dl>
            </div>
            <div class="space-y-5">
                <div class="erp-card p-5"><h2 class="text-lg font-bold text-[#071a3b]">Security emails</h2><p class="mt-3 text-sm text-slate-600">{{ implode(', ', $building->security_emails ?? []) ?: 'Not added' }}</p></div>
                <div class="erp-card p-5"><h2 class="text-lg font-bold text-[#071a3b]">Amenities</h2><div class="mt-3 flex flex-wrap gap-2">@forelse ($building->amenities ?? [] as $amenity)<span class="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-bold text-blue-700">{{ $amenity }}</span>@empty <span class="text-sm text-slate-500">No amenities.</span>@endforelse</div></div>
            </div>
        </div>
        <div class="erp-card p-5"><h2 class="text-lg font-bold text-[#071a3b]">Units in this building</h2><div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-3">@forelse ($building->units as $unit)<a href="{{ route('units.show', $unit) }}" class="rounded-2xl border border-slate-200 p-4 hover:bg-slate-50"><div class="font-bold text-[#071a3b]">{{ $unit->unit_no }}</div><div class="text-xs text-slate-500">{{ $unit->unit_type }} · {{ str($unit->availability_status)->headline() }}</div></a>@empty <p class="text-sm text-slate-500">No units registered yet.</p>@endforelse</div></div>
    </div>
</x-app-layout>
