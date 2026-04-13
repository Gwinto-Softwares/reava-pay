<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Gwinto API Credentials
    |--------------------------------------------------------------------------
    |
    | Your Gwinto platform credentials issued by Reava Pay. These connect your
    | Gwinto instance to the Reava Pay gateway for payment processing, wallet
    | sync, and merchant/tenant onboarding.
    |
    */

    'api_key' => env('GWINTO_API_KEY'),

    'secret' => env('GWINTO_SECRET'),

    'webhook_secret' => env('GWINTO_WEBHOOK_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Reava Pay Credentials
    |--------------------------------------------------------------------------
    |
    | Your Reava Pay merchant secret key used for direct API calls.
    |
    */

    'reava_pay_key' => env('REAVA_PAY_KEY'),

    'reava_pay_public_key' => env('REAVA_PAY_PUBLIC_KEY'),

    'reava_pay_base_url' => env('REAVA_PAY_BASE_URL', 'https://reavapay.com/api/v1'),

    /*
    |--------------------------------------------------------------------------
    | Environment
    |--------------------------------------------------------------------------
    */

    'environment' => env('GWINTO_ENVIRONMENT', 'live'),

    /*
    |--------------------------------------------------------------------------
    | HTTP Timeout
    |--------------------------------------------------------------------------
    */

    'timeout' => 30,

    /*
    |--------------------------------------------------------------------------
    | Queue
    |--------------------------------------------------------------------------
    */

    'queue' => env('GWINTO_QUEUE', 'default'),

    /*
    |--------------------------------------------------------------------------
    | Auto-Onboarding
    |--------------------------------------------------------------------------
    |
    | When enabled, new companies are automatically registered as Reava Pay
    | merchants, and new tenants as Reava Pay customers. Gwinto wallets are
    | synced bi-directionally with Reava Pay float accounts.
    |
    */

    'auto_onboard_companies' => env('GWINTO_AUTO_ONBOARD_COMPANIES', true),

    'auto_onboard_tenants' => env('GWINTO_AUTO_ONBOARD_TENANTS', true),

    'auto_sync_wallets' => env('GWINTO_AUTO_SYNC_WALLETS', true),

    /*
    |--------------------------------------------------------------------------
    | Payment Channels
    |--------------------------------------------------------------------------
    */

    'channels' => [
        'mpesa' => env('GWINTO_MPESA_ENABLED', true),
        'card' => env('GWINTO_CARD_ENABLED', true),
        'bank_transfer' => env('GWINTO_BANK_ENABLED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Currency
    |--------------------------------------------------------------------------
    */

    'currency' => env('GWINTO_CURRENCY', 'KES'),

    /*
    |--------------------------------------------------------------------------
    | Webhook Path
    |--------------------------------------------------------------------------
    */

    'webhook_path' => env('GWINTO_WEBHOOK_PATH', 'webhooks/gwinto'),

    /*
    |--------------------------------------------------------------------------
    | Subscriptions & Recurring
    |--------------------------------------------------------------------------
    |
    | Enable tenants to consume recurring subscription plans via Reava Pay.
    |
    */

    'subscriptions_enabled' => env('GWINTO_SUBSCRIPTIONS_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Agent Network
    |--------------------------------------------------------------------------
    |
    | Reava Pay agents serve as Gwinto agents for rent collection,
    | drop-off/pick-up points, and cash operations.
    |
    */

    'agent_network_enabled' => env('GWINTO_AGENT_NETWORK_ENABLED', true),

];
