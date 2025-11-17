<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
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

        return Inertia::render('admin/index', [
            'metrics' => [
                'totalUsers' => $totalUsers,
                'adminCount' => $adminCount,
                'recentSignups' => $recentSignups,
            ],
        ]);
    }
}

