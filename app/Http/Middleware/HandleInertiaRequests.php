<?php

namespace App\Http\Middleware;

use App\Domain\Billing\Services\CreditService;
use App\Domain\Billing\Services\PlanService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    public function __construct(
        protected PlanService $planService,
        protected CreditService $creditService
    ) {
    }

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        [$message, $author] = str(Inspiring::quotes()->random())->explode('-');

        $user = $request->user();

        // Build billing data if user is authenticated
        $billingData = null;
        if ($user) {
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

            $billingData = [
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
                'pendingPlanChange' => $user->pendingPlanChange(),
            ];
        }

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'quote' => ['message' => trim($message), 'author' => trim($author)],
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatar' => $user->avatar ?? null,
                    'email_verified_at' => $user->email_verified_at?->toISOString(),
                    'is_admin' => $user->is_admin ?? false,
                    'created_at' => $user->created_at->toISOString(),
                    'updated_at' => $user->updated_at->toISOString(),
                    'billing' => $billingData,
                ] : null,
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
