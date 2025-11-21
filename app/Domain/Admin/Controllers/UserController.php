<?php

namespace App\Domain\Admin\Controllers;

use App\Domain\Admin\Requests\UpdateUserRequest;
use App\Domain\Users\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UserController extends Controller
{
    /**
     * Display a listing of users.
     */
    public function index(Request $request)
    {
        $query = User::query();

        // Search functionality
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Status filter
        if ($request->has('status') && is_array($request->status) && !empty($request->status)) {
            $statuses = $request->status;
            $query->where(function ($q) use ($statuses) {
                $hasCondition = false;
                
                if (in_array('admin', $statuses)) {
                    $q->orWhere('is_admin', true);
                    $hasCondition = true;
                }
                
                if (in_array('user', $statuses)) {
                    $q->orWhere('is_admin', false);
                    $hasCondition = true;
                }
                
                if (in_array('suspended', $statuses)) {
                    $q->orWhereNotNull('suspended_at');
                    $hasCondition = true;
                }
                
                if (!$hasCondition) {
                    // If no valid status selected, return no results
                    $q->whereRaw('1 = 0');
                }
            });
        }

        // Calculate filter counts
        $totalUsers = User::count();
        $adminCount = User::where('is_admin', true)->count();
        $userCount = User::where('is_admin', false)->count();
        $suspendedCount = User::whereNotNull('suspended_at')->count();

        $users = $query->latest('created_at')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('domains/admin/pages/users/index', [
            'users' => $users->through(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'is_admin' => $user->is_admin,
                    'suspended_at' => $user->suspended_at?->toISOString(),
                    'created_at' => $user->created_at->toISOString(),
                ];
            }),
            'filters' => $request->only(['search', 'status']),
            'filterCounts' => [
                'admin' => $adminCount,
                'user' => $userCount,
                'suspended' => $suspendedCount,
            ],
        ]);
    }

    /**
     * Display the specified user.
     */
    public function show(Request $request, User $user)
    {
        $userData = $this->getUserData($user);

        // For Inertia requests, return user data as prop
        if ($request->header('X-Inertia')) {
            return Inertia::render('domains/admin/pages/users/index', [
                'user' => $userData,
            ]);
        }

        // For direct URL visits, redirect to index page
        return redirect()->route('admin.users.index');
    }

    /**
     * Get user data as JSON (API endpoint for fetching user details without navigation).
     */
    public function apiShow(User $user)
    {
        return response()->json($this->getUserData($user));
    }

    /**
     * Build user data array with all details.
     */
    private function getUserData(User $user): array
    {
        $subscription = null;
        $nextBillingDate = null;
        if ($user->subscribed()) {
            $subscription = $user->subscription();
            $nextBillingDate = $subscription->asStripeSubscription()->current_period_end ?? null;
        }

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'is_admin' => $user->is_admin,
            'suspended_at' => $user->suspended_at?->toISOString(),
            'email_verified_at' => $user->email_verified_at?->toISOString(),
            'created_at' => $user->created_at->toISOString(),
            'updated_at' => $user->updated_at->toISOString(),
            'billing' => [
                'credits_balance' => $user->creditsBalance(),
                'current_plan' => $user->currentPlan(),
                'subscription_status' => $user->subscriptionStatus(),
                'stripe_customer_id' => $user->stripe_id,
                'next_billing_date' => $nextBillingDate ? date('c', $nextBillingDate) : null,
                'stripe_subscription_id' => $subscription?->stripe_id,
            ],
        ];
    }

    /**
     * Update the specified user.
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        $user->update($request->validated());

        // If this is an Inertia request from the dialog, redirect back to index
        if ($request->header('X-Inertia')) {
            return redirect()
                ->route('admin.users.index')
                ->with('success', 'User updated successfully.');
        }

        return redirect()
            ->route('admin.users.show', $user)
            ->with('success', 'User updated successfully.');
    }

    /**
     * Suspend the specified user.
     */
    public function suspend(Request $request, User $user)
    {
        // Prevent suspending the current admin user
        if ($user->id === auth()->id()) {
            $redirectRoute = $request->header('X-Inertia') 
                ? route('admin.users.index')
                : route('admin.users.show', $user);
            
            return redirect()
                ->to($redirectRoute)
                ->with('error', 'You cannot suspend your own account.');
        }

        $user->update(['suspended_at' => now()]);

        // If this is an Inertia request, redirect back to index
        if ($request->header('X-Inertia')) {
            return redirect()
                ->route('admin.users.index')
                ->with('success', 'User has been suspended.');
        }

        return redirect()
            ->route('admin.users.show', $user)
            ->with('success', 'User has been suspended.');
    }

    /**
     * Unsuspend the specified user.
     */
    public function unsuspend(Request $request, User $user)
    {
        $user->update(['suspended_at' => null]);

        // If this is an Inertia request, redirect back to index
        if ($request->header('X-Inertia')) {
            return redirect()
                ->route('admin.users.index')
                ->with('success', 'User has been unsuspended.');
        }

        return redirect()
            ->route('admin.users.show', $user)
            ->with('success', 'User has been unsuspended.');
    }
}

