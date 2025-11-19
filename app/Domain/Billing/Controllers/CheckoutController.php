<?php

namespace App\Domain\Billing\Controllers;

use App\Domain\Billing\Services\CreditService;
use App\Domain\Billing\Services\PlanService;
use App\Domain\Users\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Checkout\Session;
use Stripe\StripeClient;

class CheckoutController extends Controller
{
    public function __construct(
        protected PlanService $planService,
        protected CreditService $creditService,
        protected StripeClient $stripe
    ) {
    }

    /**
     * Handle successful checkout redirect.
     */
    public function success(Request $request)
    {
        $sessionId = $request->query('session_id');

        if (! $sessionId) {
            return redirect()->route('dashboard')->withErrors(['checkout' => 'Invalid checkout session.']);
        }

        try {
            $session = $this->stripe->checkout->sessions->retrieve($sessionId);
            $user = User::find($session->metadata->user_id ?? null);

            if (! $user) {
                return redirect()->route('dashboard')->withErrors(['checkout' => 'User not found.']);
            }

            // Handle subscription checkout
            if ($session->metadata->type === 'subscription' && isset($session->metadata->plan_key)) {
                $plan = $this->planService->getPlan($session->metadata->plan_key);
                if ($plan) {
                    $user->update(['current_plan_key' => $plan['key']]);
                }
            }

            // Handle credit pack checkout
            if ($session->metadata->type === 'credit_pack' && isset($session->metadata->credits)) {
                $credits = (int) $session->metadata->credits;
                $user->addCredits($credits, 'purchase', "Purchased {$credits} credits via Stripe");
            }

            return redirect()->route('dashboard')->with('success', 'Payment successful!');
        } catch (\Exception $e) {
            Log::error('Checkout success error', [
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('dashboard')->withErrors(['checkout' => 'Failed to process checkout.']);
        }
    }

    /**
     * Handle canceled checkout redirect.
     */
    public function cancel()
    {
        return redirect()->route('dashboard')->with('info', 'Checkout was canceled.');
    }
}

