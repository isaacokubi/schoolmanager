<?php

// PRESENTATION NOTE: mail configuration/bootstrap file.
// This file configures a Laravel service or prepares the framework before handling requests.
// Keep environment-specific credentials and endpoints in environment variables.

return [
    // Configure the default option for this Laravel service.
    'default' => env('MAIL_MAILER', 'smtp'),
    // Select the mailers implementation used by Laravel.
    'mailers' => [
        // Configure the smtp option for this Laravel service.
        'smtp' => ['transport' => 'smtp', 'host' => env('MAIL_HOST', '127.0.0.1'), 'port' => env('MAIL_PORT', 2525), 'encryption' => env('MAIL_ENCRYPTION', 'tls'), 'username' => env('MAIL_USERNAME'), 'password' => env('MAIL_PASSWORD'), 'timeout' => null, 'auth_mode' => null],
        // Configure the ses option for this Laravel service.
        'ses' => ['transport' => 'ses'],
        // Configure the mailgun option for this Laravel service.
        'mailgun' => ['transport' => 'mailgun'],
        // Configure the postmark option for this Laravel service.
        'postmark' => ['transport' => 'postmark'],
        // Configure the sendmail option for this Laravel service.
        'sendmail' => ['transport' => 'sendmail', 'path' => env('MAIL_SENDMAIL_PATH', '/usr/sbin/sendmail -bs')],
        // Configure the log option for this Laravel service.
        'log' => ['transport' => 'log', 'channel' => env('MAIL_LOG_CHANNEL')],
        // Configure the array option for this Laravel service.
        'array' => ['transport' => 'array'],
    ],
    // Configure the from option for this Laravel service.
    'from' => ['address' => env('MAIL_FROM_ADDRESS', 'hello@example.com'), 'name' => env('MAIL_FROM_NAME', 'School Manager')],
    // Configure the markdown option for this Laravel service.
    'markdown' => ['theme' => 'default', 'paths' => [resource_path('views/vendor/mail')]],
];
