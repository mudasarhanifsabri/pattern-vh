<!DOCTYPE html>
<html lang="en">
<head><meta charset="utf-8"><title>Receipt</title></head>
<body style="margin:0;background:#f3f6fb;font-family:Arial,sans-serif;color:#071a3b;">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="padding:24px;background:#f3f6fb;"><tr><td align="center">
<table role="presentation" width="640" cellspacing="0" cellpadding="0" style="max-width:640px;background:#fff;border-radius:22px;overflow:hidden;border:1px solid #dfe7f1;">
<tr><td style="background:#061a38;color:#fff;padding:24px 28px;"><div style="font-size:12px;letter-spacing:2px;text-transform:uppercase;color:#93c5fd;">Pattern Vacation Homes Rental</div><h1 style="margin:10px 0 0;font-size:24px;">Payment receipt issued</h1></td></tr>
<tr><td style="padding:28px;">
@php($booking = $receipt->booking)
<p style="margin:0 0 16px;color:#516384;">Dear {{ $booking->tenant->full_name }},</p>
<p style="margin:0 0 20px;color:#516384;line-height:1.6;">Your payment has been received. Your check-in code is below. Please keep it ready for check-in.</p>
<div style="border-radius:18px;background:#eff6ff;padding:18px;text-align:center;"><div style="font-size:12px;letter-spacing:2px;text-transform:uppercase;color:#2563eb;">Check-in code</div><div style="font-size:30px;font-weight:800;margin-top:8px;">{{ $receipt->check_in_code }}</div></div>
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-top:22px;border-collapse:collapse;">
<tr><td style="padding:8px 0;color:#64748b;">Receipt</td><td style="padding:8px 0;font-weight:700;">{{ $receipt->receipt_no }}</td></tr>
<tr><td style="padding:8px 0;color:#64748b;">Booking</td><td style="padding:8px 0;font-weight:700;">{{ $booking->booking_no }}</td></tr>
<tr><td style="padding:8px 0;color:#64748b;">Unit</td><td style="padding:8px 0;font-weight:700;">{{ $booking->unit->building->name }} / {{ $booking->unit->unit_no }}</td></tr>
<tr><td style="padding:8px 0;color:#64748b;">Amount</td><td style="padding:8px 0;font-weight:700;">AED {{ number_format((float) $receipt->amount, 2) }}</td></tr>
</table>
<p style="margin:24px 0 0;color:#516384;">Regards,<br>Pattern Operations Team</p>
</td></tr></table>
</td></tr></table>
</body>
</html>
