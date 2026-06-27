<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 0; }
        body { margin: 0; background: #ffffff; color: #101827; font-family: dejavusans, sans-serif; font-size: 9.3px; }
        .page { padding: 16px 20px 14px; }
        .header { background: #071a33; color: #ffffff; border-radius: 8px 8px 0 0; padding: 18px 22px 15px; }
        .brand { width: 128px; vertical-align: middle; }
        .header-title { font-size: 23px; font-weight: 800; line-height: 1.1; letter-spacing: .2px; color: #ffffff; }
        .gold { color: #f4c75b; }
        .contact { font-size: 8.5px; line-height: 1.55; text-align: right; color: #e7eefb; }
        .meta { background: #f7ca61; color: #061a38; padding: 8px 18px; font-size: 9px; font-weight: 800; }
        .section { border: 1px solid #dfe5ef; border-radius: 7px; padding: 11px 12px; background: #ffffff; }
        .section.soft { background: #fbfdff; }
        .section-title { color: #0d376b; font-size: 10px; font-weight: 800; text-transform: uppercase; margin-bottom: 9px; border-bottom: 1px solid #e7edf5; padding-bottom: 5px; }
        .icon { display: inline-block; background: #0d4b85; color: #ffffff; font-size: 7.5px; font-weight: 800; padding: 2px 5px; margin-right: 8px; border-radius: 3px; }
        table { width: 100%; border-collapse: collapse; }
        td { vertical-align: top; }
        .grid td { padding: 6px 8px 6px 0; }
        .label { width: 37%; color: #34445b; font-weight: 700; }
        .value { color: #111827; font-weight: 700; }
        .hero-img { width: 100%; height: 142px; object-fit: cover; border-radius: 6px; }
        .hero-fallback { height: 142px; border-radius: 6px; background: #eff6ff; color: #0d376b; text-align: center; padding-top: 48px; font-size: 16px; font-weight: 800; }
        .charges th { background: #eef2f7; color: #111827; font-size: 8.5px; padding: 7px; text-align: left; }
        .charges td { border-bottom: 1px solid #edf1f5; padding: 7px; }
        .amount { text-align: right; font-weight: 800; }
        .total { background: #071a33; color: #ffffff; font-size: 12px; font-weight: 800; border-radius: 5px; }
        .total td { padding: 9px 11px; }
        .status-pill { display: inline-block; border: 1px solid #86d39f; color: #16944a; background: #ecfdf3; border-radius: 5px; padding: 8px 18px; font-size: 11px; font-weight: 800; }
        .muted { color: #64748b; }
        .note-list { margin: 0; padding-left: 14px; line-height: 1.7; }
        .signature-box { height: 58px; border-bottom: 1px solid #b9c2cf; text-align: center; }
        .signature-img { max-height: 54px; max-width: 190px; opacity: .92; }
        .emboss { display: inline-block; border: 2px solid #f4c75b; background: #071a33; color: #f4c75b; border-radius: 50%; width: 86px; height: 86px; text-align: center; font-weight: 800; font-size: 10px; line-height: 1.2; padding-top: 16px; }
        .signed-ribbon { background: #ecfdf3; color: #047857; border: 1px solid #a7f3d0; border-radius: 6px; padding: 8px 10px; font-weight: 800; }
        .company-strip { background: #f8fafc; border: 1px solid #dfe5ef; border-radius: 7px; padding: 8px 10px; color: #334155; font-size: 8.2px; line-height: 1.45; }
        .footer { background: #071a33; color: #ffffff; text-align: center; padding: 8px; font-size: 8.2px; border-radius: 0 0 8px 8px; }
        .thanks { color: #d99f1f; font-size: 18px; font-style: italic; }
    </style>
</head>
<body>
@php
    $unit = $booking->unit;
    $building = $unit?->building;
    $tenant = $booking->tenant;
    $bookingDate = $booking->created_at?->format('M d, Y') ?? now()->format('M d, Y');
    $checkIn = trim(($booking->check_in_date?->format('M d, Y') ?? 'Not set').' '.($booking->check_in_time ? '('.$booking->check_in_time.')' : ''));
    $checkOut = trim(($booking->check_out_date?->format('M d, Y') ?? 'Not set').' '.($booking->check_out_time ? '('.$booking->check_out_time.')' : ''));
@endphp
<div class="page">
    <div class="header">
        <table>
            <tr>
                <td style="width: 24%;">@if($logo)<img src="{{ $logo }}" class="brand">@else <div style="font-size:18px;font-weight:800">PATTERN</div> @endif</td>
                <td style="width: 43%;"><div class="header-title">BOOKING<br>CONFIRMATION</div><div class="gold" style="margin-top:7px;font-size:10px;">THANK YOU FOR BOOKING WITH US</div></td>
                <td class="contact">+971 4 329 9693<br>customerservice@pattern.ae<br>pattern.ae<br>413, AB Center, Sheikh Zayed Road<br>Al Barsha 1, Dubai</td>
            </tr>
        </table>
    </div>
    <div class="meta">
        <table><tr>
            <td style="text-align:right;">BOOKING ID: {{ $booking->booking_no }}</td>
            <td style="width: 30px;"></td>
            <td style="width: 190px;text-align:right;">BOOKING DATE: {{ $bookingDate }}</td>
        </tr></table>
    </div>

    <table style="margin-top: 12px;">
        <tr>
            <td style="width: 43%; padding-right: 9px;">
                <div class="section soft">
                    <div class="section-title"><span class="icon">01</span>Guest information</div>
                    <table class="grid">
                        <tr><td class="label">Guest name</td><td class="value">: {{ $tenant?->full_name ?? 'Not assigned' }}</td></tr>
                        <tr><td class="label">Email</td><td class="value">: {{ $tenant?->email ?: 'Not added' }}</td></tr>
                        <tr><td class="label">Phone</td><td class="value">: {{ $tenant?->mobile_no ?: 'Not added' }}</td></tr>
                        <tr><td class="label">Nationality</td><td class="value">: {{ $tenant?->nationality ?: 'Not added' }}</td></tr>
                        <tr><td class="label">ID / Passport</td><td class="value">: {{ $tenant?->identity_no ?: 'Not added' }}</td></tr>
                        <tr><td class="label">Guests</td><td class="value">: {{ $booking->guest_count }}</td></tr>
                    </table>
                </div>
            </td>
            <td style="width: 57%;">
                @if($propertyImage)
                    <img src="{{ $propertyImage }}" class="hero-img">
                @else
                    <div class="hero-fallback">{{ $building?->name ?? 'Pattern Vacation Home' }}<br><span style="font-size:10px;font-weight:400;">Unit {{ $unit?->unit_no ?? '-' }}</span></div>
                @endif
            </td>
        </tr>
    </table>

    <table style="margin-top: 10px;">
        <tr>
            <td style="width: 50%; padding-right: 8px;">
                <div class="section soft">
                    <div class="section-title"><span class="icon">02</span>Property information</div>
                    <table class="grid">
                        <tr><td class="label">Building</td><td class="value">: {{ $building?->name ?? 'Not set' }}</td></tr>
                        <tr><td class="label">Unit number</td><td class="value">: {{ $unit?->unit_no ?? 'Not set' }}</td></tr>
                        <tr><td class="label">Property type</td><td class="value">: {{ $unit?->unit_type ?: str($booking->booking_type)->replace('_', ' ')->headline() }}</td></tr>
                        <tr><td class="label">View</td><td class="value">: {{ $unit?->view ?: 'Not added' }}</td></tr>
                        <tr><td class="label">Location</td><td class="value">: {{ $building?->area ?: $building?->address ?: 'Dubai, UAE' }}</td></tr>
                    </table>
                </div>
            </td>
            <td style="width: 50%; padding-left: 8px;">
                <div class="section soft">
                    <div class="section-title"><span class="icon">03</span>Stay details</div>
                    <table class="grid">
                        <tr><td class="label">Check-in</td><td class="value">: {{ $checkIn }}</td></tr>
                        <tr><td class="label">Check-out</td><td class="value">: {{ $checkOut }}</td></tr>
                        <tr><td class="label">Nights</td><td class="value">: {{ $stayNights }} nights</td></tr>
                        <tr><td class="label">Booking source</td><td class="value">: {{ $booking->source ?: 'Direct booking' }}</td></tr>
                        <tr><td class="label">Agent</td><td class="value">: {{ $booking->agent?->full_name ?: 'Direct' }}</td></tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    <table style="margin-top: 10px;">
        <tr>
            <td style="width: 68%; padding-right: 9px;">
                <div class="section soft">
                    <div class="section-title"><span class="icon">04</span>Payment summary</div>
                    <table class="charges">
                        <thead><tr><th>Description</th><th style="width:18%;">Qty</th><th style="width:24%;text-align:right;">Amount (AED)</th></tr></thead>
                        <tbody>
                        @foreach($chargeRows as $row)
                            <tr><td>{{ $row[0] }}</td><td>{{ $row[1] }}</td><td class="amount">{{ number_format($row[2], 2) }}</td></tr>
                        @endforeach
                        </tbody>
                    </table>
                    <table class="total" style="margin-top: 8px;"><tr><td>TOTAL AMOUNT</td><td style="text-align:right;">AED {{ number_format((float) $booking->total_amount, 2) }}</td></tr></table>
                </div>
            </td>
            <td style="width: 32%;">
                <div class="section soft">
                    <div class="section-title">Payment status</div>
                    <div style="text-align:center;margin:8px 0 12px;"><span class="status-pill">{{ strtoupper($paymentSummary['status']) }}</span></div>
                    <table class="grid">
                        <tr><td class="label">Paid</td><td class="value">: AED {{ number_format($paymentSummary['paid'], 2) }}</td></tr>
                        <tr><td class="label">Balance</td><td class="value">: AED {{ number_format($paymentSummary['balance'], 2) }}</td></tr>
                        <tr><td class="label">Method</td><td class="value">: {{ $paymentSummary['method'] }}</td></tr>
                        <tr><td class="label">Reference</td><td class="value">: {{ $paymentSummary['reference'] }}</td></tr>
                        <tr><td class="label">Date</td><td class="value">: {{ $paymentSummary['date'] }}</td></tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    <table style="margin-top: 10px;">
        <tr>
            <td style="width: 38%; padding-right: 8px;">
                <div class="section soft">
                    <div class="section-title"><span class="icon">05</span>Important notes</div>
                    <ul class="note-list">
                        <li>Please present valid ID or passport at check-in.</li>
                        <li>Smoking, parties, and events are not permitted unless approved.</li>
                        <li>Checkout is at {{ $booking->check_out_time ?: '11:00' }} and late checkout is subject to availability.</li>
                        @if($booking->notes)<li>{{ $booking->notes }}</li>@endif
                    </ul>
                </div>
            </td>
            <td style="width: 29%; padding: 0 8px;">
                <div class="section soft">
                    <div class="section-title"><span class="icon">06</span>Cancellation policy</div>
                    <p style="line-height:1.6;margin:0;">Extensions, early checkout, cancellation, and refunds are subject to written company approval and applicable booking terms.</p>
                </div>
            </td>
            <td style="width: 33%; padding-left: 8px;">
                <div class="section soft">
                    <div class="section-title"><span class="icon">07</span>Need help?</div>
                    <p style="line-height:1.7;margin:0;">Our guest support team is available to assist you.</p>
                    <p style="line-height:1.7;margin:8px 0 0;color:#0d376b;font-weight:800;">+971 4 329 9693<br>customerservice@pattern.ae<br>pattern.ae</p>
                </div>
            </td>
        </tr>
    </table>

    <table style="margin-top: 12px;">
        <tr>
            <td style="width: 35%;">
                <div class="thanks">Thank You!</div>
                <div style="margin-top:4px;">We look forward to hosting you.<br>Have a wonderful stay.</div>
            </td>
            <td style="width: 30%; text-align:center;">
                <div class="emboss">PATTERN<br>PREMIUM<br>STAY</div>
            </td>
            <td style="width: 35%;">
                @if($booking->confirmation_signed_at)
                    <div class="signed-ribbon">SIGNED AND ACCEPTED</div>
                @endif
                <div class="signature-box" style="margin-top:6px;">
                    @if($booking->confirmation_signature_data)
                        <img src="{{ $booking->confirmation_signature_data }}" class="signature-img">
                    @elseif($booking->confirmation_signed_at)
                        <span style="line-height:58px;font-size:15px;font-weight:800;color:#111827;">{{ $booking->confirmation_signed_by }}</span>
                    @else
                        <span class="muted" style="line-height:58px;">Awaiting guest signature</span>
                    @endif
                </div>
                <div style="font-size:8.5px;margin-top:5px;">
                    <strong>{{ $booking->confirmation_signed_by ?: $tenant?->full_name ?: 'Guest signature' }}</strong><br>
                    {{ $booking->confirmation_signed_at ? 'Signed '.$booking->confirmation_signed_at->format('M d, Y H:i') : 'Electronic signature pending' }}<br>
                    @if($booking->confirmation_signed_ip)<span class="muted">IP {{ $booking->confirmation_signed_ip }}</span>@endif
                </div>
            </td>
        </tr>
    </table>

    <div class="company-strip" style="margin-top: 8px;">
        <strong>Pattern Vacation Homes Rental</strong> - 413, AB Center, Sheikh Zayed Road, Al Barsha 1, Dubai<br>
        Tel: +971 4 329 9693 | Email: customerservice@pattern.ae | Web: pattern.ae
    </div>
    <div class="footer">Pattern Vacation Homes Rental - Secure booking confirmation</div>
</div>
</body>
</html>
