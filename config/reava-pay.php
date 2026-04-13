<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Reava Pay Platform Credentials
    |--------------------------------------------------------------------------
    |
    | These are the platform-level Reava Pay credentials used by the Gwinto
    | platform itself. Individual companies can override with their own
    | credentials configured via the admin/company settings panels.
    |
    */

    'key' => env('REAVA_PAY_KEY'),

    'public_key' => env('REAVA_PAY_PUBLIC_KEY'),

    'webhook_secret' => env('REAVA_PAY_WEBHOOK_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Base URL
    |--------------------------------------------------------------------------
    |
    | The Reava Pay API base URL. Override for sandbox/testing environments.
    |
    */

    'base_url' => env('REAVA_PAY_BASE_URL', 'https://reavapay.com/api/v1'),

    /*
    |--------------------------------------------------------------------------
    | HTTP Timeout
    |--------------------------------------------------------------------------
    */

    'timeout' => env('REAVA_PAY_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | Supported Payment Channels
    |--------------------------------------------------------------------------
    |
    | List of payment channels available through Reava Pay.
    |
    */

    'channels' => [
        'mpesa' => [
            'name' => 'M-Pesa',
            'icon' => 'bi-phone',
            'color' => '#4CAF50',
            'enabled' => true,
        ],
        'card' => [
            'name' => 'Card Payment',
            'icon' => 'bi-credit-card',
            'color' => '#2196F3',
            'enabled' => true,
        ],
        'bank_transfer' => [
            'name' => 'Bank Transfer',
            'icon' => 'bi-bank',
            'color' => '#FF9800',
            'enabled' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Supported Currencies
    |--------------------------------------------------------------------------
    */

    'currencies' => ['KES', 'UGX', 'TZS', 'NGN', 'GHS', 'ZAR', 'USD'],

    'default_currency' => env('REAVA_PAY_CURRENCY', 'KES'),

    /*
    |--------------------------------------------------------------------------
    | Queue
    |--------------------------------------------------------------------------
    */

    'queue' => env('REAVA_PAY_QUEUE', 'default'),

    /*
    |--------------------------------------------------------------------------
    | Webhook Path
    |--------------------------------------------------------------------------
    |
    | The path where Reava Pay sends webhook notifications.
    |
    */

    'webhook_path' => env('REAVA_PAY_WEBHOOK_PATH', 'webhooks/reava-pay'),

    /*
    |--------------------------------------------------------------------------
    | Auto-credit Wallet
    |--------------------------------------------------------------------------
    |
    | When true, successful payments automatically credit the tenant/company
    | wallet. When false, manual confirmation is required.
    |
    */

    'auto_credit_wallet' => env('REAVA_PAY_AUTO_CREDIT', true),

    /*
    |--------------------------------------------------------------------------
    | Payment Callback URL
    |--------------------------------------------------------------------------
    |
    | Base URL for payment status callbacks. Auto-detected if null.
    |
    */

    'callback_base_url' => env('REAVA_PAY_CALLBACK_URL'),

    /*
    |--------------------------------------------------------------------------
    | Transaction Limits
    |--------------------------------------------------------------------------
    */

    'limits' => [
        'min_amount' => env('REAVA_PAY_MIN_AMOUNT', 10),
        'max_amount' => env('REAVA_PAY_MAX_AMOUNT', 500000),
    ],

];
