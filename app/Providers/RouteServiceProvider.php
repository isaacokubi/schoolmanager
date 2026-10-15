<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;
/**
 * Defines the route service provider component of the school-management application.
 *
 * This file contains application logic, framework configuration, or schema
 * changes. The method comments describe each operation's purpose and lifecycle.
 */

class RouteServiceProvider extends ServiceProvider
{
    public const HOME = '/admin';

    /**
     * Register application-wide services, bindings, listeners, or configuration during Laravel's boot process.
     */
    public function boot()
    {
        $this->routes(function () {
            Route::middleware('web')
                ->group(base_path('routes/web.php'));

            Route::prefix('api')
                ->middleware('api')
                ->group(base_path('routes/api.php'));
        });
    }
}
