@csrf
@if ($errors->any())<div class="mb-5 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"><p class="font-bold">Please fix the highlighted fields.</p><ul class="mt-2 list-inside list-disc">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<div class="grid gap-5 xl:grid-cols-[1fr_360px]">
    <div class="space-y-5">
        <div class="erp-card p-5"><h2 class="text-lg font-bold text-[#071a3b]">Booking details</h2><div class="mt-5 grid gap-4 md:grid-cols-2">
            <div><x-input-label for="booking_type" value="Booking type" /><select id="booking_type" name="booking_type" class="erp-focus mt-1 h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm">@foreach ($types as $type)<option value="{{ $type }}" @selected(old('booking_type', $booking->booking_type ?? 'holiday_home') === $type)>{{ str($type)->replace('_', ' ')->headline() }}</option>@endforeach</select></div>
            <div><x-input-label for="booking_status" value="Status" /><select id="booking_status" name="booking_status" class="erp-focus mt-1 h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm">@foreach ($statuses as $status)<option value="{{ $status }}" @selected(old('booking_status', $booking->booking_status ?? 'draft') === $status)>{{ str($status)->headline() }}</option>@endforeach</select></div>
            <div><x-input-label for="unit_id" value="Unit" /><select id="unit_id" name="unit_id" class="erp-focus mt-1 h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm" required><option value="">Select unit</option>@foreach ($units as $unit)<option value="{{ $unit->id }}" @selected(old('unit_id', $booking->unit_id ?? '') == $unit->id)>{{ $unit->building->name }} / {{ $unit->unit_no }}</option>@endforeach</select></div>
            <div><x-input-label for="tenant_id" value="Tenant" /><select id="tenant_id" name="tenant_id" class="erp-focus mt-1 h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm" required><option value="">Select tenant</option>@foreach ($tenants as $tenant)<option value="{{ $tenant->id }}" @selected(old('tenant_id', $booking->tenant_id ?? '') == $tenant->id)>{{ $tenant->full_name }}</option>@endforeach</select></div>
            <div><x-input-label for="agent_id" value="Agent / source partner" /><select id="agent_id" name="agent_id" class="erp-focus mt-1 h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm"><option value="">Direct booking</option>@foreach ($agents as $agent)<option value="{{ $agent->id }}" @selected(old('agent_id', $booking->agent_id ?? '') == $agent->id)>{{ $agent->full_name }} - {{ $agent->commission_percent ?? 0 }}%</option>@endforeach</select></div>
            <div><x-input-label for="source" value="Booking source" /><x-text-input id="source" name="source" class="mt-1 block w-full" placeholder="Airbnb, Booking.com, direct..." :value="old('source', $booking->source ?? '')" /></div>
        </div></div>
        <div class="erp-card p-5"><h2 class="text-lg font-bold text-[#071a3b]">Stay dates</h2><div class="mt-5 grid gap-4 md:grid-cols-4">
            <div><x-input-label for="check_in_date" value="Check-in date" /><x-text-input id="check_in_date" name="check_in_date" type="date" class="mt-1 block w-full" :value="old('check_in_date', isset($booking) && $booking->check_in_date ? $booking->check_in_date->format('Y-m-d') : '')" required /></div>
            <div><x-input-label for="check_in_time" value="Check-in time" /><x-text-input id="check_in_time" name="check_in_time" type="time" class="mt-1 block w-full" :value="old('check_in_time', isset($booking) && $booking->check_in_time ? \Illuminate\Support\Carbon::parse($booking->check_in_time)->format('H:i') : '15:00')" /></div>
            <div><x-input-label for="check_out_date" value="Check-out date" /><x-text-input id="check_out_date" name="check_out_date" type="date" class="mt-1 block w-full" :value="old('check_out_date', isset($booking) && $booking->check_out_date ? $booking->check_out_date->format('Y-m-d') : '')" required /></div>
            <div><x-input-label for="check_out_time" value="Check-out time" /><x-text-input id="check_out_time" name="check_out_time" type="time" class="mt-1 block w-full" :value="old('check_out_time', isset($booking) && $booking->check_out_time ? \Illuminate\Support\Carbon::parse($booking->check_out_time)->format('H:i') : '11:00')" /></div>
            <div><x-input-label for="guest_count" value="Guests" /><x-text-input id="guest_count" name="guest_count" type="number" min="1" class="mt-1 block w-full" :value="old('guest_count', $booking->guest_count ?? 1)" /></div>
        </div></div>
        <div class="erp-card p-5">
            <h2 class="text-lg font-bold text-[#071a3b]">Fees and rent schedule</h2>
            <div class="mt-5 grid gap-4 md:grid-cols-3">
                @foreach (['rent_amount' => 'Default monthly rent', 'deposit_amount' => 'Deposit', 'dtcm_fee' => 'DTCM', 'cleaning_fee' => 'Cleaning', 'agency_fee' => 'Agency fee'] as $field => $label)
                    <div><x-input-label :for="$field" :value="$label" /><x-text-input :id="$field" :name="$field" class="mt-1 block w-full" :value="old($field, $booking->{$field} ?? 0)" /></div>
                @endforeach
            </div>

            @php($existingPeriods = old('rental_periods', $booking->rental_periods ?? []))
            <div class="mt-5 rounded-[1.5rem] border border-blue-100 bg-blue-50 p-4">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h3 class="text-sm font-black text-[#071a3b]">Monthly invoice schedule</h3>
                        <p class="mt-1 text-xs text-slate-500">First invoice includes rent + 5% VAT on rent only + deposit/DTCM/cleaning/agency. Following periods are rent + VAT only.</p>
                    </div>
                    <span class="rounded-full bg-white px-3 py-1 text-xs font-bold text-blue-700" data-period-count>0 periods</span>
                </div>
                <div class="mt-4 space-y-3" data-rental-periods data-existing-periods='@json($existingPeriods)'></div>
            </div>
        </div>
    </div>
    <div class="space-y-5">
        <div class="erp-card p-5"><h2 class="text-lg font-bold text-[#071a3b]">Automation preview</h2><div class="mt-4 space-y-3 text-sm text-slate-600"><p class="rounded-2xl bg-blue-50 p-4">Confirmed bookings create pending email, WhatsApp, and push notification logs.</p><p class="rounded-2xl bg-emerald-50 p-4">Checkout cleaning task goes to an available cleaner marked for auto assignment.</p><p class="rounded-2xl bg-amber-50 p-4">Checkout inspection task goes to an available technician marked for auto assignment.</p></div></div>
        <div class="erp-card p-5"><h2 class="text-lg font-bold text-[#071a3b]">Internal notes</h2><textarea name="notes" rows="8" class="erp-focus mt-4 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">{{ old('notes', $booking->notes ?? '') }}</textarea></div>
    </div>
</div>
<div class="mt-6 flex justify-end gap-3"><a href="{{ route('bookings.index') }}" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-bold text-slate-600">Cancel</a><x-primary-button>{{ $submitLabel }}</x-primary-button></div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const checkIn = document.getElementById('check_in_date');
        const checkOut = document.getElementById('check_out_date');
        const defaultRent = document.getElementById('rent_amount');
        const list = document.querySelector('[data-rental-periods]');
        const count = document.querySelector('[data-period-count]');
        const existing = JSON.parse(list?.dataset.existingPeriods || '[]');

        if (!checkIn || !checkOut || !defaultRent || !list) return;

        const iso = (date) => date.toISOString().slice(0, 10);
        const label = (date) => date.toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
        const addMonth = (date) => {
            const next = new Date(date);
            const day = next.getDate();
            next.setMonth(next.getMonth() + 1);
            if (next.getDate() !== day) next.setDate(0);
            return next;
        };

        const buildPeriods = () => {
            if (!checkIn.value || !checkOut.value) {
                list.innerHTML = '<p class="rounded-2xl border border-dashed border-blue-200 bg-white px-4 py-5 text-center text-xs font-bold text-slate-400">Select check-in and checkout dates to build monthly rent periods.</p>';
                count.textContent = '0 periods';
                return;
            }

            const start = new Date(`${checkIn.value}T00:00:00`);
            const checkout = new Date(`${checkOut.value}T00:00:00`);
            const finalDay = new Date(checkout);
            finalDay.setDate(finalDay.getDate() - 1);

            if (finalDay < start) return;

            const periods = [];
            let cursor = new Date(start);
            let index = 1;

            while (cursor <= finalDay && index <= 36) {
                let periodEnd = addMonth(cursor);
                periodEnd.setDate(periodEnd.getDate() - 1);
                if (periodEnd > finalDay) periodEnd = new Date(finalDay);
                const oldPeriod = existing.find((item) => Number(item.index) === index) || {};
                periods.push({
                    index,
                    label: label(cursor),
                    start: iso(cursor),
                    end: iso(periodEnd),
                    rent: oldPeriod.rent_amount ?? defaultRent.value ?? 0,
                });
                cursor = new Date(periodEnd);
                cursor.setDate(cursor.getDate() + 1);
                index++;
            }

            count.textContent = `${periods.length} period${periods.length === 1 ? '' : 's'}`;
            list.innerHTML = periods.map((period) => `
                <div class="grid gap-3 rounded-2xl border border-blue-100 bg-white p-3 md:grid-cols-[1.2fr_1fr_1fr_1fr] md:items-end">
                    <input type="hidden" name="rental_periods[${period.index - 1}][index]" value="${period.index}">
                    <input type="hidden" name="rental_periods[${period.index - 1}][label]" value="${period.label}">
                    <input type="hidden" name="rental_periods[${period.index - 1}][start]" value="${period.start}">
                    <input type="hidden" name="rental_periods[${period.index - 1}][end]" value="${period.end}">
                    <div><p class="text-xs font-bold uppercase text-slate-400">Period ${period.index}</p><p class="mt-1 font-black text-[#071a3b]">${period.label}</p></div>
                    <div><p class="text-xs font-bold uppercase text-slate-400">From</p><p class="mt-1 text-sm font-bold text-slate-600">${period.start}</p></div>
                    <div><p class="text-xs font-bold uppercase text-slate-400">To</p><p class="mt-1 text-sm font-bold text-slate-600">${period.end}</p></div>
                    <label><span class="text-xs font-bold uppercase text-slate-400">Rent AED</span><input name="rental_periods[${period.index - 1}][rent_amount]" value="${period.rent}" class="erp-focus mt-1 h-10 w-full rounded-xl border border-slate-200 px-3 text-sm"></label>
                </div>
            `).join('');
        };

        [checkIn, checkOut, defaultRent].forEach((input) => input.addEventListener('change', buildPeriods));
        defaultRent.addEventListener('input', buildPeriods);
        buildPeriods();
    });
</script>
