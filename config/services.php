<?php

// PRESENTATION NOTE: services configuration/bootstrap file.
// This file configures a Laravel service or prepares the framework before handling requests.
// Keep environment-specific credentials and endpoints in environment variables.

return [
    // Provider credentials are loaded from environment variables instead of hard-coded in source.
    'mailgun' => ['domain' => env('MAILGUN_DOMAIN'), 'secret' => env('MAILGUN_SECRET'), 'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net')],
    // Configure the postmark option for this Laravel service.
    'postmark' => ['token' => env('POSTMARK_TOKEN')],
    // Configure the ses option for this Laravel service.
    'ses' => ['key' => env('AWS_ACCESS_KEY_ID'), 'secret' => env('AWS_SECRET_ACCESS_KEY'), 'region' => env('AWS_DEFAULT_REGION', 'us-east-1')],
    // MpesaService reads these settings when connecting to Safaricom Daraja.
    'mpesa' => [
        // Read the consumer key value; real secrets should be stored in environment variables.
        'consumer_key' => env('MPESA_CONSUMER_KEY'),
        // Read the consumer secret value; real secrets should be stored in environment variables.
        'consumer_secret' => env('MPESA_CONSUMER_SECRET'),
        // Configure the shortcode option for this Laravel service.
        'shortcode' => env('MPESA_SHORTCODE'),
        // Read the passkey value; real secrets should be stored in environment variables.
        'passkey' => env('MPESA_PASSKEY'),
        // Configure the environment option for this Laravel service.
        'environment' => env('MPESA_ENV', 'sandbox'),
        // Set the callback url endpoint or storage location.
        'callback_url' => env('MPESA_CALLBACK_URL'),
    ],
];
