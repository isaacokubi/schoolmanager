<?php

// PRESENTATION NOTE: database configuration.
// This file returns settings that Laravel reads when booting or using this service.
// Environment variables keep deployment-specific values outside the application code.

return [
    // Configure the default option for this Laravel component.
    'default' => env('DB_CONNECTION', 'mysql'),
    // Select the connections implementation used by Laravel.
    'connections' => [
        // Configure the mysql option for this Laravel component.
        'mysql' => [
            // Select the driver implementation used by Laravel.
            'driver' => 'mysql',
            // Set the url endpoint or location for this service.
            'url' => env('DATABASE_URL'),
            // Set the host endpoint or location for this service.
            'host' => env('DB_HOST', '127.0.0.1'),
            // Set the port endpoint or location for this service.
            'port' => env('DB_PORT', '3306'),
            // Configure the database option for this Laravel component.
            'database' => env('DB_DATABASE', 'schoolmanager'),
            // Configure the username option for this Laravel component.
            'username' => env('DB_USERNAME', 'root'),
            // Read the password setting; keep real secrets in environment variables, not source control.
            'password' => env('DB_PASSWORD', ''),
            // Configure the unix socket option for this Laravel component.
            'unix_socket' => env('DB_SOCKET', ''),
            // Configure the charset option for this Laravel component.
            'charset' => 'utf8mb4',
            // Configure the collation option for this Laravel component.
            'collation' => 'utf8mb4_unicode_ci',
            // Configure the prefix option for this Laravel component.
            'prefix' => '',
            // Configure the prefix indexes option for this Laravel component.
            'prefix_indexes' => true,
            // Configure the strict option for this Laravel component.
            'strict' => true,
            // Configure the engine option for this Laravel component.
            'engine' => null,
            // Configure the options option for this Laravel component.
            'options' => extension_loaded('pdo_mysql') ? array_filter([PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA')]) : [],
        ],
        // Configure the sqlite option for this Laravel component.
        'sqlite' => [
            // Select the driver implementation used by Laravel.
            'driver' => 'sqlite',
            // Set the url endpoint or location for this service.
            'url' => env('DATABASE_URL'),
            // Configure the database option for this Laravel component.
            'database' => env('DB_DATABASE', database_path('database.sqlite')),
            // Configure the prefix option for this Laravel component.
            'prefix' => '',
            // Read the foreign key constraints setting; keep real secrets in environment variables, not source control.
            'foreign_key_constraints' => true,
        ],
    ],
    // Configure the migrations option for this Laravel component.
    'migrations' => 'migrations',
    // Configure the redis option for this Laravel component.
    'redis' => [
        // Configure the client option for this Laravel component.
        'client' => env('REDIS_CLIENT', 'phpredis'),
        // Configure the default option for this Laravel component.
        'default' => [
            // Set the host endpoint or location for this service.
            'host' => env('REDIS_HOST', '127.0.0.1'),
            // Read the password setting; keep real secrets in environment variables, not source control.
            'password' => env('REDIS_PASSWORD'),
            // Set the port endpoint or location for this service.
            'port' => env('REDIS_PORT', 6379),
            // Configure the database option for this Laravel component.
            'database' => env('REDIS_DB', 0),
        ],
    ],
];
