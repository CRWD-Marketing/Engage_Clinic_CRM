<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\User\RoleController as WebRoleController;

/**
 * Roles & access for the mobile app. Every change - templates, a user's
 * template, per-user access, suspend, add / edit / remove a user - is the
 * web controller's own action (they already answer JSON); the page itself
 * is returned here as the data user/roles.blade.php is rendered with.
 */
class RoleController extends WebRoleController
{
    /** GET /roles-access */
    public function index()
    {
        $d = parent::index()->getData();

        return response()->json([
            'users' => $d['usersForJs'],
            'templates' => $d['templatesForJs'],
            'modules' => $d['modules'],
            'levels' => $d['levels'],
            'actions' => $d['actions'],
            'base_roles' => $d['baseRoles'],
            'departments' => $d['departments'],
            'managers' => $d['managersForJs'],
            'department_for_role' => $d['departmentForRole'],
            'stats' => $d['stats'],
            'can_manage' => $d['canManage'],
        ]);
    }
}
