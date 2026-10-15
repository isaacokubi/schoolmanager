<?php

namespace App\Providers;

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\ServiceProvider;
/**
 * Defines the broadcast service provider component of the school-management application.
 *
 * This file contains application logic, framework configuration, or schema
 * changes. The method comments describe each operation's purpose and lifecycle.
 */

class BroadcastServiceProvider extends ServiceProvider
{
    /**
     * Register application-wide services, bindings, listeners, or configuration during Laravel's boot process.
     */
    public function boot()
    {
        Broadcast::routes();
        require base_path('routes/channels.php');
    }
}
