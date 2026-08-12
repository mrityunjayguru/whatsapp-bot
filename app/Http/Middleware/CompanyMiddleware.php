<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CompanyMiddleware
{
    /**
     * Allow:
     *  - role_id = 2 (company owner)
     *  - Sub-users created by a company (created_by is not null and role_id not in 1,2)
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if (!$user) {
            abort(403, 'Access denied.');
        }

        // Company owner
        if ($user->role_id === 2) {
            return $next($request);
        }

        // Sub-user created by a company
        if ($user->created_by !== null && !in_array($user->role_id, [1, 2])) {
            return $next($request);
        }

        abort(403, 'Access denied.');
    }
}
