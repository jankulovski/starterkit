<?php

use App\Domain\Admin\Controllers\AdminController;
use App\Domain\Admin\Controllers\AdminCreditController;
use App\Domain\Admin\Controllers\BillingMetricsController;
use App\Domain\Admin\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', [AdminController::class, 'index'])->name('admin.index');

Route::prefix('users')->name('users.')->group(function () {
    Route::get('/', [UserController::class, 'index'])->name('index');
    Route::get('/{user}', [UserController::class, 'show'])->name('show');
    Route::patch('/{user}', [UserController::class, 'update'])->name('update');
    Route::post('/{user}/suspend', [UserController::class, 'suspend'])->name('suspend');
    Route::post('/{user}/unsuspend', [UserController::class, 'unsuspend'])->name('unsuspend');
});

Route::prefix('credits')->name('credits.')->group(function () {
    Route::post('/adjust', [AdminCreditController::class, 'adjustCredits'])->name('adjust');
    Route::get('/history', [AdminCreditController::class, 'getCreditHistory'])->name('history');
});

Route::prefix('billing')->name('billing.')->group(function () {
    Route::get('/metrics', [BillingMetricsController::class, 'index'])->name('metrics');
    Route::get('/metrics/realtime', [BillingMetricsController::class, 'realtime'])->name('metrics.realtime');
});

