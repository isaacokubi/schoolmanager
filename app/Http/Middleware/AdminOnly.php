<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
/**
 * Defines the admin only component of the application.
 *
 * This class coordinates its assigned HTTP, middleware, or framework lifecycle
 * responsibility. The comments below describe the purpose of each method.
 */

class AdminOnly
{
    /**
     * Process the incoming request or framework lifecycle event, applying this component's checks before passing control onward.
     */
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        $role = strtolower((string) ($user->role ?? ''));

        if (!$user || !in_array($role, ['admin', 'administrator', 'superadmin'], true)) {
            abort(403, 'This area is restricted to school administrators.');
        }

        return $next($request);
    }
}
