<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminOnly
{
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
