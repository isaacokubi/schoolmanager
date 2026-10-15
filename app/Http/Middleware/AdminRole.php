<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
/**
 * Defines the admin role component of the application.
 *
 * This class coordinates its assigned HTTP, middleware, or framework lifecycle
 * responsibility. The comments below describe the purpose of each method.
 */

class AdminRole
{
    /**
     * Process the incoming request or framework lifecycle event, applying this component's checks before passing control onward.
     */
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if (!$user || !in_array($user->role ?? 'admin', ['admin', 'manager'], true)) {
            abort(403, 'You do not have permission to access the administration area.');
        }
        return $next($request);
    }
}
