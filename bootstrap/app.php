<?php

// PRESENTATION NOTE: app configuration/bootstrap file.
// This file configures a Laravel service or prepares the framework before handling requests.
// Keep environment-specific credentials and endpoints in environment variables.

$app = new Illuminate\Foundation\Application(
    $_ENV['APP_BASE_PATH'] ?? dirname(__DIR__)
);

/*
|--------------------------------------------------------------------------
| Bind Important Interfaces
|--------------------------------------------------------------------------
|
| Here we bind the interfaces into the container so they can be resolved
| by the framework. You may change these bindings as needed.
|
*/

$app->singleton(
    Illuminate\Contracts\Http\Kernel::class,
    App\Http\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    App\Console\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    App\Exceptions\Handler::class
);

return $app;
