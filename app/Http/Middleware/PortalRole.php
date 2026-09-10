<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PortalRole
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $user = $request->user();
        if (!$user || !in_array($user->role, $roles, true)) {
            abort(403, 'You do not have permission to access this portal.');
        }

        $profile = DB::table('portal_profiles')
            ->where('user_id', $user->id)
            ->where('active', true)
            ->first();

        if (!$profile || $profile->portal_type !== $user->role) {
            abort(403, 'Your portal account is inactive or not configured correctly.');
        }

        return $next($request);
    }
}
