<?php

namespace App\Domain\Settings\Controllers;

use App\Domain\Auth\Models\MagicLinkToken;
use App\Domain\Settings\Actions\CancelEmailChange;
use App\Domain\Settings\Actions\VerifyEmailChange;
use App\Domain\Settings\Requests\ProfileUpdateRequest;
use App\Domain\Users\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Show the user's profile settings page.
     */
    public function edit(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('domains/settings/pages/profile', [
            'mustVerifyEmail' => $user instanceof MustVerifyEmail,
            'hasPendingEmailChange' => $user->hasPendingEmailChange(),
            'pendingEmail' => $user->pending_email,
            'status' => $request->session()->get('status'),
            'error' => $request->session()->get('error'),
        ]);
    }

    /**
     * Update the user's profile settings.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        // If email is being changed
        if (isset($validated['email']) && $validated['email'] !== $user->email) {
            // Check if there's already a pending change
            if ($user->hasPendingEmailChange()) {
                return back()->withErrors([
                    'email' => 'You already have a pending email change. Please verify or cancel it first.',
                ]);
            }

            // Store old email for notification
            $oldEmail = $user->email;
            $newEmail = $validated['email'];

            // Generate verification token
            $token = Str::random(64);
            $hashedToken = Hash::make($token);

            // Store pending email
            $user->pending_email = $newEmail;
            $user->pending_email_verification_token = $hashedToken;
            $user->pending_email_verification_sent_at = now();
            $user->email_verified_at = null; // Require re-verification
            $user->save();

            // Invalidate all magic link tokens for old email
            MagicLinkToken::where('email', $oldEmail)->delete();

            // Send verification email to new address
            Mail::send('emails.verify-email-change', [
                'url' => URL::temporarySignedRoute(
                    'settings.email.verify',
                    now()->addHours(24),
                    ['token' => $token]
                ),
                'email' => $newEmail,
            ], function ($message) use ($newEmail) {
                $message->to($newEmail)
                    ->subject('Verify Your New Email Address');
            });

            // Send notification to old email
            Mail::send('emails.email-change-notification', [
                'cancelUrl' => URL::temporarySignedRoute(
                    'settings.email.cancel',
                    now()->addHours(24),
                    ['token' => $token]
                ),
                'newEmail' => $newEmail,
                'oldEmail' => $oldEmail,
            ], function ($message) use ($oldEmail) {
                $message->to($oldEmail)
                    ->subject('Email Change Requested');
            });

            return back()->with('status', 'Verification email sent to your new email address. Please check your inbox.');
        }

        // For non-email changes, update normally
        $user->fill($validated);
        $user->save();

        return to_route('profile.edit');
    }

    /**
     * Verify email change.
     */
    public function verifyEmailChange(Request $request, VerifyEmailChange $verifyEmailChange): RedirectResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
        ]);

        try {
            $verifyEmailChange->execute($request->user(), $request->token);

            return to_route('profile.edit')->with('status', 'Email address verified and updated successfully.');
        } catch (\RuntimeException $e) {
            return to_route('profile.edit')->with('error', $e->getMessage());
        }
    }

    /**
     * Cancel email change.
     * This route works without authentication - user is identified by token.
     */
    public function cancelEmailChange(Request $request, CancelEmailChange $cancelEmailChange): RedirectResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
        ]);

        // Find user by token (works without authentication)
        $user = User::whereNotNull('pending_email_verification_token')
            ->get()
            ->first(function ($u) use ($request) {
                return Hash::check($request->token, $u->pending_email_verification_token);
            });

        if (! $user || ! $user->hasPendingEmailChange()) {
            return redirect('/login')->with('error', 'Invalid or expired cancellation link.');
        }

        $cancelEmailChange->execute($user);

        // If user is authenticated, redirect to profile, otherwise to login
        if ($request->user()) {
            return to_route('profile.edit')->with('status', 'Email change cancelled.');
        }

        return redirect('/login')->with('status', 'Email change cancelled. Please log in to continue.');
    }

    /**
     * Resend email verification.
     */
    public function resendEmailVerification(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user->hasPendingEmailChange()) {
            return back()->with('error', 'No pending email change found.');
        }

        // Can only resend if expired or after 5 minutes
        $lastSent = $user->pending_email_verification_sent_at;
        if ($lastSent && $lastSent->copy()->addMinutes(5)->isFuture()) {
            return back()->with('error', 'Please wait before requesting another verification email.');
        }

        // Regenerate token
        $token = Str::random(64);
        $user->pending_email_verification_token = Hash::make($token);
        $user->pending_email_verification_sent_at = now();
        $user->save();

        // Resend verification email
        Mail::send('emails.verify-email-change', [
            'url' => URL::temporarySignedRoute(
                'settings.email.verify',
                now()->addHours(24),
                ['token' => $token]
            ),
            'email' => $user->pending_email,
        ], function ($message) use ($user) {
            $message->to($user->pending_email)
                ->subject('Verify Your New Email Address');
        });

        return back()->with('status', 'Verification email resent.');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}

