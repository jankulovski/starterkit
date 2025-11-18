<?php

namespace App\Domain\Auth\Controllers;

use App\Domain\Auth\Actions\ResolveUserFromGoogle;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

class GoogleOAuthController extends Controller
{
    /**
     * Redirect the user to Google OAuth provider.
     */
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')
            ->scopes(['openid', 'profile', 'email'])
            ->redirect();
    }

    /**
     * Handle the callback from Google OAuth provider.
     */
    public function callback(ResolveUserFromGoogle $resolveUser): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();

            $user = $resolveUser->execute($googleUser);

            Auth::login($user, true); // Remember user

            return redirect()->intended('/dashboard');
        } catch (\RuntimeException $e) {
            Log::warning('Google OAuth error', [
                'error' => $e->getMessage(),
            ]);

            return redirect('/login')->with('error', $e->getMessage());
        } catch (\Exception $e) {
            Log::error('Google OAuth unexpected error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect('/login')->with('error', 'Authentication failed. Please try again.');
        }
    }
}

