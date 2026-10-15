<?php

// PRESENTATION NOTE: broadcasting configuration.
// This file returns settings that Laravel reads when booting or using this service.
// Environment variables keep deployment-specific values outside the application code.

return [
    // Configure the default option for this Laravel component.
    'default' => env('BROADCAST_DRIVER', 'null'),
    // Select the connections implementation used by Laravel.
    'connections' => [
        // Configure the pusher option for this Laravel component.
        'pusher' => ['driver' => 'pusher', 'key' => env('PUSHER_APP_KEY'), 'secret' => env('PUSHER_APP_SECRET'), 'app_id' => env('PUSHER_APP_ID'), 'options' => ['cluster' => env('PUSHER_APP_CLUSTER'), 'useTLS' => true]],
        // Configure the ably option for this Laravel component.
        'ably' => ['driver' => 'ably', 'key' => env('ABLY_KEY')],
        // Configure the redis option for this Laravel component.
        'redis' => ['driver' => 'redis', 'connection' => 'default'],
        // Configure the log option for this Laravel component.
        'log' => ['driver' => 'log'],
        // Configure the null option for this Laravel component.
        'null' => ['driver' => 'null'],
    ],
];
