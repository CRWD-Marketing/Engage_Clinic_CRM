/**
 * Mock of Roles & access (User\RoleController): role templates, adding and
 * editing users from a template, per-user grants, suspend and remove. The
 * same validation messages, the same "only a Full Admin hands out Full Admin
 * or Clinical Supervisor" rule, and the same guards against locking yourself
 * out. Invite emails are not sent.
 */

import { canDo, effectiveActions, effectiveModuleLevels, effectiveModules, levelFor } from '@/auth/permissions';
import { ACTIONS, type ActionKey, DEPARTMENTS, LEVELS, type Level, MODULES, type ModuleKey, ROLES, type Role } from '@/auth/roles';
import { isoToYmd, todayYmd } from '@/utils/dates';

import type { ApiClient } from '../client';
import { ApiError } from '../errors';
import type { AccessGrantInput, AccessTemplate, AccessUser, AccessUserInput, ModuleLevels, RolesAccessPage, TemplateInput } from '../types';
import type { MockDb, RoleTemplateRow, UserRow } from './rows';
import { dateCast, laravelIso } from './seed';
import { delay, EMAIL_PATTERN, getDb, requireFeature, requireUser, toApiUser } from './server';

const SENSITIVE_ROLES: Role[] = ['FULL_ADMIN', 'CLINICAL_SUPERVISOR'];

/** RoleTemplate::DEPARTMENT_FOR_ROLE */
const DEPARTMENT_FOR_ROLE: RolesAccessPage['department_for_role'] = {
  FULL_ADMIN: 'EXECUTIVE',
  HR_STAFF: 'HUMAN_RESOURCES',
  SALES_STAFF: 'SALES',
  COORDINATOR: 'COORDINATOR',
  FINANCE_STAFF: 'FINANCE',
  CLINICAL_SUPERVISOR: 'CLINICAL',
  THERAPIST: 'CLINICAL',
  OTHER_STAFF: 'OTHER',
};
const LEVEL_LABELS: Record<Level, string> = { full: 'Full access', own: 'Own records only', view: 'View only', edit: 'Can edit' };
const ACTION_LABELS: Record<ActionKey, string> = {
  assign_lead_owner: 'Assign lead owner',
  reassign_lead_owner: 'Reassign lead owner',
  terminate_lead: 'Terminate lead',
  add_lead_notes: 'Add lead notes',
  convert_to_client: 'Convert to client',
  book_modify_session: 'Book / modify session',
  assign_change_schedule: 'Assign & change schedule',
  assign_multiple_therapists: 'Assign multiple therapists',
  view_terminated_history: 'View terminated history',
  create_invoice: 'Create invoice',
  manage_users_roles: 'Manage users & roles',
};

/** As Laravel sends it: an empty PHP array is `[]`, not `{}`. */
const asSent = (levels: Partial<Record<ModuleKey, Level>>): ModuleLevels => (Object.keys(levels).length ? levels : []);

const now = () => laravelIso(new Date().toISOString());
const fail = (field: string, message: string) => new ApiError(422, { message, errors: { [field]: [message] } });
const refuse = (message: string) => new ApiError(422, { message });
const moduleKeys = Object.keys(MODULES) as ModuleKey[];
const unique = <T,>(list: T[]) => [...new Set(list)];
/** PHP's array_intersect_key($levels, array_flip($modules)). */
const levelsFor = (levels: Partial<Record<ModuleKey, Level>> | null | undefined, modules: ModuleKey[]) =>
  Object.fromEntries(Object.entries(levels ?? {}).filter(([k]) => modules.includes(k as ModuleKey))) as Partial<Record<ModuleKey, Level>>;

/** User::accessStatus() */
function statusOf(u: UserRow): AccessUser['status'] {
  if (!u.is_active) return 'suspended';
  if (u.invited_at && !u.last_login_at) return 'invited';
  return 'active';
}

/** RoleController::userPayload() */
function userPayload(me: UserRow, u: UserRow): AccessUser {
  const api = toApiUser(u);
  const modules = (effectiveModules(api) ?? []).filter((m) => moduleKeys.includes(m));
  const name = `${u.first_name} ${u.last_name}`.trim();
  const parts = name.split(/\s+/);
  return {
    id: u.public_id,
    uid: u.id,
    name,
    initials: `${parts[0]?.[0] ?? ''}${parts[parts.length - 1]?.[0] ?? ''}`.toUpperCase(),
    email: u.email,
    job_title: u.job_title,
    first_name: u.first_name,
    middle_name: u.middle_name,
    last_name: u.last_name,
    phone_number: u.phone_number,
    department: u.department,
    manager_id: u.manager_id,
    start_date: u.start_date ? isoToYmd(u.start_date) : null,
    notes: u.notes,
    is_active: u.is_active,
    template_id: u.role_template_id,
    base_role: u.role,
    modules,
    module_levels: asSent(levelsFor(effectiveModuleLevels(api), modules)),
    actions: (effectiveActions(api) ?? []).filter((a) => (ACTIONS as readonly string[]).includes(a)),
    access: modules.length,
    status: statusOf(u),
    is_me: u.id === me.id,
  };
}

/** RoleController::templatePayload() */
function templatePayload(db: MockDb, t: RoleTemplateRow): AccessTemplate {
  const modules = t.modules ?? [];
  return {
    id: t.id,
    key: t.key,
    name: t.name,
    description: t.description,
    base_role: t.base_role,
    modules,
    module_levels: asSent(levelsFor(t.module_levels, modules)),
    actions: t.actions ?? [],
    is_system: t.is_system,
    locked: t.key === 'full_admin',
    users_count: db.users.filter((u) => u.role_template_id === t.id).length,
  };
}

const canManage = (u: UserRow) => canDo(toApiUser(u), 'manage_users_roles') && levelFor(toApiUser(u), 'roles_access') !== 'view';

function requireRolesAccess(): UserRow {
  const user = requireUser();
  requireFeature(user, 'roles_access');
  return user;
}

/** Every change: module write access, then the Manage users & roles gate. */
function requireManager(): UserRow {
  const user = requireRolesAccess();
  requireFeature(user, 'roles_access', true);
  if (!canManage(user)) throw new ApiError(403, { message: 'Only users with “Manage users & roles” can change access.' });
  return user;
}

function findTemplate(db: MockDb, id: number): RoleTemplateRow {
  const t = db.roleTemplates.find((x) => x.id === Number(id));
  if (!t) throw new ApiError(404, { message: 'Not found.' });
  return t;
}

function findUser(db: MockDb, publicId: string): UserRow {
  const u = db.users.find((x) => x.public_id === publicId);
  if (!u) throw new ApiError(404, { message: 'Not found.' });
  return u;
}

/** User::applyTemplate() */
function applyTemplate(u: UserRow, t: RoleTemplateRow) {
  Object.assign(u, {
    role_template_id: t.id,
    role: t.base_role,
    modules: t.modules ? [...t.modules] : null,
    module_levels: t.module_levels ? { ...t.module_levels } : null,
    actions: t.actions ? [...t.actions] : null,
    updated_at: now(),
  });
}

/** GET /roles-access */
function page(): RolesAccessPage {
  const me = requireRolesAccess();
  const db = getDb();
  const users = [...db.users].sort((a, b) => a.first_name.localeCompare(b.first_name) || a.last_name.localeCompare(b.last_name));
  const payloads = users.map((u) => userPayload(me, u));
  return {
    users: payloads,
    templates: [...db.roleTemplates]
      .sort((a, b) => Number(b.is_system) - Number(a.is_system) || a.sort_order - b.sort_order || a.name.localeCompare(b.name))
      .map((t) => templatePayload(db, t)),
    modules: { ...MODULES },
    levels: LEVEL_LABELS,
    actions: ACTION_LABELS,
    base_roles: [...ROLES],
    departments: [...DEPARTMENTS],
    managers: users.filter((u) => u.is_active).map((u) => ({ id: u.id, name: `${u.first_name} ${u.last_name}`.trim() })),
    department_for_role: DEPARTMENT_FOR_ROLE,
    stats: {
      total: payloads.length,
      active: payloads.filter((u) => u.status === 'active').length,
      invited: payloads.filter((u) => u.status === 'invited').length,
      suspended: payloads.filter((u) => u.status === 'suspended').length,
    },
    can_manage: canManage(me),
  };
}

/** RoleController::validateTemplate() */
function validateTemplate(db: MockDb, input: TemplateInput, existing: RoleTemplateRow | null): TemplateInput {
  const data: TemplateInput = {};
  if ('name' in input || !existing) {
    const name = String(input.name ?? '').trim();
    if (!name) throw fail('name', 'The name field is required.');
    if (name.length > 100) throw fail('name', 'The name field must not be greater than 100 characters.');
    if (db.roleTemplates.some((t) => t.name.toLowerCase() === name.toLowerCase() && t.id !== existing?.id)) {
      throw fail('name', 'The name has already been taken.');
    }
    data.name = name;
  }
  if ('description' in input) {
    if ((input.description ?? '').length > 255) throw fail('description', 'The description field must not be greater than 255 characters.');
    data.description = input.description?.trim() || null;
  }
  if ('base_role' in input) {
    if (!input.base_role || !(ROLES as readonly string[]).includes(input.base_role)) throw fail('base_role', 'The selected base role is invalid.');
    data.base_role = input.base_role;
  }
  if (input.modules) {
    const bad = input.modules.findIndex((m) => !moduleKeys.includes(m));
    if (bad >= 0) throw fail(`modules.${bad}`, `The selected modules.${bad} is invalid.`);
    data.modules = unique(input.modules);
  }
  if (input.module_levels) {
    const bad = Object.entries(input.module_levels).find(([, v]) => !(LEVELS as readonly string[]).includes(v as string));
    if (bad) throw fail(`module_levels.${bad[0]}`, `The selected module_levels.${bad[0]} is invalid.`);
    data.module_levels = { ...input.module_levels };
  }
  if (input.actions) {
    const bad = input.actions.findIndex((a) => !(ACTIONS as readonly string[]).includes(a));
    if (bad >= 0) throw fail(`actions.${bad}`, `The selected actions.${bad} is invalid.`);
    data.actions = unique(input.actions);
  }
  if (!existing) {
    data.base_role ??= 'OTHER_STAFF';
    data.modules = unique(['dashboard', ...(data.modules ?? [])]);
    data.actions ??= [];
  }
  // A level only means something for a granted module; trimmed only when both arrived together.
  if (data.module_levels && data.modules) data.module_levels = levelsFor(data.module_levels, data.modules);
  return data;
}

/** Str::slug($name, '_') */
const slug = (name: string) =>
  name
    .toLowerCase()
    .normalize('NFKD')
    .replace(/[^a-z0-9]+/g, '_')
    .replace(/^_+|_+$/g, '');

function storeTemplate(input: TemplateInput) {
  requireManager();
  const db = getDb();
  const data = validateTemplate(db, input, null);
  const base = slug(data.name!);
  let key = base;
  for (let i = 2; db.roleTemplates.some((t) => t.key === key); i++) key = `${base}_${i}`;
  const template: RoleTemplateRow = {
    id: Math.max(0, ...db.roleTemplates.map((t) => t.id)) + 1,
    key,
    name: data.name!,
    description: data.description ?? null,
    base_role: data.base_role!,
    modules: data.modules!,
    module_levels: data.module_levels ?? null,
    actions: data.actions!,
    is_system: false,
    sort_order: Math.max(0, ...db.roleTemplates.map((t) => t.sort_order)) + 1,
  };
  db.roleTemplates.push(template);
  return { message: `Role template “${template.name}” created.`, template: templatePayload(db, template) };
}

function updateTemplate(id: number, input: TemplateInput) {
  requireManager();
  const db = getDb();
  const template = findTemplate(db, id);
  if (template.key === 'full_admin') throw refuse('Full Admin always has every module and action.');
  const data = validateTemplate(db, input, template);
  // System templates keep their name and base role; only the grants move.
  if (template.is_system) {
    delete data.name;
    delete data.base_role;
  }
  Object.assign(template, data);
  return { message: 'Template updated — people already assigned keep the access they hold.', template: templatePayload(db, template) };
}

function destroyTemplate(id: number) {
  requireManager();
  const db = getDb();
  const template = findTemplate(db, id);
  if (template.is_system) throw refuse('System templates can’t be deleted.');
  // People on it fall back to the system template for their base role, keeping what they hold.
  const fallback =
    [...db.roleTemplates].filter((t) => t.is_system && t.base_role === template.base_role).sort((a, b) => a.sort_order - b.sort_order)[0] ??
    db.roleTemplates.find((t) => t.key === 'basic');
  for (const u of db.users) if (u.role_template_id === template.id) u.role_template_id = fallback?.id ?? null;
  db.roleTemplates = db.roleTemplates.filter((t) => t.id !== template.id);
  return { message: 'Role template deleted.' };
}

/** storeUser / updateUser validation. */
function validateUser(db: MockDb, input: AccessUserInput, existing: UserRow | null) {
  const required = (key: keyof AccessUserInput, label: string) => {
    if (!String(input[key] ?? '').trim()) throw fail(key, `The ${label} field is required.`);
  };
  const max = (key: keyof AccessUserInput, label: string, n: number) => {
    if (String(input[key] ?? '').length > n) throw fail(key, `The ${label} field must not be greater than ${n} characters.`);
  };
  required('first_name', 'first name');
  max('first_name', 'first name', 255);
  max('middle_name', 'middle name', 255);
  required('last_name', 'last name');
  max('last_name', 'last name', 255);
  required('email', 'email');
  if (!EMAIL_PATTERN.test(input.email.trim())) throw fail('email', 'The email field must be a valid email address.');
  if (db.users.some((u) => u.email.toLowerCase() === input.email.trim().toLowerCase() && u.id !== existing?.id)) {
    throw fail('email', 'The email has already been taken.');
  }
  max('phone_number', 'phone number', 20);
  max('job_title', 'job title', 100);
  if (!existing || input.password) {
    if (!input.password) throw fail('password', 'The password field is required.');
    if (input.password.length < 8) throw fail('password', 'The password field must be at least 8 characters.');
    if (input.password.length > 72) throw fail('password', 'The password field must not be greater than 72 characters.');
  }
  if (!input.role_template_id) throw fail('role_template_id', 'The role template id field is required.');
  if (!db.roleTemplates.some((t) => t.id === Number(input.role_template_id))) throw fail('role_template_id', 'The selected role template id is invalid.');
  if (input.department && !(DEPARTMENTS as readonly string[]).includes(input.department)) throw fail('department', 'The selected department is invalid.');
  if (input.manager_id && (!db.users.some((u) => u.id === Number(input.manager_id)) || Number(input.manager_id) === existing?.id)) {
    throw fail('manager_id', 'The selected manager id is invalid.');
  }
  if (input.start_date && !/^\d{4}-\d{2}-\d{2}$/.test(input.start_date)) throw fail('start_date', 'The start date field must be a valid date.');
  max('notes', 'notes', 2000);
}

function storeUser(input: AccessUserInput) {
  const me = requireManager();
  const db = getDb();
  validateUser(db, input, null);
  const template = findTemplate(db, input.role_template_id);
  if (SENSITIVE_ROLES.includes(template.base_role) && me.role !== 'FULL_ADMIN') {
    throw new ApiError(403, { message: `Only a Full Admin can assign ${template.name}.` });
  }
  const stamp = now();
  const user: UserRow = {
    id: Math.max(0, ...db.users.map((u) => u.id)) + 1,
    public_id: `mock-${Date.now().toString(36)}-${Math.random().toString(36).slice(2, 8)}`,
    first_name: input.first_name.trim(),
    middle_name: input.middle_name?.trim() || null,
    last_name: input.last_name.trim(),
    job_title: input.job_title?.trim() || null,
    email: input.email.trim(),
    phone_number: input.phone_number?.trim() || null,
    timezone: null,
    message_signature: null,
    email_verified_at: null,
    department: input.department || DEPARTMENT_FOR_ROLE[template.base_role] || 'OTHER',
    manager_id: input.manager_id ? Number(input.manager_id) : null,
    role: template.base_role,
    role_template_id: null,
    modules: null,
    module_levels: null,
    actions: null,
    start_date: dateCast(input.start_date || todayYmd()),
    notes: input.notes?.trim() || null,
    is_active: input.is_active ?? true,
    invited_at: stamp,
    last_login_at: null,
    created_at: stamp,
    updated_at: stamp,
    password: input.password!,
  };
  db.users.push(user);
  applyTemplate(user, template);

  let message = `${user.first_name} added on ${template.name}.`;
  // MOCK: nothing is emailed; the message is the one the server gives when the link goes out.
  if (input.send_invite) message += ` A set-your-password link was emailed to ${user.email}.`;
  return { message, user: userPayload(me, user) };
}

function updateUser(publicId: string, input: AccessUserInput) {
  const me = requireManager();
  const db = getDb();
  const user = findUser(db, publicId);
  validateUser(db, input, user);
  const template = findTemplate(db, input.role_template_id);
  const templateChanged = template.id !== user.role_template_id;

  if (templateChanged && SENSITIVE_ROLES.includes(template.base_role) && me.role !== 'FULL_ADMIN') {
    throw new ApiError(403, { message: `Only a Full Admin can assign ${template.name}.` });
  }
  if (user.id === me.id) {
    if (templateChanged && !(template.actions ?? []).includes('manage_users_roles')) {
      throw refuse('You can’t move yourself to a template that can’t manage users & roles.');
    }
    if (input.is_active === false) throw refuse('You can’t deactivate your own account.');
  }

  Object.assign(user, {
    first_name: input.first_name.trim(),
    middle_name: input.middle_name?.trim() || null,
    last_name: input.last_name.trim(),
    email: input.email.trim(),
    phone_number: input.phone_number?.trim() || null,
    job_title: input.job_title?.trim() || null,
    department: input.department || user.department,
    manager_id: input.manager_id ? Number(input.manager_id) : null,
    start_date: input.start_date ? dateCast(input.start_date) : user.start_date,
    notes: input.notes?.trim() || null,
    is_active: input.is_active ?? user.is_active,
    updated_at: now(),
  });
  if (input.password) user.password = input.password;
  if (templateChanged) applyTemplate(user, template);

  return {
    message: `${user.first_name}’s details updated.${input.password ? ' New password set.' : ''}${templateChanged ? ` Access re-applied from ${template.name}.` : ''}`,
    user: userPayload(me, user),
  };
}

function setTemplate(publicId: string, templateId: number) {
  const me = requireManager();
  const db = getDb();
  const user = findUser(db, publicId);
  if (!templateId) throw refuse('The role template id field is required.');
  if (!db.roleTemplates.some((t) => t.id === Number(templateId))) throw refuse('The selected role template id is invalid.');
  const template = findTemplate(db, templateId);
  if (SENSITIVE_ROLES.includes(template.base_role) && me.role !== 'FULL_ADMIN') {
    throw new ApiError(403, { message: `Only a Full Admin can assign ${template.name}.` });
  }
  if (user.id === me.id && !(template.actions ?? []).includes('manage_users_roles')) {
    throw refuse('You can’t move yourself to a template that can’t manage users & roles.');
  }
  applyTemplate(user, template);
  return { message: `${user.first_name} is now on ${template.name}.`, user: userPayload(me, user) };
}

function setAccess(publicId: string, input: AccessGrantInput) {
  const me = requireManager();
  const db = getDb();
  const user = findUser(db, publicId);
  if (!Array.isArray(input.modules)) throw fail('modules', 'The modules field must be present.');
  if (!Array.isArray(input.actions)) throw fail('actions', 'The actions field must be present.');
  const badModule = input.modules.findIndex((m) => !moduleKeys.includes(m));
  if (badModule >= 0) throw fail(`modules.${badModule}`, `The selected modules.${badModule} is invalid.`);
  const badAction = input.actions.findIndex((a) => !(ACTIONS as readonly string[]).includes(a));
  if (badAction >= 0) throw fail(`actions.${badAction}`, `The selected actions.${badAction} is invalid.`);

  const modules = unique(input.modules);
  const actions = unique(input.actions);
  if (user.id === me.id && (!modules.includes('roles_access') || !actions.includes('manage_users_roles'))) {
    throw refuse('You can’t remove your own access to Roles & access.');
  }
  Object.assign(user, { modules, module_levels: levelsFor(input.module_levels, modules), actions, updated_at: now() });
  return { message: `Access updated for ${user.first_name}.`, user: userPayload(me, user) };
}

function toggleSuspend(publicId: string) {
  const me = requireManager();
  const db = getDb();
  const user = findUser(db, publicId);
  if (user.id === me.id) throw refuse('You can’t suspend your own account.');
  user.is_active = !user.is_active;
  return {
    message: user.is_active ? `${user.first_name} reactivated.` : `${user.first_name} suspended — they can’t sign in until reactivated.`,
    user: userPayload(me, user),
  };
}

function destroyUser(publicId: string) {
  const me = requireManager();
  const db = getDb();
  const user = findUser(db, publicId);
  if (user.id === me.id) throw refuse('You can’t remove your own account.');
  db.users = db.users.filter((u) => u.id !== user.id);
  return { message: `${user.first_name} removed.` };
}

export function createRolesApi(): ApiClient['roles'] {
  return {
    page: () => delay(page),
    storeTemplate: (input) => delay(() => storeTemplate(input)),
    updateTemplate: (id, input) => delay(() => updateTemplate(id, input)),
    destroyTemplate: (id) => delay(() => destroyTemplate(id)),
    storeUser: (input) => delay(() => storeUser(input)),
    updateUser: (publicId, input) => delay(() => updateUser(publicId, input)),
    setTemplate: (publicId, templateId) => delay(() => setTemplate(publicId, templateId)),
    setAccess: (publicId, input) => delay(() => setAccess(publicId, input)),
    toggleSuspend: (publicId) => delay(() => toggleSuspend(publicId)),
    destroyUser: (publicId) => delay(() => destroyUser(publicId)),
  };
}
