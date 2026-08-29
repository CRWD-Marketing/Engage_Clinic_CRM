<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gates an entire route group behind a module key from config/role_permissions.php,
 * e.g. `feature:billing`. This is the server-side enforcement of the same check
 * User::canAccessFeature() uses to hide sidebar links - hiding a link alone never
 * stopped a direct URL hit, so every module route must also carry this middleware.
 */
class FeatureMiddleware
{
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        if (!Auth::user()->canAccessFeature($feature)) {
            abort(403, 'You do not have access to this feature.');
        }

        return $next($request);
    }
}
