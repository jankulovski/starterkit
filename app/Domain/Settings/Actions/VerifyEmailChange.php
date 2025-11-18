<?php

namespace App\Domain\Settings\Actions;

use App\Domain\Auth\Models\MagicLinkToken;
use App\Domain\Users\Models\User;
use Illuminate\Support\Facades\Hash;

class VerifyEmailChange
{
    /**
     * Verify and complete email change.
     *
     * @throws \RuntimeException
     */
    public function execute(User $user, string $token): void
    {
        if (! $user->hasPendingEmailChange()) {
            throw new \RuntimeException('No pending email change found.');
        }

        if ($user->isPendingEmailVerificationExpired()) {
            throw new \RuntimeException('Email verification link has expired. Please request a new one.');
        }

        if (! Hash::check($token, $user->pending_email_verification_token)) {
            throw new \RuntimeException('Invalid verification token.');
        }

        $oldEmail = $user->email;
        $newEmail = $user->pending_email;

        // Invalidate all magic link tokens for the old email
        MagicLinkToken::where('email', $oldEmail)->delete();

        // Update email
        $user->email = $newEmail;
        $user->email_verified_at = now();
        $user->pending_email = null;
        $user->pending_email_verification_token = null;
        $user->pending_email_verification_sent_at = null;
        $user->save();
    }
}

