<?php

namespace App\Domain\Admin\Controllers;

use App\Domain\Billing\Models\CreditTransaction;
use App\Domain\Billing\Services\BillingMonitoringService;
use App\Domain\Billing\Services\PlanService;
use App\Domain\Users\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BillingMetricsController extends Controller
{
    public function __construct(
        protected BillingMonitoringService $monitoringService,
        protected PlanService $planService
    ) {
    }

    /**
     * Display billing metrics dashboard.
     */
    public function index(Request $request)
    {
        $period = $request->input('period', 'today'); // today, week, month

        $startDate = match ($period) {
            'week' => now()->startOfWeek(),
            'month' => now()->startOfMonth(),
            default => now()->startOfDay(),
        };

        $metrics = $this->calculateMetrics($startDate);

        return Inertia::render('domains/admin/pages/billing/metrics', [
            'metrics' => $metrics,
            'period' => $period,
            'health' => $this->monitoringService->getHealthMetrics(),
        ]);
    }

    /**
     * Get real-time metrics (API endpoint).
     */
    public function realtime(Request $request)
    {
        $period = $request->input('period', 'today');

        $startDate = match ($period) {
            'week' => now()->startOfWeek(),
            'month' => now()->startOfMonth(),
            default => now()->startOfDay(),
        };

        return response()->json([
            'metrics' => $this->calculateMetrics($startDate),
            'health' => $this->monitoringService->getHealthMetrics(),
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Calculate comprehensive billing metrics.
     */
    protected function calculateMetrics(\Carbon\Carbon $startDate): array
    {
        // Revenue Metrics
        $revenue = $this->calculateRevenue($startDate);

        // Subscription Metrics
        $subscriptions = $this->calculateSubscriptionMetrics($startDate);

        // Credit Metrics
        $credits = $this->calculateCreditMetrics($startDate);

        // User Metrics
        $users = $this->calculateUserMetrics();

        // Churn & Retention
        $churn = $this->calculateChurnMetrics($startDate);

        return [
            'revenue' => $revenue,
            'subscriptions' => $subscriptions,
            'credits' => $credits,
            'users' => $users,
            'churn' => $churn,
        ];
    }

    protected function calculateRevenue(\Carbon\Carbon $startDate): array
    {
        // This would require invoice data from Stripe
        // For now, estimate based on active subscriptions

        $plans = $this->planService->getAllPlans();
        $mrr = 0;

        foreach ($plans as $plan) {
            if ($plan['type'] === 'paid') {
                $count = User::where('current_plan_key', $plan['key'])->count();
                // Estimate MRR (would need actual pricing from Stripe)
                $mrr += $count * 20; // Placeholder
            }
        }

        return [
            'mrr' => $mrr,
            'arr' => $mrr * 12,
            'growth_rate' => 0, // Would need historical data
        ];
    }

    protected function calculateSubscriptionMetrics(\Carbon\Carbon $startDate): array
    {
        $plans = $this->planService->getAllPlans();
        $breakdown = [];

        foreach ($plans as $plan) {
            $breakdown[$plan['key']] = User::where('current_plan_key', $plan['key'])->count();
        }

        $newSubscriptions = User::where('created_at', '>=', $startDate)
            ->where('current_plan_key', '!=', 'free')
            ->count();

        $canceledSubscriptions = User::where('current_plan_key', 'free')
            ->whereNotNull('scheduled_plan_date')
            ->count();

        return [
            'total_active' => array_sum(array_filter($breakdown, fn($key) => $key !== 'free', ARRAY_FILTER_USE_KEY)),
            'by_plan' => $breakdown,
            'new_this_period' => $newSubscriptions,
            'canceled_this_period' => $canceledSubscriptions,
            'conversion_rate' => $this->calculateConversionRate(),
        ];
    }

    protected function calculateCreditMetrics(\Carbon\Carbon $startDate): array
    {
        $totalCreditsAllocated = CreditTransaction::where('created_at', '>=', $startDate)
            ->where('amount', '>', 0)
            ->sum('amount');

        $totalCreditsCharged = CreditTransaction::where('created_at', '>=', $startDate)
            ->where('amount', '<', 0)
            ->sum('amount');

        $creditsPurchased = CreditTransaction::where('created_at', '>=', $startDate)
            ->where('type', 'purchase')
            ->sum('amount');

        $byType = CreditTransaction::where('created_at', '>=', $startDate)
            ->selectRaw('type, SUM(amount) as total')
            ->groupBy('type')
            ->pluck('total', 'type')
            ->toArray();

        return [
            'total_allocated' => $totalCreditsAllocated,
            'total_charged' => abs($totalCreditsCharged),
            'net_change' => $totalCreditsAllocated + $totalCreditsCharged,
            'purchased' => $creditsPurchased,
            'by_type' => $byType,
            'burn_rate' => $this->calculateBurnRate($startDate),
        ];
    }

    protected function calculateUserMetrics(): array
    {
        $totalUsers = User::count();
        $activeUsers = User::where('current_plan_key', '!=', 'free')->count();
        $suspendedUsers = User::whereNotNull('suspended_at')->count();

        return [
            'total' => $totalUsers,
            'active_subscribers' => $activeUsers,
            'free_users' => $totalUsers - $activeUsers,
            'suspended' => $suspendedUsers,
            'activation_rate' => $totalUsers > 0 ? round(($activeUsers / $totalUsers) * 100, 2) : 0,
        ];
    }

    protected function calculateChurnMetrics(\Carbon\Carbon $startDate): array
    {
        $startOfMonth = now()->startOfMonth();

        $churned = User::where('current_plan_key', 'free')
            ->whereBetween('updated_at', [$startOfMonth, now()])
            ->count();

        $activeAtStart = User::where('current_plan_key', '!=', 'free')->count();

        $churnRate = $activeAtStart > 0 ? round(($churned / $activeAtStart) * 100, 2) : 0;

        return [
            'churned_users' => $churned,
            'churn_rate' => $churnRate,
            'retention_rate' => 100 - $churnRate,
        ];
    }

    protected function calculateConversionRate(): float
    {
        $totalUsers = User::count();
        $paidUsers = User::where('current_plan_key', '!=', 'free')->count();

        return $totalUsers > 0 ? round(($paidUsers / $totalUsers) * 100, 2) : 0;
    }

    protected function calculateBurnRate(\Carbon\Carbon $startDate): float
    {
        $days = now()->diffInDays($startDate) ?: 1;

        $totalCharged = abs(CreditTransaction::where('created_at', '>=', $startDate)
            ->where('amount', '<', 0)
            ->sum('amount'));

        return round($totalCharged / $days, 2);
    }
}
