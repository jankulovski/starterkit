<?php

return [
    'plans' => [
        'free' => [
            'key' => 'free',
            'name' => 'Free',
            'type' => 'free',
            'stripe_price_id' => null,
            'monthly_credits' => 0,
            'features' => ['basic_usage'],
        ],
        'pro' => [
            'key' => 'pro',
            'name' => 'Pro',
            'type' => 'paid',
            'interval' => 'monthly',
            'stripe_price_id' => env('STRIPE_PRICE_PRO_MONTHLY'),
            'monthly_credits' => 200,
            'features' => ['basic_usage', 'advanced_usage', 'priority'],
        ],
        'business' => [
            'key' => 'business',
            'name' => 'Business',
            'type' => 'paid',
            'interval' => 'monthly',
            'stripe_price_id' => env('STRIPE_PRICE_BUSINESS_MONTHLY'),
            'monthly_credits' => 1000,
            'features' => ['basic_usage', 'advanced_usage', 'priority', 'enterprise_features'],
        ],
    ],

    'credit_packs' => [
        'small' => [
            'key' => 'small',
            'name' => '50 Credits',
            'credits' => 50,
            'stripe_price_id' => env('STRIPE_PRICE_CREDITS_50'),
        ],
        'medium' => [
            'key' => 'medium',
            'name' => '100 Credits',
            'credits' => 100,
            'stripe_price_id' => env('STRIPE_PRICE_CREDITS_100'),
        ],
        'large' => [
            'key' => 'large',
            'name' => '500 Credits',
            'credits' => 500,
            'stripe_price_id' => env('STRIPE_PRICE_CREDITS_500'),
        ],
    ],
];

