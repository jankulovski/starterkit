<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Action Required: Update Payment Method</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background-color: #fef2f2; padding: 30px; border-radius: 8px; border: 2px solid #dc2626;">
        <h1 style="color: #dc2626; margin-top: 0;">⚠️ Action Required: Update Payment Method</h1>

        <p>Hi {{ $userName }},</p>

        <p style="color: #dc2626; font-weight: 600;">Your <strong>{{ $planName }}</strong> subscription payment is past due.</p>

        <p>We've attempted to charge your payment method multiple times, but all attempts have failed. To avoid losing access to your subscription features, please update your payment information immediately.</p>

        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ $updatePaymentUrl }}" style="display: inline-block; background-color: #dc2626; color: #ffffff; padding: 14px 28px; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 16px;">Update Payment Method Now</a>
        </div>

        <div style="background-color: #ffffff; padding: 20px; border-radius: 6px; border-left: 4px solid #dc2626; margin: 20px 0;">
            <p style="margin: 0; color: #111827;">
                <strong>What will happen if I don't update my payment method?</strong><br>
                <span style="color: #6b7280;">Your subscription will be canceled and you'll be downgraded to the Free plan. You'll lose access to all premium features.</span>
            </p>
        </div>

        <p style="color: #6b7280; font-size: 14px; margin-top: 30px;">
            If you believe this is an error or need assistance, please contact our support team immediately.
        </p>

        <p style="color: #6b7280; font-size: 14px;">
            If the button doesn't work, copy and paste this link into your browser:<br>
            <a href="{{ $updatePaymentUrl }}" style="color: #2563eb; word-break: break-all;">{{ $updatePaymentUrl }}</a>
        </p>
    </div>
</body>
</html>
