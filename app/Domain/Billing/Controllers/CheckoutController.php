<?php

namespace App\Domain\Billing\Controllers;

use App\Domain\Billing\Models\ProcessedWebhookEvent;
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
     *
     * SECURITY CRITICAL: This method must verify payment completion before
     * allocating credits or updating subscriptions. Without verification,
     * users could access this URL without completing payment.
     */
    public function success(Request $request)
    {
        $sessionId = $request->query('session_id');

        if (! $sessionId) {
            return redirect()->route('dashboard')->withErrors(['checkout' => 'Invalid checkout session.']);
        }

        try {
            $session = $this->stripe->checkout->sessions->retrieve($sessionId);

            // IDEMPOTENCY CHECK: Prevent duplicate processing if user refreshes the success page
            // or if this races with the webhook handler
            if (ProcessedWebhookEvent::isProcessed($sessionId)) {
                Log::info('Checkout session already processed by webhook or previous request', [
                    'session_id' => $sessionId,
                ]);

                return redirect()->route('dashboard')->with('success', 'Payment already processed!');
            }

            // SECURITY: Verify payment was actually completed
            // A session exists even if the user cancels payment, so we must check status
            if ($session->payment_status !== 'paid') {
                Log::warning('Checkout success accessed without payment', [
                    'session_id' => $sessionId,
                    'payment_status' => $session->payment_status,
                    'user_id' => $session->metadata->user_id ?? null,
                ]);

                return redirect()->route('dashboard')->withErrors([
                    'checkout' => 'Payment was not completed. Please try again.',
                ]);
            }

            $user = User::find($session->metadata->user_id ?? null);

            if (! $user) {
                return redirect()->route('dashboard')->withErrors(['checkout' => 'User not found.']);
            }

            // SECURITY: Verify the session belongs to the authenticated user
            // Prevents users from processing someone else's checkout session
            if ($user->id !== $request->user()->id) {
                Log::error('Checkout session user mismatch', [
                    'session_id' => $sessionId,
                    'session_user_id' => $user->id,
                    'authenticated_user_id' => $request->user()->id,
                ]);

                return redirect()->route('dashboard')->withErrors([
                    'checkout' => 'Unauthorized access to checkout session.',
                ]);
            }

            // Handle subscription checkout
            // NOTE: Credits are allocated via webhook, not here, to prevent double allocation
            if ($session->metadata->type === 'subscription' && isset($session->metadata->plan_key)) {
                $plan = $this->planService->getPlan($session->metadata->plan_key);
                if ($plan) {
                    $user->update(['current_plan_key' => $plan['key']]);

                    Log::info('Subscription activated via checkout', [
                        'user_id' => $user->id,
                        'plan_key' => $plan['key'],
                        'session_id' => $sessionId,
                    ]);
                }
            }

            // Handle credit pack checkout
            if ($session->metadata->type === 'credit_pack' && isset($session->metadata->credits)) {
                $credits = (int) $session->metadata->credits;
                $user->addCredits($credits, 'purchase', "Purchased {$credits} credits via Stripe", [
                    'session_id' => $sessionId,
                    'amount_paid' => $session->amount_total,
                    'currency' => $session->currency,
                ]);

                Log::info('Credits purchased via checkout', [
                    'user_id' => $user->id,
                    'credits' => $credits,
                    'amount_paid' => $session->amount_total,
                    'session_id' => $sessionId,
                ]);
            }

            // Mark session as processed to prevent duplicate processing
            // This prevents race conditions with webhook handler and page refreshes
            ProcessedWebhookEvent::markAsProcessed(
                $sessionId,
                'checkout.session.completed',
                $user->id,
                null // Don't store full payload from checkout redirect for privacy
            );

            return redirect()->route('dashboard')->with('success', 'Payment successful!');
        } catch (\Exception $e) {
            Log::error('Checkout success error', [
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
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

