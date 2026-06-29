<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 22px 24px 28px; }
        body { margin: 0; color: #172033; font-family: dejavusans, sans-serif; font-size: 10px; line-height: 1.35; }
        table { width: 100%; border-collapse: collapse; }
        td, th { vertical-align: top; }
        .muted { color: #64748b; }
        .brand-logo { width: 142px; height: auto; }
        .header-title { font-size: 24px; font-weight: 800; color: #111827; letter-spacing: .2px; }
        .header-subtitle { color: #d71920; font-size: 11px; font-weight: 800; margin-top: 4px; }
        .ref-box { text-align: right; font-size: 9.5px; line-height: 1.7; color: #111827; }
        .hero-line { border-top: 3px solid #071a33; margin: 14px 0 16px; }
        .section-title { font-size: 13.5px; font-weight: 800; color: #071a33; margin: 0 0 8px; }
        .info-table td { border-bottom: 1px solid #dbe2ea; padding: 6px 0; }
        .info-label { width: 35%; color: #334155; font-weight: 700; }
        .info-value { color: #111827; font-weight: 600; }
        .left-col { width: 64%; padding-right: 14px; }
        .right-col { width: 36%; }
        .panel { border: 1px solid #dbe2ea; padding: 10px 11px; }
        .panel-dark { border: 1px solid #071a33; color: #071a33; padding: 12px 14px; }
        .panel-title { color: #071a33; font-size: 10.5px; font-weight: 800; text-transform: uppercase; margin-bottom: 6px; }
        .panel-dark .panel-title { color: #071a33; }
        .side-row { border-bottom: 1px solid #e4e9f0; padding: 6px 0; }
        .side-label { color: #334155; font-size: 8.8px; font-weight: 800; text-transform: uppercase; }
        .side-value { color: #111827; font-size: 10px; font-weight: 700; margin-top: 2px; }
        .fee-table th { background: #071a33; color: #ffffff; padding: 7px 8px; font-size: 9px; text-align: left; }
        .fee-table td { border-bottom: 1px solid #dbe2ea; padding: 7px 8px; }
        .amount { text-align: right; white-space: nowrap; }
        .total-row td { color: #071a33; font-size: 13px; font-weight: 800; border-bottom: 2px solid #071a33; }
        .signature-box { margin-top: 14px; border-top: 1px solid #cbd5e1; padding-top: 12px; text-align: center; }
        .signature-title { font-size: 13px; font-weight: 800; letter-spacing: .18em; color: #071a33; }
        .signature-copy { margin: 7px auto 10px; color: #475569; font-size: 8.8px; line-height: 1.5; }
        .signature-img { max-height: 42px; max-width: 210px; }
        .signature-line { display: inline-block; min-width: 250px; border-bottom: 1px solid #94a3b8; padding-bottom: 5px; }
        .signature-name { margin-top: 7px; font-size: 12px; font-weight: 800; color: #111827; }
        .signature-date { margin-top: 2px; font-size: 8.8px; font-weight: 700; color: #64748b; }
        .footer { position: fixed; left: 24px; right: 24px; bottom: 10px; border-top: 1px solid #dbe2ea; color: #334155; padding: 6px 10px; font-size: 8px; }
        .footer td { color: #334155; text-align: center; }
        .page-break { page-break-before: always; }
        .guide-hero { background: #071a33; color: #ffffff; padding: 15px 16px; }
        .guide-title { font-size: 23px; font-weight: 800; line-height: 1.1; color: #ffffff; }
        .guide-gold { color: #f6c95f; font-size: 10px; font-weight: 800; margin-top: 5px; }
        .guide-small { color: #dbeafe; font-size: 8.8px; line-height: 1.6; }
        .guide-box { border: 1px solid #dbe2ea; padding: 9px 10px; }
        .guide-box-title { color: #071a33; font-size: 10.5px; font-weight: 800; }
        .guide-box-copy { color: #475569; font-size: 8.4px; line-height: 1.45; }
        .step-no { color: #b7791f; font-size: 8px; font-weight: 800; }
        .mini-table td { padding: 5px 0; border-bottom: 1px solid #e5eaf1; font-size: 8.6px; }
        .notice { border: 1px solid #dbe2ea; padding: 10px 11px; font-size: 8.8px; color: #334155; }
    </style>
</head>
<body>
@php
    $unit = $booking->unit;
    $building = $unit?->building;
    $tenant = $booking->tenant;
    $bookingDate = $booking->created_at?->format('d-M-Y') ?? now()->format('d-M-Y');
    $checkInDate = $booking->check_in_date?->format('d/m/Y') ?? '-';
    $checkOutDate = $booking->check_out_date?->format('d/m/Y') ?? '-';
    $checkInTime = $booking->check_in_time ? \Illuminate\Support\Carbon::parse($booking->check_in_time)->format('H:i') : '15:00';
    $checkOutTime = $booking->check_out_time ? \Illuminate\Support\Carbon::parse($booking->check_out_time)->format('H:i') : '11:00';
    $wifiName = $unit?->wifi_name ?: 'Pattern Vacation Homes';
    $wifiPassword = $unit?->wifi_password ?: 'Pattern@000';
    $doorCode = $booking->smart_lock_code ?: 'Pending';
    $tenantAppUrl = 'https://rms.pattern.ae';
    $monthlyInvoices = $booking->invoices->filter(fn ($invoice) => (float) $invoice->rent_amount > 0)->values();
    $signatureName = $booking->confirmation_signed_by ?: $tenant?->full_name;
    $signatureDate = $booking->confirmation_signed_at?->format('d-M-Y') ?? now()->format('d-M-Y');
@endphp

<div class="footer">
    <table><tr><td>pattern.ae</td><td>customerservice@pattern.ae</td><td>+971 4 329 9693</td></tr></table>
</div>

<table>
    <tr>
        <td style="width: 25%;">@if($logo)<img src="{{ $logo }}" class="brand-logo">@else <strong>PATTERN</strong> @endif</td>
        <td style="width: 47%; text-align: center;">
            <div class="header-title">Booking Confirmation</div>
            <div class="header-subtitle">{{ str($booking->booking_type ?: 'Reservation')->replace('_', ' ')->headline() }}</div>
        </td>
        <td style="width: 28%;" class="ref-box">
            <strong>Ref No.</strong> {{ $booking->booking_no }}<br>
            <strong>Date:</strong> {{ $bookingDate }}
        </td>
    </tr>
</table>
<div class="hero-line"></div>

<table>
    <tr>
        <td class="left-col">
            <div class="section-title">Guest Details</div>
            <table class="info-table">
                <tr><td class="info-label">Guest Name</td><td class="info-value">{{ $tenant?->full_name ?? 'Not assigned' }}</td></tr>
                <tr><td class="info-label">Contact No</td><td class="info-value">{{ $tenant?->mobile_no ?: 'Not added' }}</td></tr>
                <tr><td class="info-label">Email</td><td class="info-value">{{ $tenant?->email ?: 'Not added' }}</td></tr>
                <tr><td class="info-label">Nationality</td><td class="info-value">{{ $tenant?->nationality ?: 'Not added' }}</td></tr>
                <tr><td class="info-label">ID / Passport</td><td class="info-value">{{ $tenant?->identity_no ?: 'Not added' }}</td></tr>
            </table>

            <div class="section-title" style="margin-top: 13px;">Property Information</div>
            <table class="info-table">
                <tr><td class="info-label">Property</td><td class="info-value">{{ $unit?->unit_no ? 'Unit '.$unit->unit_no : 'Unit not set' }} - {{ $building?->name ?? 'Building not set' }}</td></tr>
                <tr><td class="info-label">Type</td><td class="info-value">{{ $unit?->unit_type ?: str($booking->booking_type)->replace('_', ' ')->headline() }}</td></tr>
                <tr><td class="info-label">Floor No</td><td class="info-value">{{ $unit?->floor ?: 'Not added' }}</td></tr>
                <tr><td class="info-label">Rooms</td><td class="info-value">{{ $unit?->bedrooms ? $unit->bedrooms.' bedroom(s)' : 'Not added' }}</td></tr>
                <tr><td class="info-label">Community</td><td class="info-value">{{ $building?->area ?: $building?->address ?: 'Dubai' }}</td></tr>
            </table>

            <div class="section-title" style="margin-top: 13px;">Reservation Details</div>
            <table class="info-table">
                <tr><td class="info-label">Check-in Date</td><td class="info-value">{{ $checkInDate }}</td></tr>
                <tr><td class="info-label">Check-in Time</td><td class="info-value">{{ $checkInTime }}</td></tr>
                <tr><td class="info-label">Check-out Date</td><td class="info-value">{{ $checkOutDate }}</td></tr>
                <tr><td class="info-label">Check-out Time</td><td class="info-value">{{ $checkOutTime }}</td></tr>
                <tr><td class="info-label">Validity To Pay</td><td class="info-value">{{ $booking->created_at?->addDay()?->format('d/m/Y') ?? $checkInDate }}</td></tr>
            </table>
        </td>
        <td class="right-col">
            <div class="panel">
                <div class="panel-title">Additional Info</div>
                <div class="side-row"><div class="side-label">Utilities Cap</div><div class="side-value">AED 500 / Month</div></div>
                <div class="side-row"><div class="side-label">WiFi</div><div class="side-value">{{ $wifiName }}</div></div>
                <div class="side-row"><div class="side-label">Password</div><div class="side-value">{{ $wifiPassword }}</div></div>
                <div class="side-row"><div class="side-label">Door Passkey</div><div class="side-value">{{ $doorCode }}</div></div>
                <div class="side-row"><div class="side-label">Required Documents</div><div class="side-value">Passport / Emirates ID</div></div>
            </div>

            <div class="panel" style="margin-top: 10px;">
                <div class="panel-title">Monthly Rates</div>
                <table class="mini-table">
                    <tr><td><strong>Duration</strong></td><td class="amount"><strong>Rate</strong></td></tr>
                    @forelse($monthlyInvoices as $invoice)
                        <tr><td>{{ $invoice->period_start?->format('d/m/Y') ?: $invoice->due_date?->format('d/m/Y') ?: 'Period '.$loop->iteration }}</td><td class="amount">{{ number_format((float) $invoice->rent_amount, 2) }}</td></tr>
                    @empty
                        <tr><td>{{ $stayNights }} night(s)</td><td class="amount">{{ number_format((float) $booking->rent_amount, 2) }}</td></tr>
                    @endforelse
                </table>
            </div>

            <div class="panel-dark" style="margin-top: 10px;">
                <div class="panel-title">Customer Service</div>
                <div style="font-size: 9px; line-height: 1.65;">+971 4 329 9693<br>customerservice@pattern.ae<br>pattern.ae<br>413, AB Center, Sheikh Zayed Road, Al Barsha 1, Dubai</div>
            </div>
        </td>
    </tr>
</table>

<div class="section-title" style="margin-top: 14px;">Fees & Charges</div>
<table class="fee-table">
    <tr><th>Description</th><th class="amount">Amount</th></tr>
    @foreach($chargeRows as $row)
        <tr><td>{{ $row[0] }}</td><td class="amount">{{ number_format($row[2], 2) }} AED</td></tr>
    @endforeach
    <tr class="total-row"><td>Total</td><td class="amount">{{ number_format((float) $booking->total_amount, 2) }} AED</td></tr>
</table>

<div class="signature-box">
    <div class="signature-title">SIGNATURE</div>
    <div class="signature-copy">By signing this document, the guest confirms that the booking details, charges, and terms have been reviewed and accepted.</div>
    <div class="signature-line">
        @if($booking->confirmation_signature_data)
            <img src="{{ $booking->confirmation_signature_data }}" class="signature-img">
        @else
            {{ $signatureName ?: 'Awaiting guest signature' }}
        @endif
    </div>
    <div class="signature-name">{{ $signatureName ?: 'Guest Signature Pending' }}</div>
    <div class="signature-date">{{ $booking->confirmation_signed_at ? $signatureDate : 'Electronic signature pending' }}</div>
</div>

<div class="page-break">
    <div class="guide-hero">
        <table>
            <tr>
                <td style="width: 24%;">@if($logo)<img src="{{ $logo }}" style="width:128px;">@else <strong>PATTERN</strong> @endif</td>
                <td style="width: 46%;">
                    <div class="guide-title">TENANT APP<br>QUICK GUIDE</div>
                    <div class="guide-gold">YOUR STAY, ACCESS, PAYMENTS, AND SUPPORT</div>
                </td>
                <td style="width: 30%; text-align: right;" class="guide-small">
                    Login: {{ $tenantAppUrl }}<br>
                    Booking: {{ $booking->booking_no }}<br>
                    Support: +971 4 329 9693
                </td>
            </tr>
        </table>
    </div>

    <div class="section-title" style="margin-top: 14px;">Your tenant app at a glance</div>
    <table>
        <tr>
            <td style="width: 25%;" class="guide-box"><strong>Booking</strong><br>{{ $booking->booking_no }}</td>
            <td style="width: 25%;" class="guide-box"><strong>Property</strong><br>{{ $building?->name ?? 'Pattern Stay' }} / Unit {{ $unit?->unit_no ?? '-' }}</td>
            <td style="width: 25%;" class="guide-box"><strong>Stay Dates</strong><br>{{ $booking->check_in_date?->format('d M') }} to {{ $booking->check_out_date?->format('d M Y') }}</td>
            <td style="width: 25%;" class="guide-box"><strong>Door Code</strong><br>{{ $booking->smart_lock_code ?: 'Pending' }}</td>
        </tr>
    </table>

    <table style="margin-top: 14px;">
        <tr>
            <td style="width: 50%; padding: 0 6px 9px 0;" class="guide-box"><span class="step-no">01</span><br><span class="guide-box-title">Open your tenant app</span><br><span class="guide-box-copy">Use the login link from your welcome email. Your current booking appears after login.</span></td>
            <td style="width: 50%; padding: 0 0 9px 6px;" class="guide-box"><span class="step-no">02</span><br><span class="guide-box-title">Check stay details</span><br><span class="guide-box-copy">Confirm property, unit, check-in, check-out, Wi-Fi details, house rules, and booking PDF.</span></td>
        </tr>
        <tr>
            <td style="width: 50%; padding: 0 6px 9px 0;" class="guide-box"><span class="step-no">03</span><br><span class="guide-box-title">Use smart lock access</span><br><span class="guide-box-copy">Use the door code or swipe lock control inside the app. Access is limited to your stay.</span></td>
            <td style="width: 50%; padding: 0 0 9px 6px;" class="guide-box"><span class="step-no">04</span><br><span class="guide-box-title">Payments and receipts</span><br><span class="guide-box-copy">View invoices, upload payment proof, request collection, and download approved receipts.</span></td>
        </tr>
        <tr>
            <td style="width: 50%; padding: 0 6px 0 0;" class="guide-box"><span class="step-no">05</span><br><span class="guide-box-title">Report apartment condition</span><br><span class="guide-box-copy">At check-in, submit inspection notes and photos so support can track any issue clearly.</span></td>
            <td style="width: 50%; padding: 0 0 0 6px;" class="guide-box"><span class="step-no">06</span><br><span class="guide-box-title">Contact support</span><br><span class="guide-box-copy">Open Support Center for maintenance, housekeeping, payments, or check-in help.</span></td>
        </tr>
    </table>

    <div class="section-title" style="margin-top: 14px;">What you can do from the tenant app</div>
    <table class="mini-table">
        <tr><td style="width: 28%;"><strong>Stay dashboard</strong></td><td>See property, dates, booking status, and quick actions.</td></tr>
        <tr><td><strong>Door access</strong></td><td>View code, set private code when enabled, and use swipe lock control during your stay.</td></tr>
        <tr><td><strong>Invoices</strong></td><td>Track balance, payment status, receipts, and collection requests.</td></tr>
        <tr><td><strong>Inspection</strong></td><td>Submit check-in condition and follow deposit or checkout updates.</td></tr>
        <tr><td><strong>Support</strong></td><td>Create tickets and message the team from mobile.</td></tr>
    </table>

    <table style="margin-top: 14px;">
        <tr>
            <td style="width: 50%; padding-right: 8px;"><div class="notice"><strong>Add to Home Screen</strong><br>On mobile, open the tenant app in your browser and choose Add to Home Screen or Install App. It will work like a normal app on your phone.</div></td>
            <td style="width: 50%; padding-left: 8px;"><div class="notice"><strong>Company Contact</strong><br>Pattern Vacation Homes Rental<br>+971 4 329 9693<br>customerservice@pattern.ae<br>413, AB Center, Sheikh Zayed Road, Al Barsha 1, Dubai</div></td>
        </tr>
    </table>

    <div class="notice" style="margin-top: 14px;">Keep this PDF saved on your phone. It includes your booking confirmation, tenant app guide, company contact details, and signed acceptance record.</div>
</div>
</body>
</html>
