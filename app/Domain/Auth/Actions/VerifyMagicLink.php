<?php

namespace App\Domain\Auth\Actions;

use App\Domain\Auth\Models\MagicLinkToken;
use App\Domain\Users\Models\User;
use Illuminate\Support\Facades\Hash;

class VerifyMagicLink
{
    /**
     * Verify a magic link token and return the user.
     *
     * @throws \RuntimeException
     */
    public function execute(string $rawToken): User
    {
        $hashedToken = hash('sha256', $rawToken);

        $magicLinkToken = MagicLinkToken::where('token', $hashedToken)->first();

        if (! $magicLinkToken) {
            throw new \RuntimeException('Invalid login link.');
        }

        if (! $magicLinkToken->isValid()) {
            throw new \RuntimeException('This login link has expired or has already been used.');
        }

        // Find user
        $user = User::where('email', $magicLinkToken->email)->first();

        if (! $user) {
            // This shouldn't happen if auto-create is working, but handle it gracefully
            throw new \RuntimeException('User account not found.');
        }

        // Check if user is suspended
        if ($user->isSuspended()) {
            throw new \RuntimeException('Your account has been suspended. Please contact support.');
        }

        // Mark token as used
        $magicLinkToken->markAsUsed();

        // Mark email as verified when user logs in via magic link
        if (is_null($user->email_verified_at)) {
            $user->email_verified_at = now();
            $user->save();
        }

        return $user;
    }
}

