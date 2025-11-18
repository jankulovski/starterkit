<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verify Your New Email Address</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background-color: #f9fafb; padding: 30px; border-radius: 8px;">
        <h1 style="color: #111827; margin-top: 0;">Verify Your New Email Address</h1>
        
        <p>You requested to change your email address to: <strong>{{ $email }}</strong></p>
        
        <p>Click the button below to verify this email address and complete the change. This link will expire in 24 hours.</p>
        
        <div style="text-align: center; margin: 30px 0;">
            <a href="{!! $url !!}" style="display: inline-block; background-color: #2563eb; color: #ffffff; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: 500;">Verify Email Address</a>
        </div>
        
        <p style="color: #6b7280; font-size: 14px; margin-top: 30px;">
            If you didn't request this change, you can safely ignore this email. Your email address will not be changed.
        </p>
        
        <p style="color: #6b7280; font-size: 14px;">
            If the button doesn't work, copy and paste this link into your browser:<br>
            <a href="{!! $url !!}" style="color: #2563eb; word-break: break-all;">{!! $url !!}</a>
        </p>
    </div>
</body>
</html>

