<?php

namespace App\Domain\Auth\Controllers;

use App\Domain\Auth\Actions\SendMagicLink;
use App\Domain\Auth\Actions\VerifyMagicLink;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class MagicLinkController extends Controller
{
    /**
     * Request a magic link for the given email.
     */
    public function request(Request $request, SendMagicLink $sendMagicLink): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email', 'max:255'],
        ]);

        if ($validator->fails()) {
            return redirect('/login')
                ->withErrors($validator)
                ->withInput();
        }

        // Rate limiting
        $key = 'magic-link:'.$request->email.':'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'email' => 'Too many login attempts. Please try again later.',
            ]);
        }

        RateLimiter::hit($key, 60); // 5 attempts per minute

        try {
            $sendMagicLink->execute($request->email);

            // Always show success message to prevent email enumeration
            // Note: Users are auto-created if they don't exist
            return redirect('/login')->with('status', 'We sent a login link to your email. Please check your inbox.');
        } catch (\Exception $e) {
            // Still show success to prevent enumeration
            // Note: Users are auto-created if they don't exist
            return redirect('/login')->with('status', 'We sent a login link to your email. Please check your inbox.');
        }
    }

    /**
     * Verify a magic link token and log the user in.
     */
    public function verify(Request $request, VerifyMagicLink $verifyMagicLink): RedirectResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
        ]);

        try {
            $user = $verifyMagicLink->execute($request->token);

            Auth::login($user, true); // Remember user

            return redirect()->intended('/dashboard');
        } catch (\RuntimeException $e) {
            return redirect('/login')->with('error', $e->getMessage());
        } catch (\Exception $e) {
            return redirect('/login')->with('error', 'Invalid or expired login link. Please request a new one.');
        }
    }
}

