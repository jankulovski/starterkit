<?php

use App\Domain\Auth\Controllers\GoogleOAuthController;
use App\Domain\Auth\Controllers\MagicLinkController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

// Google OAuth routes
Route::get('/auth/google', [GoogleOAuthController::class, 'redirect'])->name('auth.google');
Route::get('/auth/google/callback', [GoogleOAuthController::class, 'callback'])->name('auth.google.callback');

// Magic Link routes
Route::post('/auth/magic-link/request', [MagicLinkController::class, 'request'])->name('auth.magic-link.request');
Route::get('/auth/magic-link/verify', [MagicLinkController::class, 'verify'])
    ->middleware('signed')
    ->name('auth.magic-link.verify');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');
});

Route::middleware(['auth', 'verified', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(base_path('routes/admin.php'));

require __DIR__.'/settings.php';
require __DIR__.'/billing.php';
