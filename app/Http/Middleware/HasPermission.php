<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HasPermission
{
    /**
     * Usage: middleware('permission:leads.view')
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = auth()->user();

        if (!$user) {
            abort(403, 'Unauthorized.');
        }

        // Superadmin — all access
        if ($user->role_id === 1) {
            return $next($request);
        }

        // Company owner — all access within their scope
        if ($user->role_id === 2) {
            return $next($request);
        }

        // Sub-user — check role permissions
        if ($user->hasPermission($permission)) {
            return $next($request);
        }

        abort(403, 'You do not have permission to perform this action.');
    }
}
