<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <style>
        body { color: #172554; font-family: dejavusans, sans-serif; font-size: 9px; }
        h1 { font-size: 18px; margin: 0; }
        .meta { color: #64748b; margin: 5px 0 16px; }
        table { border-collapse: collapse; width: 100%; }
        th { background: #eff6ff; color: #1e3a8a; font-size: 8px; text-align: left; }
        th, td { border: 1px solid #dbeafe; padding: 6px 5px; vertical-align: top; }
        .number { text-align: right; white-space: nowrap; }
        .muted { color: #64748b; }
    </style>
</head>
<body>
    <h1>Invoice List Export</h1>
    <p class="meta">Generated {{ now()->format('d M Y, H:i') }}@if($tenant) · {{ $tenant->full_name }}@endif</p>
    <table>
        <thead><tr><th>Invoice</th><th>Booking / Tenant</th><th>Property</th><th>Check-in</th><th>Check-out</th><th>Invoice / Due</th><th class="number">Total</th><th class="number">Paid</th><th class="number">Balance</th><th>Status</th></tr></thead>
        <tbody>@forelse($invoices as $invoice)<tr><td>{{ $invoice->invoice_no }}</td><td>{{ $invoice->booking?->booking_no }}<br><span class="muted">{{ $invoice->tenant?->full_name }}</span></td><td>{{ $invoice->booking?->unit?->building?->name }}<br><span class="muted">Unit {{ $invoice->booking?->unit?->unit_no }}</span></td><td>{{ $invoice->stay_check_in_date?->format('d M Y') }}<br><span class="muted">{{ $invoice->booking?->check_in_time }}</span></td><td>{{ $invoice->stay_check_out_date?->format('d M Y') }}<br><span class="muted">{{ $invoice->booking?->check_out_time }}</span></td><td>{{ $invoice->invoice_date?->format('d M Y') }}<br><span class="muted">Due {{ $invoice->due_date?->format('d M Y') }}</span></td><td class="number">AED {{ number_format((float) $invoice->total_amount, 2) }}</td><td class="number">AED {{ number_format((float) $invoice->paid_amount, 2) }}</td><td class="number">AED {{ number_format((float) $invoice->balance_amount, 2) }}</td><td>{{ str($invoice->status)->replace('_', ' ')->headline() }}</td></tr>@empty<tr><td colspan="10" style="text-align:center; padding:18px;">No invoices found for the selected filters.</td></tr>@endforelse</tbody>
    </table>
</body>
</html>
