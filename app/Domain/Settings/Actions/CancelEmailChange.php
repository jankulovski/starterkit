<?php

namespace App\Domain\Settings\Actions;

use App\Domain\Users\Models\User;

class CancelEmailChange
{
    /**
     * Cancel pending email change.
     */
    public function execute(User $user): void
    {
        if (! $user->hasPendingEmailChange()) {
            return;
        }

        // Clear pending email fields
        $user->pending_email = null;
        $user->pending_email_verification_token = null;
        $user->pending_email_verification_sent_at = null;
        $user->save();
    }
}

