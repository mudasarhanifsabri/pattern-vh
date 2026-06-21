@csrf

@if ($errors->any())
    <div class="mb-5 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
        <p class="font-bold">Please fix the highlighted fields.</p>
        <ul class="mt-2 list-inside list-disc">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="grid gap-5 xl:grid-cols-[1fr_360px]">
    <div class="erp-card p-5">
        <h2 class="text-lg font-bold text-[#071a3b]">Building details</h2>
        <div class="mt-5 grid gap-4 md:grid-cols-2">
            <div class="md:col-span-2"><x-input-label for="name" value="Building name" /><x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name', $building->name ?? '')" required /><x-input-error :messages="$errors->get('name')" class="mt-2" /></div>
            <div><x-input-label for="code" value="Building code" /><x-text-input id="code" name="code" class="mt-1 block w-full" :value="old('code', $building->code ?? '')" /></div>
            <div><x-input-label for="area" value="Area" /><x-text-input id="area" name="area" class="mt-1 block w-full" :value="old('area', $building->area ?? '')" /></div>
            <div class="md:col-span-2"><x-input-label for="address" value="Address" /><textarea id="address" name="address" rows="3" class="erp-focus mt-1 block w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">{{ old('address', $building->address ?? '') }}</textarea></div>
            <div><x-input-label for="latitude" value="Google map latitude" /><x-text-input id="latitude" name="latitude" class="mt-1 block w-full" :value="old('latitude', $building->latitude ?? '')" /></div>
            <div><x-input-label for="longitude" value="Google map longitude" /><x-text-input id="longitude" name="longitude" class="mt-1 block w-full" :value="old('longitude', $building->longitude ?? '')" /></div>
        </div>
    </div>

    <div class="space-y-5">
        <div class="erp-card p-5">
            <h2 class="text-lg font-bold text-[#071a3b]">Security emails</h2>
            <p class="mt-1 text-sm text-slate-500">Comma separated. Used later for booking check-in details, access card requests, and building management communication.</p>
            <textarea name="security_emails" rows="5" class="erp-focus mt-4 block w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">{{ old('security_emails', implode(', ', $building->security_emails ?? [])) }}</textarea>
        </div>
        <div class="erp-card p-5">
            <h2 class="text-lg font-bold text-[#071a3b]">Amenities</h2>
            <textarea name="amenities" rows="5" class="erp-focus mt-4 block w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" placeholder="Pool&#10;Gym&#10;Security">{{ old('amenities', implode("\n", $building->amenities ?? [])) }}</textarea>
        </div>
        <div class="erp-card p-5">
            <h2 class="text-lg font-bold text-[#071a3b]">Notes</h2>
            <textarea name="notes" rows="4" class="erp-focus mt-4 block w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">{{ old('notes', $building->notes ?? '') }}</textarea>
        </div>
    </div>
</div>

<div class="mt-6 flex justify-end gap-3">
    <a href="{{ route('buildings.index') }}" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-bold text-slate-600 hover:bg-slate-50">Cancel</a>
    <x-primary-button>{{ $submitLabel }}</x-primary-button>
</div>
