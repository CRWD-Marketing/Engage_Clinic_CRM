/**
 * Straight ports of the Laravel permission checks. Keep the logic identical to
 * the backend so the app never shows something the server would refuse:
 * - User::effectiveModules/effectiveActions/effectiveModuleLevels
 * - User::levelFor, User::canAccessFeature, User::canDo
 * - CalendarController::canManage
 */

import type { User } from '@/api/types';

import {
  type ActionKey,
  CALENDAR_MANAGE_ROLES,
  LEGACY_ROLE_PERMISSIONS,
  type Level,
  MODULE_ALIASES,
  MODULES,
  type ModuleKey,
  SYSTEM_TEMPLATES,
} from './roles';

type PermissionUser = Pick<User, 'role' | 'modules' | 'module_levels' | 'actions' | 'role_template'>;

export function effectiveModules(user: PermissionUser): ModuleKey[] | null {
  return user.modules ?? user.role_template?.modules ?? null;
}

export function effectiveActions(user: PermissionUser): ActionKey[] | null {
  return user.actions ?? user.role_template?.actions ?? null;
}

export function effectiveModuleLevels(user: PermissionUser): Partial<Record<ModuleKey, Level>> {
  return user.module_levels ?? user.role_template?.module_levels ?? {};
}

/** "full" unless a more specific level (own/view/edit) was granted. */
export function levelFor(user: PermissionUser, module: ModuleKey): Level {
  return effectiveModuleLevels(user)[module] ?? 'full';
}

export function canAccessFeature(user: PermissionUser, feature: string): boolean {
  const modules = effectiveModules(user);
  const key = MODULE_ALIASES[feature] ?? feature;

  if (modules !== null && key in MODULES) {
    return modules.includes(key as ModuleKey);
  }

  return (LEGACY_ROLE_PERMISSIONS[user.role] ?? []).includes(feature);
}

export function canDo(user: PermissionUser, action: ActionKey): boolean {
  let actions = effectiveActions(user);

  if (actions === null) {
    actions = Object.values(SYSTEM_TEMPLATES).find((t) => t.base_role === user.role)?.actions ?? [];
  }

  return actions.includes(action);
}

/**
 * FeatureMiddleware: a "view" level blocks every non-GET request, so a
 * view-only user must not be offered any write action in that module.
 */
export function canWriteIn(user: PermissionUser, module: ModuleKey): boolean {
  return canAccessFeature(user, module) && levelFor(user, module) !== 'view';
}

/**
 * PROPOSED (mobile sign-off; no Laravel equivalent yet): who may sign off
 * and flag therapists' session notes. Mirrors the roles that own clinical
 * oversight on the web (the supervisor dashboard's sign-off queue).
 */
export function canReviewNotes(user: PermissionUser): boolean {
  return (user.role === 'CLINICAL_SUPERVISOR' || user.role === 'FULL_ADMIN') && canWriteIn(user, 'patients');
}

/** CalendarController::canManage(): reschedule, cancel, leave, supervision. */
export function canManageCalendar(user: PermissionUser): boolean {
  return canDo(user, 'assign_change_schedule') || CALENDAR_MANAGE_ROLES.includes(user.role);
}
