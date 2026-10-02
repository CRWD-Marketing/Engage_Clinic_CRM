/**
 * Role/permission constants mirrored from Laravel:
 * - app/Models/RoleTemplate.php (MODULES, LEVELS, ACTIONS, SYSTEM)
 * - config/role_permissions.php (legacy fallback map)
 * Keep these in sync with the backend; they are data, not UI copy.
 */

export const ROLES = [
  'FULL_ADMIN',
  'HR_STAFF',
  'SALES_STAFF',
  'COORDINATOR',
  'FINANCE_STAFF',
  'CLINICAL_SUPERVISOR',
  'THERAPIST',
  'OTHER_STAFF',
] as const;
export type Role = (typeof ROLES)[number];

export const DEPARTMENTS = [
  'EXECUTIVE',
  'HUMAN_RESOURCES',
  'SALES',
  'COORDINATOR',
  'FINANCE',
  'CLINICAL',
  'CONTENT_MANAGEMENT',
  'OTHER',
] as const;
export type Department = (typeof DEPARTMENTS)[number];

/** RoleTemplate::MODULES (key → label). */
export const MODULES = {
  dashboard: 'Dashboard',
  leads: 'Leads',
  contacts: 'Contacts',
  cms_forms: 'CMS Forms',
  whatsapp: 'WhatsApp',
  knowledge_base: 'AI Employee',
  patients: 'Patients',
  calendar: 'Calendar',
  therapists: 'Therapists',
  careers: 'Job Applications',
  users: 'User Management',
  roles_access: 'Roles & access',
  billing: 'Billing',
  reports: 'Reports',
  packages: 'Packages',
  settings: 'Settings',
} as const;
export type ModuleKey = keyof typeof MODULES;

/** RoleTemplate::MODULE_ALIASES (currently empty in Laravel). */
export const MODULE_ALIASES: Partial<Record<string, ModuleKey>> = {};

export const LEVELS = ['full', 'own', 'view', 'edit'] as const;
export type Level = (typeof LEVELS)[number];

export const ACTIONS = [
  'assign_lead_owner',
  'reassign_lead_owner',
  'terminate_lead',
  'add_lead_notes',
  'convert_to_client',
  'book_modify_session',
  'assign_change_schedule',
  'assign_multiple_therapists',
  'view_terminated_history',
  'create_invoice',
  'manage_users_roles',
] as const;
export type ActionKey = (typeof ACTIONS)[number];

export type RoleTemplateDef = {
  name: string;
  base_role: Role;
  modules: ModuleKey[];
  module_levels?: Partial<Record<ModuleKey, Level>>;
  actions: ActionKey[];
};

/** RoleTemplate::SYSTEM — the eight locked system templates. */
export const SYSTEM_TEMPLATES: Record<string, RoleTemplateDef> = {
  full_admin: {
    name: 'Full Admin',
    base_role: 'FULL_ADMIN',
    modules: [
      'dashboard', 'leads', 'contacts', 'cms_forms', 'whatsapp', 'knowledge_base', 'patients', 'calendar',
      'therapists', 'careers', 'users', 'roles_access', 'billing', 'reports', 'packages', 'settings',
    ],
    actions: [...ACTIONS],
  },
  staff_admin: {
    name: 'Staff Admin Access',
    base_role: 'HR_STAFF',
    modules: ['dashboard', 'calendar', 'therapists', 'careers', 'users', 'roles_access', 'reports'],
    actions: ['assign_change_schedule', 'manage_users_roles'],
  },
  sales: {
    name: 'Sales Module Access',
    base_role: 'SALES_STAFF',
    modules: ['dashboard', 'leads', 'contacts', 'cms_forms', 'whatsapp', 'reports'],
    actions: ['assign_lead_owner', 'terminate_lead', 'add_lead_notes', 'convert_to_client', 'view_terminated_history'],
  },
  scheduling: {
    name: 'Scheduling & Coordination Access',
    base_role: 'COORDINATOR',
    modules: ['dashboard', 'leads', 'contacts', 'whatsapp', 'patients', 'calendar'],
    actions: [
      'assign_lead_owner', 'add_lead_notes', 'convert_to_client', 'book_modify_session',
      'assign_change_schedule', 'assign_multiple_therapists',
    ],
  },
  finance: {
    name: 'Finance Module Access',
    base_role: 'FINANCE_STAFF',
    modules: ['dashboard', 'calendar', 'billing', 'reports', 'packages'],
    actions: ['view_terminated_history', 'create_invoice'],
  },
  clinical_admin: {
    name: 'Clinical Admin Access',
    base_role: 'CLINICAL_SUPERVISOR',
    modules: ['dashboard', 'patients', 'calendar', 'therapists', 'reports'],
    actions: [
      'add_lead_notes', 'book_modify_session', 'assign_change_schedule',
      'assign_multiple_therapists', 'view_terminated_history',
    ],
  },
  clinical_standard: {
    name: 'Clinical Standard Access',
    base_role: 'THERAPIST',
    modules: ['dashboard', 'patients', 'calendar'],
    module_levels: { patients: 'own', calendar: 'own' },
    actions: [],
  },
  basic: {
    name: 'Basic / View-Only Access',
    base_role: 'OTHER_STAFF',
    modules: ['dashboard', 'billing'],
    actions: ['create_invoice'],
  },
};

/** config/role_permissions.php — legacy fallback when a user has no module list. */
export const LEGACY_ROLE_PERMISSIONS: Record<Role, string[]> = {
  FULL_ADMIN: [
    'dashboard', 'leads', 'contacts', 'cms_forms', 'whatsapp', 'patients', 'calendar', 'therapists', 'users',
    'billing', 'reports', 'knowledge_base', 'careers', 'settings', 'packages', 'roles_access',
  ],
  HR_STAFF: ['dashboard', 'users', 'calendar', 'careers', 'roles_access'],
  SALES_STAFF: ['dashboard', 'leads', 'contacts', 'cms_forms', 'whatsapp'],
  COORDINATOR: ['dashboard', 'calendar', 'whatsapp', 'leads', 'contacts', 'patients'],
  FINANCE_STAFF: ['dashboard', 'billing', 'reports'],
  CLINICAL_SUPERVISOR: ['dashboard', 'patients', 'calendar', 'therapists', 'reports'],
  THERAPIST: ['dashboard', 'patients', 'calendar'],
  OTHER_STAFF: ['dashboard', 'billing'],
};

/** CalendarController::MANAGE_ROLES — roles that manage the calendar regardless of actions. */
export const CALENDAR_MANAGE_ROLES: Role[] = ['CLINICAL_SUPERVISOR', 'FULL_ADMIN'];
