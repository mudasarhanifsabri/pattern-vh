{{-- Detailed booking invoice export: designed for multi-page PDF output with the full payment history. --}}
@php
    $booking = $invoice->booking;
    $approvedPayments = $invoice->payments->where('status', 'approved')->sum('amount');
    $pendingPayments = $invoice->payments->where('status', 'pending')->sum('amount');
    $nights = $invoice->stay_check_in_date && $invoice->stay_check_out_date ? $invoice->stay_check_in_date->diffInDays($invoice->stay_check_out_date) : 0;
    $charges = [
        'Rent' => $invoice->rent_amount,
        'VAT 5% on rent only' => $invoice->vat_amount,
        'Security deposit' => $invoice->deposit_amount,
        'DTCM fee' => $invoice->dtcm_fee,
        'Cleaning fee' => $invoice->cleaning_fee,
        'Agency fee' => $invoice->agency_fee,
    ];
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <style>
        body { color: #172033; font-family: dejavusans, sans-serif; font-size: 10px; }
        .header { border-bottom: 3px solid #2563eb; padding-bottom: 14px; }
        .brand { color: #071a3b; font-size: 23px; font-weight: bold; }
        .title { color: #2563eb; font-size: 16px; font-weight: bold; margin-top: 5px; }
        .muted { color: #64748b; }
        h2 { color: #071a3b; font-size: 13px; margin: 22px 0 9px; }
        table { border-collapse: collapse; width: 100%; }
        th { background: #eff6ff; color: #1d4ed8; font-size: 8px; text-align: left; text-transform: uppercase; }
        th, td { border: 1px solid #dbe3ef; padding: 7px; vertical-align: top; }
        .meta td { width: 33.333%; }
        .meta strong { color: #64748b; display: block; font-size: 8px; margin-bottom: 3px; text-transform: uppercase; }
        .number { text-align: right; white-space: nowrap; }
        .summary { margin-top: 12px; width: 48%; margin-left: auto; }
        .summary td:first-child { font-weight: bold; }
        .summary .total { background: #071a3b; color: #fff; font-size: 11px; font-weight: bold; }
        .status { border-radius: 4px; font-size: 8px; font-weight: bold; padding: 3px 5px; }
        .approved { background: #dcfce7; color: #166534; }
        .pending { background: #fef3c7; color: #92400e; }
        .rejected { background: #fee2e2; color: #991b1b; }
        .footer { color: #64748b; font-size: 8px; margin-top: 20px; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <div class="brand">Pattern Vacation Homes Rental</div>
        <div class="title">Booking Invoice Export</div>
        <div class="muted">Invoice {{ $invoice->invoice_no }} &middot; Generated {{ now()->format('d M Y H:i') }}</div>
    </div>

    <h2>Invoice and Booking Details</h2>
    <table class="meta">
        <tr><td><strong>Invoice no</strong>{{ $invoice->invoice_no }}</td><td><strong>Invoice status</strong>{{ str($invoice->status)->replace('_', ' ')->headline() }}</td><td><strong>Invoice date</strong>{{ $invoice->invoice_date?->format('d M Y') }}</td></tr>
        <tr><td><strong>Tenant</strong>{{ $invoice->tenant?->full_name }}</td><td><strong>Booking no</strong>{{ $booking?->booking_no }}</td><td><strong>Guests</strong>{{ $booking?->guest_count ?: '-' }}</td></tr>
        <tr><td><strong>Property</strong>{{ $booking?->unit?->building?->name }}</td><td><strong>Unit</strong>{{ $booking?->unit?->unit_no }}</td><td><strong>Stay length</strong>{{ $nights }} night{{ $nights === 1 ? '' : 's' }}</td></tr>
    </table>

    <h2>Check-in and Check-out</h2>
    <table class="meta">
        <tr><td><strong>Check-in date</strong>{{ $invoice->stay_check_in_date?->format('d M Y') ?: '-' }}</td><td><strong>Check-in time</strong>{{ $booking?->check_in_time ?: '-' }}</td><td><strong>Booking status</strong>{{ str($booking?->booking_status ?? '')->replace('_', ' ')->headline() }}</td></tr>
        <tr><td><strong>Check-out date</strong>{{ $invoice->stay_check_out_date?->format('d M Y') ?: '-' }}</td><td><strong>Check-out time</strong>{{ $booking?->check_out_time ?: '-' }}</td><td><strong>Due date</strong>{{ $invoice->due_date?->format('d M Y') ?: 'On receipt' }}</td></tr>
    </table>

    <h2>Invoice Charges</h2>
    <table>
        <thead><tr><th>Description</th><th class="number">Amount (AED)</th></tr></thead>
        <tbody>@foreach($charges as $label => $amount)<tr><td>{{ $label }}</td><td class="number">{{ number_format((float) $amount, 2) }}</td></tr>@endforeach</tbody>
    </table>
    <table class="summary">
        <tr><td>Invoice total</td><td class="number">AED {{ number_format((float) $invoice->total_amount, 2) }}</td></tr>
        <tr><td>Approved paid</td><td class="number">AED {{ number_format((float) $approvedPayments, 2) }}</td></tr>
        <tr><td>Pending payments</td><td class="number">AED {{ number_format((float) $pendingPayments, 2) }}</td></tr>
        <tr class="total"><td>Balance due</td><td class="number">AED {{ number_format((float) $invoice->balance_amount, 2) }}</td></tr>
    </table>

    <h2>All Payments</h2>
    <table>
        <thead><tr><th>Payment</th><th>Status</th><th>Method</th><th class="number">Amount</th><th>Paid at</th><th>Reference</th><th>Receipt / Check-in Code</th><th>Notes</th></tr></thead>
        <tbody>@forelse($invoice->payments as $payment)<tr><td>{{ $payment->payment_no }}</td><td><span class="status {{ $payment->status }}">{{ str($payment->status)->headline() }}</span></td><td>{{ str($payment->method)->replace('_', ' ')->headline() }}</td><td class="number">AED {{ number_format((float) $payment->amount, 2) }}</td><td>{{ $payment->paid_at?->format('d M Y H:i') ?: '-' }}</td><td>{{ $payment->reference_no ?: '-' }}</td><td>{{ $payment->receipt?->receipt_no ?: '-' }}@if($payment->receipt?->check_in_code)<br><span class="muted">{{ $payment->receipt->check_in_code }}</span>@endif</td><td>{{ $payment->notes ?: $payment->verification_notes ?: '-' }}</td></tr>@empty<tr><td colspan="8" class="muted">No payments have been recorded for this invoice.</td></tr>@endforelse</tbody>
    </table>

    @if($invoice->notes)<h2>Invoice Notes</h2><p>{{ $invoice->notes }}</p>@endif
    <p class="footer">Pattern Vacation Homes Rental &middot; This export includes all recorded payment statuses.</p>
</body>
</html>
