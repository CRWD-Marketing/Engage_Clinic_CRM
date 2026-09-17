<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoleTemplate extends Model
{
    /**
     * Every module in the system a template / user can be granted - one per
     * sidebar entry, keyed by the feature key the `feature:` middleware and
     * sidebar already use.
     */
    const MODULES = [
        'dashboard' => 'Dashboard',
        'leads' => 'Leads',
        'contacts' => 'Contacts',
        'whatsapp' => 'WhatsApp',
        'voice' => 'Voice Calls',
        'knowledge_base' => 'AI Employee',
        'patients' => 'Patients',
        'calendar' => 'Calendar',
        'therapists' => 'Therapists',
        'careers' => 'Job Applications',
        'users' => 'User Management',
        'roles_access' => 'Roles & access',
        'billing' => 'Billing',
        'reports' => 'Reports',
        'packages' => 'Packages',
        'settings' => 'Settings',
    ];

    /**
     * Feature keys that map onto another module's grant. None at present -
     * kept so a future merged module can be aliased without touching User.
     */
    const MODULE_ALIASES = [];

    /**
     * How much of a granted module a template/user can reach - refines
     * `modules` (the yes/no gate) rather than replacing it. A module absent
     * from `module_levels` defaults to "full" (see User::levelFor()), so
     * every grant made before this concept existed keeps working unchanged.
     */
    const LEVELS = [
        'full' => 'Full access',
        'own' => 'Own records only',
        'view' => 'View only',
        'edit' => 'Can edit',
    ];

    const ACTIONS = [
        'assign_lead_owner' => 'Assign lead owner',
        'reassign_lead_owner' => 'Reassign lead owner',
        'terminate_lead' => 'Terminate lead',
        'add_lead_notes' => 'Add lead notes',
        'convert_to_client' => 'Convert to client',
        'book_modify_session' => 'Book / modify session',
        'assign_change_schedule' => 'Assign & change schedule',
        'assign_multiple_therapists' => 'Assign multiple therapists',
        'view_terminated_history' => 'View terminated history',
        'create_invoice' => 'Create invoice',
        'manage_users_roles' => 'Manage users & roles',
    ];

    const BASE_ROLES = ['FULL_ADMIN', 'HR_STAFF', 'SALES_STAFF', 'COORDINATOR', 'FINANCE_STAFF', 'CLINICAL_SUPERVISOR', 'THERAPIST', 'OTHER_STAFF'];

    /**
     * Which department a user lands in when created straight from a template.
     */
    const DEPARTMENT_FOR_ROLE = [
        'FULL_ADMIN' => 'EXECUTIVE',
        'HR_STAFF' => 'HUMAN_RESOURCES',
        'SALES_STAFF' => 'SALES',
        'COORDINATOR' => 'COORDINATOR',
        'FINANCE_STAFF' => 'FINANCE',
        'CLINICAL_SUPERVISOR' => 'CLINICAL',
        'THERAPIST' => 'CLINICAL',
        'OTHER_STAFF' => 'OTHER',
    ];

    /**
     * The eight access levels from the CRM Account Creation Request Form.
     * Seeded as locked "system" templates; custom templates are added on top.
     */
    const SYSTEM = [
        'full_admin' => [
            'name' => 'Full Admin',
            'base_role' => 'FULL_ADMIN',
            'description' => 'Complete access to every module: user management, billing, system settings, all client and financial records',
            'modules' => ['dashboard', 'leads', 'contacts', 'whatsapp', 'voice', 'knowledge_base', 'patients', 'calendar', 'therapists', 'careers', 'users', 'roles_access', 'billing', 'reports', 'packages', 'settings'],
            'actions' => ['assign_lead_owner', 'reassign_lead_owner', 'terminate_lead', 'add_lead_notes', 'convert_to_client', 'book_modify_session', 'assign_change_schedule', 'assign_multiple_therapists', 'view_terminated_history', 'create_invoice', 'manage_users_roles'],
        ],
        'staff_admin' => [
            'name' => 'Staff Admin Access',
            'base_role' => 'HR_STAFF',
            'description' => 'Staff / HR profiles, internal scheduling, job applications and user accounts. No billing or clinical data',
            'modules' => ['dashboard', 'calendar', 'therapists', 'careers', 'users', 'roles_access', 'reports'],
            'actions' => ['assign_change_schedule', 'manage_users_roles'],
        ],
        'sales' => [
            'name' => 'Sales Module Access',
            'base_role' => 'SALES_STAFF',
            'description' => 'Leads, deals, pipeline, client communication and intake forms only. No clinical records',
            'modules' => ['dashboard', 'leads', 'contacts', 'whatsapp', 'voice', 'reports'],
            'actions' => ['assign_lead_owner', 'terminate_lead', 'add_lead_notes', 'convert_to_client', 'view_terminated_history'],
        ],
        'scheduling' => [
            'name' => 'Scheduling & Coordination Access',
            'base_role' => 'COORDINATOR',
            'description' => 'Appointment calendars, client communication and intake coordination',
            'modules' => ['dashboard', 'leads', 'contacts', 'whatsapp', 'voice', 'patients', 'calendar'],
            'actions' => ['assign_lead_owner', 'add_lead_notes', 'convert_to_client', 'book_modify_session', 'assign_change_schedule', 'assign_multiple_therapists'],
        ],
        'finance' => [
            'name' => 'Finance Module Access',
            'base_role' => 'FINANCE_STAFF',
            'description' => 'Invoicing, payments and financial reporting. No clinical records',
            'modules' => ['dashboard', 'calendar', 'billing', 'reports', 'packages'],
            'actions' => ['view_terminated_history', 'create_invoice'],
        ],
        'clinical_admin' => [
            'name' => 'Clinical Admin Access',
            'base_role' => 'CLINICAL_SUPERVISOR',
            'description' => 'Full clinical records, treatment plans and oversight of therapist accounts and session notes',
            'modules' => ['dashboard', 'patients', 'calendar', 'therapists', 'reports'],
            'actions' => ['add_lead_notes', 'book_modify_session', 'assign_change_schedule', 'assign_multiple_therapists', 'view_terminated_history'],
        ],
        'clinical_standard' => [
            'name' => 'Clinical Standard Access',
            'base_role' => 'THERAPIST',
            'description' => "Session notes and records limited to the therapist's own assigned clients",
            'modules' => ['dashboard', 'patients', 'calendar'],
            'module_levels' => ['patients' => 'own', 'calendar' => 'own'],
            'actions' => [],
        ],
        'basic' => [
            'name' => 'Basic / View-Only Access',
            'base_role' => 'OTHER_STAFF',
            'description' => 'Limited or custom access, defined case by case — currently invoices and quotations only',
            'modules' => ['dashboard', 'billing'],
            'actions' => ['create_invoice'],
        ],
    ];

    protected $fillable = ['key', 'name', 'description', 'base_role', 'modules', 'module_levels', 'actions', 'is_system', 'sort_order'];

    protected $casts = [
        'modules' => 'array',
        'module_levels' => 'array',
        'actions' => 'array',
        'is_system' => 'boolean',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    /**
     * Full Admin can't be trimmed - there must always be a template that can
     * reach everything, otherwise access could be locked out for good.
     */
    public function isLocked(): bool
    {
        return $this->key === 'full_admin';
    }

    public static function systemForRole(string $role): ?self
    {
        return static::where('is_system', true)->where('base_role', $role)->orderBy('sort_order')->first();
    }

    /**
     * What a user's access level can and can't do, for the capability strip
     * shown under the top bar on every screen (see billing/calendar pages).
     */
    public static function capabilitiesForUser(User $user, string $fallbackVerb = 'view this area'): array
    {
        $labels = self::ACTIONS;
        $held = $user->effectiveActions();

        if ($held === null) {
            return ['label' => $user->roleLabel(), 'can' => [$fallbackVerb], 'locked' => array_values(array_map('lcfirst', $labels))];
        }

        $can = array_values(array_map(fn ($k) => lcfirst($labels[$k]), array_intersect(array_keys($labels), $held)));
        $locked = array_values(array_map(fn ($k) => lcfirst($labels[$k]), array_diff(array_keys($labels), $held)));

        return [
            'label' => $user->roleTemplate?->name ?? $user->roleLabel(),
            'can' => $can ?: [$fallbackVerb],
            'locked' => $locked,
        ];
    }
}
