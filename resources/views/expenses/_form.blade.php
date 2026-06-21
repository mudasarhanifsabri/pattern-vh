@php
    $expense = $expense ?? null;
    $selectedRole = old('expense_to_role', $expense->expense_to_role ?? 'company');
    $selectedExpenseToId = old('expense_to_id', $expense->expense_to_id ?? '');
    $selectedOwnerId = old('owner_id', $expense->owner_id ?? '');
    $selectedUnitId = old('unit_id', $expense->unit_id ?? '');
    $selectedAssociation = old('association', $expense->association ?? 'company');
@endphp
<div class="grid gap-5 xl:grid-cols-[1fr_360px]">
    <div class="erp-card p-5">
        <div class="flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
            <div>
                <h2 class="text-lg font-bold text-[#071a3b]">Expense details</h2>
                <p class="mt-1 text-sm text-slate-500">Select the target first. The form will reveal only the fields needed for that expense.</p>
            </div>
            <span id="expenseTargetBadge" class="inline-flex w-fit rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600">Company expense</span>
        </div>
        <div class="mt-5 grid gap-4 md:grid-cols-2">
            <div><x-input-label for="name" value="Expense name" /><x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name', $expense->name ?? '')" required /></div>
            <div><x-input-label for="type" value="Expense type" /><select id="type" name="type" class="erp-focus mt-1 h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm">@foreach($types as $type)<option value="{{ $type }}" @selected(old('type', $expense->type ?? '') === $type)>{{ str($type)->replace('_',' ')->headline() }}</option>@endforeach</select></div>
            <div class="md:col-span-2">
                <x-input-label for="expense_to_role" value="Expense to" />
                <select id="expense_to_role" name="expense_to_role" class="erp-focus mt-1 h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm">
                    @foreach($targetRoles as $role)<option value="{{ $role }}" @selected($selectedRole === $role)>{{ str($role)->replace('_',' ')->headline() }}</option>@endforeach
                </select>
                <p id="targetHelpText" class="mt-2 text-xs text-slate-500">Company expenses stay under Pattern internal accounting.</p>
            </div>

            <input id="expense_to_id" name="expense_to_id" type="hidden" value="{{ $selectedExpenseToId }}">

            <div data-target-panel="owner" class="hidden rounded-2xl border border-blue-100 bg-blue-50/40 p-4 md:col-span-2">
                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <x-input-label for="owner_id" value="Owner" />
                        <select id="owner_id" name="owner_id" class="erp-focus mt-1 h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm">
                            <option value="">Select owner</option>
                            @foreach($owners as $owner)<option value="{{ $owner->id }}" @selected($selectedOwnerId == $owner->id)>{{ $owner->full_name }}</option>@endforeach
                        </select>
                        <p class="mt-2 text-xs text-slate-500">After selecting owner, only that owner's apartments will appear.</p>
                    </div>
                    <div id="ownerUnitWrap" class="hidden">
                        <x-input-label for="unit_id" value="Apartment / unit" />
                        <select id="unit_id" name="unit_id" class="erp-focus mt-1 h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm">
                            <option value="">Select apartment</option>
                            @foreach($units as $unit)
                                <option value="{{ $unit->id }}" data-owner-ids="{{ $unit->owners->pluck('id')->implode(',') }}" @selected($selectedUnitId == $unit->id)>{{ $unit->building->name }} / {{ $unit->unit_no }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <div data-target-panel="tenant" class="hidden">
                <x-input-label for="tenant_target_id" value="Tenant" />
                <select id="tenant_target_id" data-person-select="tenant" class="erp-focus mt-1 h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm">
                    <option value="">Select tenant</option>
                    @foreach($tenants as $tenant)<option value="{{ $tenant->id }}" @selected($selectedRole === 'tenant' && $selectedExpenseToId == $tenant->id)>{{ $tenant->full_name }}{{ $tenant->mobile_no ? ' · '.$tenant->mobile_no : '' }}</option>@endforeach
                </select>
            </div>
            <div data-target-panel="agent" class="hidden">
                <x-input-label for="agent_target_id" value="Agent" />
                <select id="agent_target_id" data-person-select="agent" class="erp-focus mt-1 h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm">
                    <option value="">Select agent</option>
                    @foreach($agents as $agent)<option value="{{ $agent->id }}" @selected($selectedRole === 'agent' && $selectedExpenseToId == $agent->id)>{{ $agent->full_name }}{{ $agent->commission_percent ? ' · '.$agent->commission_percent.'%' : '' }}</option>@endforeach
                </select>
            </div>
            <div data-target-panel="operations_team" class="hidden">
                <x-input-label for="operations_team_target_id" value="Operations team member" />
                <select id="operations_team_target_id" data-person-select="operations_team" class="erp-focus mt-1 h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm">
                    <option value="">Select cleaner / technician</option>
                    @foreach($teamMembers as $member)<option value="{{ $member->id }}" @selected($selectedRole === 'operations_team' && $selectedExpenseToId == $member->id)>{{ $member->full_name }} · {{ str($member->team_role)->headline() }}</option>@endforeach
                </select>
            </div>

            <div><x-input-label for="association" value="Association" /><select id="association" name="association" class="erp-focus mt-1 h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm">@foreach($associations as $association)<option value="{{ $association }}" @selected($selectedAssociation === $association)>{{ str($association)->replace('_',' ')->headline() }}</option>@endforeach</select></div>
            <div><x-input-label for="incurred_on" value="Incurred on" /><x-text-input id="incurred_on" name="incurred_on" type="date" class="mt-1 block w-full" :value="old('incurred_on', isset($expense) ? $expense->incurred_on?->format('Y-m-d') : now()->toDateString())" required /></div>
            <div><x-input-label for="amount" value="Amount" /><x-text-input id="amount" name="amount" class="mt-1 block w-full" :value="old('amount', $expense->amount ?? '')" required /></div>
            <div>
                <x-input-label for="receipt" value="Upload receipt" />
                <label for="receipt" class="mt-1 flex min-h-11 cursor-pointer items-center justify-between gap-3 rounded-xl border border-dashed border-blue-200 bg-blue-50/50 px-4 py-3 text-sm text-slate-600 hover:bg-blue-50">
                    <span id="receiptFileName">Drop receipt or choose file</span>
                    <span class="rounded-lg bg-white px-3 py-1 text-xs font-bold text-blue-600 shadow-sm">Browse</span>
                </label>
                <input id="receipt" name="receipt" type="file" accept=".pdf,.jpg,.jpeg,.png,.webp" class="sr-only">
            </div>
            <div class="md:col-span-2"><x-input-label for="notes" value="Extra notes" /><textarea id="notes" name="notes" rows="5" class="erp-focus mt-1 block w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">{{ old('notes', $expense->notes ?? '') }}</textarea></div>
        </div>
    </div>
    <aside class="space-y-5">
        <div class="erp-card p-5"><h2 class="text-lg font-bold text-[#071a3b]">Target guide</h2><div class="mt-4 space-y-3 text-sm text-slate-600"><p class="rounded-2xl bg-blue-50 p-4">For owner expenses, select owner and apartment so it appears on owner statement.</p><p class="rounded-2xl bg-emerald-50 p-4">Company expenses stay under Pattern internal accounting.</p><p class="rounded-2xl bg-amber-50 p-4">Receipt uploads follow the ERP S3 folder structure.</p></div></div>
        <div class="flex justify-end gap-3"><a href="{{ route('expenses.index') }}" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-bold text-slate-600">Cancel</a><button class="rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-bold text-white">Save expense</button></div>
    </aside>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const roleSelect = document.getElementById('expense_to_role');
        const expenseToId = document.getElementById('expense_to_id');
        const ownerSelect = document.getElementById('owner_id');
        const unitSelect = document.getElementById('unit_id');
        const ownerUnitWrap = document.getElementById('ownerUnitWrap');
        const associationSelect = document.getElementById('association');
        const targetHelpText = document.getElementById('targetHelpText');
        const badge = document.getElementById('expenseTargetBadge');
        const receipt = document.getElementById('receipt');
        const receiptFileName = document.getElementById('receiptFileName');
        const panels = document.querySelectorAll('[data-target-panel]');
        const personSelects = document.querySelectorAll('[data-person-select]');

        const help = {
            company: 'Company expenses stay under Pattern internal accounting.',
            owner: 'Select owner first, then select one of the apartments attached to that owner.',
            tenant: 'Select the tenant who should carry or reimburse this expense.',
            agent: 'Select the agent if this expense is linked to commission or agent payout.',
            operations_team: 'Select cleaner or technician when the expense belongs to operations work.',
        };

        const labels = {
            company: 'Company expense',
            owner: 'Owner expense',
            tenant: 'Tenant expense',
            agent: 'Agent expense',
            operations_team: 'Operations expense',
        };

        function syncUnitOptions() {
            const ownerId = ownerSelect.value;
            let hasVisibleSelectedUnit = false;

            Array.from(unitSelect.options).forEach((option) => {
                if (!option.value) {
                    option.hidden = false;
                    return;
                }

                const ownerIds = (option.dataset.ownerIds || '').split(',').filter(Boolean);
                const visible = ownerId && ownerIds.includes(ownerId);
                option.hidden = !visible;
                option.disabled = !visible;

                if (visible && option.selected) {
                    hasVisibleSelectedUnit = true;
                }
            });

            ownerUnitWrap.classList.toggle('hidden', !ownerId);

            if (!hasVisibleSelectedUnit) {
                unitSelect.value = '';
            }
        }

        function syncTarget() {
            const role = roleSelect.value;

            panels.forEach((panel) => panel.classList.toggle('hidden', panel.dataset.targetPanel !== role));
            targetHelpText.textContent = help[role] || help.company;
            badge.textContent = labels[role] || labels.company;

            personSelects.forEach((select) => {
                if (select.dataset.personSelect !== role) {
                    select.value = '';
                }
            });

            if (role === 'owner') {
                expenseToId.value = ownerSelect.value || '';
                associationSelect.value = associationSelect.value === 'unit' ? 'unit' : 'owner_account';
                syncUnitOptions();
            } else if (role === 'company') {
                expenseToId.value = '';
                ownerSelect.value = '';
                unitSelect.value = '';
                associationSelect.value = 'company';
                syncUnitOptions();
            } else {
                const activePerson = document.querySelector(`[data-person-select="${role}"]`);
                expenseToId.value = activePerson ? activePerson.value : '';
                ownerSelect.value = '';
                unitSelect.value = '';
                if (associationSelect.value === 'company' || associationSelect.value === 'owner_account' || associationSelect.value === 'unit') {
                    associationSelect.value = role === 'operations_team' ? 'operations' : 'booking';
                }
                syncUnitOptions();
            }
        }

        roleSelect.addEventListener('change', syncTarget);
        ownerSelect.addEventListener('change', syncTarget);
        personSelects.forEach((select) => select.addEventListener('change', syncTarget));
        receipt?.addEventListener('change', () => {
            receiptFileName.textContent = receipt.files?.[0]?.name || 'Drop receipt or choose file';
        });

        syncTarget();
    });
</script>
