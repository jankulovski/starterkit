<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Credits Added to Your Account</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background-color: #f9fafb; padding: 30px; border-radius: 8px;">
        <h1 style="color: #2563eb; margin-top: 0;">💎 Credits Added</h1>

        <p>Hi {{ $userName }},</p>

        <p><strong>{{ $creditsAmount }} credits</strong> have been added to your account!</p>

        <div style="background-color: #ffffff; padding: 20px; border-radius: 6px; border-left: 4px solid #2563eb; margin: 20px 0;">
            <p style="margin: 0 0 10px 0; color: #111827;">
                <strong>Transaction Details</strong>
            </p>
            <p style="margin: 0; color: #6b7280; font-size: 14px;">
                <strong>Credits Added:</strong> {{ $creditsAmount }}<br>
                <strong>Source:</strong> {{ $source }}<br>
                <strong>New Balance:</strong> {{ $newBalance }} credits
            </p>
        </div>

        @if($description)
            <p style="color: #6b7280; font-size: 14px;">
                {{ $description }}
            </p>
        @endif

        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ $dashboardUrl }}" style="display: inline-block; background-color: #2563eb; color: #ffffff; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: 500;">Start Using Credits</a>
        </div>

        <p style="color: #6b7280; font-size: 14px; margin-top: 30px;">
            Questions about credits? Contact our support team anytime.
        </p>
    </div>
</body>
</html>
