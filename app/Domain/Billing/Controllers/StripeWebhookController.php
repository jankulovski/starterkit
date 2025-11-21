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

        // Sync billing period to avoid API calls on every request
        if ($user) {
            $this->syncBillingPeriod($user, $subscription);
        }

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

        // Sync billing period to avoid API calls on every request
        if ($user) {
            $this->syncBillingPeriod($user, $subscription);
        }

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

        // Get charge_id from payment_intent for refund tracking
        $chargeId = null;
        if ($session['payment_intent'] ?? null) {
            try {
                $paymentIntent = $this->stripe->paymentIntents->retrieve($session['payment_intent']);
                $chargeId = $paymentIntent->charges->data[0]->id ?? null;
            } catch (\Exception $e) {
                Log::warning('Failed to retrieve charge ID from payment intent in webhook', [
                    'payment_intent' => $session['payment_intent'],
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $user->addCredits($credits, 'purchase', "Purchased {$credits} credits via Stripe (webhook)", [
            'session_id' => $sessionId,
            'charge_id' => $chargeId,
            'event_id' => $eventId,
        ]);

        // Send credit allocation confirmation email
        $this->sendCreditsAllocatedEmail($user, $credits, 'purchase', "Thank you for your purchase!");

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

        // Reset payment failure tracking on successful payment
        if ($user->payment_failure_count > 0) {
            $user->update([
                'payment_failure_count' => 0,
                'last_payment_failure_at' => null,
                'payment_failure_notified_at' => null,
            ]);

            Log::info('Payment failure tracking reset after successful payment', [
                'user_id' => $user->id,
            ]);
        }

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

                    // Send renewal confirmation email
                    $this->sendSubscriptionRenewedEmail($user, $plan, $monthlyCredits);
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

        // Update payment failure tracking
        $user->increment('payment_failure_count');
        $user->update(['last_payment_failure_at' => now()]);

        $attemptNumber = $user->payment_failure_count;

        // Log the payment failure
        Log::warning('Stripe webhook: Invoice payment failed', [
            'user_id' => $user->id,
            'invoice_id' => $invoice['id'],
            'attempt_number' => $attemptNumber,
        ]);

        // Send notification email (throttled to avoid spam)
        // Only send if we haven't notified in the last 24 hours
        $shouldNotify = ! $user->payment_failure_notified_at
            || $user->payment_failure_notified_at->diffInHours(now()) >= 24;

        if ($shouldNotify) {
            $this->sendPaymentFailedEmail($user, $attemptNumber);
            $user->update(['payment_failure_notified_at' => now()]);
        }
    }

    /**
     * Handle customer subscription past due.
     * This fires when subscription enters past_due status after payment failures.
     */
    protected function handleCustomerSubscriptionPastDue(array $payload): void
    {
        $subscription = $payload['data']['object'];
        $stripeCustomerId = $subscription['customer'];

        $user = User::where('stripe_id', $stripeCustomerId)->first();

        if (! $user) {
            return;
        }

        // Determine plan from subscription
        $priceId = $subscription['items']['data'][0]['price']['id'] ?? null;
        $plan = $this->findPlanByPriceId($priceId);

        Log::warning('Stripe webhook: Subscription past due', [
            'user_id' => $user->id,
            'subscription_id' => $subscription['id'],
            'plan' => $plan['key'] ?? 'unknown',
        ]);

        // Send urgent notification
        $this->sendSubscriptionPastDueEmail($user, $plan);
    }

    /**
     * Send payment failed email notification.
     */
    protected function sendPaymentFailedEmail(User $user, int $attemptNumber): void
    {
        $updatePaymentUrl = route('billing.portal');

        $plan = $this->planService->getPlan($user->current_plan_key);

        \Illuminate\Support\Facades\Mail::send('emails.payment-failed', [
            'userName' => $user->name,
            'planName' => $plan['name'] ?? $user->current_plan_key,
            'attemptNumber' => $attemptNumber,
            'updatePaymentUrl' => $updatePaymentUrl,
        ], function ($message) use ($user) {
            $message->to($user->email)
                ->subject('Payment Failed - Action Required');
        });

        Log::info('Payment failed email sent', [
            'user_id' => $user->id,
            'attempt_number' => $attemptNumber,
        ]);
    }

    /**
     * Send subscription past due email notification.
     */
    protected function sendSubscriptionPastDueEmail(User $user, ?array $plan): void
    {
        $updatePaymentUrl = route('billing.portal');

        \Illuminate\Support\Facades\Mail::send('emails.subscription-past-due', [
            'userName' => $user->name,
            'planName' => $plan['name'] ?? $user->current_plan_key,
            'updatePaymentUrl' => $updatePaymentUrl,
        ], function ($message) use ($user) {
            $message->to($user->email)
                ->subject('⚠️ Action Required: Update Payment Method');
        });

        Log::info('Subscription past due email sent', [
            'user_id' => $user->id,
        ]);
    }

    /**
     * Send subscription renewed email notification.
     */
    protected function sendSubscriptionRenewedEmail(User $user, array $plan, int $creditsAllocated): void
    {
        $dashboardUrl = url('/dashboard');

        // Get next billing date from database (avoid API call)
        $subscription = $user->subscription('default');
        $nextBillingDate = $subscription?->current_period_end?->timestamp;
        $nextBillingDateFormatted = $nextBillingDate ? date('F j, Y', $nextBillingDate) : 'N/A';

        \Illuminate\Support\Facades\Mail::send('emails.subscription-renewed', [
            'userName' => $user->name,
            'planName' => $plan['name'],
            'creditsAllocated' => $creditsAllocated,
            'nextBillingDate' => $nextBillingDateFormatted,
            'dashboardUrl' => $dashboardUrl,
        ], function ($message) use ($user) {
            $message->to($user->email)
                ->subject('Subscription Renewed Successfully');
        });

        Log::info('Subscription renewed email sent', [
            'user_id' => $user->id,
            'plan' => $plan['key'],
        ]);
    }

    /**
     * Send credits allocated email notification.
     */
    protected function sendCreditsAllocatedEmail(User $user, int $creditsAmount, string $source, string $description = ''): void
    {
        $dashboardUrl = url('/dashboard');

        // Format source for display
        $sourceDisplay = match($source) {
            'subscription' => 'Monthly Subscription',
            'purchase' => 'Credit Purchase',
            'bonus' => 'Bonus Credits',
            default => ucfirst($source),
        };

        \Illuminate\Support\Facades\Mail::send('emails.credits-allocated', [
            'userName' => $user->name,
            'creditsAmount' => $creditsAmount,
            'source' => $sourceDisplay,
            'newBalance' => $user->credits_balance,
            'description' => $description,
            'dashboardUrl' => $dashboardUrl,
        ], function ($message) use ($user) {
            $message->to($user->email)
                ->subject('Credits Added to Your Account');
        });

        Log::info('Credits allocated email sent', [
            'user_id' => $user->id,
            'credits' => $creditsAmount,
        ]);
    }

    /**
     * Handle customer subscription unpaid (after all retries failed).
     * This is the final state before subscription is canceled.
     */
    protected function handleCustomerSubscriptionUnpaid(array $payload): void
    {
        $subscription = $payload['data']['object'];
        $stripeCustomerId = $subscription['customer'];

        $user = User::where('stripe_id', $stripeCustomerId)->first();

        if (! $user) {
            return;
        }

        Log::error('Stripe webhook: Subscription unpaid - final failure', [
            'user_id' => $user->id,
            'subscription_id' => $subscription['id'],
        ]);

        // The subscription will be canceled automatically by Stripe
        // We just log this critical event
        // The customer.subscription.deleted webhook will handle the cleanup
    }

    /**
     * Handle customer subscription incomplete (initial payment failed).
     * This happens when the first payment for a subscription fails.
     */
    protected function handleCustomerSubscriptionIncomplete(array $payload): void
    {
        $subscription = $payload['data']['object'];
        $stripeCustomerId = $subscription['customer'];

        $user = User::where('stripe_id', $stripeCustomerId)->first();

        if (! $user) {
            return;
        }

        Log::warning('Stripe webhook: Subscription incomplete - initial payment failed', [
            'user_id' => $user->id,
            'subscription_id' => $subscription['id'],
        ]);

        // Don't downgrade plan yet - give user chance to fix payment
        // Stripe will automatically retry or expire the subscription
    }

    /**
     * Handle customer subscription incomplete expired.
     * This happens when the initial payment fails and all retries are exhausted.
     */
    protected function handleCustomerSubscriptionIncompleteExpired(array $payload): void
    {
        $subscription = $payload['data']['object'];
        $stripeCustomerId = $subscription['customer'];

        $user = User::where('stripe_id', $stripeCustomerId)->first();

        if (! $user) {
            return;
        }

        Log::warning('Stripe webhook: Subscription incomplete expired', [
            'user_id' => $user->id,
            'subscription_id' => $subscription['id'],
        ]);

        // Revert to free plan since subscription never activated
        $oldPlanKey = $user->current_plan_key;
        $user->update(['current_plan_key' => 'free']);

        // Reset credits
        $this->creditService->resetCreditsForPlan($user, 'free', $oldPlanKey);
    }

    /**
     * Handle charge refunded.
     * Reverses credits if the refund was for a credit pack purchase.
     */
    protected function handleChargeRefunded(array $payload): void
    {
        $eventId = $payload['id'] ?? null;
        $charge = $payload['data']['object'];
        $chargeId = $charge['id'] ?? null;

        // IDEMPOTENCY CHECK: Prevent duplicate processing
        if ($eventId && ProcessedWebhookEvent::isProcessed($eventId)) {
            Log::info('Webhook event already processed (charge.refunded)', [
                'event_id' => $eventId,
                'charge_id' => $chargeId,
            ]);
            return;
        }

        $stripeCustomerId = $charge['customer'] ?? null;

        if (! $stripeCustomerId) {
            Log::warning('Stripe webhook: No customer found for refunded charge', [
                'charge_id' => $chargeId,
            ]);
            return;
        }

        $user = User::where('stripe_id', $stripeCustomerId)->first();

        if (! $user) {
            Log::warning('Stripe webhook: User not found for refunded charge', [
                'stripe_customer_id' => $stripeCustomerId,
                'charge_id' => $chargeId,
            ]);
            return;
        }

        // Find the original credit transaction for this charge
        // Check if this refund is for a credit pack purchase
        $creditTransaction = \App\Domain\Billing\Models\CreditTransaction::where('user_id', $user->id)
            ->where('type', 'purchase')
            ->where('metadata->charge_id', $chargeId)
            ->first();

        if ($creditTransaction) {
            $creditsToReverse = abs($creditTransaction->amount);

            // Only reverse if user has enough credits
            if ($user->credits_balance >= $creditsToReverse) {
                $user->chargeCredits(
                    $creditsToReverse,
                    'refund',
                    "Refund: Credit pack purchase reversed",
                    [
                        'charge_id' => $chargeId,
                        'original_transaction_id' => $creditTransaction->id,
                        'event_id' => $eventId,
                    ]
                );

                Log::warning('Credits reversed due to refund', [
                    'user_id' => $user->id,
                    'credits_reversed' => $creditsToReverse,
                    'charge_id' => $chargeId,
                    'new_balance' => $user->fresh()->credits_balance,
                ]);
            } else {
                // User doesn't have enough credits to reverse - flag for manual review
                Log::error('Cannot reverse credits: insufficient balance', [
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'credits_to_reverse' => $creditsToReverse,
                    'current_balance' => $user->credits_balance,
                    'charge_id' => $chargeId,
                    'requires_manual_review' => true,
                ]);

                // Still mark as processed to avoid retry loop
            }
        } else {
            // Refund is for subscription payment, not credit pack
            Log::info('Refund received but no credit pack transaction found (likely subscription refund)', [
                'user_id' => $user->id,
                'charge_id' => $chargeId,
            ]);
        }

        // Mark event as processed
        if ($eventId) {
            ProcessedWebhookEvent::markAsProcessed(
                $eventId,
                'charge.refunded',
                $user->id,
                $payload
            );
        }
    }

    /**
     * Handle charge dispute created.
     * Logs disputes for investigation without immediately reversing credits.
     */
    protected function handleChargeDisputeCreated(array $payload): void
    {
        $eventId = $payload['id'] ?? null;
        $dispute = $payload['data']['object'];
        $chargeId = $dispute['charge'] ?? null;

        // IDEMPOTENCY CHECK
        if ($eventId && ProcessedWebhookEvent::isProcessed($eventId)) {
            Log::info('Webhook event already processed (charge.dispute.created)', [
                'event_id' => $eventId,
            ]);
            return;
        }

        // Fetch the charge to get customer information
        try {
            $charge = $this->stripe->charges->retrieve($chargeId);
            $stripeCustomerId = $charge->customer ?? null;

            if (! $stripeCustomerId) {
                Log::error('Stripe webhook: No customer found for disputed charge', [
                    'charge_id' => $chargeId,
                    'dispute_id' => $dispute['id'] ?? null,
                ]);
                return;
            }

            $user = User::where('stripe_id', $stripeCustomerId)->first();

            if (! $user) {
                Log::error('Stripe webhook: User not found for disputed charge', [
                    'stripe_customer_id' => $stripeCustomerId,
                    'charge_id' => $chargeId,
                ]);
                return;
            }

            // Find if this was a credit pack purchase
            $creditTransaction = \App\Domain\Billing\Models\CreditTransaction::where('user_id', $user->id)
                ->where('type', 'purchase')
                ->where('metadata->charge_id', $chargeId)
                ->first();

            Log::error('CHARGEBACK ALERT: Dispute filed against charge', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'user_name' => $user->name,
                'charge_id' => $chargeId,
                'dispute_id' => $dispute['id'] ?? null,
                'dispute_amount' => $dispute['amount'] ?? null,
                'dispute_reason' => $dispute['reason'] ?? null,
                'is_credit_pack_purchase' => $creditTransaction !== null,
                'credits_purchased' => $creditTransaction ? $creditTransaction->amount : null,
                'current_credit_balance' => $user->credits_balance,
                'requires_investigation' => true,
            ]);

            // Don't reverse credits yet - wait for dispute outcome
            // If dispute is lost (charge.dispute.closed with status 'lost'), handle in separate webhook

        } catch (\Exception $e) {
            Log::error('Error processing dispute webhook', [
                'charge_id' => $chargeId,
                'error' => $e->getMessage(),
            ]);
        }

        // Mark event as processed
        if ($eventId) {
            ProcessedWebhookEvent::markAsProcessed(
                $eventId,
                'charge.dispute.created',
                null, // May not have user_id if lookup failed
                $payload
            );
        }
    }

    /**
     * Sync billing period from Stripe subscription to local database.
     * This avoids expensive Stripe API calls on every request.
     */
    protected function syncBillingPeriod(User $user, array $stripeSubscription): void
    {
        $subscription = $user->subscription('default');

        if (! $subscription) {
            return;
        }

        $currentPeriodStart = $stripeSubscription['current_period_start'] ?? null;
        $currentPeriodEnd = $stripeSubscription['current_period_end'] ?? null;

        if ($currentPeriodStart && $currentPeriodEnd) {
            $subscription->current_period_start = date('Y-m-d H:i:s', $currentPeriodStart);
            $subscription->current_period_end = date('Y-m-d H:i:s', $currentPeriodEnd);
            $subscription->save();

            Log::debug('Synced billing period to database', [
                'user_id' => $user->id,
                'period_start' => $subscription->current_period_start,
                'period_end' => $subscription->current_period_end,
            ]);
        }
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

        // Log warning if plan not found
        Log::warning('Plan not found for Stripe price ID', [
            'price_id' => $priceId,
        ]);

        return null;
    }
}

