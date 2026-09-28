<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    // routes mein ->middleware('role:farmer') ya 'role:admin,farmer'
    public function handle(Request $request, Closure $next, string ...$allowedRoles): Response
    {
        $userRole = $request->user()?->role?->value;

        if (! in_array($userRole, $allowedRoles, true)) {
            abort(403, 'This area is not available for your account type.');
        }

        return $next($request);
    }
}
