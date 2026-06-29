<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 0; }
        body { margin: 0; background: #f4f4f4; color: #172033; font-family: dejavusans, sans-serif; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; }
        td, th { vertical-align: top; }
        .page { padding: 24px 28px 18px; background: #f4f4f4; }
        .booking-card { background: #ffffff; padding: 28px 30px 26px; box-shadow: 0 8px 22px rgba(15, 23, 42, .08); }
        .logo { width: 138px; height: auto; }
        .title { text-align: center; font-size: 22px; font-weight: 800; color: #111827; line-height: 1.15; }
        .subtitle { margin-top: 6px; color: #d71920; font-size: 11px; font-weight: 800; text-align: center; }
        .ref { text-align: right; font-size: 9.5px; line-height: 1.8; color: #111827; }
        .content-title { margin: 0 0 9px; font-size: 15px; font-weight: 800; color: #111827; }
        .info-row td { border-bottom: 1px solid #dedede; padding: 7px 0; }
        .info-label { width: 34%; color: #344054; font-weight: 700; }
        .info-value { color: #111827; font-weight: 600; }
        .left-col { width: 68%; padding-right: 20px; }
        .side-col { width: 32%; }
        .sidebar { background: #eef1f3; padding: 18px 17px; min-height: 580px; }
        .sidebar h4 { margin: 0 0 12px; font-size: 14px; font-weight: 800; color: #111827; }
        .sidebar h5 { margin: 12px 0 7px; font-size: 11px; font-weight: 800; color: #111827; }
        .side-block { margin-bottom: 11px; line-height: 1.45; }
        .side-label { font-weight: 800; color: #111827; }
        .side-muted { color: #475569; font-size: 8.7px; line-height: 1.45; }
        .rule { border-top: 1px solid #cfd6dd; margin: 13px 0; }
        .fee-table td, .fee-table th { padding: 8px 7px; border-bottom: 1px solid #e5e7eb; }
        .fee-table th { background: #f3f4f6; color: #111827; text-align: left; font-size: 9px; }
        .amount { text-align: right; font-weight: 800; }
        .total-row td { font-size: 13px; font-weight: 800; color: #111827; border-bottom: 2px solid #111827; }
        .monthly-table th, .monthly-table td { padding: 5px 4px; border-bottom: 1px solid #d8dee5; font-size: 8.2px; }
        .monthly-table th { text-align: left; color: #334155; font-weight: 800; }
        .signature-box { text-align: center; margin-top: 32px; }
        .signature-title { font-size: 15px; font-weight: 800; color: #111827; letter-spacing: .08em; }
        .signature-copy { max-width: 480px; margin: 8px auto 14px; color: #475569; font-size: 9.3px; line-height: 1.55; }
        .signature-img { max-height: 54px; max-width: 220px; opacity: .95; }
        .signature-line { display: inline-block; min-width: 230px; border-bottom: 1px solid #94a3b8; padding-bottom: 5px; }
        .signature-name { margin-top: 8px; font-size: 16px; font-weight: 800; color: #111827; }
        .signature-date { margin-top: 3px; font-size: 10px; font-weight: 700; color: #475569; }
        .footer-bar { background: #000000; color: #ffffff; padding: 12px 22px; font-size: 9px; }
        .footer-bar td { text-align: center; color: #ffffff; }
        .page-break { page-break-before: always; }
        .guide-page { padding: 18px 22px 16px; background: #ffffff; }
        .guide-hero { background: #071a33; color: #ffffff; border-radius: 8px; padding: 18px 22px; }
        .guide-title { font-size: 22px; font-weight: 800; line-height: 1.15; color: #ffffff; }
        .gold { color: #f4c75b; }
        .guide-subtitle { color: #cbd5e1; font-size: 9px; line-height: 1.6; }
        .brand { width: 128px; vertical-align: middle; }
        .section { border: 1px solid #dfe5ef; border-radius: 8px; background: #fbfdff; padding: 10px; }
        .section-title { color: #0d376b; font-size: 10px; font-weight: 800; text-transform: uppercase; margin-bottom: 9px; border-bottom: 1px solid #e7edf5; padding-bottom: 5px; }
        .icon { display: inline-block; background: #0d4b85; color: #ffffff; font-size: 7.5px; font-weight: 800; padding: 2px 5px; margin-right: 8px; border-radius: 3px; }
        .step-card { border: 1px solid #dfe5ef; border-radius: 8px; background: #fbfdff; padding: 10px; }
        .step-no { display: inline-block; background: #f7ca61; color: #061a38; font-size: 8px; font-weight: 800; padding: 3px 6px; border-radius: 4px; }
        .step-title { color: #071a33; font-size: 11px; font-weight: 800; margin-top: 7px; }
        .step-text { color: #475569; font-size: 8.5px; line-height: 1.45; margin-top: 4px; }
        .phone { width: 188px; border: 5px solid #071a33; border-radius: 20px; background: #071a33; padding: 9px 7px 11px; color: #ffffff; }
        .phone-screen { background: #f8fafc; border-radius: 13px; padding: 9px; color: #0f172a; min-height: 264px; }
        .phone-notch { width: 44px; height: 5px; background: #1e293b; border-radius: 10px; margin: 0 auto 7px; }
        .app-hero { background: #2563eb; color: #ffffff; border-radius: 10px; padding: 10px; }
        .app-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 9px; padding: 8px; margin-top: 7px; }
        .app-muted { color: #64748b; font-size: 7.5px; }
        .app-strong { color: #071a33; font-size: 9px; font-weight: 800; }
        .lock-code { letter-spacing: 3px; color: #2563eb; font-size: 15px; font-weight: 800; }
        .bottom-nav { background: #071a33; color: #ffffff; border-radius: 10px; padding: 6px 4px; margin-top: 8px; text-align: center; font-size: 7px; }
        .mini-table td { padding: 4px 0; border-bottom: 1px solid #eef2f7; font-size: 8.5px; }
        .guide-footer { background: #f8fafc; border: 1px solid #dfe5ef; border-radius: 8px; padding: 10px 12px; font-size: 8.5px; color: #334155; }
        .small-footer { background: #071a33; color: #ffffff; text-align: center; padding: 8px; font-size: 8.2px; border-radius: 0 0 8px 8px; }
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
    $wifiName = $unit?->wifi_name ?: 'PatternVacationHome';
    $wifiPassword = $unit?->wifi_password ?: 'Pattern@000';
    $doorCode = $booking->smart_lock_code ?: 'Pending';
    $tenantAppUrl = 'https://rms.pattern.ae';
    $monthlyInvoices = $booking->invoices->filter(fn ($invoice) => (float) $invoice->rent_amount > 0)->values();
    $signatureName = $booking->confirmation_signed_by ?: $tenant?->full_name;
    $signatureDate = $booking->confirmation_signed_at?->format('d-M-Y') ?? now()->format('d-M-Y');
@endphp

<div class="page">
    <div class="booking-card">
        <table style="margin-bottom: 28px;">
            <tr>
                <td style="width: 24%;">@if($logo)<img src="{{ $logo }}" class="logo">@else <strong>PATTERN</strong> @endif</td>
                <td style="width: 48%;">
                    <div class="title">Booking Confirmation</div>
                    <div class="subtitle">{{ str($booking->booking_type ?: 'Reservation')->replace('_', ' ')->headline() }}</div>
                </td>
                <td style="width: 28%;" class="ref">
                    <strong>Ref No.</strong> {{ $booking->booking_no }}<br>
                    <strong>Date:</strong> {{ $bookingDate }}
                </td>
            </tr>
        </table>

        <table>
            <tr>
                <td class="left-col">
                    <h4 class="content-title">Guest's Details</h4>
                    <table>
                        <tr class="info-row"><td class="info-label">Guest Name</td><td class="info-value">{{ $tenant?->full_name ?? 'Not assigned' }}</td></tr>
                        <tr class="info-row"><td class="info-label">Contact No</td><td class="info-value">{{ $tenant?->mobile_no ?: 'Not added' }}</td></tr>
                        <tr class="info-row"><td class="info-label">Email</td><td class="info-value">{{ $tenant?->email ?: 'Not added' }}</td></tr>
                        <tr class="info-row"><td class="info-label">Nationality</td><td class="info-value">{{ $tenant?->nationality ?: 'Not added' }}</td></tr>
                        <tr class="info-row"><td class="info-label">ID / Passport</td><td class="info-value">{{ $tenant?->identity_no ?: 'Not added' }}</td></tr>
                    </table>

                    <h4 class="content-title" style="margin-top: 24px;">Property Information</h4>
                    <table>
                        <tr class="info-row"><td class="info-label">Property</td><td class="info-value">{{ $unit?->unit_no ? 'Unit '.$unit->unit_no : 'Unit not set' }} - {{ $building?->name ?? 'Building not set' }}</td></tr>
                        <tr class="info-row"><td class="info-label">Type</td><td class="info-value"><strong>{{ $unit?->unit_type ?: str($booking->booking_type)->replace('_', ' ')->headline() }}</strong></td></tr>
                        <tr class="info-row"><td class="info-label">Floor No</td><td class="info-value">{{ $unit?->floor ?: 'Not added' }}</td></tr>
                        <tr class="info-row"><td class="info-label">No Room</td><td class="info-value">{{ $unit?->bedrooms ? $unit->bedrooms.' bedroom(s)' : 'Not added' }}</td></tr>
                        <tr class="info-row"><td class="info-label">Community</td><td class="info-value">{{ $building?->area ?: $building?->address ?: 'Dubai' }}</td></tr>
                    </table>

                    <h4 class="content-title" style="margin-top: 24px;">Reservation Details</h4>
                    <table>
                        <tr class="info-row"><td class="info-label">Check-in Date</td><td class="info-value">{{ $checkInDate }}</td></tr>
                        <tr class="info-row"><td class="info-label">Check-in Time</td><td class="info-value">{{ $checkInTime }}</td></tr>
                        <tr class="info-row"><td class="info-label">Check-out Date</td><td class="info-value">{{ $checkOutDate }}</td></tr>
                        <tr class="info-row"><td class="info-label">Check-out Time</td><td class="info-value">{{ $checkOutTime }}</td></tr>
                        <tr class="info-row"><td class="info-label">Validity To Pay</td><td class="info-value">{{ $booking->created_at?->addDay()?->format('d/m/Y') ?? $checkInDate }}</td></tr>
                    </table>

                    <h4 class="content-title" style="margin-top: 24px;">Fees & Charges</h4>
                    <table class="fee-table">
                        @foreach($chargeRows as $row)
                            <tr><td>{{ $row[0] }}</td><td class="amount">{{ number_format($row[2], 2) }} AED</td></tr>
                        @endforeach
                        <tr class="total-row"><td>Total</td><td class="amount">{{ number_format((float) $booking->total_amount, 2) }} AED</td></tr>
                    </table>
                </td>

                <td class="side-col">
                    <div class="sidebar">
                        <h4>Additional Info</h4>
                        <div class="side-block">Utilities Cap <strong>AED 500 / Month</strong></div>
                        <div class="rule"></div>
                        <div class="side-block"><span class="side-label">WiFi</span><br>{{ $wifiName }}</div>
                        <div class="side-block"><span class="side-label">Password</span><br>{{ $wifiPassword }}</div>
                        <div class="side-block"><span class="side-label">Door Passkey</span><br>{{ $doorCode }}</div>
                        <h4 style="margin-top: 16px;">Monthly Rates</h4>
                        <table class="monthly-table">
                            <tr><th>Duration</th><th class="amount">Rate</th></tr>
                            @forelse($monthlyInvoices as $invoice)
                                <tr>
                                    <td>{{ $invoice->period_start?->format('d/m/Y') ?: $invoice->due_date?->format('d/m/Y') ?: 'Period '.$loop->iteration }}</td>
                                    <td class="amount">{{ number_format((float) $invoice->rent_amount, 2) }}</td>
                                </tr>
                            @empty
                                <tr><td>{{ $stayNights }} night(s)</td><td class="amount">{{ number_format((float) $booking->rent_amount, 2) }}</td></tr>
                            @endforelse
                        </table>
                        <div class="rule"></div>
                        <h5>Required Documents</h5>
                        <p class="side-muted">Passport / Emirates ID</p>
                        <div class="rule"></div>
                        <h5>Customer Service</h5>
                        <p class="side-muted">+971 4 329 9693<br>customerservice@pattern.ae<br>pattern.ae</p>
                    </div>
                </td>
            </tr>
        </table>

        <div class="signature-box">
            <div class="signature-title">SIGNATURE</div>
            <div class="signature-copy">By signing this I certify that I have read and accepted the Terms & Conditions for my reservation.</div>
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
    </div>
</div>

<div class="footer-bar">
    <table>
        <tr>
            <td>pattern.ae</td>
            <td>customerservice@pattern.ae</td>
            <td>+971 4 329 9693</td>
        </tr>
    </table>
</div>

<div class="page-break guide-page">
    <div class="guide-hero">
        <table>
            <tr>
                <td style="width: 24%;">@if($logo)<img src="{{ $logo }}" class="brand">@else <div style="font-size:18px;font-weight:800">PATTERN</div> @endif</td>
                <td style="width: 46%;">
                    <div class="guide-title">TENANT APP<br>QUICK GUIDE</div>
                    <div class="gold" style="margin-top:7px;font-size:10px;">YOUR STAY, ACCESS, PAYMENTS, AND SUPPORT</div>
                </td>
                <td class="guide-subtitle" style="text-align:right;">
                    Login: {{ $tenantAppUrl }}<br>
                    Booking: {{ $booking->booking_no }}<br>
                    Support: +971 4 329 9693
                </td>
            </tr>
        </table>
    </div>

    <table style="margin-top: 14px;">
        <tr>
            <td style="width: 35%; padding-right: 14px;">
                <div class="phone">
                    <div class="phone-notch"></div>
                    <div class="phone-screen">
                        <div class="app-hero">
                            <div style="font-size:7px;opacity:.8;">Current stay</div>
                            <div style="font-size:12px;font-weight:800;line-height:1.15;margin-top:4px;">{{ $building?->name ?? 'Pattern Stay' }}<br>Unit {{ $unit?->unit_no ?? '-' }}</div>
                            <div style="margin-top:8px;font-size:7px;">{{ $booking->check_in_date?->format('d M') }} to {{ $booking->check_out_date?->format('d M Y') }}</div>
                        </div>
                        <div class="app-card">
                            <div class="app-muted">Booking ID</div>
                            <div class="app-strong">{{ $booking->booking_no }}</div>
                        </div>
                        <div class="app-card">
                            <div class="app-muted">Smart door code</div>
                            <div class="lock-code">{{ $booking->smart_lock_code ? trim(chunk_split($booking->smart_lock_code, 1, ' ')) : 'PENDING' }}</div>
                            <div class="app-muted">Works only during your booking dates.</div>
                        </div>
                        <div class="app-card">
                            <div class="app-muted">Quick actions</div>
                            <table style="margin-top:5px;"><tr><td style="background:#eff6ff;border-radius:7px;padding:5px;text-align:center;font-size:7px;font-weight:800;color:#2563eb;">PDF</td><td style="width:5px;"></td><td style="background:#ecfdf3;border-radius:7px;padding:5px;text-align:center;font-size:7px;font-weight:800;color:#047857;">Pay</td><td style="width:5px;"></td><td style="background:#fff7ed;border-radius:7px;padding:5px;text-align:center;font-size:7px;font-weight:800;color:#c2410c;">Help</td></tr></table>
                        </div>
                        <div class="bottom-nav">Home | Stay | Support</div>
                    </div>
                </div>
            </td>
            <td style="width: 65%;">
                <table>
                    <tr>
                        <td style="width: 50%; padding: 0 6px 10px 0;"><div class="step-card"><span class="step-no">01</span><br><div class="step-title">Open your tenant app</div><div class="step-text">Use the login link from your welcome email. Your current booking appears on the first screen after login.</div></div></td>
                        <td style="width: 50%; padding: 0 0 10px 6px;"><div class="step-card"><span class="step-no">02</span><br><div class="step-title">Check stay details</div><div class="step-text">Confirm property, unit, check-in, check-out, guest count, Wi-Fi details, house rules, and booking PDF.</div></div></td>
                    </tr>
                    <tr>
                        <td style="width: 50%; padding: 0 6px 10px 0;"><div class="step-card"><span class="step-no">03</span><br><div class="step-title">Use smart lock access</div><div class="step-text">Use the door code or swipe lock control inside the app. Access is active only from check-in until check-out.</div></div></td>
                        <td style="width: 50%; padding: 0 0 10px 6px;"><div class="step-card"><span class="step-no">04</span><br><div class="step-title">Payments and receipts</div><div class="step-text">View open invoices, upload payment proof, request cash/card collection, and download receipts after approval.</div></div></td>
                    </tr>
                    <tr>
                        <td style="width: 50%; padding: 0 6px 0 0;"><div class="step-card"><span class="step-no">05</span><br><div class="step-title">Report apartment condition</div><div class="step-text">At check-in, submit the apartment inspection with notes and photos so support can track any issues clearly.</div></div></td>
                        <td style="width: 50%; padding: 0 0 0 6px;"><div class="step-card"><span class="step-no">06</span><br><div class="step-title">Contact support</div><div class="step-text">Open Support Center for maintenance, housekeeping, payments, or check-in help. Quote your booking number.</div></div></td>
                    </tr>
                </table>

                <div class="section" style="margin-top: 12px;">
                    <div class="section-title"><span class="icon">APP</span>&nbsp; What you can do from the tenant app</div>
                    <table class="mini-table">
                        <tr><td style="width: 34%;"><strong>Stay dashboard</strong></td><td>See property, dates, booking status, and quick actions.</td></tr>
                        <tr><td><strong>Door access</strong></td><td>View code, set private code when enabled, and use swipe lock control during your stay.</td></tr>
                        <tr><td><strong>Invoices</strong></td><td>Track balance, payment status, receipts, and collection requests.</td></tr>
                        <tr><td><strong>Inspection</strong></td><td>Submit check-in condition and follow deposit/checkout updates.</td></tr>
                        <tr><td><strong>Support</strong></td><td>Create tickets and message the team from mobile.</td></tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    <table style="margin-top: 14px;">
        <tr>
            <td style="width: 50%; padding-right: 8px;"><div class="section"><div class="section-title"><span class="icon">TIP</span>&nbsp; Add to home screen</div><p style="margin:0;line-height:1.6;">On mobile, open the tenant app in your browser and choose <strong>Add to Home Screen</strong> or <strong>Install App</strong>. It will work like a normal app on your phone.</p></div></td>
            <td style="width: 50%; padding-left: 8px;"><div class="section"><div class="section-title"><span class="icon">HELP</span>&nbsp; Company contact</div><p style="margin:0;line-height:1.6;"><strong>Pattern Vacation Homes Rental</strong><br>+971 4 329 9693<br>customerservice@pattern.ae<br>413, AB Center, Sheikh Zayed Road, Al Barsha 1, Dubai</p></div></td>
        </tr>
    </table>

    <div class="guide-footer" style="margin-top: 12px;">
        Keep this PDF saved on your phone. It includes your booking confirmation, tenant app guide, company contact details, and signed acceptance record.
    </div>
    <div class="small-footer" style="margin-top: 8px;">Pattern Vacation Homes Rental - Tenant app guide</div>
</div>
</body>
</html>
