<?php

// PRESENTATION NOTE: view configuration/bootstrap file.
// This file configures a Laravel service or prepares the framework before handling requests.
// Keep environment-specific credentials and endpoints in environment variables.

return [
    // Set the paths endpoint or storage location.
    'paths' => [resource_path('views')],
    // Configure the compiled option for this Laravel service.
    'compiled' => env('VIEW_COMPILED_PATH', realpath(storage_path('framework/views'))),
];
