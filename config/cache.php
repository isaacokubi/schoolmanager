<?php

// PRESENTATION NOTE: cache configuration.
// This file returns settings that Laravel reads when booting or using this service.
// Environment variables keep deployment-specific values outside the application code.

return [
    // Configure the default option for this Laravel component.
    'default' => env('CACHE_DRIVER', env('CACHE_STORE', 'file')),
    // Configure the stores option for this Laravel component.
    'stores' => [
        // Configure the apc option for this Laravel component.
        'apc' => ['driver' => 'apc'],
        // Configure the array option for this Laravel component.
        'array' => ['driver' => 'array'],
        // Configure the database option for this Laravel component.
        'database' => ['driver' => 'database', 'table' => 'cache', 'connection' => null],
        // Configure the file option for this Laravel component.
        'file' => ['driver' => 'file', 'path' => storage_path('framework/cache/data')],
        // Configure the memcached option for this Laravel component.
        'memcached' => ['driver' => 'memcached', 'persistent_id' => env('MEMCACHED_PERSISTENT_ID'), 'sasl' => [env('MEMCACHED_USERNAME'), env('MEMCACHED_PASSWORD')], 'options' => [], 'servers' => [['host' => env('MEMCACHED_HOST', '127.0.0.1'), 'port' => env('MEMCACHED_PORT', 11211), 'weight' => 100]]],
        // Configure the redis option for this Laravel component.
        'redis' => ['driver' => 'redis', 'connection' => 'cache'],
        // Configure the dynamodb option for this Laravel component.
        'dynamodb' => ['driver' => 'dynamodb', 'table' => env('DYNAMODB_CACHE_TABLE', 'cache'), 'endpoint' => env('DYNAMODB_ENDPOINT')],
    ],
    // Configure the prefix option for this Laravel component.
    'prefix' => env('CACHE_PREFIX', 'schoolmanager_cache'),
];
