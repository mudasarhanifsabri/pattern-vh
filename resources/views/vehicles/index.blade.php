<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-[11px] font-bold uppercase tracking-[0.22em] text-blue-600">Operations fleet</p>
            <h1 class="text-3xl font-black tracking-[-0.04em] text-[#071a3b]">Vehicle management</h1>
            <p class="mt-2 text-sm text-slate-500">Track vehicle availability, operation handovers, check-in/check-out remarks, and four-side condition photos.</p>
        </div>
    </x-slot>

    <div class="space-y-6">
        @if (session('status'))<div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">{{ session('status') }}</div>@endif

        <div class="erp-card p-2">
            <div class="flex flex-wrap gap-2 text-sm font-bold">
                <a href="#vehicles" class="rounded-xl bg-blue-50 px-4 py-2.5 text-blue-700">Vehicles</a>
                <a href="#handover" class="rounded-xl px-4 py-2.5 text-slate-500 hover:bg-slate-50">Check in / out</a>
                <a href="#history" class="rounded-xl px-4 py-2.5 text-slate-500 hover:bg-slate-50">Handover history</a>
            </div>
        </div>

        <div class="space-y-5">
                <section id="vehicles">
                    <div class="mb-4 flex items-center justify-between gap-3"><div><h2 class="text-lg font-bold text-[#071a3b]">Fleet register</h2><p class="mt-1 text-sm text-slate-500">Vehicles, availability, and latest usage.</p></div>@can('vehicles.manage')<div class="flex gap-2"><button type="button" x-data x-on:click="$dispatch('open-modal', 'vehicle-handover')" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-bold">Check in / out</button><button type="button" x-data x-on:click="$dispatch('open-modal', 'add-vehicle')" class="rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-bold text-white">+ Add vehicle</button></div>@endcan</div>
                    <div class="grid gap-4 lg:grid-cols-2">
                    @forelse ($vehicles as $vehicle)
                        @php $last = $vehicle->handovers->sortByDesc('handover_at')->first(); @endphp
                        <div class="erp-card p-5">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">{{ $vehicle->vehicle_type ?: 'Vehicle' }}</p>
                                    <h2 class="mt-1 text-xl font-black text-[#071a3b]">{{ $vehicle->name }}</h2>
                                    <p class="mt-1 text-sm text-slate-500">{{ $vehicle->plate_no }} · {{ $vehicle->make_model ?: 'Model not set' }}</p>
                                </div>
                                <span class="rounded-full {{ $vehicle->status === 'available' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }} px-3 py-1 text-xs font-bold">{{ str($vehicle->status)->headline() }}</span>
                            </div>
                            <dl class="mt-5 grid gap-3 sm:grid-cols-3">
                                <div class="rounded-2xl bg-slate-50 p-3"><dt class="text-[10px] font-bold uppercase text-slate-400">Odometer</dt><dd class="mt-1 text-sm font-bold text-[#071a3b]">{{ $vehicle->odometer ? number_format($vehicle->odometer).' km' : '-' }}</dd></div>
                                <div class="rounded-2xl bg-slate-50 p-3"><dt class="text-[10px] font-bold uppercase text-slate-400">Insurance</dt><dd class="mt-1 text-sm font-bold text-[#071a3b]">{{ $vehicle->insurance_expiry_date?->format('M d') ?? '-' }}</dd></div>
                                <div class="rounded-2xl bg-slate-50 p-3"><dt class="text-[10px] font-bold uppercase text-slate-400">Last use</dt><dd class="mt-1 text-sm font-bold text-[#071a3b]">{{ $last?->teamMember?->full_name ?? 'No handover' }}</dd></div>
                            </dl>
                        </div>
                    @empty
                        <div class="erp-card p-8 text-center text-sm text-slate-500 lg:col-span-2">No vehicles added yet.</div>
                    @endforelse
                    </div>
                </section>

                <section id="history" class="erp-card p-5">
                    <h2 class="text-lg font-bold text-[#071a3b]">Handover history</h2>
                    <div class="mt-4 overflow-hidden rounded-2xl border border-slate-200">
                        @forelse ($vehicles->flatMap->handovers->sortByDesc('handover_at') as $handover)
                            <div class="grid gap-3 border-b border-slate-100 p-4 md:grid-cols-[1fr_160px] md:items-center last:border-b-0">
                                <div>
                                    <p class="font-bold text-[#071a3b]">{{ $handover->vehicle->name }} · {{ str($handover->handover_type)->replace('_', ' ')->headline() }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $handover->teamMember?->full_name ?? 'Team member not selected' }} · {{ $handover->handover_at?->format('M d, Y H:i') }} · {{ $handover->fuel_level ?: 'Fuel not set' }}</p>
                                    @if ($handover->remarks)<p class="mt-2 text-sm text-slate-600">{{ $handover->remarks }}</p>@endif
                                </div>
                                <span class="rounded-full bg-blue-50 px-3 py-1 text-center text-xs font-bold text-blue-700">{{ count($handover->photos ?? []) }} photos</span>
                            </div>
                        @empty
                            <p class="px-4 py-8 text-center text-sm text-slate-500">No vehicle handovers yet.</p>
                        @endforelse
                    </div>
                </section>
        </div>

        @can('vehicles.manage')
            <x-modal name="add-vehicle" maxWidth="lg" focusable>
                    <form method="POST" action="{{ route('vehicles.store') }}" class="p-6">
                        @csrf
                        <div class="flex items-center justify-between"><h2 class="text-lg font-bold text-[#071a3b]">Add vehicle</h2><button type="button" x-on:click="$dispatch('close')" class="rounded-xl bg-slate-100 px-3 py-2 text-sm font-bold">Close</button></div>
                        <div class="mt-4 space-y-3">
                            <input name="name" class="erp-focus h-11 w-full rounded-xl border border-slate-200 px-3 text-sm" placeholder="Vehicle name" required>
                            <input name="plate_no" class="erp-focus h-11 w-full rounded-xl border border-slate-200 px-3 text-sm" placeholder="Plate no" required>
                            <input name="make_model" class="erp-focus h-11 w-full rounded-xl border border-slate-200 px-3 text-sm" placeholder="Make / model">
                            <input name="vehicle_type" class="erp-focus h-11 w-full rounded-xl border border-slate-200 px-3 text-sm" placeholder="Type e.g. Van">
                            <input type="hidden" name="status" value="available">
                            <button class="w-full rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-bold text-white">Save vehicle</button>
                        </div>
                    </form>
            </x-modal>
            <x-modal name="vehicle-handover" maxWidth="xl" focusable>
                    <form id="handover" method="POST" action="{{ $vehicles->first() ? route('vehicles.handover', $vehicles->first()) : '#' }}" enctype="multipart/form-data" class="p-6" data-vehicle-handover-form>
                        @csrf
                        <div class="flex items-center justify-between"><h2 class="text-lg font-bold text-[#071a3b]">Check in / check out</h2><button type="button" x-on:click="$dispatch('close')" class="rounded-xl bg-slate-100 px-3 py-2 text-sm font-bold">Close</button></div>
                        <div class="mt-4 space-y-3">
                            <select data-vehicle-action class="erp-focus h-11 w-full rounded-xl border border-slate-200 px-3 text-sm" required>
                                @foreach($vehicles as $vehicle)<option value="{{ route('vehicles.handover', $vehicle) }}">{{ $vehicle->name }} · {{ $vehicle->plate_no }}</option>@endforeach
                            </select>
                            <select name="handover_type" class="erp-focus h-11 w-full rounded-xl border border-slate-200 px-3 text-sm"><option value="check_out">Check out vehicle</option><option value="check_in">Check in vehicle</option></select>
                            <select name="team_member_id" class="erp-focus h-11 w-full rounded-xl border border-slate-200 px-3 text-sm"><option value="">Select team member</option>@foreach($teamMembers as $member)<option value="{{ $member->id }}">{{ $member->full_name }} · {{ str($member->team_role)->headline() }}</option>@endforeach</select>
                            <input name="handover_at" type="datetime-local" value="{{ now()->format('Y-m-d\TH:i') }}" class="erp-focus h-11 w-full rounded-xl border border-slate-200 px-3 text-sm" required>
                            <div class="grid grid-cols-2 gap-3"><input name="odometer" class="erp-focus h-11 rounded-xl border border-slate-200 px-3 text-sm" placeholder="Odometer"><input name="fuel_level" class="erp-focus h-11 rounded-xl border border-slate-200 px-3 text-sm" placeholder="Fuel level"></div>
                            <div class="grid grid-cols-2 gap-3 text-xs text-slate-500">
                                @foreach(['front_photo' => 'Front photo', 'back_photo' => 'Back photo', 'left_photo' => 'Left photo', 'right_photo' => 'Right photo'] as $field => $label)
                                    <label class="rounded-xl border border-dashed border-blue-200 bg-blue-50/50 p-3">{{ $label }}<input name="{{ $field }}" type="file" accept="image/*" class="mt-2 block w-full text-xs"></label>
                                @endforeach
                            </div>
                            <textarea name="remarks" rows="3" class="erp-focus w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" placeholder="Remarks / damage notes"></textarea>
                            <button class="w-full rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-bold text-white" @disabled($vehicles->isEmpty())>Save handover</button>
                        </div>
                    </form>
            </x-modal>
        @endcan
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.querySelector('[data-vehicle-handover-form]');
            const select = document.querySelector('[data-vehicle-action]');
            select?.addEventListener('change', () => form.action = select.value);
        });
    </script>
</x-app-layout>
