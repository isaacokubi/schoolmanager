<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;
/**
 * Defines the app service provider component of the school-management application.
 *
 * This file contains application logic, framework configuration, or schema
 * changes. The method comments describe each operation's purpose and lifecycle.
 */

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register services or bindings with Laravel's service container.
     */
    public function register()
    {
        // Application services may be registered here.
    }

    /**
     * Register application-wide services, bindings, listeners, or configuration during Laravel's boot process.
     */
    public function boot()
    {
        Paginator::defaultView('vendor.pagination.admin');
    }
}
