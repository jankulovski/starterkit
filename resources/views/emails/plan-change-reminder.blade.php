<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Plan Change Scheduled</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background-color: #f9fafb; padding: 30px; border-radius: 8px;">
        <h1 style="color: #f59e0b; margin-top: 0;">📅 Plan Change Scheduled</h1>

        <p>Hi {{ $userName }},</p>

        <p>This is a reminder that your subscription plan will change soon.</p>

        <div style="background-color: #ffffff; padding: 20px; border-radius: 6px; border-left: 4px solid #f59e0b; margin: 20px 0;">
            <p style="margin: 0 0 10px 0; color: #111827;">
                <strong>Scheduled Change</strong>
            </p>
            <p style="margin: 0; color: #6b7280; font-size: 14px;">
                <strong>Current Plan:</strong> {{ $currentPlanName }}<br>
                <strong>New Plan:</strong> {{ $newPlanName }}<br>
                <strong>Effective Date:</strong> {{ $scheduledDate }}<br>
                <strong>Days Until Change:</strong> {{ $daysUntilChange }} days
            </p>
        </div>

        @if($isDowngrade)
            <div style="background-color: #fef2f2; padding: 15px; border-radius: 6px; margin: 20px 0;">
                <p style="margin: 0; color: #991b1b; font-size: 14px;">
                    <strong>⚠️ Note:</strong> When your plan changes, your credit allocation will adjust from {{ $currentCredits }} to {{ $newCredits }} credits per month.
                </p>
            </div>
        @else
            <p style="color: #6b7280; font-size: 14px;">
                Your credit allocation will change from {{ $currentCredits }} to {{ $newCredits }} credits per month.
            </p>
        @endif

        <p style="color: #6b7280; font-size: 14px;">
            You'll keep your current plan features until the scheduled date. If you'd like to make changes, visit your billing settings.
        </p>

        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ $billingUrl }}" style="display: inline-block; background-color: #2563eb; color: #ffffff; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: 500;">Manage Subscription</a>
        </div>

        <p style="color: #6b7280; font-size: 14px; margin-top: 30px;">
            Questions about your plan change? Contact our support team.
        </p>
    </div>
</body>
</html>
