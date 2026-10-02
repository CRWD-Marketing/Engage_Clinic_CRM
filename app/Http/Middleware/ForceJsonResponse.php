<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Every /api/v1 request is treated as wanting JSON, whatever Accept header
 * the client sent. The mobile routes reuse web controllers whose actions
 * answer JSON only when `wantsJson()` is true (and redirect otherwise), and
 * it also makes validation, auth and abort() failures come back as
 * `{message, errors}` rather than a redirect or an HTML error page.
 *
 * Registered as global middleware (bootstrap/app.php) rather than on the
 * route group: Laravel always runs `auth` ahead of ordinary route
 * middleware, so on the group it would run too late to stop an
 * unauthenticated request being redirected to the login page.
 */
class ForceJsonResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('api/v1', 'api/v1/*')) {
            $request->headers->set('Accept', 'application/json');
        }

        return $next($request);
    }
}
