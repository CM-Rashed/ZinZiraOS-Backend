<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verification Code</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f4f6f9; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; -webkit-font-smoothing: antialiased;">
    <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0" style="background-color: #f4f6f9; padding: 40px 10px;">
        <tr>
            <td align="center">
                <!-- Main Container -->
                <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0" style="max-width: 520px; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05); border: 1px solid #e5e7eb;">
                    
                    <!-- Header Banner -->
                    <tr>
                        <td align="center" style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); padding: 32px 20px; text-align: center;">
                            <h1 style="margin: 0; font-size: 24px; font-weight: 700; color: #ffffff; letter-spacing: -0.5px; text-transform: uppercase;">
                                {{ config('app.name', 'Zinzira') }}
                            </h1>
                            <p style="margin: 6px 0 0 0; font-size: 13px; color: #94a3b8; font-weight: 500; letter-spacing: 0.5px;">
                                SECURITY VERIFICATION
                            </p>
                        </td>
                    </tr>

                    <!-- Body Content -->
                    <tr>
                        <td style="padding: 40px 32px; text-align: center;">
                            <h2 style="margin: 0 0 12px 0; font-size: 20px; font-weight: 700; color: #0f172a;">
                                Verify Your Email
                            </h2>
                            <p style="margin: 0 0 28px 0; font-size: 14px; line-height: 22px; color: #64748b;">
                                Thank you for choosing <strong>{{ config('app.name', 'Zinzira') }}</strong>. Use the security code below to complete your setup.
                            </p>

                            <!-- OTP Box -->
                            <div style="background-color: #f8fafc; border: 2px dashed #cbd5e1; border-radius: 12px; padding: 20px 10px; margin: 0 auto 28px auto;">
                                <span style="font-family: 'Courier New', Courier, monospace; font-size: 36px; font-weight: 800; color: #2563eb; letter-spacing: 10px; display: inline-block; padding-left: 10px;">
                                    {{ $otp }}
                                </span>
                            </div>

                            <!-- Expiry Notice -->
                            <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td align="center">
                                        <div style="display: inline-block; background-color: #fef3c7; border-radius: 20px; padding: 6px 16px;">
                                            <p style="margin: 0; font-size: 12px; font-weight: 600; color: #d97706;">
                                                ⏱️ Code expires in <strong>10 minutes</strong>
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                            </table>

                            <hr style="border: none; border-top: 1px solid #f1f5f9; margin: 32px 0;">

                            <p style="margin: 0; font-size: 12px; line-height: 18px; color: #94a3b8;">
                                If you did not request this verification code, please ignore this email or contact support if you suspect unauthorized activity.
                            </p>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f8fafc; padding: 20px; text-align: center; border-top: 1px solid #f1f5f9;">
                            <p style="margin: 0; font-size: 12px; color: #94a3b8;">
                                &copy; {{ date('Y') }} {{ config('app.name', 'Zinzira') }}. All rights reserved.
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>