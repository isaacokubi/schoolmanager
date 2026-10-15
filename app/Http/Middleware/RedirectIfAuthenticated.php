<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
/**
 * Defines the redirect if authenticated component of the application.
 *
 * This class coordinates its assigned HTTP, middleware, or framework lifecycle
 * responsibility. The comments below describe the purpose of each method.
 */

class RedirectIfAuthenticated
{
    /**
     * Process the incoming request or framework lifecycle event, applying this component's checks before passing control onward.
     */
    public function handle($request, Closure $next, ...$guards)
    {
        $guards = empty($guards) ? [null] : $guards;

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                $user = Auth::guard($guard)->user();

                if (in_array($user->role, ['admin', 'manager'], true)) {
                    return redirect()->route('admin.dashboard');
                }

                if (in_array($user->role, ['pupil', 'parent', 'sponsor', 'teacher'], true)) {
                    return redirect()->route('portal.dashboard');
                }

                Auth::guard($guard)->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }
        }

        return $next($request);
    }
}
