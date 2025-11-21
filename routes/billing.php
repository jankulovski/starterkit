<?php

use App\Domain\Billing\Controllers\BillingController;
use App\Domain\Billing\Controllers\CheckoutController;
use App\Domain\Billing\Controllers\StripeWebhookController;
use Illuminate\Support\Facades\Route;

// SECURITY: Rate limiting prevents abuse of billing endpoints
// throttle:20,1 = 20 requests per minute per user
// This prevents users from spamming checkout creation or subscription changes
Route::middleware(['auth', 'verified', 'throttle:20,1'])->group(function () {
    Route::post('/billing/checkout', [BillingController::class, 'createCheckoutSession'])->name('billing.checkout');
    Route::post('/billing/change-subscription', [BillingController::class, 'changeSubscription'])->name('billing.change-subscription');
    Route::post('/billing/portal', [BillingController::class, 'createBillingPortalSession'])->name('billing.portal');
    Route::post('/billing/cancel', [BillingController::class, 'cancelSubscription'])->name('billing.cancel');
    Route::post('/billing/resume', [BillingController::class, 'resumeSubscription'])->name('billing.resume');
    Route::post('/billing/credits/purchase', [BillingController::class, 'purchaseCredits'])->name('billing.credits.purchase');

    // Checkout redirects (no throttle needed - these are redirects from Stripe)
    Route::get('/billing/checkout/success', [CheckoutController::class, 'success'])->name('billing.checkout.success')->withoutMiddleware('throttle:20,1');
    Route::get('/billing/checkout/cancel', [CheckoutController::class, 'cancel'])->name('billing.checkout.cancel')->withoutMiddleware('throttle:20,1');
});

// Stripe webhook (no auth required, but must be signed)
Route::post('/stripe/webhook', [StripeWebhookController::class, 'handleWebhook'])->name('cashier.webhook');

