<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\TrimStrings as Middleware;
/**
 * Middleware component: trim strings.
 * Middleware inspects or transforms an incoming request before it reaches the controller,
 * or applies the framework behavior associated with this request stage.
 */

class TrimStrings extends Middleware
{
    protected $except = [
        'password',
        'password_confirmation',
    ];
}
