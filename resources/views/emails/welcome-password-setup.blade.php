<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Welcome to Pattern Vacation Homes</title>
</head>
<body style="margin:0;padding:0;background:#f4f6f8;font-family:Arial,sans-serif;color:#222;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f8;padding:30px 0;">
        <tr>
            <td align="center">
                <table width="620" cellpadding="0" cellspacing="0" style="width:620px;max-width:94%;background:#ffffff;border-radius:18px;overflow:hidden;box-shadow:0 8px 30px rgba(0,0,0,0.08);">
                    <tr>
                        <td style="background:#111;padding:28px;text-align:center;">
                            <img src="{{ asset('brand/pattern-logo.jpeg') }}" alt="Pattern Vacation Homes" style="display:block;margin:0 auto 16px;max-width:210px;height:auto;background:#ffffff;border-radius:12px;padding:10px;">
                            <h1 style="margin:0;color:#ffffff;font-size:26px;">Pattern Vacation Homes</h1>
                            <p style="margin:8px 0 0;color:#d6d6d6;font-size:14px;">Owner Portal Access</p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:35px;">
                            <h2 style="margin:0 0 15px;font-size:24px;color:#111;">Welcome, {{ $ownerName }}!</h2>

                            <p style="font-size:15px;line-height:1.7;margin:0 0 18px;">
                                We are delighted to welcome you as a valued property owner at
                                <strong>Pattern Vacation Homes</strong>.
                            </p>

                            <p style="font-size:15px;line-height:1.7;margin:0 0 25px;">
                                Your Owner Portal account has been created. You can now view your properties,
                                bookings, income, statements, and important updates online.
                            </p>

                            <table width="100%" cellpadding="0" cellspacing="0" style="background:#f8fafc;border-radius:14px;padding:20px;margin-bottom:25px;border:1px solid #e5e7eb;">
                                <tr>
                                    <td style="font-size:14px;color:#555;padding:8px 0;">Portal Link</td>
                                    <td style="font-size:14px;padding:8px 0;text-align:right;">
                                        <a href="{{ $loginUrl }}" style="color:#111;font-weight:bold;text-decoration:none;">
                                            Login Portal
                                        </a>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="font-size:14px;color:#555;padding:8px 0;">Email</td>
                                    <td style="font-size:14px;padding:8px 0;text-align:right;font-weight:bold;">{{ $ownerEmail }}</td>
                                </tr>
                                <tr>
                                    <td style="font-size:14px;color:#555;padding:8px 0;">Password Setup</td>
                                    <td style="font-size:14px;padding:8px 0;text-align:right;font-weight:bold;">
                                        <a href="{{ $setupUrl }}" style="color:#111;text-decoration:none;">Set your password</a>
                                    </td>
                                </tr>
                            </table>

                            <div style="text-align:center;margin:30px 0;">
                                <a href="{{ $setupUrl }}"
                                   style="background:#111;color:#ffffff;text-decoration:none;padding:14px 32px;border-radius:30px;font-size:15px;font-weight:bold;display:inline-block;">
                                    Access Owner Portal
                                </a>
                            </div>

                            <p style="font-size:14px;line-height:1.7;color:#555;margin:0 0 18px;">
                                For your security, please set your password using the secure link above before your first login.
                            </p>

                            <p style="font-size:14px;line-height:1.7;color:#555;margin:0;">
                                If you need any assistance, our team will be happy to help.
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="background:#f1f1f1;text-align:center;padding:20px;font-size:13px;color:#666;">
                            Thank you for trusting <strong>Pattern Vacation Homes</strong><br>
                            <a href="{{ config('app.url') }}" style="color:#666;text-decoration:none;">{{ config('app.url') }}</a>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
