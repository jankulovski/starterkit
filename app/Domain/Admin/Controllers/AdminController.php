<?php

namespace App\Domain\Admin\Controllers;

use App\Domain\Users\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
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

        return Inertia::render('domains/admin/pages/index', [
            'metrics' => [
                'totalUsers' => $totalUsers,
                'adminCount' => $adminCount,
                'recentSignups' => $recentSignups,
            ],
        ]);
    }
}

