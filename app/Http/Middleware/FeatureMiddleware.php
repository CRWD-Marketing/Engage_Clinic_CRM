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
 *
 * Every module's route file wraps its index route AND every create/update/
 * delete route for that module in this same middleware group, so this is
 * also the single place "View only" (RoleTemplate::LEVELS) gets enforced
 * for the whole system: a safe (GET/HEAD) request still reads the module
 * normally, but anything that would change data is blocked here before it
 * ever reaches the controller - a user on "View only" genuinely can't take
 * any action in that module, not just have the buttons hidden client-side.
 */
class FeatureMiddleware
{
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        if (!$user->canAccessFeature($feature)) {
            abort(403, 'You do not have access to this feature.');
        }

        if (!$request->isMethodSafe() && $user->levelFor($feature) === 'view') {
            abort(403, 'Your access to this area is view-only.');
        }

        return $next($request);
    }
}
