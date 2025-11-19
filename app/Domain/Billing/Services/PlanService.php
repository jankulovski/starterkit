<?php

namespace App\Domain\Billing\Services;

class PlanService
{
    /**
     * Get all available plans.
     */
    public function getAllPlans(): array
    {
        return config('plans.plans', []);
    }

    /**
     * Get a specific plan by key.
     */
    public function getPlan(string $key): ?array
    {
        $plans = $this->getAllPlans();

        return $plans[$key] ?? null;
    }

    /**
     * Get plans that the user can upgrade to.
     */
    public function getAvailablePlans(?string $currentPlanKey = null): array
    {
        $allPlans = $this->getAllPlans();
        $available = [];

        foreach ($allPlans as $plan) {
            // Skip free plan if user already has a paid plan
            if ($plan['type'] === 'free' && $currentPlanKey && $currentPlanKey !== 'free') {
                continue;
            }

            // Skip current plan
            if ($plan['key'] === $currentPlanKey) {
                continue;
            }

            $available[] = $plan;
        }

        return $available;
    }

    /**
     * Calculate upgrade price (proration).
     * This is a simplified version - in production, you'd calculate based on Stripe's proration.
     */
    public function calculateUpgradePrice(string $fromPlanKey, string $toPlanKey): ?int
    {
        $fromPlan = $this->getPlan($fromPlanKey);
        $toPlan = $this->getPlan($toPlanKey);

        if (! $fromPlan || ! $toPlan) {
            return null;
        }

        // This is a placeholder - actual proration would be calculated via Stripe
        // For now, return null to indicate it should be calculated by Stripe
        return null;
    }
}

