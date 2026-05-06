<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     * Usage in routes: middleware('role:owner') or middleware('role:cashier')
     */
    public function handle(Request $request, Closure $next, string ...$roles)
    {
        $user = $request->user();

        // superadmin bypasses all role restrictions
        if (!$user || ($user->role !== 'superadmin' && !in_array($user->role, $roles))) {
            abort(403, 'Access denied.');
        }

        return $next($request);
    }
}
