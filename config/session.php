<?php

// PRESENTATION NOTE: session configuration/bootstrap file.
// This file configures a Laravel service or prepares the framework before handling requests.
// Keep environment-specific credentials and endpoints in environment variables.

return [
    // Select the driver implementation used by Laravel.
    'driver' => env('SESSION_DRIVER', 'file'),
    // Configure the lifetime option for this Laravel service.
    'lifetime' => env('SESSION_LIFETIME', 120),
    // Configure the expire on close option for this Laravel service.
    'expire_on_close' => false,
    // Configure the encrypt option for this Laravel service.
    'encrypt' => false,
    // Configure the files option for this Laravel service.
    'files' => storage_path('framework/sessions'),
    // Select the connection implementation used by Laravel.
    'connection' => env('SESSION_CONNECTION'),
    // Configure the table option for this Laravel service.
    'table' => 'sessions',
    // Configure the store option for this Laravel service.
    'store' => env('SESSION_STORE'),
    // Configure the lottery option for this Laravel service.
    'lottery' => [2, 100],
    // Configure the cookie option for this Laravel service.
    'cookie' => env('SESSION_COOKIE', 'schoolmanager_session'),
    // Set the path endpoint or storage location.
    'path' => '/',
    // Configure the domain option for this Laravel service.
    'domain' => env('SESSION_DOMAIN'),
    // Configure the secure option for this Laravel service.
    'secure' => env('SESSION_SECURE_COOKIE'),
    // Configure the http only option for this Laravel service.
    'http_only' => true,
    // Configure the same site option for this Laravel service.
    'same_site' => 'lax',
];
