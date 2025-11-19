<?php

namespace App\Domain\Billing\Controllers;

use App\Domain\Billing\Services\CreditService;
use App\Domain\Billing\Services\PlanService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Stripe\Checkout\Session;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

class BillingController extends Controller
{
    public function __construct(
        protected PlanService $planService,
        protected CreditService $creditService,
        protected StripeClient $stripe
    ) {
    }

    /**
     * Create a Stripe checkout session for subscription.
     */
    public function createCheckoutSession(Request $request)
    {
        $request->validate([
            'plan_key' => ['required', 'string'],
        ]);

        $user = $request->user();
        $plan = $this->planService->getPlan($request->plan_key);

        if (! $plan || $plan['type'] === 'free') {
            return back()->withErrors(['plan' => 'Invalid plan selected.']);
        }

        if (! $plan['stripe_price_id']) {
            return back()->withErrors(['plan' => 'Plan is not configured for checkout.']);
        }

        // Ensure user has a Stripe customer ID
        if (! $user->stripe_id) {
            $user->createAsStripeCustomer();
        }

        try {
            $checkoutSession = $this->stripe->checkout->sessions->create([
                'customer' => $user->stripe_id,
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price' => $plan['stripe_price_id'],
                    'quantity' => 1,
                ]],
                'mode' => 'subscription',
                'success_url' => route('billing.checkout.success').'?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('dashboard'),
                'metadata' => [
                    'user_id' => $user->id,
                    'plan_key' => $plan['key'],
                    'type' => 'subscription',
                ],
            ]);

            return redirect($checkoutSession->url);
        } catch (ApiErrorException $e) {
            return back()->withErrors(['stripe' => 'Failed to create checkout session: '.$e->getMessage()]);
        }
    }

    /**
     * Create a Stripe billing portal session.
     */
    public function createBillingPortalSession(Request $request)
    {
        $user = $request->user();

        if (! $user->stripe_id) {
            return back()->withErrors(['stripe' => 'No billing account found.']);
        }

        try {
            $portalSession = $this->stripe->billingPortal->sessions->create([
                'customer' => $user->stripe_id,
                'return_url' => route('dashboard'),
            ]);

            return redirect($portalSession->url);
        } catch (ApiErrorException $e) {
            return back()->withErrors(['stripe' => 'Failed to create billing portal session: '.$e->getMessage()]);
        }
    }

    /**
     * Cancel the current subscription.
     */
    public function cancelSubscription(Request $request)
    {
        $user = $request->user();

        if (! $user->subscribed()) {
            return back()->withErrors(['subscription' => 'No active subscription found.']);
        }

        try {
            $user->subscription()->cancel();

            // Update plan to free
            $user->update(['current_plan_key' => 'free']);

            return back()->with('success', 'Subscription canceled successfully. You will retain access until the end of your billing period.');
        } catch (\Exception $e) {
            return back()->withErrors(['subscription' => 'Failed to cancel subscription: '.$e->getMessage()]);
        }
    }

    /**
     * Purchase a credit pack.
     */
    public function purchaseCredits(Request $request)
    {
        $request->validate([
            'pack_key' => ['required', 'string'],
        ]);

        try {
            $checkoutSession = $this->creditService->createCreditPackCheckout($request->user(), $request->pack_key);

            return redirect($checkoutSession->url);
        } catch (\Exception $e) {
            return back()->withErrors(['credits' => 'Failed to create checkout session: '.$e->getMessage()]);
        }
    }
}

