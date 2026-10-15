<?php

// PRESENTATION NOTE: auth configuration.
// This file returns settings that Laravel reads when booting or using this service.
// Environment variables keep deployment-specific values outside the application code.

return [
    // Configure the defaults option for this Laravel component.
    'defaults' => [
        // Select the guard implementation used by Laravel.
        'guard' => 'web',
        // Read the passwords setting; keep real secrets in environment variables, not source control.
        'passwords' => 'users',
    ],
    // Select the guards implementation used by Laravel.
    'guards' => [
        // Configure the web option for this Laravel component.
        'web' => [
            // Select the driver implementation used by Laravel.
            'driver' => 'session',
            // Select the provider implementation used by Laravel.
            'provider' => 'users',
        ],
    ],
    // Select the providers implementation used by Laravel.
    'providers' => [
        // Configure the users option for this Laravel component.
        'users' => [
            // Select the driver implementation used by Laravel.
            'driver' => 'eloquent',
            // Configure the model option for this Laravel component.
            'model' => App\Models\User::class,
        ],
    ],
    // Read the passwords setting; keep real secrets in environment variables, not source control.
    'passwords' => [
        // Configure the users option for this Laravel component.
        'users' => [
            // Select the provider implementation used by Laravel.
            'provider' => 'users',
            // Configure the table option for this Laravel component.
            'table' => 'password_resets',
            // Configure the expire option for this Laravel component.
            'expire' => 60,
            // Configure the throttle option for this Laravel component.
            'throttle' => 60,
        ],
    ],
    // Read the password timeout setting; keep real secrets in environment variables, not source control.
    'password_timeout' => 10800,
];
