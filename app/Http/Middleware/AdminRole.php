<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminRole
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if (!$user || !in_array($user->role ?? 'admin', ['admin', 'manager'], true)) {
            abort(403, 'You do not have permission to access the administration area.');
        }
        return $next($request);
    }
}
