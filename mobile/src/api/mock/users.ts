/**
 * Mock of User Management (Auth\CreateAccount + the mobile API's list and
 * profile additions): the paged, filtered list with its stat tiles, a
 * profile with manager and direct reports, add, edit and delete. Same
 * validation messages and the same rule that only a Full Admin may delete
 * or hand out the Full Admin and Clinical Supervisor roles.
 */

import { DEPARTMENTS, ROLES } from '@/auth/roles';

import type { ApiClient } from '../client';
import { ApiError } from '../errors';
import type { StaffUser, UserInput, UserListQuery, UserListResponse, UserProfile } from '../types';
import type { MockDb, UserRow } from './rows';
import { dateCast, laravelIso } from './seed';
import { delay, EMAIL_PATTERN, getDb, requireFeature, requireUser } from './server';

const PER_PAGE = 10;
const SENSITIVE_ROLES = ['FULL_ADMIN', 'CLINICAL_SUPERVISOR'];

const now = () => laravelIso(new Date().toISOString());
const fail = (field: string, message: string) => new ApiError(422, { message, errors: { [field]: [message] } });

/** A user as Laravel serialises it: every column but the password. */
function plain(u: UserRow): Omit<StaffUser, 'manager'> {
  const { password: _password, ...rest } = u;
  return rest;
}

function withManager(db: MockDb, u: UserRow): StaffUser {
  const manager = db.users.find((m) => m.id === u.manager_id);
  return { ...plain(u), manager: manager ? plain(manager) : null };
}

function requireUsersAccess(): UserRow {
  const user = requireUser();
  requireFeature(user, 'users');
  return user;
}

function findUser(db: MockDb, publicId: string): UserRow {
  const user = db.users.find((u) => u.public_id === publicId);
  if (!user) throw new ApiError(404, { message: 'Not found.' });
  return user;
}

/** GET /users */
function list(query: UserListQuery = {}): UserListResponse {
  const me = requireUsersAccess();
  const db = getDb();
  const term = query.search?.trim().toLowerCase() ?? '';
  const matches = db.users
    .filter(
      (u) =>
        (!term || [u.first_name, u.last_name, u.email, `${u.first_name} ${u.last_name}`].some((v) => v.toLowerCase().includes(term))) &&
        (!query.department || u.department === query.department) &&
        (!query.role || u.role === query.role) &&
        (!query.status || u.is_active === (query.status === 'active')),
    )
    // latest(): newest first.
    .sort((a, b) => b.created_at.localeCompare(a.created_at) || b.id - a.id);
  const lastPage = Math.max(1, Math.ceil(matches.length / PER_PAGE));
  const page = Math.max(1, Number(query.page) || 1);

  return {
    users: matches.slice((page - 1) * PER_PAGE, page * PER_PAGE).map((u) => withManager(db, u)),
    meta: { current_page: page, last_page: lastPage, per_page: PER_PAGE, total: matches.length },
    stats: {
      total: db.users.length,
      active: db.users.filter((u) => u.is_active).length,
      inactive: db.users.filter((u) => !u.is_active).length,
      departments: new Set(db.users.map((u) => u.department)).size,
    },
    departments: [...DEPARTMENTS],
    roles: [...ROLES],
    managers: [...db.users]
      .sort((a, b) => a.first_name.localeCompare(b.first_name))
      .map((u) => ({ id: u.id, name: `${u.first_name} ${u.last_name}`.trim() })),
    can_delete: me.role === 'FULL_ADMIN',
  };
}

/** GET /users/{user} */
function show(publicId: string): UserProfile {
  const me = requireUsersAccess();
  const db = getDb();
  const user = findUser(db, publicId);
  return {
    ...withManager(db, user),
    subordinates: db.users.filter((u) => u.manager_id === user.id).map(plain),
    can_delete: me.role === 'FULL_ADMIN',
  };
}

/** CreateAccount's validation rules, `creating` decides which are required. */
function validate(db: MockDb, input: Partial<UserInput>, existing: UserRow | null) {
  const creating = existing === null;
  const has = (key: keyof UserInput) => key in input && input[key] !== undefined;
  const required = (key: keyof UserInput, label: string) => {
    if ((creating || has(key)) && !String(input[key] ?? '').trim()) throw fail(key, `The ${label} field is required.`);
  };
  const max = (key: keyof UserInput, label: string, n: number) => {
    if (String(input[key] ?? '').length > n) throw fail(key, `The ${label} field must not be greater than ${n} characters.`);
  };

  required('first_name', 'first name');
  max('first_name', 'first name', 255);
  max('middle_name', 'middle name', 255);
  required('last_name', 'last name');
  max('last_name', 'last name', 255);
  required('email', 'email');
  if (has('email') || creating) {
    const email = String(input.email ?? '').trim();
    if (!EMAIL_PATTERN.test(email)) throw fail('email', 'The email field must be a valid email address.');
    if (db.users.some((u) => u.email.toLowerCase() === email.toLowerCase() && u.id !== existing?.id)) {
      throw fail('email', 'The email has already been taken.');
    }
  }
  max('phone_number', 'phone number', 20);
  if (creating || input.password) {
    if (!input.password) throw fail('password', 'The password field is required.');
    if (input.password !== input.password_confirmation) throw fail('password', 'The password field confirmation does not match.');
    if (input.password.length < 8) throw fail('password', 'The password field must be at least 8 characters.');
  }
  required('department', 'department');
  if (input.department && !(DEPARTMENTS as readonly string[]).includes(input.department)) throw fail('department', 'The selected department is invalid.');
  required('role', 'role');
  if (input.role && !(ROLES as readonly string[]).includes(input.role)) throw fail('role', 'The selected role is invalid.');
  if (input.manager_id && !db.users.some((u) => u.id === Number(input.manager_id))) throw fail('manager_id', 'The selected manager id is invalid.');
  if (input.start_date && !/^\d{4}-\d{2}-\d{2}$/.test(input.start_date)) throw fail('start_date', 'The start date field must be a valid date.');
}

/** Only a Full Admin may grant the roles with clinical-record or full-system access. */
function assertCanAssignRole(me: UserRow, role: string) {
  if (SENSITIVE_ROLES.includes(role) && me.role !== 'FULL_ADMIN') {
    throw new ApiError(403, { message: `Only a Full Admin can assign the ${role} role.` });
  }
}

/** POST /users */
function store(input: UserInput) {
  const me = requireUsersAccess();
  requireFeature(me, 'users', true);
  const db = getDb();
  validate(db, input, null);
  assertCanAssignRole(me, input.role);

  const stamp = now();
  const user: UserRow = {
    id: Math.max(0, ...db.users.map((u) => u.id)) + 1,
    public_id: `mock-${Date.now().toString(36)}-${Math.random().toString(36).slice(2, 8)}`,
    first_name: input.first_name.trim(),
    middle_name: input.middle_name?.trim() || null,
    last_name: input.last_name.trim(),
    job_title: null,
    email: input.email.trim(),
    phone_number: input.phone_number?.trim() || null,
    timezone: null,
    message_signature: null,
    email_verified_at: null,
    department: input.department,
    manager_id: input.manager_id ? Number(input.manager_id) : null,
    role: input.role,
    // Added here (not from Roles & access): no template, so access follows the role's defaults.
    role_template_id: null,
    modules: null,
    module_levels: null,
    actions: null,
    start_date: input.start_date ? dateCast(input.start_date) : stamp,
    notes: input.notes?.trim() || null,
    is_active: input.is_active ?? true,
    invited_at: null,
    last_login_at: null,
    created_at: stamp,
    updated_at: stamp,
    password: input.password!,
  };
  db.users.push(user);
  return { success: true, message: 'User created successfully.', data: withManager(db, user) };
}

/** PUT /users/{user} */
function update(publicId: string, input: Partial<UserInput>) {
  const me = requireUsersAccess();
  requireFeature(me, 'users', true);
  const db = getDb();
  const user = findUser(db, publicId);
  validate(db, input, user);
  if (input.role) assertCanAssignRole(me, input.role);
  // A Full Admin or Clinical Supervisor account is only edited by a Full Admin.
  if (SENSITIVE_ROLES.includes(user.role) && me.role !== 'FULL_ADMIN') {
    throw new ApiError(403, { message: `Only a Full Admin can edit a ${user.role === 'FULL_ADMIN' ? 'Full Admin' : 'Clinical Supervisor'} account.` });
  }

  const text = (v: string | null | undefined) => (v === undefined ? undefined : v?.trim() || null);
  const changes: Partial<UserRow> = {
    first_name: input.first_name?.trim(),
    middle_name: text(input.middle_name),
    last_name: input.last_name?.trim(),
    email: input.email?.trim(),
    phone_number: text(input.phone_number),
    department: input.department,
    role: input.role,
    manager_id: input.manager_id === undefined ? undefined : input.manager_id ? Number(input.manager_id) : null,
    start_date: input.start_date === undefined ? undefined : input.start_date ? dateCast(input.start_date) : user.start_date,
    notes: text(input.notes),
    is_active: input.is_active,
    password: input.password || undefined,
  };
  for (const [key, value] of Object.entries(changes)) {
    if (value !== undefined) Object.assign(user, { [key]: value });
  }
  user.updated_at = now();
  return { success: true, message: 'User updated successfully.', data: withManager(db, user) };
}

/** DELETE /users/{user} — permanent, so Full Admin only; everyone else deactivates instead. */
function destroy(publicId: string) {
  const me = requireUsersAccess();
  requireFeature(me, 'users', true);
  if (me.role !== 'FULL_ADMIN') {
    throw new ApiError(403, { message: 'Only a Full Admin can delete a user account. Deactivate the account instead.' });
  }
  const db = getDb();
  const user = findUser(db, publicId);
  db.users = db.users.filter((u) => u.id !== user.id);
  return { success: true, message: 'User deleted successfully.' };
}

export function createUsersApi(): ApiClient['users'] {
  return {
    list: (query) => delay(() => list(query)),
    show: (publicId) => delay(() => show(publicId)),
    store: (input) => delay(() => store(input)),
    update: (publicId, input) => delay(() => update(publicId, input)),
    destroy: (publicId) => delay(() => destroy(publicId)),
  };
}
