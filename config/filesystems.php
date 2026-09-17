<?php

return [
    'default' => env('FILESYSTEM_DISK', 'public'),
    'disks' => [
        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
        ],
        'public' => [
            // Keep the existing application-wide "public" disk name, but make
            // its backend configurable so production can use persistent S3
            // storage without changing every existing upload controller.
            'driver' => env('PUBLIC_DISK_DRIVER', 'local'),
            'root' => storage_path('app/public'),
            'url' => env('PUBLIC_DISK_URL'),
            'visibility' => env('PUBLIC_DISK_VISIBILITY', 'public'),
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
            'bucket' => env('AWS_BUCKET'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
        ],
        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'visibility' => env('AWS_VISIBILITY', 'public'),
        ],
    ],
    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],
];
