<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'quickbooks' => [
        'client_id' => env('QUICKBOOKS_CLIENT_ID', 'demo-client-id'),
        'client_secret' => env('QUICKBOOKS_CLIENT_SECRET', 'demo-client-secret'),
        'redirect_uri' => env('QUICKBOOKS_REDIRECT_URI', 'http://localhost/integrations/quickbooks/callback'),
    ],

    'xero' => [
        'client_id' => env('XERO_CLIENT_ID', 'demo-xero-client-id'),
        'client_secret' => env('XERO_CLIENT_SECRET', 'demo-xero-client-secret'),
        'redirect_uri' => env('XERO_REDIRECT_URI', 'http://localhost/integrations/xero/callback'),
    ],

    'zoho' => [
        'client_id' => env('ZOHO_CLIENT_ID', 'demo-zoho-client-id'),
        'client_secret' => env('ZOHO_CLIENT_SECRET', 'demo-zoho-client-secret'),
        'redirect_uri' => env('ZOHO_REDIRECT_URI', 'http://localhost/integrations/zoho/callback'),
    ],

    'clamav' => [
        'enabled' => (bool) env('CLAMAV_ENABLED', false),
        'host' => env('CLAMD_HOST', '127.0.0.1'),
        'port' => (int) env('CLAMD_PORT', 3310),
        'timeout' => (float) env('CLAMD_TIMEOUT', 10),
    ],

    'document_extraction' => [
        'driver' => env('DOCUMENT_EXTRACTION_DRIVER', 'simulated'),
    ],

    'payments' => [
        'stripe' => [
            'secret' => env('STRIPE_SECRET'),
            'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
            'base_url' => env('STRIPE_BASE_URL', 'https://api.stripe.com'),
        ],
        'razorpay' => [
            'key_id' => env('RAZORPAY_KEY_ID'),
            'key_secret' => env('RAZORPAY_KEY_SECRET'),
            'webhook_secret' => env('RAZORPAY_WEBHOOK_SECRET'),
            'base_url' => env('RAZORPAY_BASE_URL', 'https://api.razorpay.com'),
        ],
        'paypal' => [
            'client_id' => env('PAYPAL_CLIENT_ID'),
            'client_secret' => env('PAYPAL_CLIENT_SECRET'),
            'webhook_id' => env('PAYPAL_WEBHOOK_ID'),
            'base_url' => env('PAYPAL_BASE_URL', 'https://api-m.sandbox.paypal.com'),
        ],
    ],

];
