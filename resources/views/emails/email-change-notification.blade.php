<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Email Change Requested</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background-color: #f9fafb; padding: 30px; border-radius: 8px;">
        <h1 style="color: #111827; margin-top: 0;">Email Change Requested</h1>
        
        <p>A request has been made to change your email address from:</p>
        <p style="font-weight: bold;">{{ $oldEmail }}</p>
        
        <p>to:</p>
        <p style="font-weight: bold;">{{ $newEmail }}</p>
        
        <p style="color: #dc2626; font-weight: bold; margin-top: 30px;">
            If you did not request this change, click the button below immediately to cancel it.
        </p>
        
        <div style="text-align: center; margin: 30px 0;">
            <a href="{!! $cancelUrl !!}" style="display: inline-block; background-color: #dc2626; color: #ffffff; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: 500;">Cancel Email Change</a>
        </div>
        
        <p style="color: #6b7280; font-size: 14px; margin-top: 30px;">
            If you requested this change, you can safely ignore this email. The change will only be completed after you verify the new email address.
        </p>
        
        <p style="color: #6b7280; font-size: 14px;">
            If the button doesn't work, copy and paste this link into your browser:<br>
            <a href="{!! $cancelUrl !!}" style="color: #dc2626; word-break: break-all;">{!! $cancelUrl !!}</a>
        </p>
    </div>
</body>
</html>

