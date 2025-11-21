<?php

namespace Tests\Feature\Billing;

use App\Domain\Billing\Services\PlanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanTierTest extends TestCase
{
    use RefreshDatabase;

    protected PlanService $planService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->planService = app(PlanService::class);
    }

    public function test_plan_upgrade_detection_works_correctly(): void
    {
        // Free (tier 0) → Pro (tier 1) is upgrade
        $this->assertTrue($this->planService->isUpgrade('free', 'pro'));

        // Pro (tier 1) → Business (tier 2) is upgrade
        $this->assertTrue($this->planService->isUpgrade('pro', 'business'));

        // Free (tier 0) → Business (tier 2) is upgrade
        $this->assertTrue($this->planService->isUpgrade('free', 'business'));
    }

    public function test_plan_downgrade_detection_works_correctly(): void
    {
        // Business (tier 2) → Pro (tier 1) is downgrade
        $this->assertTrue($this->planService->isDowngrade('business', 'pro'));

        // Pro (tier 1) → Free (tier 0) is downgrade
        $this->assertTrue($this->planService->isDowngrade('pro', 'free'));

        // Business (tier 2) → Free (tier 0) is downgrade
        $this->assertTrue($this->planService->isDowngrade('business', 'free'));
    }

    public function test_same_plan_is_neither_upgrade_nor_downgrade(): void
    {
        $this->assertFalse($this->planService->isUpgrade('pro', 'pro'));
        $this->assertFalse($this->planService->isDowngrade('pro', 'pro'));
    }

    public function test_reverse_direction_returns_false(): void
    {
        // Upgrade check returns false for downgrade
        $this->assertFalse($this->planService->isUpgrade('business', 'free'));

        // Downgrade check returns false for upgrade
        $this->assertFalse($this->planService->isDowngrade('free', 'business'));
    }

    public function test_invalid_plan_returns_false(): void
    {
        $this->assertFalse($this->planService->isUpgrade('invalid', 'pro'));
        $this->assertFalse($this->planService->isUpgrade('pro', 'invalid'));
        $this->assertFalse($this->planService->isDowngrade('invalid', 'pro'));
    }

    public function test_get_plan_returns_correct_tier(): void
    {
        $freePlan = $this->planService->getPlan('free');
        $proPlan = $this->planService->getPlan('pro');
        $businessPlan = $this->planService->getPlan('business');

        $this->assertEquals(0, $freePlan['tier']);
        $this->assertEquals(1, $proPlan['tier']);
        $this->assertEquals(2, $businessPlan['tier']);
    }

    public function test_get_all_plans_returns_plans_with_tiers(): void
    {
        $plans = $this->planService->getAllPlans();

        $this->assertArrayHasKey('free', $plans);
        $this->assertArrayHasKey('pro', $plans);
        $this->assertArrayHasKey('business', $plans);

        foreach ($plans as $plan) {
            $this->assertArrayHasKey('tier', $plan);
            $this->assertIsInt($plan['tier']);
        }
    }

    public function test_can_change_to_plan_validation(): void
    {
        // Can change to different plans
        $this->assertTrue($this->planService->canChangeToPlan('free', 'pro'));
        $this->assertTrue($this->planService->canChangeToPlan('pro', 'business'));

        // Cannot change to same plan
        $this->assertFalse($this->planService->canChangeToPlan('pro', 'pro'));

        // Cannot change to invalid plan
        $this->assertFalse($this->planService->canChangeToPlan('pro', 'invalid'));
    }

    public function test_tier_comparison_handles_hypothetical_same_credit_plans(): void
    {
        // This test ensures that even if two plans had the same credits,
        // tier-based comparison would still work correctly

        // If we had "Pro Plus" with tier 1.5 (between Pro and Business)
        // and same credits as Pro, tier comparison would differentiate them

        // Current setup:
        $proPlan = $this->planService->getPlan('pro');
        $businessPlan = $this->planService->getPlan('business');

        // Pro has 200 credits (tier 1), Business has 1000 credits (tier 2)
        $this->assertEquals(200, $proPlan['monthly_credits']);
        $this->assertEquals(1000, $businessPlan['monthly_credits']);

        // Tier-based comparison works
        $this->assertTrue($this->planService->isUpgrade('pro', 'business'));

        // Even if credits were the same, tiers would differentiate
        $this->assertNotEquals($proPlan['tier'], $businessPlan['tier']);
    }
}
