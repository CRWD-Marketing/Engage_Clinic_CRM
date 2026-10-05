<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Auth\CreateAccount;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * User Management for the mobile app. Create, update and delete are the web
 * controller's own actions (they already answer JSON). The list and the
 * profile add what the web pages show around the JSON the web returns: the
 * stat tiles and the form options, and a user's manager and direct reports.
 */
class UserController extends CreateAccount
{
    /** GET /users — the page of users (10 per page, newest first) plus the stat tiles and form options. */
    public function index(Request $request): \Illuminate\Http\JsonResponse
    {
        $page = parent::index($request)->getData(true);

        return response()->json([
            'users' => $page['data'],
            'meta' => $page['meta'],
            // Stats reflect the whole table, not the current filter (as on the web page).
            'stats' => [
                'total' => User::count(),
                'active' => User::where('is_active', true)->count(),
                'inactive' => User::where('is_active', false)->count(),
                'departments' => User::distinct()->count('department'),
            ],
            'departments' => $this->departments,
            'roles' => $this->roles,
            'managers' => User::orderBy('first_name')->get(['id', 'first_name', 'last_name'])
                ->map(fn (User $u) => ['id' => $u->id, 'name' => trim($u->first_name.' '.$u->last_name)])->values(),
            'can_delete' => $request->user()->role === 'FULL_ADMIN',
        ]);
    }

    /** GET /users/{user} — user/[id].blade.php's data. */
    public function show(Request $request, User $user): \Illuminate\Http\JsonResponse
    {
        $user->load(['manager', 'subordinates']);

        return response()->json($user->toArray() + [
            'can_delete' => $request->user()->role === 'FULL_ADMIN',
        ]);
    }
}
