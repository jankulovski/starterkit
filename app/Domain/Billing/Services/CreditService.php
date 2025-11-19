<?php

namespace App\Domain\Billing\Services;

use App\Domain\Users\Models\User;
use Stripe\Checkout\Session;
use Stripe\StripeClient;

class CreditService
{
    public function __construct(
        protected StripeClient $stripe
    ) {
    }

    /**
     * Add credits to a user's account.
     */
    public function addCredits(User $user, int $amount, string $type, ?string $description = null, ?array $metadata = null): User
    {
        return $user->addCredits($amount, $type, $description, $metadata);
    }

    /**
     * Charge credits from a user's account.
     */
    public function chargeCredits(User $user, int $amount, string $context, ?array $metadata = null): bool
    {
        return $user->chargeCredits($amount, $context, $metadata);
    }

    /**
     * Get available credit packs.
     */
    public function getCreditPacks(): array
    {
        return config('plans.credit_packs', []);
    }

    /**
     * Create a Stripe checkout session for a credit pack purchase.
     */
    public function createCreditPackCheckout(User $user, string $packKey): Session
    {
        $packs = $this->getCreditPacks();
        $pack = $packs[$packKey] ?? null;

        if (! $pack || ! $pack['stripe_price_id']) {
            throw new \InvalidArgumentException("Invalid credit pack: {$packKey}");
        }

        // Ensure user has a Stripe customer ID
        if (! $user->stripe_id) {
            $user->createAsStripeCustomer();
        }

        return $this->stripe->checkout->sessions->create([
            'customer' => $user->stripe_id,
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price' => $pack['stripe_price_id'],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => route('billing.checkout.success').'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('dashboard'),
            'metadata' => [
                'user_id' => $user->id,
                'pack_key' => $packKey,
                'credits' => $pack['credits'],
                'type' => 'credit_pack',
            ],
        ]);
    }
}

