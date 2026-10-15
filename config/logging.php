<?php

// PRESENTATION NOTE: logging configuration/bootstrap file.
// This file configures a Laravel service or prepares the framework before handling requests.
// Keep environment-specific credentials and endpoints in environment variables.

return [
    // Configure the default option for this Laravel service.
    'default' => env('LOG_CHANNEL', 'stack'),
    // Configure the deprecations option for this Laravel service.
    'deprecations' => ['channel' => env('LOG_DEPRECATIONS_CHANNEL', 'null')],
    // Configure the channels option for this Laravel service.
    'channels' => [
        // Configure the stack option for this Laravel service.
        'stack' => ['driver' => 'stack', 'channels' => ['single'], 'ignore_exceptions' => false],
        // Configure the single option for this Laravel service.
        'single' => ['driver' => 'single', 'path' => storage_path('logs/laravel.log'), 'level' => env('LOG_LEVEL', 'debug'), 'replace_placeholders' => true],
        // Configure the daily option for this Laravel service.
        'daily' => ['driver' => 'daily', 'path' => storage_path('logs/laravel.log'), 'level' => env('LOG_LEVEL', 'debug'), 'days' => 14, 'replace_placeholders' => true],
        // Configure the stderr option for this Laravel service.
        'stderr' => ['driver' => 'monolog', 'level' => env('LOG_LEVEL', 'debug'), 'handler' => Monolog\Handler\StreamHandler::class, 'formatter' => env('LOG_STDERR_FORMATTER')],
        // Configure the syslog option for this Laravel service.
        'syslog' => ['driver' => 'syslog', 'level' => env('LOG_LEVEL', 'debug'), 'facility' => LOG_USER, 'replace_placeholders' => true],
        // Configure the errorlog option for this Laravel service.
        'errorlog' => ['driver' => 'errorlog', 'level' => env('LOG_LEVEL', 'debug'), 'replace_placeholders' => true],
        // Configure the null option for this Laravel service.
        'null' => ['driver' => 'monolog', 'handler' => Monolog\Handler\NullHandler::class],
    ],
];
