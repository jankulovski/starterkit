<?php

namespace App\Domain\Billing\Controllers;

use App\Domain\Billing\Services\CreditService;
use App\Domain\Billing\Services\PlanService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class BillingSettingsController extends Controller
{
    public function __construct(
        protected PlanService $planService,
        protected CreditService $creditService
    ) {
    }

    /**
     * Get billing data for settings dialog.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $currentPlan = $user->currentPlan();
        $availablePlans = $this->planService->getAvailablePlans($user->current_plan_key);
        $creditPacks = $this->creditService->getCreditPacks();

        // Get subscription info if user has one
        $subscription = null;
        $nextBillingDate = null;
        if ($user->subscribed()) {
            $subscription = $user->subscription();
            $nextBillingDate = $subscription->asStripeSubscription()->current_period_end ?? null;
        }

        return response()->json([
            'currentPlan' => $currentPlan,
            'availablePlans' => $availablePlans,
            'creditPacks' => $creditPacks,
            'subscription' => [
                'status' => $user->subscriptionStatus(),
                'stripe_id' => $subscription?->stripe_id,
                'stripe_status' => $subscription?->stripe_status,
                'next_billing_date' => $nextBillingDate ? date('c', $nextBillingDate) : null,
            ],
            'credits' => [
                'balance' => $user->creditsBalance(),
                'monthly_allocation' => $user->getMonthlyCredits(),
            ],
            'stripe_customer_id' => $user->stripe_id,
        ]);
    }
}

