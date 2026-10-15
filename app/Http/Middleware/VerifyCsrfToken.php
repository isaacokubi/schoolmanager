<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;
/**
 * Middleware component: verify csrf token.
 * Middleware inspects or transforms an incoming request before it reaches the controller,
 * or applies the framework behavior associated with this request stage.
 */

class VerifyCsrfToken extends Middleware
{
    protected $except = [];
}
