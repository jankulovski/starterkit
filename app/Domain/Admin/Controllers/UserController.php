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
            'filters' => $request->only(['search']),
        ]);
    }

    /**
     * Display the specified user.
     */
    public function show(User $user)
    {
        return Inertia::render('domains/admin/pages/users/show', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_admin' => $user->is_admin,
                'suspended_at' => $user->suspended_at?->toISOString(),
                'email_verified_at' => $user->email_verified_at?->toISOString(),
                'two_factor_enabled' => ! is_null($user->two_factor_confirmed_at),
                'created_at' => $user->created_at->toISOString(),
                'updated_at' => $user->updated_at->toISOString(),
            ],
        ]);
    }

    /**
     * Update the specified user.
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        $user->update($request->validated());

        return redirect()
            ->route('admin.users.show', $user)
            ->with('success', 'User updated successfully.');
    }

    /**
     * Suspend the specified user.
     */
    public function suspend(User $user)
    {
        // Prevent suspending the current admin user
        if ($user->id === auth()->id()) {
            return redirect()
                ->route('admin.users.show', $user)
                ->with('error', 'You cannot suspend your own account.');
        }

        $user->update(['suspended_at' => now()]);

        return redirect()
            ->route('admin.users.show', $user)
            ->with('success', 'User has been suspended.');
    }

    /**
     * Unsuspend the specified user.
     */
    public function unsuspend(User $user)
    {
        $user->update(['suspended_at' => null]);

        return redirect()
            ->route('admin.users.show', $user)
            ->with('success', 'User has been unsuspended.');
    }
}

