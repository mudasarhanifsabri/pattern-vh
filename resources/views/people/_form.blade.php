@csrf

<div class="grid gap-5 xl:grid-cols-[1fr_360px]">
    <div class="space-y-5">
        <div class="erp-card p-5">
            <h2 class="text-lg font-bold text-[#071a3b]">{{ $config['singularTitle'] }} details</h2>
            <p class="mt-1 text-sm text-slate-500">Primary identity and contact information.</p>

            <div class="mt-5 grid gap-4 md:grid-cols-2">
                <div class="md:col-span-2">
                    <x-input-label for="full_name" value="Full name" />
                    <x-text-input id="full_name" name="full_name" class="mt-1 block w-full" :value="old('full_name', $record->full_name ?? '')" required autofocus />
                    <x-input-error :messages="$errors->get('full_name')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="mobile_no" value="Mobile no" />
                    <x-text-input id="mobile_no" name="mobile_no" class="mt-1 block w-full" :value="old('mobile_no', $record->mobile_no ?? '')" required />
                    <x-input-error :messages="$errors->get('mobile_no')" class="mt-2" />
                    <label class="mt-3 flex items-center gap-2 text-sm font-medium text-slate-600">
                        <input type="checkbox" name="mobile_has_whatsapp" value="1" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500" @checked(old('mobile_has_whatsapp', $record->mobile_has_whatsapp ?? true))>
                        This number has WhatsApp
                    </label>
                </div>
                <div>
                    <x-input-label for="email" value="Email" />
                    <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $record->email ?? '')" />
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="date_of_birth" value="Date of birth" />
                    <x-text-input id="date_of_birth" name="date_of_birth" type="date" class="mt-1 block w-full" :value="old('date_of_birth', isset($record) && $record->date_of_birth ? $record->date_of_birth->format('Y-m-d') : '')" />
                </div>
            </div>
        </div>

        <div class="erp-card p-5">
            <h2 class="text-lg font-bold text-[#071a3b]">Passport / Emirates ID</h2>
            <div class="mt-5 grid gap-4 md:grid-cols-2">
                <div>
                    <x-input-label for="identity_type" value="Document type" />
                    <select id="identity_type" name="identity_type" class="erp-focus mt-1 block h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-700">
                        <option value="emirates_id" @selected(old('identity_type', $record->identity_type ?? 'emirates_id') === 'emirates_id')>Emirates ID</option>
                        <option value="passport" @selected(old('identity_type', $record->identity_type ?? '') === 'passport')>Passport</option>
                    </select>
                </div>
                <div>
                    <x-input-label for="identity_no" value="Passport / Emirates ID no" />
                    <x-text-input id="identity_no" name="identity_no" class="mt-1 block w-full" :value="old('identity_no', $record->identity_no ?? '')" />
                </div>
                <div>
                    <x-input-label for="identity_expiry_date" value="Expiry date" />
                    <x-text-input id="identity_expiry_date" name="identity_expiry_date" type="date" class="mt-1 block w-full" :value="old('identity_expiry_date', isset($record) && $record->identity_expiry_date ? $record->identity_expiry_date->format('Y-m-d') : '')" />
                </div>
                <div x-data="{ fileName: '' }" data-identity-ocr>
                    <x-input-label for="document" value="Upload document" />
                    <label for="document" class="mt-1 flex cursor-pointer flex-col items-center justify-center rounded-2xl border-2 border-dashed border-blue-100 bg-blue-50/40 px-4 py-6 text-center transition hover:border-blue-300 hover:bg-blue-50">
                        <span class="text-sm font-bold text-[#071a3b]">Choose passport or Emirates ID file</span>
                        <span class="mt-1 text-xs text-slate-500">PDF, JPG, PNG, or WEBP up to 5 MB</span>
                        <span class="mt-3 rounded-full bg-white px-3 py-1 text-xs font-bold text-blue-700">Browse file</span>
                    </label>
                    <input id="document" name="document" type="file" accept=".pdf,.jpg,.jpeg,.png,.webp" class="sr-only" @change="fileName = $event.target.files[0]?.name || ''">
                    <template x-if="fileName"><p class="mt-3 break-all rounded-xl bg-blue-50 px-3 py-2 text-sm font-bold text-blue-900" x-text="fileName"></p></template>
                    <button type="button" data-ocr-scan class="mt-3 w-full rounded-xl border border-blue-200 bg-white px-4 py-2.5 text-xs font-black text-blue-700 hover:bg-blue-50">Scan & fill form</button>
                    <div data-ocr-status class="hidden"></div>
                    <x-input-error :messages="$errors->get('document')" class="mt-2" />
                    @if (! empty($record?->document_original_name))
                        <p class="mt-3 break-all rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700">{{ $record->document_original_name }}</p>
                    @endif
                </div>
            </div>
        </div>

        @if ($config['extra'] === 'agent')
            <div class="erp-card p-5">
                <h2 class="text-lg font-bold text-[#071a3b]">Agent commission</h2>
                <div class="mt-5 grid gap-4 md:grid-cols-3">
                    <div><x-input-label for="agency_name" value="Agency name" /><x-text-input id="agency_name" name="agency_name" class="mt-1 block w-full" :value="old('agency_name', $record->agency_name ?? '')" /></div>
                    <div><x-input-label for="rera_no" value="RERA no" /><x-text-input id="rera_no" name="rera_no" class="mt-1 block w-full" :value="old('rera_no', $record->rera_no ?? '')" /></div>
                    <div><x-input-label for="commission_percent" value="Commission %" /><x-text-input id="commission_percent" name="commission_percent" class="mt-1 block w-full" :value="old('commission_percent', $record->commission_percent ?? '')" /></div>
                </div>
            </div>
        @elseif ($config['extra'] === 'operations')
            <div class="erp-card p-5">
                <h2 class="text-lg font-bold text-[#071a3b]">Task management setup</h2>
                <p class="mt-1 text-sm text-slate-500">Ready for future booking checkout and in-stay task automation.</p>
                <div class="mt-5 grid gap-4 md:grid-cols-2">
                    <div><x-input-label for="team_role" value="Team role" /><select id="team_role" name="team_role" class="erp-focus mt-1 h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm">@foreach (['operations' => 'Operations', 'cleaner' => 'Cleaner', 'technician' => 'Technician'] as $value => $label)<option value="{{ $value }}" @selected(old('team_role', $record->team_role ?? 'operations') === $value)>{{ $label }}</option>@endforeach</select></div>
                    <div><x-input-label for="availability_status" value="Availability" /><x-text-input id="availability_status" name="availability_status" class="mt-1 block w-full" :value="old('availability_status', $record->availability_status ?? 'available')" /></div>
                    <div><x-input-label for="specialty" value="Specialty" /><x-text-input id="specialty" name="specialty" class="mt-1 block w-full" placeholder="Deep cleaning, AC, plumbing..." :value="old('specialty', $record->specialty ?? '')" /></div>
                    <div><x-input-label for="service_area" value="Service area" /><x-text-input id="service_area" name="service_area" class="mt-1 block w-full" placeholder="Dubai Marina, JVC..." :value="old('service_area', $record->service_area ?? '')" /></div>
                </div>
                <div class="mt-5 space-y-3">
                    @foreach ([
                        'auto_assign_checkout_cleaning' => 'Auto assign checkout cleaning',
                        'auto_assign_checkout_inspection' => 'Auto assign checkout inspection',
                        'auto_assign_stay_tasks' => 'Auto assign tenant stay tasks',
                    ] as $field => $label)
                        <label class="flex items-center justify-between rounded-2xl border border-blue-100 bg-blue-50/50 px-4 py-3 text-sm font-bold text-[#071a3b]">
                            <span>{{ $label }}</span>
                            <input type="checkbox" name="{{ $field }}" value="1" class="rounded border-slate-300 text-blue-600" @checked(old($field, $record->{$field} ?? false))>
                        </label>
                    @endforeach
                </div>
            </div>
        @else
            <div class="erp-card p-5">
                <h2 class="text-lg font-bold text-[#071a3b]">Tenant stay details</h2>
                <div class="mt-5 grid gap-4 md:grid-cols-3">
                    <div><x-input-label for="nationality" value="Nationality" /><x-text-input id="nationality" name="nationality" class="mt-1 block w-full" :value="old('nationality', $record->nationality ?? '')" /></div>
                    <div><x-input-label for="emergency_contact_name" value="Emergency contact" /><x-text-input id="emergency_contact_name" name="emergency_contact_name" class="mt-1 block w-full" :value="old('emergency_contact_name', $record->emergency_contact_name ?? '')" /></div>
                    <div><x-input-label for="emergency_contact_mobile" value="Emergency mobile" /><x-text-input id="emergency_contact_mobile" name="emergency_contact_mobile" class="mt-1 block w-full" :value="old('emergency_contact_mobile', $record->emergency_contact_mobile ?? '')" /></div>
                </div>
            </div>
        @endif

        <div class="erp-card p-5">
            <h2 class="text-lg font-bold text-[#071a3b]">Bank details</h2>
            <div class="mt-5 grid gap-4 md:grid-cols-2">
                @foreach (['bank_name' => 'Bank name', 'bank_account_name' => 'Account name', 'bank_account_no' => 'Account no', 'iban' => 'IBAN', 'swift_code' => 'SWIFT code'] as $field => $label)
                    <div><x-input-label :for="$field" :value="$label" /><x-text-input :id="$field" :name="$field" class="mt-1 block w-full" :value="old($field, $record->{$field} ?? '')" /></div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="space-y-5">
        <div class="erp-card p-5">
            <h2 class="text-lg font-bold text-[#071a3b]">Portal access</h2>
            <label class="mt-4 flex cursor-pointer items-center justify-between rounded-2xl border border-blue-100 bg-blue-50/60 px-4 py-3">
                <span><span class="block text-sm font-bold text-[#071a3b]">Send welcome email now</span><span class="block text-xs text-slate-500">You can also send it later from the profile.</span></span>
                <input type="checkbox" name="send_portal_invite" value="1" class="rounded border-slate-300 text-blue-600" @checked(old('send_portal_invite', false))>
            </label>
        </div>

        <div class="erp-card p-5">
            <h2 class="text-lg font-bold text-[#071a3b]">Status</h2>
            <label class="mt-4 flex items-center justify-between rounded-2xl border border-slate-200 px-4 py-3">
                <span><span class="block text-sm font-bold text-[#071a3b]">Blacklist record</span><span class="block text-xs text-slate-500">Flag risky or blocked people.</span></span>
                <input type="checkbox" name="is_blacklisted" value="1" class="rounded border-slate-300 text-rose-600" @checked(old('is_blacklisted', $record->is_blacklisted ?? false))>
            </label>
            <div class="mt-4"><x-input-label for="blacklist_reason" value="Blacklist reason" /><textarea id="blacklist_reason" name="blacklist_reason" rows="4" class="erp-focus mt-1 block w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">{{ old('blacklist_reason', $record->blacklist_reason ?? '') }}</textarea></div>
        </div>

        <div class="erp-card p-5">
            <h2 class="text-lg font-bold text-[#071a3b]">Notes history</h2>
            <textarea name="note" rows="6" class="erp-focus mt-4 block w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" placeholder="Add an internal note while saving...">{{ old('note') }}</textarea>
        </div>
    </div>
</div>

<div class="mt-6 flex items-center justify-end gap-3">
    <a href="{{ route($config['route'].'.index') }}" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-bold text-slate-600 hover:bg-slate-50">Cancel</a>
    <x-primary-button>{{ $submitLabel }}</x-primary-button>
</div>

<x-identity-ocr-script />
