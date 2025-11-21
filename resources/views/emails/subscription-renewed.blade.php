<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Subscription Renewed</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background-color: #f9fafb; padding: 30px; border-radius: 8px;">
        <h1 style="color: #10b981; margin-top: 0;">✓ Subscription Renewed</h1>

        <p>Hi {{ $userName }},</p>

        <p>Your <strong>{{ $planName }}</strong> subscription has been successfully renewed.</p>

        <div style="background-color: #ffffff; padding: 20px; border-radius: 6px; border-left: 4px solid #10b981; margin: 20px 0;">
            <p style="margin: 0 0 10px 0; color: #111827;">
                <strong>Renewal Details</strong>
            </p>
            <p style="margin: 0; color: #6b7280; font-size: 14px;">
                <strong>Plan:</strong> {{ $planName }}<br>
                <strong>Credits Added:</strong> {{ $creditsAllocated }} credits<br>
                <strong>Next Billing Date:</strong> {{ $nextBillingDate }}
            </p>
        </div>

        <p style="color: #6b7280; font-size: 14px;">
            Your monthly credits have been added to your account and are ready to use.
        </p>

        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ $dashboardUrl }}" style="display: inline-block; background-color: #2563eb; color: #ffffff; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: 500;">Go to Dashboard</a>
        </div>

        <p style="color: #6b7280; font-size: 14px; margin-top: 30px;">
            If you have any questions about your subscription, please contact our support team.
        </p>
    </div>
</body>
</html>
