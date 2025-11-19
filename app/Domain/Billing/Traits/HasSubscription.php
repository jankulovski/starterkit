<?php

namespace App\Domain\Billing\Traits;

trait HasSubscription
{
    /**
     * Get the user's current plan.
     */
    public function currentPlan(): ?array
    {
        $planKey = $this->current_plan_key ?? 'free';
        $plans = config('plans.plans', []);

        return $plans[$planKey] ?? $plans['free'] ?? null;
    }

    /**
     * Check if the user is on a specific plan.
     */
    public function onPlan(string $planKey): bool
    {
        $currentPlan = $this->currentPlan();

        return $currentPlan && $currentPlan['key'] === $planKey;
    }

    /**
     * Check if the user can use a specific feature.
     */
    public function canUseFeature(string $featureKey): bool
    {
        $plan = $this->currentPlan();

        if (! $plan) {
            return false;
        }

        return in_array($featureKey, $plan['features'] ?? []);
    }

    /**
     * Get the monthly credits allocation for the current plan.
     */
    public function getMonthlyCredits(): int
    {
        $plan = $this->currentPlan();

        return $plan['monthly_credits'] ?? 0;
    }

    /**
     * Check if the user has an active subscription.
     */
    public function hasActiveSubscription(): bool
    {
        return $this->subscribed() && ! $this->subscription()->canceled();
    }

    /**
     * Get the subscription status.
     */
    public function subscriptionStatus(): string
    {
        if (! $this->subscribed()) {
            return 'none';
        }

        $subscription = $this->subscription();

        if ($subscription->canceled()) {
            return $subscription->onGracePeriod() ? 'canceled' : 'expired';
        }

        return 'active';
    }
}

