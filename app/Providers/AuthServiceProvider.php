<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
/**
 * Defines the auth service provider component of the school-management application.
 *
 * This file contains application logic, framework configuration, or schema
 * changes. The method comments describe each operation's purpose and lifecycle.
 */

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [];

    /**
     * Register application-wide services, bindings, listeners, or configuration during Laravel's boot process.
     */
    public function boot()
    {
        $this->registerPolicies();
    }
}
