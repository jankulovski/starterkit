<?php

namespace App\Domain\Billing\Controllers;

use App\Domain\Billing\Services\CreditService;
use App\Domain\Billing\Services\PlanService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
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
        // SECURITY: Validate plan_key against allowed values to prevent injection
        $validPlanKeys = array_keys(config('plans.plans'));

        $request->validate([
            'plan_key' => ['required', 'string', Rule::in($validPlanKeys)],
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

            // Return JSON for Inertia requests (external redirects don't work with Inertia)
            if ($request->header('X-Inertia')) {
                return response()->json(['checkout_url' => $checkoutSession->url]);
            }

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

            // Return JSON for Inertia requests (external redirects don't work with Inertia)
            if ($request->header('X-Inertia')) {
                return response()->json(['portal_url' => $portalSession->url]);
            }

            return redirect($portalSession->url);
        } catch (ApiErrorException $e) {
            return back()->withErrors(['stripe' => 'Failed to create billing portal session: '.$e->getMessage()]);
        }
    }

    /**
     * Change subscription plan (upgrade or downgrade).
     */
    public function changeSubscription(Request $request)
    {
        // SECURITY: Validate plan_key against allowed values to prevent injection
        $validPlanKeys = array_keys(config('plans.plans'));

        $request->validate([
            'plan_key' => ['required', 'string', Rule::in($validPlanKeys)],
        ]);

        $user = $request->user();
        $targetPlanKey = $request->plan_key;
        $currentPlanKey = $user->current_plan_key ?? 'free';

        // Validate plan change
        if (! $this->planService->canChangeToPlan($currentPlanKey, $targetPlanKey)) {
            return back()->withErrors(['plan' => 'Cannot change to the selected plan.']);
        }

        $targetPlan = $this->planService->getPlan($targetPlanKey);

        if (! $targetPlan) {
            return back()->withErrors(['plan' => 'Invalid plan selected.']);
        }

        $currentPlan = $this->planService->getPlan($currentPlanKey);

        try {
            // Determine if this is an upgrade or downgrade
            $isUpgrade = $this->planService->isUpgrade($currentPlanKey, $targetPlanKey);
            $isDowngrade = $this->planService->isDowngrade($currentPlanKey, $targetPlanKey);

            // Handle upgrades: immediate switch with proration
            if ($isUpgrade) {
                // Cancel any pending downgrade
                if ($user->hasPendingPlanChange()) {
                    $user->cancelPendingPlanChange();
                }

                // If user doesn't have a subscription yet (free plan), create checkout
                if (! $user->subscribed() || $currentPlanKey === 'free') {
                    // Ensure user has a Stripe customer ID
                    if (! $user->stripe_id) {
                        $user->createAsStripeCustomer();
                    }

                    $checkoutSession = $this->stripe->checkout->sessions->create([
                        'customer' => $user->stripe_id,
                        'payment_method_types' => ['card'],
                        'line_items' => [[
                            'price' => $targetPlan['stripe_price_id'],
                            'quantity' => 1,
                        ]],
                        'mode' => 'subscription',
                        'success_url' => route('billing.checkout.success').'?session_id={CHECKOUT_SESSION_ID}',
                        'cancel_url' => route('dashboard'),
                        'metadata' => [
                            'user_id' => $user->id,
                            'plan_key' => $targetPlan['key'],
                            'type' => 'subscription',
                        ],
                    ]);

                    // Return JSON for Inertia requests
                    if ($request->header('X-Inertia')) {
                        return response()->json(['checkout_url' => $checkoutSession->url]);
                    }

                    return redirect($checkoutSession->url);
                }

                // Swap subscription immediately with proration
                $user->subscription()->swapAndInvoice($targetPlan['stripe_price_id']);

                // Update current plan
                $user->update(['current_plan_key' => $targetPlanKey]);

                // Reset credits to new plan allocation
                $this->creditService->resetCreditsForPlan($user, $targetPlanKey, $currentPlanKey);

                $message = 'Plan upgraded successfully! Your credits have been reset to '.$targetPlan['monthly_credits'].'.';

                // Return JSON for Inertia requests
                if ($request->header('X-Inertia')) {
                    return response()->json([
                        'success' => true,
                        'message' => $message,
                    ]);
                }

                return back()->with('success', $message);
            }

            // Handle downgrades: schedule for end of period
            if ($isDowngrade) {
                if (! $user->subscribed()) {
                    return back()->withErrors(['subscription' => 'No active subscription found to downgrade.']);
                }

                $subscription = $user->subscription();

                // Get the period end date BEFORE any changes
                $periodEnd = $subscription->onGracePeriod()
                    ? $subscription->ends_at
                    : $subscription->currentPeriodEnd();

                // If subscription is on grace period (cancel_at_period_end=true), resume it first
                if ($subscription->onGracePeriod()) {
                    $subscription->resume();
                }

                // If downgrading to free, cancel the subscription at period end
                if ($targetPlanKey === 'free') {
                    // Store scheduled plan change BEFORE canceling (so webhook can see it)
                    $user->update([
                        'scheduled_plan_key' => $targetPlanKey,
                        'scheduled_plan_date' => $periodEnd,
                    ]);

                    $subscription->cancel();

                    $message = 'Your subscription will be canceled at the end of your billing period. You\'ll return to the Free plan on '.$periodEnd->format('M d, Y').'.';

                    // Return JSON for Inertia requests
                    if ($request->header('X-Inertia')) {
                        return response()->json([
                            'success' => true,
                            'message' => $message,
                        ]);
                    }

                    return back()->with('success', $message);
                }

                // For paid downgrades:
                // Store scheduled plan change FIRST (before swap, so webhook can see it)
                // Do NOT update current_plan_key - user keeps their current features until period ends
                $user->update([
                    'scheduled_plan_key' => $targetPlanKey,
                    'scheduled_plan_date' => $periodEnd,
                ]);

                // Now swap subscription without proration (billing changes but features don't)
                $subscription->noProrate()->swap($targetPlan['stripe_price_id']);

                $message = 'Your plan will change to '.$targetPlan['name'].' on '.$periodEnd->format('M d, Y').'. You\'ll keep your current '.$currentPlan['name'].' features until then.';

                // Return JSON for Inertia requests
                if ($request->header('X-Inertia')) {
                    return response()->json([
                        'success' => true,
                        'message' => $message,
                    ]);
                }

                return back()->with('success', $message);
            }

            return back()->withErrors(['plan' => 'Unable to determine plan change type.']);
        } catch (\Exception $e) {
            return back()->withErrors(['subscription' => 'Failed to change plan: '.$e->getMessage()]);
        }
    }

    /**
     * Cancel the current subscription.
     *
     * BUSINESS LOGIC: Schedules the downgrade to free plan at period end,
     * rather than immediately removing access. User keeps paid features until
     * the end of the period they paid for.
     *
     * HANDLES SCHEDULED DOWNGRADES: If user has a scheduled downgrade (e.g., Business → Pro),
     * canceling will override that scheduled change. User will go directly to Free plan
     * instead of the scheduled paid plan.
     *
     * FRONTEND NOTE: Consider showing a confirmation dialog if user has a scheduled_plan_key,
     * warning them that canceling will also cancel their scheduled downgrade.
     */
    public function cancelSubscription(Request $request)
    {
        $user = $request->user();

        if (! $user->subscribed()) {
            return back()->withErrors(['subscription' => 'No active subscription found.']);
        }

        try {
            $subscription = $user->subscription();

            // Check if user has a scheduled plan change (downgrade)
            $hadScheduledDowngrade = $user->hasPendingPlanChange();
            $scheduledPlanKey = $user->scheduled_plan_key;
            $scheduledPlanName = null;

            if ($hadScheduledDowngrade && $scheduledPlanKey && $scheduledPlanKey !== 'free') {
                // User was scheduled to downgrade to a paid plan
                $scheduledPlan = $this->planService->getPlan($scheduledPlanKey);
                $scheduledPlanName = $scheduledPlan['name'] ?? $scheduledPlanKey;
            }

            // Cancel at period end (not immediately)
            $subscription->cancel();

            // Get the period end date with robust null handling
            $stripeSubscription = $subscription->asStripeSubscription();

            if ($stripeSubscription && $stripeSubscription->current_period_end) {
                // Best case: Use Stripe's current_period_end (most accurate)
                $periodEndDate = \Carbon\Carbon::createFromTimestamp($stripeSubscription->current_period_end);
            } elseif ($subscription->ends_at) {
                // Fallback: Use Cashier's ends_at (set by cancel() method)
                $periodEndDate = $subscription->ends_at;
            } else {
                // Last resort: End of current billing month
                $periodEndDate = now()->addMonth()->startOfDay();

                Log::warning('Subscription cancellation fallback: Using estimated period end', [
                    'user_id' => $user->id,
                    'stripe_subscription_exists' => !is_null($stripeSubscription),
                    'cashier_ends_at' => $subscription->ends_at,
                ]);
            }

            // Clear any scheduled plan change and set to free
            // This overrides any scheduled downgrades (e.g., Business → Pro becomes Business → Free)
            $user->update([
                'scheduled_plan_key' => 'free',
                'scheduled_plan_date' => $periodEndDate,
            ]);

            Log::info('Subscription canceled with scheduled downgrade to free', [
                'user_id' => $user->id,
                'current_plan' => $user->current_plan_key,
                'had_scheduled_downgrade' => $hadScheduledDowngrade,
                'previous_scheduled_plan' => $scheduledPlanKey,
                'new_scheduled_plan' => 'free',
                'scheduled_date' => $periodEndDate->toISOString(),
            ]);

            // Build success message based on whether they had a scheduled downgrade
            if ($hadScheduledDowngrade && $scheduledPlanName) {
                $message = "Subscription canceled successfully. Your scheduled downgrade to {$scheduledPlanName} has also been canceled. You will move to the Free plan on ".$periodEndDate->format('M d, Y').'.';
            } else {
                $message = 'Subscription canceled successfully. You will retain access until '.$periodEndDate->format('M d, Y').'.';
            }

            return back()->with('success', $message);
        } catch (\Exception $e) {
            Log::error('Failed to cancel subscription', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors(['subscription' => 'Failed to cancel subscription: '.$e->getMessage()]);
        }
    }

    /**
     * Resume a canceled subscription.
     *
     * BUSINESS LOGIC: When resuming a subscription that had a scheduled downgrade,
     * we need to swap the Stripe subscription back to the user's current plan.
     * This handles the case where:
     * 1. User on Business scheduled downgrade to Pro (Stripe subscription swapped to Pro)
     * 2. User canceled (wanting to go to Free)
     * 3. User resumes (wants to stay on Business, not Pro)
     */
    public function resumeSubscription(Request $request)
    {
        $user = $request->user();

        if (! $user->subscribed()) {
            return back()->withErrors(['subscription' => 'No subscription found to resume.']);
        }

        $subscription = $user->subscription();

        if (! $subscription->onGracePeriod()) {
            return back()->withErrors(['subscription' => 'Subscription is not canceled or already active.']);
        }

        try {
            // Get current plan before resuming
            $currentPlanKey = $user->current_plan_key ?? 'free';
            $currentPlan = $this->planService->getPlan($currentPlanKey);

            // Check if user had a scheduled downgrade that changed the Stripe subscription
            $hadScheduledDowngrade = $user->hasPendingPlanChange();
            $scheduledPlanKey = $user->scheduled_plan_key;

            // Clear any scheduled plan change first
            if ($hadScheduledDowngrade) {
                $user->cancelPendingPlanChange();
            }

            // Resume the subscription
            $subscription->resume();

            // If there was a scheduled downgrade, the Stripe subscription might be on the wrong plan
            // We need to swap it back to the user's current plan
            if ($hadScheduledDowngrade && $currentPlan && $currentPlan['stripe_price_id']) {
                // Get the current Stripe subscription price
                $stripeSubscription = $subscription->asStripeSubscription();
                $currentStripePriceId = $stripeSubscription->items->data[0]->price->id ?? null;

                // If Stripe subscription price doesn't match current plan, swap it back
                if ($currentStripePriceId !== $currentPlan['stripe_price_id']) {
                    $subscription->swap($currentPlan['stripe_price_id']);

                    Log::info('Swapped subscription back to current plan on resume', [
                        'user_id' => $user->id,
                        'current_plan' => $currentPlanKey,
                        'scheduled_plan_was' => $scheduledPlanKey,
                        'stripe_price_from' => $currentStripePriceId,
                        'stripe_price_to' => $currentPlan['stripe_price_id'],
                    ]);
                }
            }

            return back()->with('success', 'Subscription resumed successfully. Your '.$currentPlan['name'].' plan will continue as normal.');
        } catch (\Exception $e) {
            Log::error('Failed to resume subscription', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors(['subscription' => 'Failed to resume subscription: '.$e->getMessage()]);
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

