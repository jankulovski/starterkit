<?php

namespace App\Providers;

use App\Domain\Users\Models\User;
use Illuminate\Support\ServiceProvider;
use Laravel\Cashier\Cashier;
use Stripe\StripeClient;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(StripeClient::class, function ($app) {
            $secret = config('services.stripe.secret');
            
            if (empty($secret)) {
                $message = 'Stripe secret key is not configured. Please set STRIPE_SECRET in your .env file. ' .
                          'For local development, you can use a test key from https://dashboard.stripe.com/test/apikeys';
                
                if (app()->environment('production')) {
                    throw new \RuntimeException($message);
                }
                
                // For non-production, throw a more helpful exception
                throw new \RuntimeException($message);
            }
            
            // Validate key format (Stripe keys start with sk_test_ or sk_live_)
            if (!preg_match('/^sk_(test|live)_/', $secret)) {
                throw new \RuntimeException(
                    'Invalid Stripe secret key format. Stripe keys must start with sk_test_ or sk_live_. ' .
                    'Please check your STRIPE_SECRET in .env file.'
                );
            }
            
            return new StripeClient($secret);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Configure Cashier to use the correct User model
        Cashier::useCustomerModel(User::class);
    }
}
