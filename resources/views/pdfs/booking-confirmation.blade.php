<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 0; }
        body { margin: 0; color: #101010; font-family: dejavusans, sans-serif; font-size: 10.5px; line-height: 1.25; background: #fff; }
        table { width: 100%; border-collapse: collapse; }
        td { vertical-align: top; }
        .page { padding: 28px 36px 76px; }
        .brand-logo { width: 150px; height: auto; }
        .header-title { text-align: center; font-size: 18px; font-weight: 800; padding-top: 18px; }
        .ref-box { text-align: left; font-size: 11px; line-height: 1.7; padding-top: 10px; }
        .content { margin-top: 80px; }
        .main-col { width: 69%; padding-right: 18px; }
        .side-col { width: 27%; background: #e9eeee; padding: 16px 14px; }
        .section-title { margin: 0 0 14px; font-size: 13px; font-weight: 800; }
        .section-spacer { height: 18px; }
        .info-table td { padding: 7px 0; border-bottom: 1px solid #ddd; }
        .info-label { width: 47%; padding-left: 18px !important; font-size: 10.5px; }
        .info-value { width: 53%; font-size: 10.8px; font-weight: 800; }
        .fee-table td { padding: 5.8px 0; border-bottom: 1px solid #ddd; }
        .fee-label { width: 58%; padding-left: 20px !important; font-weight: 800; }
        .fee-value { width: 42%; text-align: center; }
        .fee-total td { border-top: 1px solid #d0d0d0; border-bottom: 0; padding-top: 8px; font-weight: 800; }
        .side-title { margin: 0 0 18px; font-size: 13px; font-weight: 800; }
        .side-block { border-bottom: 1px solid #d9dede; padding-bottom: 14px; }
        .side-spacer-sm { height: 38px; }
        .side-spacer-md { height: 82px; }
        .side-spacer-lg { height: 168px; border-top: 1px solid #d9dede; border-bottom: 1px solid #d9dede; }
        .side-label { font-size: 10.8px; margin-bottom: 12px; }
        .side-strong { font-size: 10.5px; font-weight: 500; margin-bottom: 6px; }
        .side-muted { color: #999; font-size: 9.8px; font-style: italic; line-height: 1.45; }
        .doc-list { margin: 0; padding-left: 17px; }
        .doc-list li { padding-bottom: 6px; }
        .prepared { font-size: 10px; line-height: 1.4; }
        .prepared-mark { margin-top: 10px; color: #2768bb; border: 2px solid #2768bb; padding: 8px 10px; text-align: center; font-size: 9px; font-weight: 800; line-height: 1.35; }
        .signature-area { margin-top: 56px; }
        .signature-title { font-size: 12px; font-weight: 800; }
        .signature-copy { margin-top: 14px; text-align: center; font-size: 10.5px; }
        .signature-line { width: 315px; margin: 34px auto 0; border-bottom: 1px solid #ddd; text-align: center; min-height: 34px; color: #bbb; font-size: 12px; }
        .signature-img { max-height: 36px; max-width: 230px; }
        .signature-name { margin-top: 10px; text-align: center; font-size: 16px; }
        .signature-date { margin-top: 16px; text-align: center; font-size: 15px; }
        .footer { position: fixed; left: 0; right: 0; bottom: 0; height: 64px; background: #111; color: #fff; font-size: 10.5px; }
        .footer-address { text-align: center; padding-top: 11px; font-size: 11px; }
        .footer-row { margin-top: 14px; }
        .footer-row td { color: #fff; padding: 0 28px; }
        .footer-center { text-align: center; }
        .footer-right { text-align: right; }
    </style>
</head>
<body>
@php
    $unit = $booking->unit;
    $building = $unit?->building;
    $tenant = $booking->tenant;
    $bookingDate = $booking->created_at?->format('d-m-Y') ?? now()->format('d-m-Y');
    $checkInDate = $booking->check_in_date?->format('d-m-Y') ?? '-';
    $checkOutDate = $booking->tenant_check_out_date?->format('d-m-Y') ?? '-';
    $checkInTime = $booking->check_in_time ? \Illuminate\Support\Carbon::parse($booking->check_in_time)->format('H:i') : '15:00';
    $checkOutTime = $booking->check_out_time ? \Illuminate\Support\Carbon::parse($booking->check_out_time)->format('H:i') : '11:00';
    $reservationFee = (float) $booking->rent_amount + (float) $booking->vat_amount;
    $tourismFee = (float) $booking->dtcm_fee;
    $checkoutCleaning = (float) $booking->cleaning_fee;
    $securityDeposit = (float) $booking->deposit_amount;
    $agencyCommission = (float) $booking->agency_fee;
    $additionalService = max(0, (float) $booking->total_amount - ($reservationFee + $tourismFee + $checkoutCleaning + $securityDeposit + $agencyCommission));
    $totalAmount = (float) $booking->total_amount;
    $signatureName = $booking->confirmation_signed_by ?: $tenant?->full_name;
    $signatureDate = $booking->confirmation_signed_at?->format('d-m-Y') ?? $bookingDate;
    $property = trim(($unit?->unit_no ? $unit->unit_no.' - ' : '').($building?->name ?? 'Pattern Vacation Homes'));
    $roomLabel = $unit?->bedrooms ? $unit->bedrooms.'-Bedroom' : 'Not added';
    $floorLabel = $unit?->floor ? $unit->floor.' Floor' : 'Not added';
    $typeLabel = $unit?->unit_type ?: str($booking->booking_type ?: 'Apartment')->replace('_', ' ')->headline();
@endphp

<div class="footer">
    <div class="footer-address">Office 413, B. O. Box 1327, Al Attar Business Centre, Al Barsha, Dubai, United Arab Emirates</div>
    <table class="footer-row">
        <tr>
            <td>pattern.ae</td>
            <td class="footer-center">customerservice@pattern.ae</td>
            <td class="footer-right">+971 (4) 329 9693</td>
        </tr>
    </table>
</div>

<div class="page">
    <table>
        <tr>
            <td style="width: 32%;">@if($logo)<img src="{{ $logo }}" class="brand-logo">@else <strong>PATTERN</strong> @endif</td>
            <td style="width: 43%;"><div class="header-title">Booking Confirmation</div></td>
            <td style="width: 25%;" class="ref-box">
                Ref no. {{ $booking->booking_no }}<br>
                Date: {{ $bookingDate }}
            </td>
        </tr>
    </table>

    <table class="content">
        <tr>
            <td class="main-col">
                <div class="section-title">Guest's Details</div>
                <table class="info-table">
                    <tr><td class="info-label">Guest Name</td><td class="info-value">{{ $tenant?->full_name ?? 'Not assigned' }}</td></tr>
                    <tr><td class="info-label">Contact no</td><td class="info-value">{{ $tenant?->mobile_no ?: 'Not added' }}</td></tr>
                    <tr><td class="info-label">Email Address</td><td class="info-value">{{ $tenant?->email ?: 'Not added' }}</td></tr>
                </table>

                <div class="section-spacer"></div>
                <div class="section-title">Property Information</div>
                <table class="info-table">
                    <tr><td class="info-label">Property</td><td class="info-value">{{ $property }}</td></tr>
                    <tr><td class="info-label">Type</td><td class="info-value">{{ $typeLabel }}</td></tr>
                    <tr><td class="info-label">Floor No</td><td class="info-value">{{ $floorLabel }}</td></tr>
                    <tr><td class="info-label">No. Room</td><td class="info-value">{{ $roomLabel }}</td></tr>
                    <tr><td class="info-label">Community</td><td class="info-value">{{ $building?->area ?: $building?->address ?: 'Dubai' }}</td></tr>
                </table>

                <div class="section-spacer"></div>
                <div class="section-title">Reservation Details</div>
                <table class="info-table">
                    <tr><td class="info-label">Check-in date</td><td class="info-value">{{ $checkInDate }}</td></tr>
                    <tr><td class="info-label">Check-in time</td><td class="info-value">{{ $checkInTime }}</td></tr>
                    <tr><td class="info-label">Check-out date</td><td class="info-value">{{ $checkOutDate }}</td></tr>
                    <tr><td class="info-label">Check-out time</td><td class="info-value">{{ $checkOutTime }}</td></tr>
                </table>

                <div class="section-spacer"></div>
                <div class="section-title">Fees & Charges</div>
                <table class="fee-table">
                    <tr><td class="fee-label">Reservation Fee</td><td class="fee-value">{{ number_format($reservationFee, 2) }}</td></tr>
                    <tr><td class="fee-label">Housekeeping</td><td class="fee-value">collected from deposit</td></tr>
                    <tr><td class="fee-label">Tourism Fee</td><td class="fee-value">{{ number_format($tourismFee, 2) }}</td></tr>
                    <tr><td class="fee-label"><em>Check out cleaning</em></td><td class="fee-value">{{ number_format($checkoutCleaning, 2) }}</td></tr>
                    <tr><td class="fee-label">Security Deposit</td><td class="fee-value">{{ number_format($securityDeposit, 2) }}</td></tr>
                    <tr><td class="fee-label">Agency Commission</td><td class="fee-value">{{ number_format($agencyCommission, 2) }}</td></tr>
                    <tr><td class="fee-label">Additional Service</td><td class="fee-value">{{ number_format($additionalService, 2) }}</td></tr>
                    <tr class="fee-total"><td></td><td class="fee-value">Total {{ number_format($totalAmount, 2) }}</td></tr>
                </table>
            </td>

            <td class="side-col">
                <div class="side-title">Additional Info</div>
                <div class="side-block">
                    <div class="side-label">Utilities Cap</div>
                    <div class="side-strong">AED 500 / Month</div>
                    <div class="side-muted">- Electricity & water<br>- A/C &nbsp;&nbsp;- Gas</div>
                </div>
                <div class="side-spacer-sm"></div>

                <div class="side-title">Required Documents</div>
                <div class="side-block">
                    <ul class="doc-list">
                        <li>Valid ID Document<br><span class="side-muted">Passport / Emirates ID</span></li>
                    </ul>
                </div>
                <div class="side-spacer-md"></div>

                <div class="side-spacer-lg"></div>

                <div class="side-title" style="margin-top: 26px;">Customer Service</div>
                <div style="border-top: 1px solid #d9dede; padding-top: 8px; font-size: 10.5px; line-height: 1.8;">
                    +971 (4) 329 9693<br>
                    customerservice@pattern.ae
                </div>

                <div style="height: 84px;"></div>
                <div class="prepared">Prepared By Pattern Vacation Homes</div>
                <div class="prepared-mark">PATTERN VACATION HOMES<br>DUBAI - U.A.E</div>
            </td>
        </tr>
    </table>

    <div class="signature-area">
        <div class="signature-title">SIGNATURE</div>
        <div class="signature-copy">By signing this I certify that I have read and accepted the <strong><u>Terms & Conditions</u></strong> for my reservation</div>
        <div class="signature-line">
            @if($booking->confirmation_signature_data)
                <img src="{{ $booking->confirmation_signature_data }}" class="signature-img">
            @else
                Signature
            @endif
        </div>
        <div class="signature-name">{{ $signatureName ?: 'Guest Signature Pending' }}</div>
        <div class="signature-date">{{ $booking->confirmation_signed_at ? $signatureDate : 'Electronic signature pending' }}</div>
    </div>
</div>
</body>
</html>
