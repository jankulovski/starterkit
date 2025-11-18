<?php

namespace App\Domain\Auth\Actions;

use App\Domain\Auth\Models\MagicLinkToken;
use App\Domain\Users\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

class SendMagicLink
{
    /**
     * Send a magic link to the given email address.
     *
     * Auto-creates user if they don't exist (auto-create strategy).
     */
    public function execute(string $email): void
    {
        // Find user by email
        $user = User::where('email', $email)->first();
        
        if (! $user) {
            $user = User::create([
                'name' => $email,
                'email' => $email,
                'password' => Hash::make(uniqid('', true)), // Random password since we don't use passwords
            ]);
        }

        // Check if user is suspended
        if ($user->isSuspended()) {
            // Don't reveal that the account is suspended to prevent enumeration
            // Just silently fail - the user will see the same success message
            return;
        }

        $activeEmail = $user->email;

        // Generate magic link token
        $tokenData = MagicLinkToken::generate($activeEmail);
        $rawToken = $tokenData['token'];

        // Generate signed URL with token as query parameter
        $url = URL::temporarySignedRoute(
            'auth.magic-link.verify',
            now()->addMinutes(15),
            ['token' => $rawToken]
        );

        // Send email to active email address
        Mail::send('emails.magic-link', [
            'url' => $url,
            'email' => $activeEmail,
        ], function ($message) use ($activeEmail) {
            $message->to($activeEmail)
                ->subject('Your Login Link');
        });
    }
}

