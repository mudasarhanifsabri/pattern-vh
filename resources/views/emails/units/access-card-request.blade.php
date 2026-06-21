<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $cardType }} {{ $requestType }}</title>
</head>
<body style="margin:0;background:#f3f6fb;font-family:Arial,sans-serif;color:#071a3b;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f3f6fb;padding:24px;">
        <tr>
            <td align="center">
                <table role="presentation" width="640" cellspacing="0" cellpadding="0" style="max-width:640px;background:#ffffff;border-radius:22px;overflow:hidden;border:1px solid #dfe7f1;">
                    <tr>
                        <td style="background:#061a38;color:#ffffff;padding:24px 28px;">
                            <div style="font-size:12px;letter-spacing:2px;text-transform:uppercase;color:#93c5fd;">Pattern Vacation Homes Rental</div>
                            <h1 style="margin:10px 0 0;font-size:24px;">{{ $cardType }} {{ $requestType }}</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:28px;">
                            <p style="margin:0 0 18px;color:#516384;">Dear Security Team,</p>
                            <p style="margin:0 0 24px;color:#516384;line-height:1.6;">
                                Please process the following {{ strtolower($cardType) }} request for the apartment below. Required supporting documents are attached when available in the ERP record.
                            </p>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;">
                                <tr><td style="padding:10px 0;color:#64748b;">Building</td><td style="padding:10px 0;font-weight:700;">{{ $unit->building->name }}</td></tr>
                                <tr><td style="padding:10px 0;color:#64748b;">Unit</td><td style="padding:10px 0;font-weight:700;">{{ $unit->unit_no }}</td></tr>
                                <tr><td style="padding:10px 0;color:#64748b;">Request type</td><td style="padding:10px 0;font-weight:700;">{{ $requestType }}</td></tr>
                                <tr><td style="padding:10px 0;color:#64748b;">Card type</td><td style="padding:10px 0;font-weight:700;">{{ $cardType }}</td></tr>
                                <tr><td style="padding:10px 0;color:#64748b;">Parking no.</td><td style="padding:10px 0;font-weight:700;">{{ $unit->parking_no ?: 'Not available' }}</td></tr>
                            </table>

                            <h2 style="margin:24px 0 10px;font-size:16px;">Owners</h2>
                            <ul style="margin:0 0 20px;padding-left:20px;color:#516384;line-height:1.6;">
                                @forelse ($unit->owners as $owner)
                                    <li>{{ $owner->full_name }}{{ $owner->mobile_no ? ' - '.$owner->mobile_no : '' }}{{ $owner->identity_no ? ' - ID '.$owner->identity_no : '' }}</li>
                                @empty
                                    <li>No owner attached in ERP.</li>
                                @endforelse
                            </ul>

                            @if ($notes)
                                <div style="border-radius:16px;background:#eff6ff;padding:16px;color:#1e3a8a;">
                                    <strong>Notes:</strong><br>
                                    {{ $notes }}
                                </div>
                            @endif

                            <p style="margin:24px 0 0;color:#516384;line-height:1.6;">
                                Regards,<br>
                                Pattern Operations Team
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
