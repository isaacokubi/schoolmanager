<?php

return [
    'default' => env('CACHE_DRIVER', env('CACHE_STORE', 'file')),
    'stores' => [
        'apc' => ['driver' => 'apc'],
        'array' => ['driver' => 'array'],
        'database' => ['driver' => 'database', 'table' => 'cache', 'connection' => null],
        'file' => ['driver' => 'file', 'path' => storage_path('framework/cache/data')],
        'memcached' => ['driver' => 'memcached', 'persistent_id' => env('MEMCACHED_PERSISTENT_ID'), 'sasl' => [env('MEMCACHED_USERNAME'), env('MEMCACHED_PASSWORD')], 'options' => [], 'servers' => [['host' => env('MEMCACHED_HOST', '127.0.0.1'), 'port' => env('MEMCACHED_PORT', 11211), 'weight' => 100]]],
        'redis' => ['driver' => 'redis', 'connection' => 'cache'],
        'dynamodb' => ['driver' => 'dynamodb', 'table' => env('DYNAMODB_CACHE_TABLE', 'cache'), 'endpoint' => env('DYNAMODB_ENDPOINT')],
    ],
    'prefix' => env('CACHE_PREFIX', 'schoolmanager_cache'),
];
