<?php

use App\Domain\Billing\Controllers\BillingController;
use App\Domain\Billing\Controllers\CheckoutController;
use App\Domain\Billing\Controllers\StripeWebhookController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('/billing/checkout', [BillingController::class, 'createCheckoutSession'])->name('billing.checkout');
    Route::post('/billing/portal', [BillingController::class, 'createBillingPortalSession'])->name('billing.portal');
    Route::post('/billing/cancel', [BillingController::class, 'cancelSubscription'])->name('billing.cancel');
    Route::post('/billing/credits/purchase', [BillingController::class, 'purchaseCredits'])->name('billing.credits.purchase');

    // Checkout redirects
    Route::get('/billing/checkout/success', [CheckoutController::class, 'success'])->name('billing.checkout.success');
    Route::get('/billing/checkout/cancel', [CheckoutController::class, 'cancel'])->name('billing.checkout.cancel');
});

// Stripe webhook (no auth required, but must be signed)
Route::post('/stripe/webhook', [StripeWebhookController::class, 'handleWebhook'])->name('stripe.webhook');

