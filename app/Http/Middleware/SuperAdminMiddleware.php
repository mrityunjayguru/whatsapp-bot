<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SuperAdminMiddleware
{
    /**
     * Only allow users with role_id = 1 (superadmin) to proceed.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check() && auth()->user()->role_id === 1) {
            return $next($request);
        }

        abort(403, 'Access denied. Superadmin only.');
    }
}
