<?php

namespace App\Domain\Billing\Services;

use App\Domain\Users\Models\User;

class FeatureGateService
{
    /**
     * Feature definitions with required plans and credit costs.
     */
    protected array $features = [
        'basic_usage' => [
            'plans' => ['free', 'pro', 'business'],
            'credits' => 0,
        ],
        'advanced_usage' => [
            'plans' => ['pro', 'business'],
            'credits' => 0,
        ],
        'light_ai' => [
            'plans' => ['free', 'pro', 'business'],
            'credits' => 1,
        ],
        'heavy_ai' => [
            'plans' => ['pro', 'business'],
            'credits' => 5,
        ],
        'priority' => [
            'plans' => ['pro', 'business'],
            'credits' => 0,
        ],
        'enterprise_features' => [
            'plans' => ['business'],
            'credits' => 0,
        ],
    ];

    /**
     * Check if a user can access a feature.
     */
    public function canAccess(User $user, string $featureKey, ?int $creditCost = null): bool
    {
        $feature = $this->features[$featureKey] ?? null;

        if (! $feature) {
            return false;
        }

        // Check plan requirement
        $currentPlan = $user->currentPlan();
        if (! $currentPlan || ! in_array($currentPlan['key'], $feature['plans'])) {
            return false;
        }

        // Check credit requirement
        $requiredCredits = $creditCost ?? $feature['credits'];
        if ($requiredCredits > 0 && ! $user->hasCredits($requiredCredits)) {
            return false;
        }

        return true;
    }

    /**
     * Get the required plan for a feature.
     */
    public function getRequiredPlan(string $featureKey): ?string
    {
        $feature = $this->features[$featureKey] ?? null;

        if (! $feature || empty($feature['plans'])) {
            return null;
        }

        // Return the lowest tier plan that supports this feature
        return $feature['plans'][0];
    }

    /**
     * Get the credit cost for a feature.
     */
    public function getCreditCost(string $featureKey): int
    {
        $feature = $this->features[$featureKey] ?? null;

        return $feature['credits'] ?? 0;
    }

    /**
     * Get all feature definitions.
     */
    public function getFeatures(): array
    {
        return $this->features;
    }
}

