<?php

namespace App\Domain\Billing\Controllers;

use App\Domain\Billing\Models\ProcessedWebhookEvent;
use App\Domain\Billing\Services\CreditService;
use App\Domain\Billing\Services\PlanService;
use App\Domain\Users\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Http\Controllers\WebhookController as CashierController;
use Stripe\StripeClient;

/**
 * Handles Stripe webhook events for billing operations.
 *
 * SECURITY VERIFICATION:
 * This controller extends Laravel Cashier's WebhookController, which automatically
 * verifies webhook signatures using STRIPE_WEBHOOK_SECRET from .env.
 *
 * How signature verification works:
 * 1. Stripe signs each webhook with a secret key (configured in Stripe Dashboard)
 * 2. The secret is stored in STRIPE_WEBHOOK_SECRET environment variable
 * 3. Cashier's parent class verifies the signature on each request
 * 4. If signature is invalid, the request is rejected with 400 error
 * 5. Only valid, signed webhooks reach our handler methods
 *
 * IMPORTANT: Ensure STRIPE_WEBHOOK_SECRET is set in production!
 * Without it, anyone could send fake webhook events to your application.
 *
 * To verify it's working:
 * - Check logs for "Webhook signature verification failed" errors
 * - Test with Stripe CLI: stripe trigger customer.subscription.created
 * - Monitor for suspicious webhook activity
 */
class StripeWebhookController extends CashierController
{
    public function __construct(
        protected PlanService $planService,
        protected CreditService $creditService,
        protected StripeClient $stripe
    ) {
        parent::__construct();
    }

    /**
     * Handle customer subscription created.
     *
     * SECURITY: Implements idempotency checking to prevent duplicate credit allocation
     * if Stripe sends the same webhook multiple times (e.g., network retries).
     */
    protected function handleCustomerSubscriptionCreated(array $payload)
    {
        $eventId = $payload['id'] ?? null;

        // IDEMPOTENCY CHECK: Prevent duplicate processing
        if ($eventId && ProcessedWebhookEvent::isProcessed($eventId)) {
            Log::info('Webhook event already processed (subscription.created)', [
                'event_id' => $eventId,
            ]);
            return $this->successMethod();
        }

        // Let Cashier create/update subscription records first
        parent::handleCustomerSubscriptionCreated($payload);

        $subscription = $payload['data']['object'];
        $stripeCustomerId = $subscription['customer'];

        $user = User::where('stripe_id', $stripeCustomerId)->first();

        if (! $user) {
            Log::warning('Stripe webhook: User not found for subscription', [
                'stripe_customer_id' => $stripeCustomerId,
            ]);
            return $this->successMethod();
        }

        // Determine plan from subscription
        $priceId = $subscription['items']['data'][0]['price']['id'] ?? null;
        $plan = $this->findPlanByPriceId($priceId);

        if ($plan) {
            $user->update(['current_plan_key' => $plan['key']]);

            // Add monthly credits
            $monthlyCredits = $plan['monthly_credits'] ?? 0;
            if ($monthlyCredits > 0) {
                $user->addCredits(
                    $monthlyCredits,
                    'subscription',
                    "Monthly credits for {$plan['name']} plan"
                );
            }
        }

        // Mark event as processed
        if ($eventId) {
            ProcessedWebhookEvent::markAsProcessed(
                $eventId,
                'customer.subscription.created',
                $user->id,
                $payload
            );
        }

        return $this->successMethod();
    }

    /**
     * Handle customer subscription updated.
     */
    protected function handleCustomerSubscriptionUpdated(array $payload)
    {
        // Let Cashier update subscription records first
        parent::handleCustomerSubscriptionUpdated($payload);

        $subscription = $payload['data']['object'];
        $stripeCustomerId = $subscription['customer'];

        $user = User::where('stripe_id', $stripeCustomerId)->first();

        if (! $user) {
            Log::warning('Stripe webhook: User not found for subscription update', [
                'stripe_customer_id' => $stripeCustomerId,
            ]);
            return $this->successMethod();
        }

        // Determine plan from subscription
        $priceId = $subscription['items']['data'][0]['price']['id'] ?? null;
        $plan = $this->findPlanByPriceId($priceId);

        if ($plan) {
            $oldPlanKey = $user->current_plan_key;

            // Check if subscription is canceled (on grace period)
            $isCanceled = $subscription['cancel_at_period_end'] ?? false;

            // Check if this is a canceled subscription with scheduled downgrade to free
            // This happens when user scheduled a downgrade (e.g., Business → Pro), then canceled entirely
            // In this case, the Stripe subscription was swapped to Pro, but user is now canceling to Free
            $isCanceledWithScheduledFree = $isCanceled
                && $user->hasPendingPlanChange()
                && $user->scheduled_plan_key === 'free';

            // Check if this is a scheduled downgrade that hasn't taken effect yet
            $hasScheduledDowngrade = $user->hasPendingPlanChange()
                && $user->scheduled_plan_key === $plan['key']
                && $user->scheduled_plan_date
                && now()->lt($user->scheduled_plan_date);

            if ($isCanceledWithScheduledFree) {
                // User canceled subscription after scheduling a downgrade
                // Don't update current_plan_key - they keep current plan until subscription ends
                // The subscription.deleted webhook will handle the transition to free
                Log::info('Canceled subscription with scheduled free plan detected, keeping current plan', [
                    'user_id' => $user->id,
                    'current_plan' => $oldPlanKey,
                    'stripe_plan' => $plan['key'],
                    'scheduled_plan' => 'free',
                    'cancel_at_period_end' => $isCanceled,
                ]);
            } elseif ($hasScheduledDowngrade) {
                // Don't update current_plan_key yet - user keeps current features until scheduled date
                // The scheduled downgrade will take effect when the period ends
                Log::info('Scheduled downgrade detected, keeping current plan', [
                    'user_id' => $user->id,
                    'current_plan' => $oldPlanKey,
                    'scheduled_plan' => $plan['key'],
                    'scheduled_date' => $user->scheduled_plan_date,
                ]);
            } else {
                // Update current plan immediately (for upgrades or when scheduled date has passed)
                $user->update(['current_plan_key' => $plan['key']]);

                // If this is a scheduled plan change taking effect, clear scheduled data and reset credits
                if ($user->hasPendingPlanChange() && $user->scheduled_plan_key === $plan['key']) {
                    $user->cancelPendingPlanChange();

                    // Reset credits for the new plan
                    $this->creditService->resetCreditsForPlan($user, $plan['key'], $oldPlanKey);
                }
            }
        }

        return $this->successMethod();
    }

    /**
     * Handle customer subscription deleted.
     */
    protected function handleCustomerSubscriptionDeleted(array $payload)
    {
        // Let Cashier mark subscription as canceled first
        parent::handleCustomerSubscriptionDeleted($payload);

        $subscription = $payload['data']['object'];
        $stripeCustomerId = $subscription['customer'];

        $user = User::where('stripe_id', $stripeCustomerId)->first();

        if (! $user) {
            Log::warning('Stripe webhook: User not found for subscription deletion', [
                'stripe_customer_id' => $stripeCustomerId,
            ]);
            return $this->successMethod();
        }

        $oldPlanKey = $user->current_plan_key;

        // Revert to free plan
        $user->update(['current_plan_key' => 'free']);

        // If this was a scheduled downgrade to free, clear scheduled data and reset credits
        if ($user->hasPendingPlanChange() && $user->scheduled_plan_key === 'free') {
            $user->cancelPendingPlanChange();

            // Reset credits to 0 for free plan
            $this->creditService->resetCreditsForPlan($user, 'free', $oldPlanKey);
        }

        return $this->successMethod();
    }

    /**
     * Handle checkout session completed (for credit packs).
     *
     * SECURITY: Implements idempotency to prevent duplicate credit allocation.
     * Note: The CheckoutController also processes credit packs, but only via
     * the success redirect URL. This webhook handler provides a backup and ensures
     * credits are allocated even if the user closes the browser before redirect.
     */
    protected function handleCheckoutSessionCompleted(array $payload): void
    {
        $eventId = $payload['id'] ?? null;
        $session = $payload['data']['object'];
        $sessionId = $session['id'] ?? null;

        // IDEMPOTENCY CHECK: Use session ID as the idempotency key
        // This prevents both duplicate webhook processing AND duplicate processing
        // between this webhook handler and the CheckoutController success method
        if ($sessionId && ProcessedWebhookEvent::isProcessed($sessionId)) {
            Log::info('Checkout session already processed', [
                'session_id' => $sessionId,
                'event_id' => $eventId,
            ]);
            return;
        }

        $metadata = $session['metadata'] ?? [];

        if (($metadata['type'] ?? null) !== 'credit_pack') {
            return;
        }

        // SECURITY: Verify payment was completed before allocating credits
        if (($session['payment_status'] ?? null) !== 'paid') {
            Log::warning('Checkout session completed webhook received but payment not paid', [
                'session_id' => $sessionId,
                'payment_status' => $session['payment_status'] ?? 'unknown',
            ]);
            return;
        }

        $userId = $metadata['user_id'] ?? null;
        $credits = (int) ($metadata['credits'] ?? 0);

        if (! $userId || $credits <= 0) {
            Log::warning('Stripe webhook: Invalid credit pack checkout metadata', [
                'metadata' => $metadata,
                'session_id' => $sessionId,
            ]);
            return;
        }

        $user = User::find($userId);

        if (! $user) {
            Log::warning('Stripe webhook: User not found for credit pack purchase', [
                'user_id' => $userId,
                'session_id' => $sessionId,
            ]);
            return;
        }

        $user->addCredits($credits, 'purchase', "Purchased {$credits} credits via Stripe (webhook)", [
            'session_id' => $sessionId,
            'event_id' => $eventId,
        ]);

        // Mark session as processed (not event ID, to prevent duplicate with CheckoutController)
        if ($sessionId) {
            ProcessedWebhookEvent::markAsProcessed(
                $sessionId,
                'checkout.session.completed',
                $user->id,
                $payload
            );
        }

        Log::info('Credit pack purchased via webhook', [
            'user_id' => $user->id,
            'credits' => $credits,
            'session_id' => $sessionId,
            'event_id' => $eventId,
        ]);
    }

    /**
     * Handle invoice payment succeeded (for subscription renewals).
     *
     * SECURITY: Implements idempotency checking to prevent duplicate credit allocation.
     * This is CRITICAL because invoice.payment_succeeded can be sent multiple times,
     * and without idempotency, users would receive duplicate monthly credits.
     */
    protected function handleInvoicePaymentSucceeded(array $payload)
    {
        $eventId = $payload['id'] ?? null;
        $invoice = $payload['data']['object'];

        // IDEMPOTENCY CHECK: Prevent duplicate processing
        if ($eventId && ProcessedWebhookEvent::isProcessed($eventId)) {
            Log::info('Webhook event already processed (invoice.payment_succeeded)', [
                'event_id' => $eventId,
                'invoice_id' => $invoice['id'] ?? null,
            ]);
            return $this->successMethod();
        }

        $stripeCustomerId = $invoice['customer'];

        // Only process subscription invoices, not one-time payments
        if (! isset($invoice['subscription'])) {
            return $this->successMethod();
        }

        $user = User::where('stripe_id', $stripeCustomerId)->first();

        if (! $user) {
            Log::warning('Stripe webhook: User not found for invoice payment', [
                'stripe_customer_id' => $stripeCustomerId,
                'invoice_id' => $invoice['id'],
            ]);
            return $this->successMethod();
        }

        // Determine plan from invoice line items (more efficient than fetching subscription)
        $priceId = $invoice['lines']['data'][0]['price']['id'] ?? null;
        $plan = $this->findPlanByPriceId($priceId);

        if ($plan) {
            $oldPlanKey = $user->current_plan_key;

            // Check if this is a scheduled plan change that should now take effect
            if ($user->hasPendingPlanChange()
                && $user->scheduled_plan_key === $plan['key']
                && now()->gte($user->scheduled_plan_date)) {

                // Update to the new plan
                $user->update(['current_plan_key' => $plan['key']]);

                // Clear scheduled plan change
                $user->cancelPendingPlanChange();

                // Reset credits for the new plan
                $this->creditService->resetCreditsForPlan($user, $plan['key'], $oldPlanKey);

                Log::info('Completed scheduled plan change on renewal', [
                    'user_id' => $user->id,
                    'old_plan' => $oldPlanKey,
                    'new_plan' => $plan['key'],
                    'event_id' => $eventId,
                ]);
            } else {
                // Normal renewal - just add monthly credits
                $monthlyCredits = $plan['monthly_credits'] ?? 0;
                if ($monthlyCredits > 0) {
                    $user->addCredits(
                        $monthlyCredits,
                        'subscription',
                        "Monthly credits for {$plan['name']} plan (renewal)",
                        ['invoice_id' => $invoice['id'], 'event_id' => $eventId]
                    );

                    Log::info('Monthly credits allocated on renewal', [
                        'user_id' => $user->id,
                        'credits' => $monthlyCredits,
                        'plan' => $plan['key'],
                        'invoice_id' => $invoice['id'],
                        'event_id' => $eventId,
                    ]);
                }
            }
        }

        // Mark event as processed
        if ($eventId) {
            ProcessedWebhookEvent::markAsProcessed(
                $eventId,
                'invoice.payment_succeeded',
                $user->id,
                $payload
            );
        }

        return $this->successMethod();
    }

    /**
     * Handle invoice payment failed.
     */
    protected function handleInvoicePaymentFailed(array $payload): void
    {
        $invoice = $payload['data']['object'];
        $stripeCustomerId = $invoice['customer'];

        $user = User::where('stripe_id', $stripeCustomerId)->first();

        if (! $user) {
            return;
        }

        // Log the payment failure - in production, you might want to send a notification
        Log::warning('Stripe webhook: Invoice payment failed', [
            'user_id' => $user->id,
            'invoice_id' => $invoice['id'],
        ]);
    }

    /**
     * Find a plan by Stripe price ID.
     */
    protected function findPlanByPriceId(?string $priceId): ?array
    {
        if (! $priceId) {
            return null;
        }

        $plans = $this->planService->getAllPlans();

        foreach ($plans as $plan) {
            if (($plan['stripe_price_id'] ?? null) === $priceId) {
                return $plan;
            }
        }

        return null;
    }
}

