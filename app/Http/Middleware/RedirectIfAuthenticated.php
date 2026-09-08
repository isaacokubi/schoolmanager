<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class RedirectIfAuthenticated
{
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
