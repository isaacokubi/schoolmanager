<?php

// PRESENTATION NOTE: app configuration.
// This file returns settings that Laravel reads when booting or using this service.
// Environment variables keep deployment-specific values outside the application code.

return [
    // Configure the name option for this Laravel component.
    'name' => env('APP_NAME', 'School Manager'),
    // Configure the env option for this Laravel component.
    'env' => env('APP_ENV', 'production'),
    // Configure the debug option for this Laravel component.
    'debug' => (bool) env('APP_DEBUG', false),
    // Set the url endpoint or location for this service.
    'url' => env('APP_URL', 'http://localhost'),
    // Set the asset url endpoint or location for this service.
    'asset_url' => env('ASSET_URL'),
    // Configure the timezone option for this Laravel component.
    'timezone' => 'Africa/Nairobi',
    // Configure the locale option for this Laravel component.
    'locale' => 'en',
    // Configure the fallback locale option for this Laravel component.
    'fallback_locale' => 'en',
    // Configure the faker locale option for this Laravel component.
    'faker_locale' => 'en_US',
    // Read the key setting; keep real secrets in environment variables, not source control.
    'key' => env('APP_KEY'),
    // Configure the cipher option for this Laravel component.
    'cipher' => 'AES-256-CBC',
    // Configure the maintenance option for this Laravel component.
    'maintenance' => [
        // Select the driver implementation used by Laravel.
        'driver' => 'file',
        // Configure the store option for this Laravel component.
        'store' => 'file',
    ],
    // Select the providers implementation used by Laravel.
    'providers' => [
        Illuminate\Auth\AuthServiceProvider::class,
        Illuminate\Auth\Passwords\PasswordResetServiceProvider::class,
        Illuminate\Broadcasting\BroadcastServiceProvider::class,
        Illuminate\Bus\BusServiceProvider::class,
        Illuminate\Cache\CacheServiceProvider::class,
        Illuminate\Foundation\Providers\ConsoleSupportServiceProvider::class,
        Illuminate\Cookie\CookieServiceProvider::class,
        Illuminate\Database\DatabaseServiceProvider::class,
        Illuminate\Encryption\EncryptionServiceProvider::class,
        Illuminate\Filesystem\FilesystemServiceProvider::class,
        Illuminate\Foundation\Providers\FoundationServiceProvider::class,
        Illuminate\Hashing\HashServiceProvider::class,
        Illuminate\Mail\MailServiceProvider::class,
        Illuminate\Notifications\NotificationServiceProvider::class,
        Illuminate\Pagination\PaginationServiceProvider::class,
        Illuminate\Pipeline\PipelineServiceProvider::class,
        Illuminate\Queue\QueueServiceProvider::class,
        Illuminate\Redis\RedisServiceProvider::class,
        Illuminate\Session\SessionServiceProvider::class,
        Illuminate\Translation\TranslationServiceProvider::class,
        Illuminate\Validation\ValidationServiceProvider::class,
        Illuminate\View\ViewServiceProvider::class,
        App\Providers\AppServiceProvider::class,
        App\Providers\AuthServiceProvider::class,
        App\Providers\BroadcastServiceProvider::class,
        App\Providers\EventServiceProvider::class,
        App\Providers\RouteServiceProvider::class,
    ],
    // Configure the aliases option for this Laravel component.
    'aliases' => [
        // Configure the App option for this Laravel component.
        'App' => Illuminate\Support\Facades\App::class,
        // Configure the Arr option for this Laravel component.
        'Arr' => Illuminate\Support\Arr::class,
        // Configure the Artisan option for this Laravel component.
        'Artisan' => Illuminate\Support\Facades\Artisan::class,
        // Configure the Auth option for this Laravel component.
        'Auth' => Illuminate\Support\Facades\Auth::class,
        // Configure the Blade option for this Laravel component.
        'Blade' => Illuminate\Support\Facades\Blade::class,
        // Configure the Broadcast option for this Laravel component.
        'Broadcast' => Illuminate\Support\Facades\Broadcast::class,
        // Configure the Cache option for this Laravel component.
        'Cache' => Illuminate\Support\Facades\Cache::class,
        // Configure the Config option for this Laravel component.
        'Config' => Illuminate\Support\Facades\Config::class,
        // Configure the Cookie option for this Laravel component.
        'Cookie' => Illuminate\Support\Facades\Cookie::class,
        // Configure the Crypt option for this Laravel component.
        'Crypt' => Illuminate\Support\Facades\Crypt::class,
        // Configure the DB option for this Laravel component.
        'DB' => Illuminate\Support\Facades\DB::class,
        // Configure the Eloquent option for this Laravel component.
        'Eloquent' => Illuminate\Database\Eloquent\Model::class,
        // Configure the Event option for this Laravel component.
        'Event' => Illuminate\Support\Facades\Event::class,
        // Configure the File option for this Laravel component.
        'File' => Illuminate\Support\Facades\File::class,
        // Configure the Gate option for this Laravel component.
        'Gate' => Illuminate\Support\Facades\Gate::class,
        // Configure the Hash option for this Laravel component.
        'Hash' => Illuminate\Support\Facades\Hash::class,
        // Configure the Lang option for this Laravel component.
        'Lang' => Illuminate\Support\Facades\Lang::class,
        // Configure the Log option for this Laravel component.
        'Log' => Illuminate\Support\Facades\Log::class,
        // Configure the Mail option for this Laravel component.
        'Mail' => Illuminate\Support\Facades\Mail::class,
        // Configure the Notification option for this Laravel component.
        'Notification' => Illuminate\Support\Facades\Notification::class,
        // Configure the Redirect option for this Laravel component.
        'Redirect' => Illuminate\Support\Facades\Redirect::class,
        // Configure the Redis option for this Laravel component.
        'Redis' => Illuminate\Support\Facades\Redis::class,
        // Configure the Request option for this Laravel component.
        'Request' => Illuminate\Support\Facades\Request::class,
        // Configure the Response option for this Laravel component.
        'Response' => Illuminate\Support\Facades\Response::class,
        // Configure the Route option for this Laravel component.
        'Route' => Illuminate\Support\Facades\Route::class,
        // Configure the Schema option for this Laravel component.
        'Schema' => Illuminate\Support\Facades\Schema::class,
        // Configure the Session option for this Laravel component.
        'Session' => Illuminate\Support\Facades\Session::class,
        // Configure the Storage option for this Laravel component.
        'Storage' => Illuminate\Support\Facades\Storage::class,
        // Configure the Str option for this Laravel component.
        'Str' => Illuminate\Support\Str::class,
        // Set the URL endpoint or location for this service.
        'URL' => Illuminate\Support\Facades\URL::class,
        // Configure the Validator option for this Laravel component.
        'Validator' => Illuminate\Support\Facades\Validator::class,
        // Configure the View option for this Laravel component.
        'View' => Illuminate\Support\Facades\View::class,
    ],
];
