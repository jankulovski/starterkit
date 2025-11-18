<?php

use App\Domain\Settings\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    // API routes for settings dialog
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Email change verification routes
    Route::get('settings/email/verify', [ProfileController::class, 'verifyEmailChange'])
        ->middleware('signed')
        ->name('settings.email.verify');

    Route::post('settings/email/resend', [ProfileController::class, 'resendEmailVerification'])
        ->name('settings.email.resend');
});

// Cancel email change route (outside auth middleware - works via signed URL)
Route::get('settings/email/cancel', [ProfileController::class, 'cancelEmailChange'])
    ->middleware('signed')
    ->name('settings.email.cancel');
