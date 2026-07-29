<x-app-layout>
<x-slot name="header"><div><p class="text-[11px] font-bold uppercase tracking-[0.22em] text-blue-600">Accounting</p><h1 class="text-2xl font-bold text-[#071a3b]">Finance Sheet</h1></div></x-slot>

@php
    // Keep the on-screen grid and both exports in the same column order.
    $columns = [
        ['Owner Payment', 'owner_payment'], ['Status', 'status'], ['Unit', 'unit'], ['Type', 'type'], ['Area', 'area'], ['Building Name', 'building_name'], ['Mode of Payment', 'payment_mode'], ['Tenant Name', 'tenant_name'], ['Check-In', 'check_in', 'date'], ['Check-Out', 'check_out', 'date'], ['Original Rent', 'original_rent', 'money'], ['VAT', 'vat', 'money'], ['Incl. VAT', 'including_vat', 'money'], ['Housekeeping', 'housekeeping', 'money'], ['Tourism', 'tourism', 'money'], ['Security Deposit', 'security_deposit', 'money'], ['Agency Fee', 'agency_fee', 'money'], ['Grand Total', 'grand_total', 'money'], ['Tenant Transferred', 'tenant_transferred', 'money'], ['Balance', 'balance', 'money'], ['Deposit', 'deposit', 'money'], ['Owner %', 'owner_percent', 'percent'], ['Pattern Rent Profit', 'pattern_rent_profit', 'money'], ['Owner Name', 'owner_name'], ['DEWA', 'dewa', 'money'], ['Gas', 'gas', 'money'], ['AC', 'ac', 'money'], ['Cleaning Profit', 'cleaning_profit', 'money'], ['e-Net', 'e_net', 'money'], ['Maintenance', 'maintenance', 'money'], ['Others', 'others', 'money'], ['Remarks', 'remarks'], ['Furniture Balance', 'furniture_balance', 'money'], ['Total Deduction', 'total_deduction', 'money'], ['Transfer To Owner', 'transfer_to_owner'], ['Amount to Owner', 'amount_to_owner', 'money'],
    ];
@endphp

<div class="space-y-5">
    <section class="erp-card p-5">
        <form method="GET" class="flex flex-wrap items-end gap-3">
            <label class="text-xs font-bold text-slate-500">From<input name="from" type="date" value="{{ $from->format('Y-m-d') }}" class="erp-focus mt-1 block h-11 rounded-xl border-slate-200 text-sm"></label>
            <label class="text-xs font-bold text-slate-500">To<input name="to" type="date" value="{{ $to->format('Y-m-d') }}" class="erp-focus mt-1 block h-11 rounded-xl border-slate-200 text-sm"></label>
            <button class="h-11 rounded-xl bg-slate-900 px-4 text-sm font-bold text-white">Filter</button>
            <a href="{{ route('finance-sheet.pdf', request()->query()) }}" class="inline-flex h-11 items-center rounded-xl bg-blue-600 px-4 text-sm font-bold text-white">Download PDF</a>
            <a href="{{ route('finance-sheet.excel', request()->query()) }}" class="inline-flex h-11 items-center rounded-xl border border-emerald-200 bg-emerald-50 px-4 text-sm font-bold text-emerald-700">Export Excel</a>
        </form>
        <p class="mt-4 text-sm text-slate-500">One row per invoice-owner period, filtered by invoice checkout date. Owner amount excludes VAT and deducts management fees plus owner expenses in that stay period.</p>
    </section>

    <section class="erp-card overflow-hidden">
        <div class="border-b border-slate-100 p-5"><h2 class="text-lg font-black text-[#071a3b]">Invoice Owner Finance Ledger</h2><p class="mt-1 text-sm text-slate-500">{{ $rows->count() }} row(s) for {{ $from->format('d M Y') }} to {{ $to->format('d M Y') }}.</p></div>
        <div class="overflow-x-auto"><table class="min-w-max divide-y divide-slate-200 text-xs"><thead class="bg-slate-50 text-left font-bold uppercase tracking-[0.12em] text-slate-500"><tr>@foreach($columns as [$label])<th class="whitespace-nowrap px-3 py-3">{{ $label }}</th>@endforeach</tr></thead><tbody class="divide-y divide-slate-100 bg-white">@forelse($rows as $row)<tr>@foreach($columns as $column)@php([$label, $key, $format] = array_pad($column, 3, null))<td class="max-w-64 px-3 py-3 align-top {{ $format === 'money' ? 'whitespace-nowrap text-right font-semibold text-[#071a3b]' : '' }}">@if($format === 'money')AED {{ number_format((float) $row[$key], 2) }}@elseif($format === 'date'){{ $row[$key]?->format('d M Y') ?? '-' }}@elseif($format === 'percent'){{ number_format((float) $row[$key], 2) }}%@else{{ $row[$key] }}@endif</td>@endforeach</tr>@empty<tr><td colspan="36" class="px-4 py-12 text-center text-sm text-slate-500">No invoice periods check out within the selected dates.</td></tr>@endforelse</tbody></table></div>
    </section>
</div>
</x-app-layout>
