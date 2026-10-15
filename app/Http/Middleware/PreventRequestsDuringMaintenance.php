<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance as Middleware;
/**
 * Middleware component: prevent requests during maintenance.
 * Middleware inspects or transforms an incoming request before it reaches the controller,
 * or applies the framework behavior associated with this request stage.
 */

class PreventRequestsDuringMaintenance extends Middleware
{
    protected $except = [];
}
