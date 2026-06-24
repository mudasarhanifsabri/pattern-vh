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

@php
    $existingOwnerRows = isset($unit)
        ? $unit->owners->map(fn ($owner) => [
            'owner_id' => $owner->id,
            'share_percent' => $owner->pivot?->share_percent ?? 100,
        ])->values()->all()
        : [];
    $ownerRows = old('ownership_rows', $existingOwnerRows ?: [['owner_id' => '', 'share_percent' => 100]]);
    $ownerRows = collect($ownerRows)->values()->all();
    $ownerShareTotal = collect($ownerRows)->sum(fn ($row) => (float) ($row['share_percent'] ?? 0));
    $selectedTtLockId = old('tt_lock_id', $unit->tt_lock_id ?? '');
    $existingUtilityRows = isset($unit)
        ? $unit->utilityAccounts->map(fn ($account) => [
            'id' => $account->id,
            'provider_type' => $account->provider_type,
            'provider_name' => $account->provider_name,
            'account_no' => $account->account_no,
            'username' => $account->username,
            'password' => $account->password,
            'billing_day' => $account->billing_day,
            'next_due_date' => $account->next_due_date?->format('Y-m-d'),
            'estimated_amount' => $account->estimated_amount,
            'paid_by_company' => $account->paid_by_company,
            'notes' => $account->notes,
        ])->values()->all()
        : [];
    $utilityRows = old('utility_accounts', $existingUtilityRows);
    $utilityRows = collect($utilityRows)->values()->all();
@endphp

<div class="grid gap-5 xl:grid-cols-[1fr_380px]">
    <div class="space-y-5">
        <div class="erp-card p-5">
            <h2 class="text-lg font-bold text-[#071a3b]">Unit details</h2>
            <div class="mt-5 grid gap-4 md:grid-cols-3">
                <div><x-input-label for="building_id" value="Building" /><select name="building_id" id="building_id" class="erp-focus mt-1 block h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm" required><option value="">Select building</option>@foreach ($buildings as $building)<option value="{{ $building->id }}" @selected(old('building_id', $unit->building_id ?? '') == $building->id)>{{ $building->name }}</option>@endforeach</select></div>
                <div><x-input-label for="unit_no" value="Unit no" /><x-text-input id="unit_no" name="unit_no" class="mt-1 block w-full" :value="old('unit_no', $unit->unit_no ?? '')" required /></div>
                <div><x-input-label for="unit_type" value="Unit type" /><select name="unit_type" id="unit_type" class="erp-focus mt-1 block h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm">@foreach ($unitTypes as $type)<option value="{{ $type }}" @selected(old('unit_type', $unit->unit_type ?? '') === $type)>{{ $type }}</option>@endforeach</select></div>
                <div><x-input-label for="availability_status" value="Availability" /><select name="availability_status" id="availability_status" class="erp-focus mt-1 block h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm">@foreach ($availabilityStatuses as $status)<option value="{{ $status }}" @selected(old('availability_status', $unit->availability_status ?? 'available') === $status)>{{ str($status)->headline() }}</option>@endforeach</select></div>
                <div><x-input-label for="floor" value="Floor" /><x-text-input id="floor" name="floor" class="mt-1 block w-full" :value="old('floor', $unit->floor ?? '')" /></div>
                <div><x-input-label for="size_sqft" value="Size sqft" /><x-text-input id="size_sqft" name="size_sqft" class="mt-1 block w-full" :value="old('size_sqft', $unit->size_sqft ?? '')" /></div>
                <div><x-input-label for="bedrooms" value="Bedrooms" /><x-text-input id="bedrooms" name="bedrooms" type="number" class="mt-1 block w-full" :value="old('bedrooms', $unit->bedrooms ?? '')" /></div>
                <div><x-input-label for="bathrooms" value="Bathrooms" /><x-text-input id="bathrooms" name="bathrooms" type="number" class="mt-1 block w-full" :value="old('bathrooms', $unit->bathrooms ?? '')" /></div>
                <div><x-input-label for="view" value="View" /><x-text-input id="view" name="view" class="mt-1 block w-full" :value="old('view', $unit->view ?? '')" /></div>
            </div>
        </div>

        <div class="erp-card overflow-hidden">
            <div class="flex flex-col gap-4 p-5 md:flex-row md:items-center md:justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-600">Ownership shares</p>
                    <h2 class="mt-1 text-lg font-bold text-[#071a3b]">Owner share allocation</h2>
                    <p class="mt-1 text-sm text-slate-500">Select one owner at 100%, or add multiple owners whose shares total exactly 100%.</p>
                </div>
                <div class="rounded-2xl border border-blue-100 bg-blue-50 px-4 py-3 text-right">
                    <p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-500">Allocated</p>
                    <p class="text-2xl font-black text-[#071a3b]"><span data-owner-total>{{ number_format($ownerShareTotal, 2) }}</span>%</p>
                    <p class="text-xs font-bold text-emerald-600" data-owner-total-status>{{ abs($ownerShareTotal - 100) < 0.01 ? 'Valid allocation' : 'Must total 100%' }}</p>
                </div>
            </div>

            <div class="border-y border-slate-100 bg-slate-50 px-5 py-3">
                <div class="hidden grid-cols-[1.5fr_150px_1fr_44px] gap-3 text-[11px] font-bold uppercase tracking-[0.12em] text-slate-400 md:grid">
                    <span>Owner</span>
                    <span>Share percentage</span>
                    <span>Payout account</span>
                    <span></span>
                </div>
            </div>

            <div class="space-y-3 p-5" data-owner-rows>
                @foreach ($ownerRows as $index => $row)
                    <div class="grid gap-3 rounded-2xl border border-slate-200 bg-white p-3 md:grid-cols-[1.5fr_150px_1fr_44px] md:items-end" data-owner-row>
                        <div>
                            <x-input-label value="Owner" />
                            <select name="ownership_rows[{{ $index }}][owner_id]" data-name="owner_id" class="erp-focus mt-1 h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm">
                                <option value="">Select owner</option>
                                @foreach ($owners as $owner)
                                    <option value="{{ $owner->id }}" @selected((string) ($row['owner_id'] ?? '') === (string) $owner->id)>{{ $owner->full_name }}{{ $owner->identity_no ? ' - '.$owner->identity_no : '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <x-input-label value="Share %" />
                            <div class="relative mt-1">
                                <input name="ownership_rows[{{ $index }}][share_percent]" data-name="share_percent" value="{{ $row['share_percent'] ?? '' }}" type="number" min="0" max="100" step="0.01" class="erp-focus h-11 w-full rounded-xl border border-slate-200 px-3 pr-8 text-sm" data-owner-share placeholder="100">
                                <span class="absolute right-3 top-3 text-xs font-bold text-slate-400">%</span>
                            </div>
                        </div>
                        <div>
                            <x-input-label value="Payout account" />
                            <div class="mt-1 flex h-11 items-center rounded-xl border border-slate-200 bg-slate-50 px-3 text-sm font-medium text-slate-600" data-owner-bank>
                                @php($selectedOwner = $owners->firstWhere('id', $row['owner_id'] ?? null))
                                {{ $selectedOwner?->bank_name ? $selectedOwner->bank_name.' - '.str($selectedOwner->bank_account_no ?: $selectedOwner->iban)->mask('*', 0, -4) : 'Use owner bank details' }}
                            </div>
                        </div>
                        <button type="button" class="flex h-11 items-center justify-center rounded-xl bg-rose-50 text-rose-500 hover:bg-rose-100" data-remove-owner-row aria-label="Remove owner">×</button>
                    </div>
                @endforeach
            </div>

            <div class="flex flex-col gap-3 border-t border-slate-100 bg-slate-50 px-5 py-4 md:flex-row md:items-center md:justify-between">
                <div class="rounded-2xl bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-700" data-owner-message>Active ownership shares must total exactly 100%.</div>
                <button type="button" class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-[#071a3b] hover:bg-blue-50" data-add-owner-row>+ Add owner</button>
            </div>
        </div>

        <div class="erp-card p-5">
            <h2 class="text-lg font-bold text-[#071a3b]">Rent and access</h2>
            <div class="mt-5 grid gap-4 md:grid-cols-3">
                <div><x-input-label for="management_fee_percent" value="Pattern management fee %" /><x-text-input id="management_fee_percent" name="management_fee_percent" class="mt-1 block w-full" :value="old('management_fee_percent', $unit->management_fee_percent ?? '')" /></div>
                <div><x-input-label for="rent_period" value="Rent period" /><select name="rent_period" id="rent_period" class="erp-focus mt-1 h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm">@foreach ($rentPeriods as $period)<option value="{{ $period }}" @selected(old('rent_period', $unit->rent_period ?? 'monthly') === $period)>{{ str($period)->headline() }}</option>@endforeach</select></div>
                <div><x-input-label for="rent_amount" value="Rent amount" /><x-text-input id="rent_amount" name="rent_amount" class="mt-1 block w-full" :value="old('rent_amount', $unit->rent_amount ?? '')" /></div>
                <div><x-input-label for="parking_no" value="Parking no" /><x-text-input id="parking_no" name="parking_no" class="mt-1 block w-full" :value="old('parking_no', $unit->parking_no ?? '')" /></div>
                <div><x-input-label for="wifi_name" value="WiFi name" /><x-text-input id="wifi_name" name="wifi_name" class="mt-1 block w-full" :value="old('wifi_name', $unit->wifi_name ?? '')" /></div>
                <div><x-input-label for="wifi_password" value="WiFi password" /><x-text-input id="wifi_password" name="wifi_password" class="mt-1 block w-full" :value="old('wifi_password', $unit->wifi_password ?? '')" /></div>
            </div>
        </div>

        <div class="erp-card overflow-hidden">
            <div class="flex flex-col gap-3 p-5 md:flex-row md:items-center md:justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-600">Recurring services</p>
                    <h2 class="mt-1 text-lg font-bold text-[#071a3b]">Utility accounts</h2>
                    <p class="mt-1 text-sm text-slate-500">Add only the services this apartment has. These accounts flow into Utility Management.</p>
                </div>
                <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-bold text-blue-700">Saved to Utility Management</span>
            </div>

            <div class="border-t border-slate-100 bg-slate-50/60 p-5">
                <input type="hidden" name="utility_accounts_present" value="1">

                <div class="mb-4 flex flex-wrap gap-2">
                    @foreach ([
                        ['type' => 'dewa', 'name' => 'DEWA'],
                        ['type' => 'internet', 'name' => 'Internet'],
                        ['type' => 'gas', 'name' => 'Gas'],
                        ['type' => 'cooling', 'name' => 'Cooling'],
                        ['type' => 'other', 'name' => 'Other'],
                    ] as $quickUtility)
                        <button type="button" class="rounded-xl border border-blue-200 bg-white px-3 py-2 text-xs font-black text-blue-700 hover:bg-blue-50" data-add-utility-row data-provider-type="{{ $quickUtility['type'] }}" data-provider-name="{{ $quickUtility['name'] }}">+ {{ $quickUtility['name'] }}</button>
                    @endforeach
                </div>

                <div class="space-y-4" data-utility-rows>
                @foreach ($utilityRows as $index => $row)
                    <div class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm shadow-slate-200/40" data-utility-row>
                        <input type="hidden" name="utility_accounts[{{ $index }}][id]" value="{{ $row['id'] ?? '' }}">
                        <div class="mb-3 flex items-center justify-between gap-3">
                            <p class="text-xs font-black uppercase tracking-[0.16em] text-slate-400">Utility service</p>
                            <button type="button" class="rounded-xl bg-rose-50 px-3 py-2 text-xs font-bold text-rose-600 hover:bg-rose-100" data-remove-utility-row>Remove</button>
                        </div>
                        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                            <div>
                                <x-input-label value="Service type" />
                                <select name="utility_accounts[{{ $index }}][provider_type]" data-utility-name="provider_type" class="erp-focus mt-1 h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm">
                                    <option value="">Select type</option>
                                    @foreach ($utilityProviderTypes as $type)
                                        <option value="{{ $type }}" @selected(($row['provider_type'] ?? '') === $type)>{{ str($type)->headline() }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <x-input-label value="Provider name" />
                                <input name="utility_accounts[{{ $index }}][provider_name]" data-utility-name="provider_name" value="{{ $row['provider_name'] ?? '' }}" class="erp-focus mt-1 h-11 w-full rounded-xl border border-slate-200 px-3 text-sm" placeholder="Provider name e.g. DEWA">
                            </div>
                            <div>
                                <x-input-label value="Account no" />
                                <input name="utility_accounts[{{ $index }}][account_no]" data-utility-name="account_no" value="{{ $row['account_no'] ?? '' }}" class="erp-focus mt-1 h-11 w-full rounded-xl border border-slate-200 px-3 text-sm" placeholder="Account no">
                            </div>
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-3 py-3">
                                <input type="hidden" name="utility_accounts[{{ $index }}][paid_by_company]" data-utility-name="paid_by_company_hidden" value="0">
                                <label class="flex items-center gap-2 text-sm font-bold text-[#071a3b]">
                                    <input type="checkbox" name="utility_accounts[{{ $index }}][paid_by_company]" data-utility-name="paid_by_company" value="1" class="rounded border-slate-300 text-blue-600" @checked((bool) ($row['paid_by_company'] ?? false))>
                                    Paid by Pattern
                                </label>
                                <p class="mt-1 text-xs text-slate-500">Used for accounting and owner statements.</p>
                            </div>
                            <div>
                                <x-input-label value="Billing day" />
                                <input name="utility_accounts[{{ $index }}][billing_day]" data-utility-name="billing_day" value="{{ $row['billing_day'] ?? '' }}" type="number" min="1" max="31" class="erp-focus mt-1 h-11 w-full rounded-xl border border-slate-200 px-3 text-sm" placeholder="Day 1-31">
                            </div>
                            <div>
                                <x-input-label value="Next due date" />
                                <input name="utility_accounts[{{ $index }}][next_due_date]" data-utility-name="next_due_date" value="{{ $row['next_due_date'] ?? '' }}" type="date" class="erp-focus mt-1 h-11 w-full rounded-xl border border-slate-200 px-3 text-sm">
                            </div>
                            <div>
                                <x-input-label value="Estimated amount" />
                                <input name="utility_accounts[{{ $index }}][estimated_amount]" data-utility-name="estimated_amount" value="{{ $row['estimated_amount'] ?? '' }}" class="erp-focus mt-1 h-11 w-full rounded-xl border border-slate-200 px-3 text-sm" placeholder="AED">
                            </div>
                            <div>
                                <x-input-label value="Login username" />
                                <input name="utility_accounts[{{ $index }}][username]" data-utility-name="username" value="{{ $row['username'] ?? '' }}" class="erp-focus mt-1 h-11 w-full rounded-xl border border-slate-200 px-3 text-sm" placeholder="Optional">
                            </div>
                            <div>
                                <x-input-label value="Login password" />
                                <input name="utility_accounts[{{ $index }}][password]" data-utility-name="password" value="{{ $row['password'] ?? '' }}" class="erp-focus mt-1 h-11 w-full rounded-xl border border-slate-200 px-3 text-sm" placeholder="Optional">
                            </div>
                            <div class="md:col-span-2 xl:col-span-3">
                                <x-input-label value="Notes" />
                                <input name="utility_accounts[{{ $index }}][notes]" data-utility-name="notes" value="{{ $row['notes'] ?? '' }}" class="erp-focus mt-1 h-11 w-full rounded-xl border border-slate-200 px-3 text-sm" placeholder="Responsibility, meter location, service remarks">
                            </div>
                        </div>
                    </div>
                @endforeach
                </div>

                <div class="mt-4 rounded-2xl border border-dashed border-blue-200 bg-blue-50/60 p-4 text-sm text-blue-800" data-utility-empty @class(['hidden' => count($utilityRows) > 0])>
                    No utility accounts added yet. Use the buttons above to add DEWA, gas, internet, cooling, or another service only when this unit needs it.
                </div>
            </div>
        </div>
    </div>

    <div class="space-y-5">
        <div class="erp-card p-5"><h2 class="text-lg font-bold text-[#071a3b]">Amenities</h2><textarea name="amenities" rows="5" class="erp-focus mt-4 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" placeholder="Balcony&#10;Kitchen equipped&#10;Sea view">{{ old('amenities', implode("\n", $unit->amenities ?? [])) }}</textarea></div>
        <div class="erp-card p-5">
            <h2 class="text-lg font-bold text-[#071a3b]">Documents</h2>
            <div class="mt-4 space-y-4">
                <div><x-input-label for="title_deed_no" value="Title deed no" /><x-text-input id="title_deed_no" name="title_deed_no" class="mt-1 block w-full" :value="old('title_deed_no', $unit->title_deed_no ?? '')" /></div>
                <div><x-input-label for="title_deed_issue_date" value="Title deed issue date" /><x-text-input id="title_deed_issue_date" name="title_deed_issue_date" type="date" class="mt-1 block w-full" :value="old('title_deed_issue_date', isset($unit) && $unit->title_deed_issue_date ? $unit->title_deed_issue_date->format('Y-m-d') : '')" /></div>
                <div><x-input-label for="title_deed_expiry_date" value="Title deed expiry" /><x-text-input id="title_deed_expiry_date" name="title_deed_expiry_date" type="date" class="mt-1 block w-full" :value="old('title_deed_expiry_date', isset($unit) && $unit->title_deed_expiry_date ? $unit->title_deed_expiry_date->format('Y-m-d') : '')" /></div>
                <div data-unit-document-ocr data-document-type="title_deed">
                    <label for="title_deed" class="flex cursor-pointer flex-col items-center justify-center rounded-2xl border-2 border-dashed border-blue-100 bg-blue-50/40 px-4 py-5 text-center hover:border-blue-300">
                        <span class="grid h-11 w-11 place-items-center rounded-2xl bg-white text-blue-600 shadow-sm">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 16V4m0 0-4 4m4-4 4 4"/><path d="M20 16v4H4v-4"/></svg>
                        </span>
                        <span class="mt-3 text-sm font-bold text-[#071a3b]">Upload title deed</span>
                        <span class="mt-1 text-xs text-slate-500">PDF, JPG, PNG, or WEBP up to 10 MB</span>
                    </label>
                    <input id="title_deed" name="title_deed" type="file" accept=".pdf,.jpg,.jpeg,.png,.webp" class="sr-only" data-upload-input>
                    <div class="mt-3 rounded-2xl bg-blue-50 px-3 py-2 text-xs font-bold text-blue-700" data-selected-file>{{ $unit->title_deed_original_name ?? 'No title deed file selected' }}</div>
                    <div class="mt-3 hidden overflow-hidden rounded-full bg-slate-100" data-upload-progress><div class="h-2 w-0 rounded-full bg-blue-600 transition-all duration-500" data-upload-progress-bar></div></div>
                    <button type="button" class="mt-3 w-full rounded-xl border border-blue-200 bg-white px-4 py-2.5 text-xs font-black text-blue-700 hover:bg-blue-50" data-unit-document-scan>Scan & fill title deed fields</button>
                    <div class="mt-3 hidden rounded-xl px-3 py-2 text-xs font-bold" data-unit-document-status></div>
                </div>
                <hr>
                <div><x-input-label for="dtcm_permit_no" value="DTCM unit permit no" /><x-text-input id="dtcm_permit_no" name="dtcm_permit_no" class="mt-1 block w-full" :value="old('dtcm_permit_no', $unit->dtcm_permit_no ?? '')" /></div>
                <div><x-input-label for="dtcm_permit_expiry_date" value="DTCM expiry" /><x-text-input id="dtcm_permit_expiry_date" name="dtcm_permit_expiry_date" type="date" class="mt-1 block w-full" :value="old('dtcm_permit_expiry_date', isset($unit) && $unit->dtcm_permit_expiry_date ? $unit->dtcm_permit_expiry_date->format('Y-m-d') : '')" /></div>
                <div data-unit-document-ocr data-document-type="dtcm_permit">
                    <label for="dtcm_permit" class="flex cursor-pointer flex-col items-center justify-center rounded-2xl border-2 border-dashed border-blue-100 bg-blue-50/40 px-4 py-5 text-center hover:border-blue-300">
                        <span class="grid h-11 w-11 place-items-center rounded-2xl bg-white text-blue-600 shadow-sm">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 16V4m0 0-4 4m4-4 4 4"/><path d="M20 16v4H4v-4"/></svg>
                        </span>
                        <span class="mt-3 text-sm font-bold text-[#071a3b]">Upload DTCM permit</span>
                        <span class="mt-1 text-xs text-slate-500">Used later for guest check-in details</span>
                    </label>
                    <input id="dtcm_permit" name="dtcm_permit" type="file" accept=".pdf,.jpg,.jpeg,.png,.webp" class="sr-only" data-upload-input>
                    <div class="mt-3 rounded-2xl bg-blue-50 px-3 py-2 text-xs font-bold text-blue-700" data-selected-file>{{ $unit->dtcm_permit_original_name ?? 'No DTCM permit file selected' }}</div>
                    <div class="mt-3 hidden overflow-hidden rounded-full bg-slate-100" data-upload-progress><div class="h-2 w-0 rounded-full bg-blue-600 transition-all duration-500" data-upload-progress-bar></div></div>
                    <button type="button" class="mt-3 w-full rounded-xl border border-blue-200 bg-white px-4 py-2.5 text-xs font-black text-blue-700 hover:bg-blue-50" data-unit-document-scan>Scan & fill DTCM fields</button>
                    <div class="mt-3 hidden rounded-xl px-3 py-2 text-xs font-bold" data-unit-document-status></div>
                </div>
            </div>
        </div>
        <div class="erp-card p-5">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h2 class="text-lg font-bold text-[#071a3b]">Smart lock</h2>
                    <p class="mt-1 text-sm text-slate-500">Select the installed TT Lock from TT Lock Settings. One apartment can have one attached lock.</p>
                </div>
                <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-bold text-blue-700">1 lock / unit</span>
            </div>
            <select name="tt_lock_id" id="tt_lock_id" class="erp-focus mt-4 h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm">
                <option value="">No TT Lock attached</option>
                @foreach($ttLocks as $ttLock)
                    <option value="{{ $ttLock->id }}" @selected((string) $selectedTtLockId === (string) $ttLock->id)>
                        {{ $ttLock->lock_name }} / {{ $ttLock->lock_id }}
                        @if($ttLock->battery_level !== null) - Battery {{ $ttLock->battery_level }}% @endif
                        @if($ttLock->unit && (string) $selectedTtLockId !== (string) $ttLock->id) - attached to {{ $ttLock->unit->building?->name }} {{ $ttLock->unit->unit_no }} @endif
                    </option>
                @endforeach
            </select>
            <p class="mt-3 rounded-2xl bg-blue-50 px-4 py-3 text-xs font-bold text-blue-700">Add locks, battery, gateway, and API settings from Administration > TT Lock settings.</p>
        </div>
        <div class="erp-card p-5"><h2 class="text-lg font-bold text-[#071a3b]">Pictures</h2><label class="mt-4 flex cursor-pointer flex-col items-center justify-center rounded-2xl border-2 border-dashed border-blue-100 bg-blue-50/40 px-4 py-8 text-center hover:border-blue-300"><span class="text-sm font-bold text-[#071a3b]">Drop or browse unit pictures</span><span class="mt-1 text-xs text-slate-500">JPG or PNG up to 5 MB each</span><input name="pictures_upload[]" type="file" multiple accept="image/*" class="sr-only"></label><p class="mt-2 text-xs text-slate-500">{{ count($unit->pictures ?? []) }} pictures uploaded.</p></div>
        <div class="erp-card p-5"><h2 class="text-lg font-bold text-[#071a3b]">Notes</h2><textarea name="notes" rows="5" class="erp-focus mt-4 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">{{ old('notes', $unit->notes ?? '') }}</textarea></div>
    </div>
</div>

<div class="mt-6 flex justify-end gap-3"><a href="{{ route('units.index') }}" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-bold text-slate-600">Cancel</a><x-primary-button>{{ $submitLabel }}</x-primary-button></div>

<template id="owner-row-template">
    <div class="grid gap-3 rounded-2xl border border-slate-200 bg-white p-3 md:grid-cols-[1.5fr_150px_1fr_44px] md:items-end" data-owner-row>
        <div>
            <label class="block text-sm font-medium text-gray-700">Owner</label>
            <select data-name="owner_id" class="erp-focus mt-1 h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm">
                <option value="">Select owner</option>
                @foreach ($owners as $owner)
                    <option value="{{ $owner->id }}">{{ $owner->full_name }}{{ $owner->identity_no ? ' - '.$owner->identity_no : '' }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">Share %</label>
            <div class="relative mt-1">
                <input data-name="share_percent" value="100" type="number" min="0" max="100" step="0.01" class="erp-focus h-11 w-full rounded-xl border border-slate-200 px-3 pr-8 text-sm" data-owner-share placeholder="100">
                <span class="absolute right-3 top-3 text-xs font-bold text-slate-400">%</span>
            </div>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">Payout account</label>
            <div class="mt-1 flex h-11 items-center rounded-xl border border-slate-200 bg-slate-50 px-3 text-sm font-medium text-slate-600">Use owner bank details</div>
        </div>
        <button type="button" class="flex h-11 items-center justify-center rounded-xl bg-rose-50 text-rose-500 hover:bg-rose-100" data-remove-owner-row aria-label="Remove owner">×</button>
    </div>
</template>

<template id="utility-row-template">
    <div class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm shadow-slate-200/40" data-utility-row>
        <input type="hidden" data-utility-name="id" value="">
        <div class="mb-3 flex items-center justify-between gap-3">
            <p class="text-xs font-black uppercase tracking-[0.16em] text-slate-400">Utility service</p>
            <button type="button" class="rounded-xl bg-rose-50 px-3 py-2 text-xs font-bold text-rose-600 hover:bg-rose-100" data-remove-utility-row>Remove</button>
        </div>
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Service type</label>
                <select data-utility-name="provider_type" class="erp-focus mt-1 h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm">
                    <option value="">Select type</option>
                    @foreach ($utilityProviderTypes as $type)
                        <option value="{{ $type }}">{{ str($type)->headline() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Provider name</label>
                <input data-utility-name="provider_name" class="erp-focus mt-1 h-11 w-full rounded-xl border border-slate-200 px-3 text-sm" placeholder="Provider name e.g. DEWA">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Account no</label>
                <input data-utility-name="account_no" class="erp-focus mt-1 h-11 w-full rounded-xl border border-slate-200 px-3 text-sm" placeholder="Account no">
            </div>
            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-3 py-3">
                <input type="hidden" data-utility-name="paid_by_company_hidden" value="0">
                <label class="flex items-center gap-2 text-sm font-bold text-[#071a3b]">
                    <input type="checkbox" data-utility-name="paid_by_company" value="1" class="rounded border-slate-300 text-blue-600">
                    Paid by Pattern
                </label>
                <p class="mt-1 text-xs text-slate-500">Used for accounting and owner statements.</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Billing day</label>
                <input data-utility-name="billing_day" type="number" min="1" max="31" class="erp-focus mt-1 h-11 w-full rounded-xl border border-slate-200 px-3 text-sm" placeholder="Day 1-31">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Next due date</label>
                <input data-utility-name="next_due_date" type="date" class="erp-focus mt-1 h-11 w-full rounded-xl border border-slate-200 px-3 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Estimated amount</label>
                <input data-utility-name="estimated_amount" class="erp-focus mt-1 h-11 w-full rounded-xl border border-slate-200 px-3 text-sm" placeholder="AED">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Login username</label>
                <input data-utility-name="username" class="erp-focus mt-1 h-11 w-full rounded-xl border border-slate-200 px-3 text-sm" placeholder="Optional">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Login password</label>
                <input data-utility-name="password" class="erp-focus mt-1 h-11 w-full rounded-xl border border-slate-200 px-3 text-sm" placeholder="Optional">
            </div>
            <div class="md:col-span-2 xl:col-span-3">
                <label class="block text-sm font-medium text-gray-700">Notes</label>
                <input data-utility-name="notes" class="erp-focus mt-1 h-11 w-full rounded-xl border border-slate-200 px-3 text-sm" placeholder="Responsibility, meter location, service remarks">
            </div>
        </div>
    </div>
</template>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const rows = document.querySelector('[data-owner-rows]');
        const template = document.getElementById('owner-row-template');
        const addButton = document.querySelector('[data-add-owner-row]');
        const total = document.querySelector('[data-owner-total]');
        const status = document.querySelector('[data-owner-total-status]');
        const message = document.querySelector('[data-owner-message]');

        const renameRows = () => {
            rows.querySelectorAll('[data-owner-row]').forEach((row, index) => {
                row.querySelectorAll('[data-name]').forEach((input) => {
                    input.name = `ownership_rows[${index}][${input.dataset.name}]`;
                });
            });
        };

        const refreshTotal = () => {
            const sum = Array.from(rows.querySelectorAll('[data-owner-share]')).reduce((carry, input) => carry + (parseFloat(input.value) || 0), 0);
            total.textContent = sum.toFixed(2);
            const valid = Math.abs(sum - 100) < 0.01;
            status.textContent = valid ? 'Valid allocation' : 'Must total 100%';
            status.classList.toggle('text-emerald-600', valid);
            status.classList.toggle('text-rose-600', !valid);
            message.classList.toggle('bg-emerald-50', valid);
            message.classList.toggle('text-emerald-700', valid);
            message.classList.toggle('bg-rose-50', !valid);
            message.classList.toggle('text-rose-700', !valid);
        };

        rows.addEventListener('input', refreshTotal);
        rows.addEventListener('click', (event) => {
            if (! event.target.matches('[data-remove-owner-row]')) {
                return;
            }

            if (rows.querySelectorAll('[data-owner-row]').length > 1) {
                event.target.closest('[data-owner-row]').remove();
                renameRows();
                refreshTotal();
            }
        });

        addButton?.addEventListener('click', () => {
            rows.appendChild(template.content.firstElementChild.cloneNode(true));
            renameRows();
            refreshTotal();
        });

        refreshTotal();

        const utilityRows = document.querySelector('[data-utility-rows]');
        const utilityTemplate = document.getElementById('utility-row-template');
        const utilityEmpty = document.querySelector('[data-utility-empty]');

        const renameUtilityRows = () => {
            utilityRows?.querySelectorAll('[data-utility-row]').forEach((row, index) => {
                row.querySelectorAll('[data-utility-name]').forEach((input) => {
                    const key = input.dataset.utilityName;
                    input.name = key === 'paid_by_company_hidden'
                        ? `utility_accounts[${index}][paid_by_company]`
                        : `utility_accounts[${index}][${key}]`;
                });
            });

            utilityEmpty?.classList.toggle('hidden', Boolean(utilityRows?.querySelector('[data-utility-row]')));
        };

        document.querySelectorAll('[data-add-utility-row]').forEach((button) => {
            button.addEventListener('click', () => {
                const row = utilityTemplate.content.firstElementChild.cloneNode(true);
                const type = button.dataset.providerType || '';
                const name = button.dataset.providerName || '';
                row.querySelector('[data-utility-name="provider_type"]').value = type;
                row.querySelector('[data-utility-name="provider_name"]').value = name;
                utilityRows.appendChild(row);
                renameUtilityRows();
            });
        });

        utilityRows?.addEventListener('click', (event) => {
            if (! event.target.matches('[data-remove-utility-row]')) {
                return;
            }

            event.target.closest('[data-utility-row]').remove();
            renameUtilityRows();
        });

        renameUtilityRows();

        document.querySelectorAll('[data-unit-document-ocr]').forEach((root) => {
            const fileInput = root.querySelector('[data-upload-input]');
            const selectedFile = root.querySelector('[data-selected-file]');
            const progress = root.querySelector('[data-upload-progress]');
            const progressBar = root.querySelector('[data-upload-progress-bar]');
            const scanButton = root.querySelector('[data-unit-document-scan]');
            const status = root.querySelector('[data-unit-document-status]');
            const form = root.closest('form');

            const setProgress = (percent) => {
                progress?.classList.remove('hidden');
                if (progressBar) progressBar.style.width = `${percent}%`;
            };

            const setStatus = (message, tone = 'slate') => {
                if (!status) return;
                status.textContent = message;
                status.className = 'mt-3 rounded-xl px-3 py-2 text-xs font-bold ' + {
                    slate: 'bg-slate-50 text-slate-500',
                    blue: 'bg-blue-50 text-blue-700',
                    emerald: 'bg-emerald-50 text-emerald-700',
                    amber: 'bg-amber-50 text-amber-700',
                    rose: 'bg-rose-50 text-rose-700',
                }[tone];
                status.classList.remove('hidden');
            };

            const fillField = (name, value) => {
                if (!value || !form) return;
                const input = form.querySelector(`[name="${name}"]`);
                if (!input) return;
                input.value = value;
                input.dispatchEvent(new Event('input', { bubbles: true }));
                input.dispatchEvent(new Event('change', { bubbles: true }));
            };

            fileInput?.addEventListener('change', () => {
                if (!fileInput.files.length) return;
                selectedFile.textContent = fileInput.files[0].name;
                setProgress(100);
                setStatus('File ready. Scan now to extract document fields, or save the unit.', 'blue');
            });

            scanButton?.addEventListener('click', async () => {
                if (!fileInput?.files.length) {
                    setStatus('Choose a document file first.', 'amber');
                    return;
                }

                const formData = new FormData();
                formData.append('document', fileInput.files[0]);
                formData.append('document_type', root.dataset.documentType);
                scanButton.disabled = true;
                scanButton.textContent = 'Scanning...';
                setProgress(35);
                setStatus('Uploading document to OCR...', 'blue');

                try {
                    const response = await fetch('{{ route('unit-documents.ocr') }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json',
                        },
                        body: formData,
                    });

                    setProgress(75);
                    const data = await response.json().catch(() => ({ message: 'OCR scan failed. Please fill manually.' }));

                    if (!response.ok) {
                        setStatus(data.message || 'OCR scan failed. Please fill manually.', 'rose');
                        return;
                    }

                    Object.entries(data.fields || {}).forEach(([field, value]) => fillField(field, value));
                    setProgress(100);
                    setStatus(data.message || 'Document scanned. Please review before saving.', data.ok ? 'emerald' : 'amber');
                } catch (error) {
                    setStatus('OCR scan failed. Please check AWS Textract settings or fill manually.', 'rose');
                } finally {
                    scanButton.disabled = false;
                    scanButton.textContent = root.dataset.documentType === 'title_deed' ? 'Scan & fill title deed fields' : 'Scan & fill DTCM fields';
                }
            });
        });
    });
</script>
