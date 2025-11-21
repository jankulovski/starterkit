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
     * Check if changing from one plan to another is an upgrade.
     */
    public function isUpgrade(string $fromPlanKey, string $toPlanKey): bool
    {
        $fromPlan = $this->getPlan($fromPlanKey);
        $toPlan = $this->getPlan($toPlanKey);

        if (! $fromPlan || ! $toPlan) {
            return false;
        }

        // Use explicit tier field for reliable comparison
        $fromTier = $fromPlan['tier'] ?? 0;
        $toTier = $toPlan['tier'] ?? 0;

        return $toTier > $fromTier;
    }

    /**
     * Check if changing from one plan to another is a downgrade.
     */
    public function isDowngrade(string $fromPlanKey, string $toPlanKey): bool
    {
        $fromPlan = $this->getPlan($fromPlanKey);
        $toPlan = $this->getPlan($toPlanKey);

        if (! $fromPlan || ! $toPlan) {
            return false;
        }

        // Use explicit tier field for reliable comparison
        $fromTier = $fromPlan['tier'] ?? 0;
        $toTier = $toPlan['tier'] ?? 0;

        return $toTier < $fromTier;
    }

    /**
     * Check if user can change to a specific plan.
     */
    public function canChangeToPlan(string $currentPlanKey, string $targetPlanKey): bool
    {
        // Can't change to the same plan
        if ($currentPlanKey === $targetPlanKey) {
            return false;
        }

        $targetPlan = $this->getPlan($targetPlanKey);

        // Target plan must exist
        if (! $targetPlan) {
            return false;
        }

        return true;
    }
}

