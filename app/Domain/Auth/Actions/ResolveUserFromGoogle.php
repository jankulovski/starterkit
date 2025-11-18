<?php

namespace App\Domain\Auth\Actions;

use App\Domain\Users\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Contracts\User as SocialiteUser;

class ResolveUserFromGoogle
{
    /**
     * Resolve or create a user from Google OAuth data.
     *
     * @return User
     */
    public function execute(SocialiteUser $googleUser): User
    {
        $email = $googleUser->getEmail();

        if (! $email) {
            throw new \RuntimeException('Google account does not have an email address.');
        }

        // Find existing user by email
        $user = User::where('email', $email)->first();

        if ($user) {
            // Update name if available and different
            if ($googleUser->getName() && $user->name !== $googleUser->getName()) {
                $user->update(['name' => $googleUser->getName()]);
            }

            // Check if user is suspended
            if ($user->isSuspended()) {
                throw new \RuntimeException('Your account has been suspended. Please contact support.');
            }

            return $user;
        }

        // Create new user (auto-create strategy)
        return User::create([
            'name' => $googleUser->getName() ?? $email,
            'email' => $email,
            'password' => Hash::make(uniqid('', true)), // Random password since we don't use passwords
            'email_verified_at' => now(), // Google emails are verified
        ]);
    }
}

