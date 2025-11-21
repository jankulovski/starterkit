<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Payment Failed</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background-color: #f9fafb; padding: 30px; border-radius: 8px;">
        <h1 style="color: #dc2626; margin-top: 0;">Payment Failed</h1>

        <p>Hi {{ $userName }},</p>

        <p>We were unable to process your payment for your <strong>{{ $planName }}</strong> subscription.</p>

        @if($attemptNumber > 1)
            <p style="color: #dc2626;">
                This is attempt <strong>{{ $attemptNumber }}</strong> of 4. We'll automatically retry charging your card.
            </p>
        @endif

        <p>To avoid service interruption, please update your payment method:</p>

        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ $updatePaymentUrl }}" style="display: inline-block; background-color: #dc2626; color: #ffffff; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: 500;">Update Payment Method</a>
        </div>

        <p style="color: #6b7280; font-size: 14px;">
            <strong>What happens next?</strong><br>
            We'll automatically retry charging your payment method. If all retries fail, your subscription will be canceled and you'll be downgraded to the Free plan.
        </p>

        <p style="color: #6b7280; font-size: 14px; margin-top: 30px;">
            If you have any questions, please contact our support team.
        </p>

        <p style="color: #6b7280; font-size: 14px;">
            If the button doesn't work, copy and paste this link into your browser:<br>
            <a href="{{ $updatePaymentUrl }}" style="color: #2563eb; word-break: break-all;">{{ $updatePaymentUrl }}</a>
        </p>
    </div>
</body>
</html>
