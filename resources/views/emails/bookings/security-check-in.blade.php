@php($booking->loadMissing(['unit.building', 'tenant']))
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-family:Arial,sans-serif;background:#f4f7fb;padding:24px;color:#071a3b;">
    <tr>
        <td align="center">
            <table role="presentation" width="680" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:24px;overflow:hidden;border:1px solid #e2e8f0;">
                <tr>
                    <td style="background:#061a38;color:#ffffff;padding:24px 28px;">
                        <div style="font-size:12px;letter-spacing:2px;text-transform:uppercase;color:#93c5fd;">Pattern Vacation Homes Rental</div>
                        <h1 style="margin:10px 0 0;font-size:24px;">Confirmed booking check-in details</h1>
                    </td>
                </tr>
                <tr>
                    <td style="padding:28px;">
                        <p style="margin:0 0 18px;color:#475569;line-height:1.6;">Dear Security Team,</p>
                        <p style="margin:0 0 22px;color:#475569;line-height:1.6;">Please find the confirmed guest check-in details below. The booking confirmation PDF is attached, along with tenant and unit documents where available in the ERP.</p>

                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
                            <tr><td style="padding:10px 0;color:#64748b;">Building</td><td style="padding:10px 0;font-weight:700;">{{ $booking->unit->building->name }}</td></tr>
                            <tr><td style="padding:10px 0;color:#64748b;">Unit</td><td style="padding:10px 0;font-weight:700;">{{ $booking->unit->unit_no }}</td></tr>
                            <tr><td style="padding:10px 0;color:#64748b;">Tenant</td><td style="padding:10px 0;font-weight:700;">{{ $booking->tenant->full_name }}</td></tr>
                            <tr><td style="padding:10px 0;color:#64748b;">Mobile</td><td style="padding:10px 0;font-weight:700;">{{ $booking->tenant->mobile_no }}</td></tr>
                            <tr><td style="padding:10px 0;color:#64748b;">Identity</td><td style="padding:10px 0;font-weight:700;">{{ str($booking->tenant->identity_type)->replace('_', ' ')->headline() }} {{ $booking->tenant->identity_no }}</td></tr>
                            <tr><td style="padding:10px 0;color:#64748b;">Check-in</td><td style="padding:10px 0;font-weight:700;">{{ $booking->check_in_date?->format('M d, Y') }} {{ $booking->check_in_time }}</td></tr>
                            <tr><td style="padding:10px 0;color:#64748b;">Check-out</td><td style="padding:10px 0;font-weight:700;">{{ $booking->check_out_date?->format('M d, Y') }} {{ $booking->check_out_time }}</td></tr>
                            <tr><td style="padding:10px 0;color:#64748b;">Guests</td><td style="padding:10px 0;font-weight:700;">{{ $booking->guest_count }}</td></tr>
                        </table>

                        <div style="margin-top:22px;border-radius:18px;background:#eff6ff;padding:16px;color:#1e3a8a;">
                            Attached when available: booking confirmation PDF, tenant passport/Emirates ID, and DTCM unit permit.
                        </div>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
