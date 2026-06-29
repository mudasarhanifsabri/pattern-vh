<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 0; }
        body { margin: 0; color: #132238; font-family: dejavusans, sans-serif; font-size: 10px; line-height: 1.35; background: #ffffff; }
        table { width: 100%; border-collapse: collapse; }
        td, th { vertical-align: top; }
        .page { padding: 22px 26px 24px; }
        .navy { background: #071a33; color: #ffffff; }
        .gold { color: #d8ab3f; }
        .muted { color: #66758b; }
        .logo { width: 142px; height: auto; }
        .masthead { padding: 22px 24px 18px; }
        .title { font-size: 28px; line-height: 1.08; font-weight: 900; color: #ffffff; text-transform: uppercase; }
        .subtitle { margin-top: 6px; font-size: 10.5px; font-weight: 800; color: #f7d783; text-transform: uppercase; letter-spacing: .08em; }
        .contact { color: #e7eef8; font-size: 9px; line-height: 1.75; text-align: right; }
        .gold-strip { background: #f3c866; color: #071a33; font-size: 9.5px; font-weight: 900; text-transform: uppercase; }
        .gold-strip td { padding: 8px 16px; }
        .summary-band { margin-top: 14px; border: 1px solid #dce4ef; }
        .summary-band td { padding: 10px 12px; border-right: 1px solid #e3eaf2; background: #f8fafd; }
        .summary-label { font-size: 7.6px; color: #7a8aa1; font-weight: 900; text-transform: uppercase; letter-spacing: .12em; }
        .summary-value { margin-top: 3px; color: #071a33; font-size: 11px; font-weight: 900; }
        .section-title { margin: 13px 0 7px; color: #071a33; font-size: 12.5px; font-weight: 900; text-transform: uppercase; letter-spacing: .03em; }
        .card { border: 1px solid #dce4ef; background: #ffffff; padding: 10px 12px; }
        .soft-card { border: 1px solid #dce4ef; background: #f8fafd; padding: 10px 12px; }
        .card-title { color: #071a33; font-size: 10px; font-weight: 900; text-transform: uppercase; margin-bottom: 6px; }
        .info td { padding: 5.7px 0; border-bottom: 1px solid #e7edf5; }
        .label { width: 39%; color: #5d6d83; font-weight: 800; font-size: 8.8px; }
        .value { color: #111f34; font-weight: 800; font-size: 9.4px; }
        .hero-img { width: 100%; height: 130px; object-fit: cover; }
        .placeholder { height: 130px; background: #071a33; color: #f8d374; text-align: center; font-size: 14px; font-weight: 900; padding-top: 48px; }
        .status-card { background: #071a33; color: #ffffff; padding: 13px 14px; }
        .status-label { color: #f3c866; font-size: 8px; font-weight: 900; text-transform: uppercase; letter-spacing: .12em; }
        .status-value { margin-top: 4px; color: #ffffff; font-size: 18px; font-weight: 900; }
        .status-note { margin-top: 4px; color: #cfdae8; font-size: 8.5px; line-height: 1.45; }
        .fee-table th { background: #071a33; color: #ffffff; padding: 8px 9px; font-size: 8.5px; text-align: left; text-transform: uppercase; }
        .fee-table td { padding: 7px 9px; border-bottom: 1px solid #e1e8f1; color: #132238; }
        .amount { text-align: right; white-space: nowrap; }
        .fee-total td { background: #f3c866; color: #071a33; font-size: 12.2px; font-weight: 900; border-bottom: 0; }
        .terms td { padding: 5px 0; color: #33455d; font-size: 8.2px; border-bottom: 1px solid #edf2f7; }
        .tick { width: 16px; color: #d09b27; font-weight: 900; }
        .signature { margin-top: 13px; border: 1px solid #dce4ef; padding: 11px 14px; }
        .signature-label { font-size: 8px; color: #7a8aa1; font-weight: 900; text-transform: uppercase; letter-spacing: .13em; }
        .signature-copy { margin-top: 3px; color: #5d6d83; font-size: 8.4px; line-height: 1.45; }
        .signature-line { height: 46px; border-bottom: 1px solid #94a3b8; text-align: center; padding-top: 4px; }
        .signature-img { max-height: 42px; max-width: 210px; }
        .signature-name { margin-top: 5px; color: #071a33; font-size: 10.5px; font-weight: 900; }
        .signature-date { margin-top: 1px; color: #66758b; font-size: 8px; }
        .footer { position: fixed; left: 26px; right: 26px; bottom: 9px; border-top: 1px solid #dce4ef; padding-top: 6px; color: #66758b; font-size: 7.7px; text-align: center; }
        .page-break { page-break-before: always; }
        .guide-title { font-size: 25px; line-height: 1.08; font-weight: 900; color: #ffffff; text-transform: uppercase; }
        .phone-frame { border: 2px solid #071a33; background: #071a33; padding: 8px; }
        .phone-screen { background: #f8fafd; padding: 9px; }
        .phone-card { background: #ffffff; border: 1px solid #dce4ef; padding: 8px; margin-bottom: 7px; }
        .phone-small { color: #6d7d93; font-size: 7.6px; font-weight: 800; text-transform: uppercase; }
        .phone-big { color: #071a33; font-size: 11px; font-weight: 900; margin-top: 2px; }
        .feature td { padding: 10px 11px; border: 1px solid #dce4ef; background: #ffffff; }
        .feature-no { color: #c99528; font-size: 8px; font-weight: 900; }
        .feature-title { color: #071a33; font-size: 10.5px; font-weight: 900; margin-top: 2px; }
        .feature-copy { color: #53647b; font-size: 8.3px; line-height: 1.45; margin-top: 3px; }
        .notice { background: #fff8e6; border: 1px solid #f0d58f; padding: 10px 12px; color: #4d3b12; font-size: 8.6px; line-height: 1.45; }
    </style>
</head>
<body>
@php
    $unit = $booking->unit;
    $building = $unit?->building;
    $tenant = $booking->tenant;
    $bookingDate = $booking->created_at?->format('d M Y') ?? now()->format('d M Y');
    $checkInDate = $booking->check_in_date?->format('d M Y') ?? '-';
    $checkOutDate = $booking->check_out_date?->format('d M Y') ?? '-';
    $checkInTime = $booking->check_in_time ? \Illuminate\Support\Carbon::parse($booking->check_in_time)->format('H:i') : '15:00';
    $checkOutTime = $booking->check_out_time ? \Illuminate\Support\Carbon::parse($booking->check_out_time)->format('H:i') : '11:00';
    $wifiName = $unit?->wifi_name ?: 'Pattern Vacation Homes';
    $wifiPassword = $unit?->wifi_password ?: 'Shared after payment';
    $doorCode = $booking->smart_lock_code ?: 'Shared before check-in';
    $tenantAppUrl = 'https://rms.pattern.ae';
    $signatureName = $booking->confirmation_signed_by ?: $tenant?->full_name;
    $signatureDate = $booking->confirmation_signed_at?->format('d M Y') ?? now()->format('d M Y');
    $status = $paymentSummary['status'] ?? 'Pending';
    $statusText = $paymentSummary['balance'] > 0 ? $status.' - AED '.number_format($paymentSummary['balance'], 2).' due' : 'Paid in full';
    $propertyName = trim(($building?->name ?? 'Pattern Stay').' / Unit '.($unit?->unit_no ?? '-'));
@endphp

<div class="footer">Pattern Vacation Homes Rental | 413, AB Center, Sheikh Zayed Road, Al Barsha 1, Dubai | +971 4 329 9693 | customerservice@pattern.ae | pattern.ae</div>

<table class="navy masthead">
    <tr>
        <td style="width: 26%;">@if($logo)<img src="{{ $logo }}" class="logo">@else <strong>PATTERN</strong> @endif</td>
        <td style="width: 44%;">
            <div class="title">Booking<br>Confirmation</div>
            <div class="subtitle">Thank you for booking with Pattern</div>
        </td>
        <td style="width: 30%;" class="contact">
            +971 4 329 9693<br>
            customerservice@pattern.ae<br>
            pattern.ae<br>
            AB Center, Al Barsha 1, Dubai
        </td>
    </tr>
</table>
<table class="gold-strip">
    <tr>
        <td>Booking ID: {{ $booking->booking_no }}</td>
        <td style="text-align: right;">Booking Date: {{ $bookingDate }}</td>
    </tr>
</table>

<div class="page">
    <table class="summary-band">
        <tr>
            <td style="width: 25%;"><div class="summary-label">Guest</div><div class="summary-value">{{ $tenant?->full_name ?? 'Not assigned' }}</div></td>
            <td style="width: 25%;"><div class="summary-label">Property</div><div class="summary-value">{{ $propertyName }}</div></td>
            <td style="width: 25%;"><div class="summary-label">Stay</div><div class="summary-value">{{ $checkInDate }} to {{ $checkOutDate }}</div></td>
            <td style="width: 25%; border-right: 0;"><div class="summary-label">Payment</div><div class="summary-value">{{ $statusText }}</div></td>
        </tr>
    </table>

    <table style="margin-top: 13px;">
        <tr>
            <td style="width: 49%; padding-right: 7px;">
                <div class="section-title">Guest Information</div>
                <div class="card">
                    <table class="info">
                        <tr><td class="label">Guest Name</td><td class="value">{{ $tenant?->full_name ?? 'Not assigned' }}</td></tr>
                        <tr><td class="label">Email</td><td class="value">{{ $tenant?->email ?: 'Not added' }}</td></tr>
                        <tr><td class="label">Phone</td><td class="value">{{ $tenant?->mobile_no ?: 'Not added' }}</td></tr>
                        <tr><td class="label">Nationality</td><td class="value">{{ $tenant?->nationality ?: 'Not added' }}</td></tr>
                        <tr><td class="label">ID / Passport</td><td class="value">{{ $tenant?->identity_no ?: 'Not added' }}</td></tr>
                    </table>
                </div>
            </td>
            <td style="width: 51%; padding-left: 7px;">
                <div class="section-title">Apartment Preview</div>
                @if($propertyImage)
                    <img src="{{ $propertyImage }}" class="hero-img">
                @else
                    <div class="placeholder">PATTERN VACATION HOMES</div>
                @endif
            </td>
        </tr>
    </table>

    <table style="margin-top: 12px;">
        <tr>
            <td style="width: 50%; padding-right: 7px;">
                <div class="section-title">Property Information</div>
                <div class="card">
                    <table class="info">
                        <tr><td class="label">Building</td><td class="value">{{ $building?->name ?? 'Not set' }}</td></tr>
                        <tr><td class="label">Unit</td><td class="value">{{ $unit?->unit_no ?: 'Not set' }}</td></tr>
                        <tr><td class="label">Type</td><td class="value">{{ $unit?->unit_type ?: str($booking->booking_type)->replace('_', ' ')->headline() }}</td></tr>
                        <tr><td class="label">Location</td><td class="value">{{ $building?->area ?: $building?->address ?: 'Dubai' }}</td></tr>
                        <tr><td class="label">WiFi</td><td class="value">{{ $wifiName }}</td></tr>
                    </table>
                </div>
            </td>
            <td style="width: 50%; padding-left: 7px;">
                <div class="section-title">Stay Details</div>
                <div class="card">
                    <table class="info">
                        <tr><td class="label">Check-in</td><td class="value">{{ $checkInDate }} at {{ $checkInTime }}</td></tr>
                        <tr><td class="label">Check-out</td><td class="value">{{ $checkOutDate }} at {{ $checkOutTime }}</td></tr>
                        <tr><td class="label">Days</td><td class="value">{{ $stayNights }} day(s)</td></tr>
                        <tr><td class="label">Source</td><td class="value">{{ $booking->source ?: 'Direct booking' }}</td></tr>
                        <tr><td class="label">Door Code</td><td class="value">{{ $doorCode }}</td></tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    <table style="margin-top: 13px;">
        <tr>
            <td style="width: 67%; padding-right: 8px;">
                <div class="section-title">Payment Summary</div>
                <table class="fee-table">
                    <tr><th>Description</th><th style="width: 22%;">Qty</th><th class="amount" style="width: 25%;">Amount</th></tr>
                    @foreach($chargeRows as $row)
                        <tr><td>{{ $row[0] }}</td><td>{{ $row[1] }}</td><td class="amount">AED {{ number_format($row[2], 2) }}</td></tr>
                    @endforeach
                    <tr class="fee-total"><td colspan="2">Total Amount</td><td class="amount">AED {{ number_format((float) $booking->total_amount, 2) }}</td></tr>
                </table>
            </td>
            <td style="width: 33%; padding-left: 8px;">
                <div class="section-title">Payment Status</div>
                <div class="status-card">
                    <div class="status-label">Current Status</div>
                    <div class="status-value">{{ $paymentSummary['status'] }}</div>
                    <div class="status-note">
                        Paid: AED {{ number_format($paymentSummary['paid'], 2) }}<br>
                        Balance: AED {{ number_format($paymentSummary['balance'], 2) }}<br>
                        Method: {{ $paymentSummary['method'] }}<br>
                        Ref: {{ $paymentSummary['reference'] }}
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <table style="margin-top: 12px;">
        <tr>
            <td style="width: 40%; padding-right: 7px;">
                <div class="section-title">Important Notes</div>
                <div class="soft-card">
                    <table class="terms">
                        <tr><td class="tick">01</td><td>Present passport or Emirates ID at check-in.</td></tr>
                        <tr><td class="tick">02</td><td>Smoking, parties, and events are not permitted.</td></tr>
                        <tr><td class="tick">03</td><td>Keep noise low and respect building rules.</td></tr>
                    </table>
                </div>
            </td>
            <td style="width: 30%; padding: 0 7px;">
                <div class="section-title">Cancellation</div>
                <div class="soft-card" style="font-size: 8.4px; color: #33455d; line-height: 1.5;">
                    Free cancellation terms depend on the approved booking policy. No-show or late cancellation may be charged as per agreement.
                </div>
            </td>
            <td style="width: 30%; padding-left: 7px;">
                <div class="section-title">Need Help?</div>
                <div class="soft-card" style="font-size: 8.4px; color: #33455d; line-height: 1.55;">
                    +971 4 329 9693<br>
                    customerservice@pattern.ae<br>
                    pattern.ae
                </div>
            </td>
        </tr>
    </table>

    <div class="signature">
        <table>
            <tr>
                <td style="width: 47%;">
                    <div class="signature-label">Guest Acceptance</div>
                    <div class="signature-copy">The guest confirms that the booking details, charges, stay rules, payment status, and access policy have been reviewed and accepted.</div>
                </td>
                <td style="width: 28%; text-align: center;">
                    <div class="signature-line">
                        @if($booking->confirmation_signature_data)
                            <img src="{{ $booking->confirmation_signature_data }}" class="signature-img">
                        @else
                            <span class="muted">Awaiting signature</span>
                        @endif
                    </div>
                    <div class="signature-name">{{ $signatureName ?: 'Guest Signature Pending' }}</div>
                    <div class="signature-date">{{ $booking->confirmation_signed_at ? $signatureDate : 'Electronic signature pending' }}</div>
                </td>
                <td style="width: 25%; text-align: right;">
                    <div class="signature-label">Issued By</div>
                    <div class="signature-name">Pattern Vacation Homes</div>
                    <div class="signature-date">{{ $bookingDate }}</div>
                </td>
            </tr>
        </table>
    </div>
</div>

<div class="page-break"></div>
<table class="navy masthead">
    <tr>
        <td style="width: 25%;">@if($logo)<img src="{{ $logo }}" class="logo">@else <strong>PATTERN</strong> @endif</td>
        <td style="width: 50%;">
            <div class="guide-title">Tenant App<br>Welcome Guide</div>
            <div class="subtitle">Access, payments, support, and checkout in one place</div>
        </td>
        <td style="width: 25%;" class="contact">
            Login: {{ $tenantAppUrl }}<br>
            Booking: {{ $booking->booking_no }}<br>
            Support: +971 4 329 9693
        </td>
    </tr>
</table>

<div class="page">
    <table>
        <tr>
            <td style="width: 34%; padding-right: 14px;">
                <div class="phone-frame">
                    <div class="phone-screen">
                        <div class="phone-card">
                            <div class="phone-small">Current Stay</div>
                            <div class="phone-big">{{ $propertyName }}</div>
                            <div class="muted" style="font-size: 8px; margin-top: 3px;">{{ $checkInDate }} to {{ $checkOutDate }}</div>
                        </div>
                        <div class="phone-card">
                            <div class="phone-small">Payment</div>
                            <div class="phone-big">{{ $paymentSummary['status'] }}</div>
                            <div class="muted" style="font-size: 8px; margin-top: 3px;">Balance AED {{ number_format($paymentSummary['balance'], 2) }}</div>
                        </div>
                        <div class="phone-card">
                            <div class="phone-small">Door Access</div>
                            <div class="phone-big">{{ $doorCode }}</div>
                            <div class="muted" style="font-size: 8px; margin-top: 3px;">Works only during approved stay dates.</div>
                        </div>
                        <div class="phone-card">
                            <div class="phone-small">Support</div>
                            <div class="phone-big">Open Ticket</div>
                            <div class="muted" style="font-size: 8px; margin-top: 3px;">Maintenance, cleaning, payments, or check-in help.</div>
                        </div>
                    </div>
                </div>
            </td>
            <td style="width: 66%;">
                <div class="section-title" style="margin-top: 0;">What You Can Do In The Tenant App</div>
                <table class="feature">
                    <tr>
                        <td style="width: 50%;"><div class="feature-no">01</div><div class="feature-title">Track booking status</div><div class="feature-copy">See pending payment, confirmed, checked-in, checkout, and deposit refund progress.</div></td>
                        <td style="width: 50%;"><div class="feature-no">02</div><div class="feature-title">Invoices and receipts</div><div class="feature-copy">View due invoices, payment balance, approved receipts, and collection requests from mobile.</div></td>
                    </tr>
                    <tr>
                        <td><div class="feature-no">03</div><div class="feature-title">Smart lock access</div><div class="feature-copy">Use the door code or swipe lock control during the approved check-in and check-out window.</div></td>
                        <td><div class="feature-no">04</div><div class="feature-title">Check-in report</div><div class="feature-copy">Upload photos and notes when you arrive so apartment condition is recorded clearly.</div></td>
                    </tr>
                    <tr>
                        <td><div class="feature-no">05</div><div class="feature-title">Deposit refund details</div><div class="feature-copy">Add bank details and follow refund status after inspection, subject to policy and dues.</div></td>
                        <td><div class="feature-no">06</div><div class="feature-title">Support center</div><div class="feature-copy">Message the team for maintenance, housekeeping, payment support, and general assistance.</div></td>
                    </tr>
                </table>

                <div class="section-title">How To Install</div>
                <div class="notice">
                    Open {{ $tenantAppUrl }} on your phone browser. Choose Add to Home Screen or Install App. The tenant portal will open like a normal mobile app and keep your booking, invoices, receipts, door access, and messages together.
                </div>

                <div class="section-title">Stay Access Policy</div>
                <div class="card" style="font-size: 8.8px; color: #33455d; line-height: 1.55;">
                    Smart lock access is granted only for the approved booking duration. If payment is pending, access and check-in steps can remain limited until the account is cleared and confirmation is completed.
                </div>
            </td>
        </tr>
    </table>
</div>
</body>
</html>
