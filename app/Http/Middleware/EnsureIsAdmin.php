<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Gate on the Kuartal ID entitlement behind User::isAdmin() (config/kuartalimpact.php). */
class EnsureIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user() || ! $request->user()->isAdmin()) {
            abort(403, "You don't have admin access on this Kuartal ID account.");
        }

        return $next($request);
    }
}
