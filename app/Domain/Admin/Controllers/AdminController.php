<?php

namespace App\Domain\Admin\Controllers;

use App\Domain\Users\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class AdminController extends Controller
{
    /**
     * Display the admin overview page.
     */
    public function index(Request $request)
    {
        $totalUsers = User::count();
        $adminCount = User::where('is_admin', true)->count();
        $recentSignups = User::latest('created_at')
            ->take(5)
            ->get(['id', 'name', 'email', 'created_at', 'is_admin']);

        // Get billing metrics
        $activeSubscriptions = User::whereNotNull('stripe_id')
            ->whereHas('subscriptions', function ($query) {
                $query->where('stripe_status', 'active');
            })
            ->count();

        // Calculate MRR (Monthly Recurring Revenue)
        $mrr = User::whereHas('subscriptions', function ($query) {
            $query->where('stripe_status', 'active');
        })->get()->sum(function ($user) {
            $subscription = $user->subscriptions()->where('stripe_status', 'active')->first();
            if (!$subscription) {
                return 0;
            }

            // Get plan config
            $plan = config("plans.plans.{$user->current_plan_key}");
            if (!$plan || !isset($plan['stripe_price_id'])) {
                return 0;
            }

            // Simple price mapping (customize based on your actual prices)
            $prices = [
                config('plans.plans.pro.stripe_price_id') => 9.99,
                config('plans.plans.business.stripe_price_id') => 29.99,
            ];

            return $prices[$plan['stripe_price_id']] ?? 0;
        });

        // Get total credits allocated this month
        $startOfMonth = now()->startOfMonth();
        $creditsAllocated = DB::table('credit_transactions')
            ->where('type', 'allocation')
            ->where('created_at', '>=', $startOfMonth)
            ->sum('amount');

        return Inertia::render('domains/admin/pages/index', [
            'metrics' => [
                'totalUsers' => $totalUsers,
                'adminCount' => $adminCount,
                'recentSignups' => $recentSignups,
                'billing' => [
                    'mrr' => round($mrr, 2),
                    'arr' => round($mrr * 12, 2),
                    'activeSubscriptions' => $activeSubscriptions,
                    'creditsAllocated' => $creditsAllocated,
                ],
            ],
        ]);
    }
}

