<?php

use App\Domain\Settings\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware('auth')->group(function () {
    Route::redirect('settings', '/settings/profile');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Email change verification routes
    Route::get('settings/email/verify', [ProfileController::class, 'verifyEmailChange'])
        ->middleware('signed')
        ->name('settings.email.verify');

    Route::post('settings/email/resend', [ProfileController::class, 'resendEmailVerification'])
        ->name('settings.email.resend');

    // Password and 2FA routes are disabled - only Google OAuth and Magic Link are used for authentication

    Route::get('settings/appearance', function () {
        return Inertia::render('domains/settings/pages/appearance');
    })->name('appearance.edit');
});

// Cancel email change route (outside auth middleware - works via signed URL)
Route::get('settings/email/cancel', [ProfileController::class, 'cancelEmailChange'])
    ->middleware('signed')
    ->name('settings.email.cancel');
