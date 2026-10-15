<?php

// PRESENTATION NOTE: filesystems configuration.
// This file returns settings that Laravel reads when booting or using this service.
// Environment variables keep deployment-specific values outside the application code.

return [
    // Configure the default option for this Laravel component.
    'default' => env('FILESYSTEM_DISK', 'public'),
    // Select the disks implementation used by Laravel.
    'disks' => [
        // Configure the local option for this Laravel component.
        'local' => [
            // Select the driver implementation used by Laravel.
            'driver' => 'local',
            // Configure the root option for this Laravel component.
            'root' => storage_path('app'),
        ],
        // Configure the public option for this Laravel component.
        'public' => [
            // Keep the application-wide "public" disk name stable. The backend
            // remains configurable so local hosting can use the free local disk
            // while a persistent S3-compatible provider can be enabled later.
            'driver' => env('PUBLIC_DISK_DRIVER', 'local'),
            // Configure the root option for this Laravel component.
            'root' => storage_path('app/public'),
            // Set the url endpoint or location for this service.
            'url' => env('PUBLIC_DISK_URL', rtrim(env('APP_URL', ''), '/') . '/storage'),
            // Configure the visibility option for this Laravel component.
            'visibility' => env('PUBLIC_DISK_VISIBILITY', 'public'),
            // Read the key setting; keep real secrets in environment variables, not source control.
            'key' => env('AWS_ACCESS_KEY_ID'),
            // Read the secret setting; keep real secrets in environment variables, not source control.
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            // Configure the region option for this Laravel component.
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
            // Configure the bucket option for this Laravel component.
            'bucket' => env('AWS_BUCKET'),
            // Configure the endpoint option for this Laravel component.
            'endpoint' => env('AWS_ENDPOINT'),
            // Set the use path style endpoint endpoint or location for this service.
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            // Configure the throw option for this Laravel component.
            'throw' => false,
        ],
        // Configure the s3 option for this Laravel component.
        's3' => [
            // Select the driver implementation used by Laravel.
            'driver' => 's3',
            // Read the key setting; keep real secrets in environment variables, not source control.
            'key' => env('AWS_ACCESS_KEY_ID'),
            // Read the secret setting; keep real secrets in environment variables, not source control.
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            // Configure the region option for this Laravel component.
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
            // Configure the bucket option for this Laravel component.
            'bucket' => env('AWS_BUCKET'),
            // Set the url endpoint or location for this service.
            'url' => env('AWS_URL'),
            // Configure the endpoint option for this Laravel component.
            'endpoint' => env('AWS_ENDPOINT'),
            // Set the use path style endpoint endpoint or location for this service.
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            // Configure the visibility option for this Laravel component.
            'visibility' => env('AWS_VISIBILITY', 'public'),
            // Configure the throw option for this Laravel component.
            'throw' => false,
        ],
    ],
    // Configure the links option for this Laravel component.
    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],
];
