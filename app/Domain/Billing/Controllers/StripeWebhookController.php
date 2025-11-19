<?php

namespace App\Domain\Billing\Controllers;

use App\Domain\Billing\Services\CreditService;
use App\Domain\Billing\Services\PlanService;
use App\Domain\Users\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Http\Controllers\WebhookController as CashierController;
use Stripe\StripeClient;

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
     */
    protected function handleCustomerSubscriptionCreated(array $payload): void
    {
        $subscription = $payload['data']['object'];
        $stripeCustomerId = $subscription['customer'];

        $user = User::where('stripe_id', $stripeCustomerId)->first();

        if (! $user) {
            Log::warning('Stripe webhook: User not found for subscription', [
                'stripe_customer_id' => $stripeCustomerId,
            ]);
            return;
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
    }

    /**
     * Handle customer subscription updated.
     */
    protected function handleCustomerSubscriptionUpdated(array $payload): void
    {
        $subscription = $payload['data']['object'];
        $stripeCustomerId = $subscription['customer'];

        $user = User::where('stripe_id', $stripeCustomerId)->first();

        if (! $user) {
            Log::warning('Stripe webhook: User not found for subscription update', [
                'stripe_customer_id' => $stripeCustomerId,
            ]);
            return;
        }

        // Determine plan from subscription
        $priceId = $subscription['items']['data'][0]['price']['id'] ?? null;
        $plan = $this->findPlanByPriceId($priceId);

        if ($plan) {
            $user->update(['current_plan_key' => $plan['key']]);
        }
    }

    /**
     * Handle customer subscription deleted.
     */
    protected function handleCustomerSubscriptionDeleted(array $payload): void
    {
        $subscription = $payload['data']['object'];
        $stripeCustomerId = $subscription['customer'];

        $user = User::where('stripe_id', $stripeCustomerId)->first();

        if (! $user) {
            Log::warning('Stripe webhook: User not found for subscription deletion', [
                'stripe_customer_id' => $stripeCustomerId,
            ]);
            return;
        }

        // Revert to free plan
        $user->update(['current_plan_key' => 'free']);
    }

    /**
     * Handle checkout session completed (for credit packs).
     */
    protected function handleCheckoutSessionCompleted(array $payload): void
    {
        $session = $payload['data']['object'];
        $metadata = $session['metadata'] ?? [];

        if (($metadata['type'] ?? null) !== 'credit_pack') {
            return;
        }

        $userId = $metadata['user_id'] ?? null;
        $credits = (int) ($metadata['credits'] ?? 0);

        if (! $userId || $credits <= 0) {
            Log::warning('Stripe webhook: Invalid credit pack checkout metadata', [
                'metadata' => $metadata,
            ]);
            return;
        }

        $user = User::find($userId);

        if (! $user) {
            Log::warning('Stripe webhook: User not found for credit pack purchase', [
                'user_id' => $userId,
            ]);
            return;
        }

        $user->addCredits($credits, 'purchase', "Purchased {$credits} credits via Stripe");
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

