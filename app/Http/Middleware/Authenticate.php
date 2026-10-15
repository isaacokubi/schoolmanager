<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
/**
 * Defines the authenticate component of the application.
 *
 * This class coordinates its assigned HTTP, middleware, or framework lifecycle
 * responsibility. The comments below describe the purpose of each method.
 */

class Authenticate extends Middleware
{
    /**
     * Helper method redirectTo in Authenticate.php; it performs the step required by this component.
     */
    protected function redirectTo($request)
    {
        if (! $request->expectsJson()) {
            return route('login');
        }
    }
}
