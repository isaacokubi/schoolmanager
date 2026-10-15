<?php

// PRESENTATION NOTE: queue configuration/bootstrap file.
// This file configures a Laravel service or prepares the framework before handling requests.
// Keep environment-specific credentials and endpoints in environment variables.

return [
    // Configure the default option for this Laravel service.
    'default' => env('QUEUE_CONNECTION', 'sync'),
    // Select the connections implementation used by Laravel.
    'connections' => [
        // Configure the sync option for this Laravel service.
        'sync' => ['driver' => 'sync'],
        // Configure the database option for this Laravel service.
        'database' => ['driver' => 'database', 'table' => 'jobs', 'queue' => 'default', 'retry_after' => 90],
        // Configure the redis option for this Laravel service.
        'redis' => ['driver' => 'redis', 'connection' => 'default', 'queue' => env('REDIS_QUEUE', 'default'), 'retry_after' => 90, 'block_for' => null],
    ],
    // Configure the failed option for this Laravel service.
    'failed' => ['driver' => env('QUEUE_FAILED_DRIVER', 'database-uuids'), 'database' => env('DB_CONNECTION', 'mysql'), 'table' => 'failed_jobs'],
];
