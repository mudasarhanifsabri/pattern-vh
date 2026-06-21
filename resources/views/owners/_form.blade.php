@csrf

<div class="grid gap-5 xl:grid-cols-[1fr_360px]">
    <div class="space-y-5">
        <div class="erp-card p-5">
            <h2 class="text-lg font-bold text-[#071a3b]">Owner details</h2>
            <p class="mt-1 text-sm text-slate-500">Primary identity and contact information.</p>

            <div class="mt-5 grid gap-4 md:grid-cols-2">
                <div class="md:col-span-2">
                    <x-input-label for="full_name" value="Full name" />
                    <x-text-input id="full_name" name="full_name" type="text" class="mt-1 block w-full" :value="old('full_name', $owner->full_name ?? '')" required autofocus />
                    <x-input-error :messages="$errors->get('full_name')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="mobile_no" value="Mobile no" />
                    <x-text-input id="mobile_no" name="mobile_no" type="text" class="mt-1 block w-full" :value="old('mobile_no', $owner->mobile_no ?? '')" required />
                    <x-input-error :messages="$errors->get('mobile_no')" class="mt-2" />
                    <label class="mt-3 flex items-center gap-2 text-sm font-medium text-slate-600">
                        <input type="checkbox" name="mobile_has_whatsapp" value="1" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500" @checked(old('mobile_has_whatsapp', $owner->mobile_has_whatsapp ?? true))>
                        This number has WhatsApp
                    </label>
                </div>

                <div>
                    <x-input-label for="email" value="Email" />
                    <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $owner->email ?? '')" />
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="date_of_birth" value="Date of birth" />
                    <x-text-input id="date_of_birth" name="date_of_birth" type="date" class="mt-1 block w-full" :value="old('date_of_birth', isset($owner) && $owner->date_of_birth ? $owner->date_of_birth->format('Y-m-d') : '')" />
                    <x-input-error :messages="$errors->get('date_of_birth')" class="mt-2" />
                </div>
            </div>
        </div>

        <div class="erp-card p-5">
            <h2 class="text-lg font-bold text-[#071a3b]">Passport / Emirates ID</h2>
            <p class="mt-1 text-sm text-slate-500">Upload scans or PDFs. Files use the default storage disk.</p>

            <div class="mt-5 grid gap-4 md:grid-cols-2">
                <div>
                    <x-input-label for="identity_type" value="Document type" />
                    <select id="identity_type" name="identity_type" class="erp-focus mt-1 block h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-700">
                        <option value="emirates_id" @selected(old('identity_type', $owner->identity_type ?? 'emirates_id') === 'emirates_id')>Emirates ID</option>
                        <option value="passport" @selected(old('identity_type', $owner->identity_type ?? '') === 'passport')>Passport</option>
                    </select>
                    <x-input-error :messages="$errors->get('identity_type')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="identity_no" value="Passport / Emirates ID no" />
                    <x-text-input id="identity_no" name="identity_no" type="text" class="mt-1 block w-full" :value="old('identity_no', $owner->identity_no ?? '')" />
                    <x-input-error :messages="$errors->get('identity_no')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="identity_expiry_date" value="Expiry date" />
                    <x-text-input id="identity_expiry_date" name="identity_expiry_date" type="date" class="mt-1 block w-full" :value="old('identity_expiry_date', isset($owner) && $owner->identity_expiry_date ? $owner->identity_expiry_date->format('Y-m-d') : '')" />
                    <x-input-error :messages="$errors->get('identity_expiry_date')" class="mt-2" />
                </div>

                <div x-data="{ fileName: '' }">
                    <x-input-label for="document" value="Upload document" />
                    <label for="document" class="mt-1 flex cursor-pointer flex-col items-center justify-center rounded-2xl border-2 border-dashed border-blue-100 bg-blue-50/40 px-4 py-6 text-center transition hover:border-blue-300 hover:bg-blue-50">
                        <span class="grid h-12 w-12 place-items-center rounded-2xl bg-white text-blue-600 shadow-sm">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 16V4"/><path d="m7 9 5-5 5 5"/><path d="M20 16.5V19a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-2.5"/></svg>
                        </span>
                        <span class="mt-3 text-sm font-bold text-[#071a3b]">Choose passport or Emirates ID file</span>
                        <span class="mt-1 text-xs text-slate-500">PDF, JPG, PNG, or WEBP up to 5 MB</span>
                        <span class="mt-3 rounded-full bg-white px-3 py-1 text-xs font-bold text-blue-700">Browse file</span>
                    </label>
                    <input id="document" name="document" type="file" accept=".pdf,.jpg,.jpeg,.png,.webp" class="sr-only" @change="fileName = $event.target.files[0]?.name || ''">
                    <template x-if="fileName">
                        <div class="mt-3 rounded-xl border border-blue-100 bg-blue-50 px-3 py-2">
                            <p class="text-xs font-bold uppercase tracking-[0.14em] text-blue-500">Selected file</p>
                            <p class="mt-1 break-all text-sm font-medium text-blue-900" x-text="fileName"></p>
                        </div>
                    </template>
                    <x-input-error :messages="$errors->get('document')" class="mt-2" />
                    @if (! empty($owner?->document_original_name))
                        <div class="mt-3 rounded-xl border border-slate-200 bg-white px-3 py-2">
                            <p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-400">Current file</p>
                            <p class="mt-1 break-all text-sm font-medium text-slate-700">{{ $owner->document_original_name }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="erp-card p-5">
            <h2 class="text-lg font-bold text-[#071a3b]">Bank details</h2>
            <p class="mt-1 text-sm text-slate-500">Saved for future owner payouts and accounting modules.</p>

            <div class="mt-5 grid gap-4 md:grid-cols-2">
                <div>
                    <x-input-label for="bank_name" value="Bank name" />
                    <x-text-input id="bank_name" name="bank_name" type="text" class="mt-1 block w-full" :value="old('bank_name', $owner->bank_name ?? '')" />
                </div>
                <div>
                    <x-input-label for="bank_account_name" value="Account name" />
                    <x-text-input id="bank_account_name" name="bank_account_name" type="text" class="mt-1 block w-full" :value="old('bank_account_name', $owner->bank_account_name ?? '')" />
                </div>
                <div>
                    <x-input-label for="bank_account_no" value="Account no" />
                    <x-text-input id="bank_account_no" name="bank_account_no" type="text" class="mt-1 block w-full" :value="old('bank_account_no', $owner->bank_account_no ?? '')" />
                </div>
                <div>
                    <x-input-label for="iban" value="IBAN" />
                    <x-text-input id="iban" name="iban" type="text" class="mt-1 block w-full" :value="old('iban', $owner->iban ?? '')" />
                </div>
                <div>
                    <x-input-label for="swift_code" value="SWIFT code" />
                    <x-text-input id="swift_code" name="swift_code" type="text" class="mt-1 block w-full" :value="old('swift_code', $owner->swift_code ?? '')" />
                </div>
            </div>
        </div>
    </div>

    <div class="space-y-5">
        <div class="erp-card p-5">
            <h2 class="text-lg font-bold text-[#071a3b]">Owner portal access</h2>
            <p class="mt-1 text-sm text-slate-500">Create or update the owner login and send a welcome email with password setup.</p>

            <label class="mt-4 flex cursor-pointer items-center justify-between rounded-2xl border border-blue-100 bg-blue-50/60 px-4 py-3">
                <span>
                    <span class="block text-sm font-bold text-[#071a3b]">Send welcome email now</span>
                    <span class="block text-xs text-slate-500">Requires owner email. You can also send it later from the owner profile.</span>
                </span>
                <input type="checkbox" name="send_portal_invite" value="1" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500" @checked(old('send_portal_invite', false))>
            </label>
            <x-input-error :messages="$errors->get('send_portal_invite')" class="mt-2" />
        </div>

        <div class="erp-card p-5">
            <h2 class="text-lg font-bold text-[#071a3b]">Owner status</h2>
            <label class="mt-4 flex items-center justify-between rounded-2xl border border-slate-200 px-4 py-3">
                <span>
                    <span class="block text-sm font-bold text-[#071a3b]">Blacklist owner</span>
                    <span class="block text-xs text-slate-500">Flag risky or blocked owners.</span>
                </span>
                <input type="checkbox" name="is_blacklisted" value="1" class="rounded border-slate-300 text-rose-600 focus:ring-rose-500" @checked(old('is_blacklisted', $owner->is_blacklisted ?? false))>
            </label>
            <div class="mt-4">
                <x-input-label for="blacklist_reason" value="Blacklist reason" />
                <textarea id="blacklist_reason" name="blacklist_reason" rows="4" class="erp-focus mt-1 block w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">{{ old('blacklist_reason', $owner->blacklist_reason ?? '') }}</textarea>
                <x-input-error :messages="$errors->get('blacklist_reason')" class="mt-2" />
            </div>
        </div>

        <div class="erp-card p-5">
            <h2 class="text-lg font-bold text-[#071a3b]">Notes history</h2>
            <p class="mt-1 text-sm text-slate-500">Add an internal note while saving this owner.</p>
            <textarea name="note" rows="6" class="erp-focus mt-4 block w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" placeholder="Example: Documents verified, prefers WhatsApp updates...">{{ old('note') }}</textarea>
            <x-input-error :messages="$errors->get('note')" class="mt-2" />
        </div>
    </div>
</div>

<div class="mt-6 flex items-center justify-end gap-3">
    <a href="{{ route('owners.index') }}" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-bold text-slate-600 hover:bg-slate-50">Cancel</a>
    <x-primary-button>{{ $submitLabel }}</x-primary-button>
</div>
