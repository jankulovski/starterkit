<?php

use App\Domain\Billing\Controllers\BillingSettingsController;
use App\Domain\Settings\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    // API routes for settings dialog
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('settings/billing', [BillingSettingsController::class, 'index'])->name('settings.billing');
});
