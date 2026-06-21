<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-[11px] font-bold uppercase tracking-[0.22em] text-blue-600">Unit profile</p>
            <h1 class="text-2xl font-bold text-[#071a3b]">{{ $unit->building->name }} - {{ $unit->unit_no }}</h1>
        </div>
    </x-slot>

    @php
        $primaryOwner = $unit->owners->sortByDesc(fn ($owner) => (float) ($owner->pivot?->share_percent ?? 0))->first();
        $lock = $unit->ttLock;
        $pictures = collect($unit->pictures ?? []);
        $activeBookings = $unit->bookings->whereIn('booking_status', ['confirmed', 'checked_in', 'checkout_requested']);
        $pastBookings = $unit->bookings->whereNotIn('booking_status', ['confirmed', 'checked_in', 'checkout_requested']);
        $statusClass = match ($unit->availability_status) {
            'available' => 'bg-emerald-50 text-emerald-700',
            'occupied' => 'bg-blue-50 text-blue-700',
            'maintenance' => 'bg-amber-50 text-amber-700',
            default => 'bg-rose-50 text-rose-700',
        };
    @endphp

    <div class="space-y-6">
        @if (session('status'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                <p class="font-bold">Please fix this before sending.</p>
                <ul class="mt-2 list-inside list-disc">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="erp-card overflow-hidden">
            <div class="grid gap-0 xl:grid-cols-[1.35fr_0.65fr]">
                <div class="p-6">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div>
                            <div class="flex flex-wrap items-center gap-3">
                                <span class="grid h-12 w-12 place-items-center rounded-2xl bg-blue-50 text-sm font-black text-blue-700">{{ str($unit->unit_no)->substr(0, 2)->upper() }}</span>
                                <div>
                                    <h2 class="text-2xl font-black tracking-[-0.03em] text-[#071a3b]">Unit {{ $unit->unit_no }}</h2>
                                    <p class="mt-1 text-sm text-slate-500">{{ $unit->building->name }} / {{ $unit->unit_type }} / {{ $unit->view ?: 'View not set' }}</p>
                                </div>
                                <span class="rounded-full px-3 py-1 text-xs font-bold {{ $statusClass }}">{{ str($unit->availability_status)->headline() }}</span>
                            </div>
                        </div>

                        @can('units.manage')
                            <div class="flex flex-wrap gap-2">
                                <a href="{{ route('units.edit', $unit) }}" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-bold text-slate-600 hover:bg-slate-50">Edit</a>
                                <form method="POST" action="{{ route('units.destroy', $unit) }}" onsubmit="return confirm('Delete this unit?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="rounded-xl border border-rose-200 px-4 py-2.5 text-sm font-bold text-rose-600 hover:bg-rose-50">Delete</button>
                                </form>
                            </div>
                        @endcan
                    </div>

                    <dl class="mt-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                        <div class="rounded-2xl bg-slate-50 p-4">
                            <dt class="text-xs font-bold uppercase tracking-[0.14em] text-slate-400">Rent</dt>
                            <dd class="mt-1 font-black text-[#071a3b]">{{ $unit->rent_amount ? 'AED '.number_format((float) $unit->rent_amount, 2) : 'Not set' }}</dd>
                            <dd class="text-xs text-slate-500">{{ str($unit->rent_period)->headline() }}</dd>
                        </div>
                        <div class="rounded-2xl bg-slate-50 p-4">
                            <dt class="text-xs font-bold uppercase tracking-[0.14em] text-slate-400">Management</dt>
                            <dd class="mt-1 font-black text-[#071a3b]">{{ $unit->management_fee_percent ?: 0 }}%</dd>
                            <dd class="text-xs text-slate-500">Pattern fee</dd>
                        </div>
                        <div class="rounded-2xl bg-slate-50 p-4">
                            <dt class="text-xs font-bold uppercase tracking-[0.14em] text-slate-400">Layout</dt>
                            <dd class="mt-1 font-black text-[#071a3b]">{{ $unit->bedrooms ?? '-' }} bed / {{ $unit->bathrooms ?? '-' }} bath</dd>
                            <dd class="text-xs text-slate-500">{{ $unit->size_sqft ? number_format((float) $unit->size_sqft).' sqft' : 'Size not set' }}</dd>
                        </div>
                        <div class="rounded-2xl bg-slate-50 p-4">
                            <dt class="text-xs font-bold uppercase tracking-[0.14em] text-slate-400">Parking</dt>
                            <dd class="mt-1 font-black text-[#071a3b]">{{ $unit->parking_no ?: 'Not set' }}</dd>
                            <dd class="text-xs text-slate-500">WiFi: {{ $unit->wifi_name ?: 'Not set' }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="border-t border-slate-100 bg-slate-50 p-6 xl:border-l xl:border-t-0">
                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-slate-400">Primary owner</p>
                    @if ($primaryOwner)
                        <a href="{{ route('owners.show', $primaryOwner) }}" class="mt-3 flex items-center gap-3 rounded-2xl bg-white p-4 hover:bg-blue-50">
                            <span class="grid h-11 w-11 place-items-center rounded-xl bg-blue-100 text-xs font-black text-blue-700">{{ str($primaryOwner->full_name)->substr(0, 2)->upper() }}</span>
                            <span class="min-w-0">
                                <span class="block truncate font-bold text-[#071a3b]">{{ $primaryOwner->full_name }}</span>
                                <span class="block text-xs text-slate-500">{{ $primaryOwner->pivot->share_percent }}% ownership</span>
                            </span>
                        </a>
                    @else
                        <p class="mt-3 rounded-2xl border border-dashed border-slate-200 bg-white px-4 py-6 text-center text-sm text-slate-500">No owner attached.</p>
                    @endif
                </div>
            </div>
        </div>

        @php
            $unitTabs = [
                'overview' => 'Overview',
                'photos' => 'Photos',
                'ownership' => 'Ownership',
                'contracts' => 'Contracts',
                'bookings' => 'Bookings history',
                'utilities' => 'Utilities',
                'documents' => 'Documents',
                'access' => 'Access cards',
                'smart-lock' => 'Smart lock',
            ];
        @endphp

        <div class="sticky top-20 z-10 overflow-x-auto rounded-[1.35rem] border border-slate-200 bg-white/95 p-2 shadow-xl shadow-slate-950/5 backdrop-blur" data-record-tabs>
            <div class="flex min-w-max gap-1">
                @foreach ($unitTabs as $key => $label)
                    <button type="button" data-record-tab="{{ $key }}" class="rounded-2xl px-4 py-2.5 text-xs font-black text-slate-500 transition hover:bg-blue-50 hover:text-blue-700 aria-selected:bg-blue-100 aria-selected:text-blue-700" aria-selected="{{ $key === 'overview' ? 'true' : 'false' }}">
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </div>

        <div class="space-y-5" data-record-tab-panels>
            <div class="space-y-5">
                <div class="erp-card p-5" data-record-panel="photos">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h2 class="text-lg font-bold text-[#071a3b]">Unit gallery</h2>
                            <p class="mt-1 text-sm text-slate-500">Photos uploaded for this apartment.</p>
                        </div>
                        <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-bold text-blue-700">{{ $pictures->count() }} photos</span>
                    </div>

                    @if ($pictures->isNotEmpty())
                        <div class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                            @foreach ($pictures as $index => $picture)
                                <a href="{{ route('units.picture', [$unit, $index]) }}" target="_blank" class="group overflow-hidden rounded-2xl border border-slate-200 bg-slate-100">
                                    <div class="aspect-[4/3] overflow-hidden">
                                        <img src="{{ route('units.picture', [$unit, $index]) }}" alt="{{ $picture['name'] ?? 'Unit picture' }}" class="h-full w-full object-cover transition duration-300 group-hover:scale-105" loading="lazy">
                                    </div>
                                    <div class="flex items-center justify-between gap-3 bg-white px-3 py-2">
                                        <span class="truncate text-xs font-bold text-[#071a3b]">{{ $picture['name'] ?? 'Unit picture' }}</span>
                                        <span class="text-xs font-bold text-blue-600">View</span>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    @else
                        <div class="mt-5 rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-10 text-center text-sm text-slate-500">
                            No unit pictures uploaded yet.
                        </div>
                    @endif
                </div>

                <div class="erp-card p-5" data-record-panel="ownership">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h2 class="text-lg font-bold text-[#071a3b]">Ownership shares</h2>
                            <p class="mt-1 text-sm text-slate-500">Attached owners and share allocation.</p>
                        </div>
                        <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700">{{ number_format((float) $unit->owners->sum(fn ($owner) => $owner->pivot->share_percent), 2) }}%</span>
                    </div>
                    <div class="mt-4 overflow-hidden rounded-2xl border border-slate-200">
                        @forelse ($unit->owners as $owner)
                            <a href="{{ route('owners.show', $owner) }}" class="grid gap-3 border-b border-slate-100 bg-white p-4 hover:bg-slate-50 sm:grid-cols-[1fr_120px] sm:items-center last:border-b-0">
                                <span>
                                    <span class="block font-bold text-[#071a3b]">{{ $owner->full_name }}</span>
                                    <span class="mt-1 block text-xs text-slate-500">{{ $owner->bank_name ?: 'Bank details not added' }}</span>
                                </span>
                                <span class="justify-self-start rounded-full bg-blue-50 px-3 py-1 text-xs font-bold text-blue-700 sm:justify-self-end">{{ $owner->pivot->share_percent }}%</span>
                            </a>
                        @empty
                            <p class="p-4 text-sm text-slate-500">No owners attached.</p>
                        @endforelse
                    </div>
                </div>

                <div class="erp-card p-5" data-record-panel="contracts">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h2 class="text-lg font-bold text-[#071a3b]">Owner contracts</h2>
                            <p class="mt-1 text-sm text-slate-500">Property management agreements linked to this unit.</p>
                        </div>
                        @can('owner-contracts.manage')<a href="{{ route('owner-contracts.create', ['unit_id' => $unit->id]) }}" class="rounded-xl bg-blue-600 px-4 py-2.5 text-xs font-bold text-white">Add contract</a>@endcan
                    </div>
                    <div class="mt-4 overflow-hidden rounded-2xl border border-slate-200">
                        @forelse($unit->ownerContracts as $contract)
                            <a href="{{ route('owner-contracts.show', $contract) }}" class="grid gap-3 border-b border-slate-100 p-4 hover:bg-slate-50 md:grid-cols-[1fr_120px] md:items-center last:border-b-0">
                                <span><span class="block font-bold text-[#071a3b]">{{ $contract->contract_no }} · {{ $contract->owner->full_name }}</span><span class="mt-1 block text-xs text-slate-500">{{ $contract->contract_start_date?->format('M d, Y') ?? 'No start' }} - {{ $contract->contract_end_date?->format('M d, Y') ?? 'No end' }}</span></span>
                                <span class="justify-self-start rounded-full bg-blue-50 px-3 py-1 text-xs font-bold text-blue-700 md:justify-self-end">{{ str($contract->status)->headline() }}</span>
                            </a>
                        @empty
                            <p class="px-4 py-8 text-center text-sm text-slate-500">No owner contract linked yet.</p>
                        @endforelse
                    </div>
                </div>

                <div class="erp-card p-5" data-record-panel="bookings">
                    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                        <div>
                            <h2 class="text-lg font-bold text-[#071a3b]">Bookings</h2>
                            <p class="mt-1 text-sm text-slate-500">Current stay and booking history for this apartment.</p>
                        </div>
                        <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-bold text-blue-700">{{ $unit->bookings->count() }} total</span>
                    </div>

                    <div class="mt-5 grid gap-4 lg:grid-cols-[0.9fr_1.1fr]">
                        <div class="rounded-3xl border border-blue-100 bg-blue-50/50 p-4">
                            <p class="text-xs font-bold uppercase tracking-[0.16em] text-blue-500">Current / upcoming</p>
                            <div class="mt-4 space-y-3">
                                @forelse ($activeBookings as $booking)
                                    <a href="{{ route('bookings.show', $booking) }}" class="block rounded-2xl bg-white p-4 shadow-sm hover:bg-slate-50">
                                        <div class="flex items-start justify-between gap-3">
                                            <div>
                                                <p class="font-bold text-[#071a3b]">{{ $booking->tenant?->full_name ?? 'Tenant not assigned' }}</p>
                                                <p class="mt-1 text-xs text-slate-500">{{ $booking->booking_no }} / {{ str($booking->booking_type)->headline() }}</p>
                                            </div>
                                            <span class="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-bold text-blue-700">{{ str($booking->booking_status)->headline() }}</span>
                                        </div>
                                        <p class="mt-3 text-sm font-bold text-[#071a3b]">{{ $booking->check_in_date?->format('M d, Y') }} - {{ $booking->check_out_date?->format('M d, Y') }}</p>
                                        <p class="mt-1 text-xs text-slate-500">Total AED {{ number_format((float) $booking->total_amount, 2) }}</p>
                                    </a>
                                @empty
                                    <p class="rounded-2xl border border-dashed border-blue-200 bg-white/70 px-4 py-8 text-center text-sm text-slate-500">No current booking for this unit.</p>
                                @endforelse
                            </div>
                        </div>

                        <div class="rounded-3xl border border-slate-200 bg-white p-4">
                            <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">Booking history</p>
                            <div class="mt-4 overflow-hidden rounded-2xl border border-slate-200">
                                @forelse ($pastBookings as $booking)
                                    <a href="{{ route('bookings.show', $booking) }}" class="grid gap-3 border-b border-slate-100 p-4 hover:bg-slate-50 md:grid-cols-[1fr_140px] md:items-center last:border-b-0">
                                        <span>
                                            <span class="block font-bold text-[#071a3b]">{{ $booking->tenant?->full_name ?? 'Tenant not assigned' }}</span>
                                            <span class="mt-1 block text-xs text-slate-500">{{ $booking->booking_no }} / {{ $booking->check_in_date?->format('M d') }} - {{ $booking->check_out_date?->format('M d, Y') }}</span>
                                        </span>
                                        <span class="justify-self-start rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600 md:justify-self-end">{{ str($booking->booking_status)->headline() }}</span>
                                    </a>
                                @empty
                                    <p class="px-4 py-8 text-center text-sm text-slate-500">No previous bookings yet.</p>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid gap-5 lg:grid-cols-2">
                    <div class="erp-card p-5" data-record-panel="utilities">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h2 class="text-lg font-bold text-[#071a3b]">Utility Management</h2>
                                <p class="mt-1 text-sm text-slate-500">DEWA, internet, gas, cooling, billing dates, and responsibility.</p>
                            </div>
                            @can('units.manage')
                                <a href="{{ route('units.edit', $unit) }}#utilities" class="rounded-xl bg-blue-600 px-4 py-2.5 text-xs font-bold text-white">Update utilities</a>
                            @endcan
                        </div>

                        <div class="mt-5 grid gap-3">
                            @forelse ($unit->utilityAccounts as $account)
                                <div class="rounded-3xl border border-slate-200 bg-white p-4">
                                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                        <div>
                                            <p class="text-xs font-bold uppercase tracking-[0.14em] text-blue-600">{{ str($account->provider_type)->headline() }}</p>
                                            <h3 class="mt-1 text-lg font-black text-[#071a3b]">{{ $account->provider_name }}</h3>
                                            <p class="mt-1 text-xs text-slate-500">Account: {{ $account->account_no ?: 'Not added' }}</p>
                                        </div>
                                        <span class="w-fit rounded-full {{ $account->paid_by_company ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }} px-2.5 py-1 text-[11px] font-black">
                                            {{ $account->paid_by_company ? 'Paid by Pattern' : 'Owner paid' }}
                                        </span>
                                    </div>
                                    <div class="mt-4 grid gap-3 sm:grid-cols-3">
                                        <div class="rounded-2xl bg-slate-50 p-3"><p class="text-[10px] font-bold uppercase text-slate-400">Billing day</p><p class="mt-1 text-sm font-black text-[#071a3b]">{{ $account->billing_day ? 'Day '.$account->billing_day : '-' }}</p></div>
                                        <div class="rounded-2xl bg-slate-50 p-3"><p class="text-[10px] font-bold uppercase text-slate-400">Next due</p><p class="mt-1 text-sm font-black text-[#071a3b]">{{ $account->next_due_date?->format('M d, Y') ?? '-' }}</p></div>
                                        <div class="rounded-2xl bg-slate-50 p-3"><p class="text-[10px] font-bold uppercase text-slate-400">Estimate</p><p class="mt-1 text-sm font-black text-[#071a3b]">{{ $account->estimated_amount ? 'AED '.number_format((float) $account->estimated_amount, 2) : '-' }}</p></div>
                                    </div>
                                    @if ($account->notes)
                                        <p class="mt-3 rounded-2xl bg-blue-50 p-3 text-sm text-slate-600">{{ $account->notes }}</p>
                                    @endif
                                </div>
                            @empty
                                <p class="rounded-2xl border border-dashed border-slate-200 px-4 py-10 text-center text-sm text-slate-500">No utility accounts added yet. Add DEWA, internet, gas, or cooling from the unit edit page.</p>
                            @endforelse
                        </div>
                    </div>

                    <div class="erp-card p-5" data-record-panel="overview">
                        <h2 class="text-lg font-bold text-[#071a3b]">Amenities</h2>
                        <div class="mt-4 flex flex-wrap gap-2">
                            @forelse ($unit->amenities ?? [] as $amenity)
                                <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-bold text-blue-700">{{ $amenity }}</span>
                            @empty
                                <span class="text-sm text-slate-500">No amenities.</span>
                            @endforelse
                        </div>
                        @if ($unit->notes)
                            <div class="mt-5 rounded-2xl bg-slate-50 p-4 text-sm text-slate-600">{{ $unit->notes }}</div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="space-y-5">
                <div class="erp-card p-5" data-record-panel="documents">
                    <h2 class="text-lg font-bold text-[#071a3b]">Documents</h2>
                    <div class="mt-4 space-y-3">
                        @foreach (['title_deed' => 'Title deed', 'dtcm_permit' => 'DTCM permit', 'ttlock_attachment' => 'TT Lock'] as $type => $label)
                            <div class="rounded-2xl border border-slate-200 p-4">
                                <div class="font-bold text-[#071a3b]">{{ $label }}</div>
                                <div class="mt-1 text-xs text-slate-500">{{ $unit->getAttribute($type.'_original_name') ?: 'No file uploaded' }}</div>
                                @if ($unit->getAttribute($type.'_path'))
                                    <a href="{{ route('units.document', [$unit, $type]) }}" target="_blank" class="mt-3 inline-flex rounded-xl bg-blue-600 px-3 py-2 text-xs font-bold text-white">Open</a>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="erp-card p-5" data-record-panel="access">
                    <h2 class="text-lg font-bold text-[#071a3b]">Access card email</h2>
                    <div class="mt-4 rounded-2xl border border-blue-100 bg-blue-50 p-4">
                        <p class="text-sm font-bold text-[#071a3b]">Attachments that will be sent</p>
                        <div class="mt-3 space-y-2 text-xs">
                            <div class="flex items-center justify-between gap-3 rounded-xl bg-white px-3 py-2">
                                <span>
                                    <span class="font-bold text-[#071a3b]">Title deed</span>
                                    <span class="block text-slate-500">{{ $unit->title_deed_original_name ?: 'Missing - upload in edit unit' }}</span>
                                </span>
                                @if ($unit->title_deed_path)
                                    <a href="{{ route('units.document', [$unit, 'title_deed']) }}" target="_blank" class="rounded-lg bg-blue-600 px-3 py-1.5 font-bold text-white">View</a>
                                @else
                                    <span class="font-bold text-rose-600">Required</span>
                                @endif
                            </div>
                            <div class="flex items-center justify-between gap-3 rounded-xl bg-white px-3 py-2">
                                <span>
                                    <span class="font-bold text-[#071a3b]">Primary owner ID</span>
                                    <span class="block text-slate-500">{{ $primaryOwner ? $primaryOwner->full_name.' - '.($primaryOwner->document_original_name ?: 'Missing document') : 'No owner attached' }}</span>
                                </span>
                                @if ($primaryOwner?->document_path)
                                    <a href="{{ route('owners.document', $primaryOwner) }}" target="_blank" class="rounded-lg bg-blue-600 px-3 py-1.5 font-bold text-white">View</a>
                                @else
                                    <span class="font-bold text-rose-600">Required</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    @can('units.manage')
                        <form method="POST" action="{{ route('units.access-card-request', $unit) }}" class="mt-4 space-y-3">
                            @csrf
                            <select name="request_type" class="erp-focus h-10 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm text-[#071a3b]">
                                <option>New card</option>
                                <option>Lost card</option>
                                <option>Replacement card</option>
                            </select>
                            <select name="card_type" class="erp-focus h-10 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm text-[#071a3b]">
                                <option>Access card</option>
                                <option>Parking card</option>
                                <option>Access and parking card</option>
                            </select>
                            <textarea name="notes" rows="3" class="erp-focus w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-[#071a3b]" placeholder="Request notes for security"></textarea>
                            <button class="w-full rounded-xl bg-blue-600 px-4 py-2.5 text-xs font-bold text-white hover:bg-blue-700">Queue security email</button>
                        </form>
                    @endcan
                </div>

                <div class="erp-card p-5" data-record-panel="smart-lock">
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="text-lg font-bold text-[#071a3b]">Smart lock</h2>
                        <span class="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-bold text-blue-700">One lock / apartment</span>
                    </div>
                    <div class="mt-4">
                        @if ($lock)
                            <div class="rounded-2xl border border-slate-200 p-4">
                                <div class="flex items-center justify-between gap-3">
                                    <div class="font-bold text-[#071a3b]">{{ $lock->lock_name }}</div>
                                    <span class="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-bold text-blue-700">{{ str($lock->status)->replace('_', ' ')->headline() }}</span>
                                </div>
                                <div class="mt-2 text-xs text-slate-500">Lock ID: {{ $lock->lock_id }} / Gateway: {{ $lock->gateway_id ?: 'N/A' }}</div>
                                <div class="mt-2 text-xs text-slate-500">Battery: {{ $lock->battery_level !== null ? $lock->battery_level.'%' : 'N/A' }} / Signal: {{ $lock->signal_strength ?: 'N/A' }}</div>
                                <div class="mt-2 text-xs text-slate-500">Last sync: {{ $lock->last_synced_at?->format('M d, Y H:i') ?? 'Not synced' }}</div>
                                @if ($lock->notes)
                                    <p class="mt-3 text-sm text-slate-600">{{ $lock->notes }}</p>
                                @endif
                            </div>
                        @else
                            <p class="rounded-2xl border border-dashed border-slate-200 px-4 py-6 text-center text-sm text-slate-500">No smart lock attached yet.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const tabs = Array.from(document.querySelectorAll('[data-record-tab]'));
            const panels = Array.from(document.querySelectorAll('[data-record-panel]'));

            if (! tabs.length || ! panels.length) {
                return;
            }

            const showTab = (key) => {
                tabs.forEach((tab) => tab.setAttribute('aria-selected', tab.dataset.recordTab === key ? 'true' : 'false'));
                panels.forEach((panel) => {
                    panel.toggleAttribute('hidden', panel.dataset.recordPanel !== key);
                });
            };

            const requested = window.location.hash ? window.location.hash.substring(1) : 'overview';
            const initial = tabs.some((tab) => tab.dataset.recordTab === requested) ? requested : 'overview';
            showTab(initial);

            tabs.forEach((tab) => {
                tab.addEventListener('click', () => {
                    const key = tab.dataset.recordTab;
                    showTab(key);
                    history.replaceState(null, '', `#${key}`);
                });
            });
        });
    </script>
</x-app-layout>
