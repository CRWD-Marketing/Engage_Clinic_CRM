<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\RoleTemplate;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class RoleController extends Controller
{
    private const DEPARTMENTS = ['EXECUTIVE', 'HUMAN_RESOURCES', 'SALES', 'COORDINATOR', 'FINANCE', 'CLINICAL', 'CONTENT_MANAGEMENT', 'OTHER'];

    /**
     * Templates whose base role reaches clinical records or the whole system -
     * only a Full Admin may hand these out (same rule as User Management).
     */
    private const SENSITIVE_ROLES = ['FULL_ADMIN', 'CLINICAL_SUPERVISOR'];

    /**
     * Roles & access - users with their role template / access level, and the
     * template cards (system + custom).
     */
    public function index()
    {
        $templates = RoleTemplate::withCount('users')->orderBy('is_system', 'desc')->orderBy('sort_order')->orderBy('name')->get();
        $users = User::with('roleTemplate')->orderBy('first_name')->orderBy('last_name')->get();

        return view('user.roles', [
            'usersForJs' => $users->map(fn (User $u) => $this->userPayload($u))->values(),
            'templatesForJs' => $templates->map(fn (RoleTemplate $t) => $this->templatePayload($t))->values(),
            'modules' => RoleTemplate::MODULES,
            'levels' => RoleTemplate::LEVELS,
            'actions' => RoleTemplate::ACTIONS,
            'baseRoles' => RoleTemplate::BASE_ROLES,
            'departments' => self::DEPARTMENTS,
            'managersForJs' => $users->where('is_active', true)->map(fn (User $u) => [
                'id' => $u->id,
                'name' => trim($u->first_name.' '.$u->last_name),
            ])->values(),
            'departmentForRole' => RoleTemplate::DEPARTMENT_FOR_ROLE,
            'stats' => [
                'total' => $users->count(),
                'active' => $users->filter(fn ($u) => $u->accessStatus() === 'active')->count(),
                'invited' => $users->filter(fn ($u) => $u->accessStatus() === 'invited')->count(),
                'suspended' => $users->filter(fn ($u) => $u->accessStatus() === 'suspended')->count(),
            ],
            'canManage' => $this->canManage(),
        ]);
    }

    // ---- Templates ----------------------------------------------------

    public function storeTemplate(Request $request)
    {
        $this->assertCanManage();

        $data = $this->validateTemplate($request);
        $key = Str::slug($data['name'], '_');
        $base = $key;
        for ($i = 2; RoleTemplate::where('key', $key)->exists(); $i++) {
            $key = "{$base}_{$i}";
        }

        $template = RoleTemplate::create($data + [
            'key' => $key,
            'is_system' => false,
            'sort_order' => (RoleTemplate::max('sort_order') ?? 0) + 1,
        ]);

        return response()->json([
            'message' => "Role template “{$template->name}” created.",
            'template' => $this->templatePayload($template->loadCount('users')),
        ], 201);
    }

    public function updateTemplate(Request $request, RoleTemplate $template)
    {
        $this->assertCanManage();

        if ($template->isLocked()) {
            return response()->json(['message' => 'Full Admin always has every module and action.'], 422);
        }

        $data = $this->validateTemplate($request, $template);

        // System templates keep their name / base role - only the grants move.
        if ($template->is_system) {
            unset($data['name'], $data['base_role']);
        }

        $template->update($data);

        return response()->json([
            'message' => 'Template updated — people already assigned keep the access they hold.',
            'template' => $this->templatePayload($template->fresh()->loadCount('users')),
        ]);
    }

    public function destroyTemplate(RoleTemplate $template)
    {
        $this->assertCanManage();

        if ($template->is_system) {
            return response()->json(['message' => 'System templates can’t be deleted.'], 422);
        }

        // Anyone on the custom template drops back to the system template
        // for their base role, keeping the modules/actions they already hold.
        $fallback = RoleTemplate::systemForRole($template->base_role) ?? RoleTemplate::where('key', 'basic')->first();
        $template->users()->update(['role_template_id' => $fallback?->id]);

        $template->delete();

        return response()->json(['message' => 'Role template deleted.']);
    }

    // ---- Users --------------------------------------------------------

    /**
     * Add a user - the same profile fields as User Management, with the role
     * template deciding their access. The generated password is shown to the
     * admin to hand over; optionally a set-your-password link is emailed too.
     * They show as "Invited" until their first sign-in.
     */
    public function storeUser(Request $request)
    {
        $this->assertCanManage();

        $validator = Validator::make($request->all(), [
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'phone_number' => ['nullable', 'string', 'max:20'],
            'job_title' => ['nullable', 'string', 'max:100'],
            'password' => ['required', 'string', 'min:8', 'max:72'],
            'role_template_id' => ['required', 'exists:role_templates,id'],
            'department' => ['nullable', 'in:'.implode(',', self::DEPARTMENTS)],
            'manager_id' => ['nullable', 'exists:users,id'],
            'start_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['sometimes', 'boolean'],
            'send_invite' => ['sometimes', 'boolean'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first(), 'errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $template = RoleTemplate::findOrFail($data['role_template_id']);

        if (in_array($template->base_role, self::SENSITIVE_ROLES, true) && auth()->user()->role !== 'FULL_ADMIN') {
            return response()->json(['message' => "Only a Full Admin can assign {$template->name}."], 403);
        }

        $user = User::create([
            'first_name' => $data['first_name'],
            'middle_name' => $data['middle_name'] ?? null,
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'phone_number' => $data['phone_number'] ?? null,
            'job_title' => $data['job_title'] ?? null,
            'password' => $data['password'],
            'department' => $data['department'] ?? (RoleTemplate::DEPARTMENT_FOR_ROLE[$template->base_role] ?? 'OTHER'),
            'role' => $template->base_role,
            'manager_id' => $data['manager_id'] ?? null,
            'start_date' => $data['start_date'] ?? now()->toDateString(),
            'notes' => $data['notes'] ?? null,
            'is_active' => $request->boolean('is_active', true),
            'invited_at' => now(),
        ]);
        $user->applyTemplate($template);

        $emailed = false;
        if ($request->boolean('send_invite')) {
            try {
                $emailed = Password::sendResetLink(['email' => $user->email]) === Password::RESET_LINK_SENT;
            } catch (\Throwable $e) {
                report($e);
            }
        }

        $message = "{$user->first_name} added on {$template->name}.";
        if ($request->boolean('send_invite')) {
            $message .= $emailed
                ? " A set-your-password link was emailed to {$user->email}."
                : ' The invite email couldn’t be sent — share the generated password with them instead.';
        }

        return response()->json([
            'message' => $message,
            'user' => $this->userPayload($user->fresh('roleTemplate')),
        ], 201);
    }

    /**
     * Edit a user's profile from the Roles & access dialog - same fields as
     * Add user. Password is only changed when a new one is supplied; a
     * template change re-applies that template's access.
     */
    public function updateUser(Request $request, User $user)
    {
        $this->assertCanManage();

        $validator = Validator::make($request->all(), [
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email,'.$user->id],
            'phone_number' => ['nullable', 'string', 'max:20'],
            'job_title' => ['nullable', 'string', 'max:100'],
            'password' => ['nullable', 'string', 'min:8', 'max:72'],
            'role_template_id' => ['required', 'exists:role_templates,id'],
            'department' => ['nullable', 'in:'.implode(',', self::DEPARTMENTS)],
            'manager_id' => ['nullable', 'exists:users,id', 'not_in:'.$user->id],
            'start_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first(), 'errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $template = RoleTemplate::findOrFail($data['role_template_id']);
        $templateChanged = (int) $template->id !== (int) $user->role_template_id;

        if ($templateChanged && in_array($template->base_role, self::SENSITIVE_ROLES, true) && auth()->user()->role !== 'FULL_ADMIN') {
            return response()->json(['message' => "Only a Full Admin can assign {$template->name}."], 403);
        }
        if ($user->id === auth()->id()) {
            if ($templateChanged && ! in_array('manage_users_roles', $template->actions, true)) {
                return response()->json(['message' => 'You can’t move yourself to a template that can’t manage users & roles.'], 422);
            }
            if (! $request->boolean('is_active', true)) {
                return response()->json(['message' => 'You can’t deactivate your own account.'], 422);
            }
        }

        $user->fill([
            'first_name' => $data['first_name'],
            'middle_name' => $data['middle_name'] ?? null,
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'phone_number' => $data['phone_number'] ?? null,
            'job_title' => $data['job_title'] ?? null,
            'department' => $data['department'] ?? $user->department,
            'manager_id' => $data['manager_id'] ?? null,
            'start_date' => $data['start_date'] ?? $user->start_date,
            'notes' => $data['notes'] ?? null,
            'is_active' => $request->boolean('is_active', $user->is_active),
        ]);
        if (! empty($data['password'])) {
            $user->password = $data['password'];
        }
        $user->save();

        if ($templateChanged) {
            $user->applyTemplate($template);
        }

        return response()->json([
            'message' => "{$user->first_name}’s details updated.".(! empty($data['password']) ? ' New password set.' : '').($templateChanged ? " Access re-applied from {$template->name}." : ''),
            'user' => $this->userPayload($user->fresh('roleTemplate')),
        ]);
    }

    public function updateUserTemplate(Request $request, User $user)
    {
        $this->assertCanManage();

        $validator = Validator::make($request->all(), ['role_template_id' => ['required', 'exists:role_templates,id']]);
        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $template = RoleTemplate::findOrFail($request->role_template_id);

        if ($user->id === auth()->id() && ! in_array('manage_users_roles', $template->actions, true)) {
            return response()->json(['message' => 'You can’t move yourself to a template that can’t manage users & roles.'], 422);
        }

        $user->applyTemplate($template);

        return response()->json([
            'message' => "{$user->first_name} is now on {$template->name}.",
            'user' => $this->userPayload($user->fresh('roleTemplate')),
        ]);
    }

    /**
     * Per-user grants beyond (or below) their template.
     */
    public function updateUserAccess(Request $request, User $user)
    {
        $this->assertCanManage();

        $validator = Validator::make($request->all(), [
            'modules' => ['present', 'array'],
            'modules.*' => ['string', 'in:'.implode(',', array_keys(RoleTemplate::MODULES))],
            'module_levels' => ['sometimes', 'present', 'array'],
            'module_levels.*' => ['string', 'in:'.implode(',', array_keys(RoleTemplate::LEVELS))],
            'actions' => ['present', 'array'],
            'actions.*' => ['string', 'in:'.implode(',', array_keys(RoleTemplate::ACTIONS))],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first(), 'errors' => $validator->errors()], 422);
        }

        $modules = array_values(array_unique($request->input('modules', [])));
        $actions = array_values(array_unique($request->input('actions', [])));
        $moduleLevels = array_intersect_key($request->input('module_levels', []), array_flip($modules));

        if ($user->id === auth()->id() && (! in_array('roles_access', $modules, true) || ! in_array('manage_users_roles', $actions, true))) {
            return response()->json(['message' => 'You can’t remove your own access to Roles & access.'], 422);
        }

        $user->forceFill(['modules' => $modules, 'module_levels' => $moduleLevels, 'actions' => $actions])->save();

        return response()->json([
            'message' => "Access updated for {$user->first_name}.",
            'user' => $this->userPayload($user->fresh('roleTemplate')),
        ]);
    }

    public function toggleSuspend(User $user)
    {
        $this->assertCanManage();

        if ($user->id === auth()->id()) {
            return response()->json(['message' => 'You can’t suspend your own account.'], 422);
        }

        $user->forceFill(['is_active' => ! $user->is_active])->save();

        return response()->json([
            'message' => $user->is_active ? "{$user->first_name} reactivated." : "{$user->first_name} suspended — they can’t sign in until reactivated.",
            'user' => $this->userPayload($user->fresh('roleTemplate')),
        ]);
    }

    public function destroyUser(User $user)
    {
        $this->assertCanManage();

        if ($user->id === auth()->id()) {
            return response()->json(['message' => 'You can’t remove your own account.'], 422);
        }

        $name = $user->first_name;
        $user->delete();

        return response()->json(['message' => "{$name} removed."]);
    }

    // ---- Helpers ------------------------------------------------------

    private function assertCanManage(): void
    {
        abort_unless($this->canManage(), 403, 'Only users with “Manage users & roles” can change access.');
    }

    /**
     * Whether this viewer can create/edit anything on this page - the
     * "manage_users_roles" action gate, further narrowed by their own
     * access level for the "roles_access" module itself: a level of "View
     * only" always wins, even for someone who otherwise holds that action,
     * so the level dropdown in Roles & access has real teeth here instead
     * of only ever affecting other modules.
     */
    private function canManage(): bool
    {
        $user = auth()->user();

        return $user->canDo('manage_users_roles') && $user->levelFor('roles_access') !== 'view';
    }

    private function validateTemplate(Request $request, ?RoleTemplate $existing = null): array
    {
        $validator = Validator::make($request->all(), [
            'name' => ['sometimes', 'required', 'string', 'max:100', 'unique:role_templates,name'.($existing ? ",{$existing->id}" : '')],
            'description' => ['nullable', 'string', 'max:255'],
            'base_role' => ['sometimes', 'required', 'in:'.implode(',', RoleTemplate::BASE_ROLES)],
            'modules' => ['sometimes', 'present', 'array'],
            'modules.*' => ['string', 'in:'.implode(',', array_keys(RoleTemplate::MODULES))],
            'module_levels' => ['sometimes', 'present', 'array'],
            'module_levels.*' => ['string', 'in:'.implode(',', array_keys(RoleTemplate::LEVELS))],
            'actions' => ['sometimes', 'present', 'array'],
            'actions.*' => ['string', 'in:'.implode(',', array_keys(RoleTemplate::ACTIONS))],
        ]);

        if ($validator->fails()) {
            abort(response()->json(['message' => $validator->errors()->first(), 'errors' => $validator->errors()], 422));
        }

        $data = $validator->validated();

        if (! $existing) {
            $data['base_role'] = $data['base_role'] ?? 'OTHER_STAFF';
            $data['modules'] = array_values(array_unique(array_merge(['dashboard'], $data['modules'] ?? [])));
            $data['actions'] = array_values(array_unique($data['actions'] ?? []));
        } else {
            if (isset($data['modules'])) {
                $data['modules'] = array_values(array_unique($data['modules']));
            }
            if (isset($data['actions'])) {
                $data['actions'] = array_values(array_unique($data['actions']));
            }
        }

        // A level only means anything for a module actually granted - keep
        // it from lingering (or applying) against one that isn't. Only
        // trimmed when both arrived together; a level-only PATCH (e.g. the
        // template card's per-module level select) has no modules list here
        // to intersect against, so it's trusted as-is.
        if (isset($data['module_levels'], $data['modules'])) {
            $data['module_levels'] = array_intersect_key($data['module_levels'], array_flip($data['modules']));
        }

        return $data;
    }

    private function userPayload(User $u): array
    {
        $modules = array_values(array_intersect($u->effectiveModules() ?? [], array_keys(RoleTemplate::MODULES)));
        $actions = array_values(array_intersect($u->effectiveActions() ?? [], array_keys(RoleTemplate::ACTIONS)));
        $moduleLevels = array_intersect_key($u->effectiveModuleLevels(), array_flip($modules));
        $parts = preg_split('/\s+/', trim($u->first_name.' '.$u->last_name));

        return [
            'id' => $u->public_id,
            'uid' => $u->id,
            'name' => trim($u->first_name.' '.$u->last_name),
            'initials' => strtoupper(mb_substr($parts[0] ?? '', 0, 1).mb_substr($parts[count($parts) - 1] ?? '', 0, 1)),
            'email' => $u->email,
            'job_title' => $u->job_title,
            'first_name' => $u->first_name,
            'middle_name' => $u->middle_name,
            'last_name' => $u->last_name,
            'phone_number' => $u->phone_number,
            'department' => $u->department,
            'manager_id' => $u->manager_id,
            'start_date' => optional($u->start_date)->format('Y-m-d'),
            'notes' => $u->notes,
            'is_active' => (bool) $u->is_active,
            'template_id' => $u->role_template_id,
            'base_role' => $u->role,
            'modules' => $modules,
            'module_levels' => $moduleLevels,
            'actions' => $actions,
            'access' => count($modules),
            'status' => $u->accessStatus(),
            'is_me' => $u->id === auth()->id(),
            'edit_url' => route('users.edit', $u),
        ];
    }

    private function templatePayload(RoleTemplate $t): array
    {
        return [
            'id' => $t->id,
            'key' => $t->key,
            'name' => $t->name,
            'description' => $t->description,
            'base_role' => $t->base_role,
            'modules' => array_values($t->modules ?? []),
            'module_levels' => array_intersect_key($t->module_levels ?? [], array_flip($t->modules ?? [])),
            'actions' => array_values($t->actions ?? []),
            'is_system' => $t->is_system,
            'locked' => $t->isLocked(),
            'users_count' => $t->users_count ?? $t->users()->count(),
        ];
    }
}
