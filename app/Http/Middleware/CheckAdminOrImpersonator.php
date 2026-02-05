<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAdminOrImpersonator
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (! $request->user()) {
            abort(403, 'Unauthorized action.');
        }

        // Check if someone is impersonating
        if (session()->has('impersonate')) {
            // Get the impersonator ID from session
            $impersonatorId = session()->get('impersonate');

            // Get the impersonator (the original admin user)
            $impersonator = User::find($impersonatorId);

            // Check if the impersonator has the required role
            if ($impersonator && $impersonator->hasAnyRole($roles)) {
                return $next($request);
            }
        }

        // Check if the current user has the required role
        if ($request->user()->hasAnyRole($roles)) {
            return $next($request);
        }

        abort(403, 'Unauthorized action.');
    }
}
