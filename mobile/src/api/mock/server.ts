/**
 * Shared plumbing for the mock handlers: the in-memory database, simulated
 * latency, and the stand-ins for Laravel's `auth` and `feature:` middleware.
 */

import { canAccessFeature, levelFor } from '@/auth/permissions';
import type { ModuleKey } from '@/auth/roles';

import { ApiError } from '../errors';
import { getAuthToken } from '../token';
import type { User } from '../types';
import type { MockDb, UserRow } from './rows';
import { buildSeed } from './seed';

/** Simulated network latency so loading states are real. */
const LATENCY_MS: [number, number] = [250, 600];

let db: MockDb | null = null;
export function getDb(): MockDb {
  db ??= buildSeed();
  return db;
}

export function delay<T>(value: () => T): Promise<T> {
  const ms = LATENCY_MS[0] + Math.random() * (LATENCY_MS[1] - LATENCY_MS[0]);
  return new Promise((resolve, reject) => {
    setTimeout(() => {
      try {
        // Clone so callers can't mutate the mock database by accident.
        resolve(JSON.parse(JSON.stringify(value())) as T);
      } catch (e) {
        reject(e);
      }
    }, ms);
  });
}

/** Laravel's `email` rule (simplified). */
export const EMAIL_PATTERN = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

export function validationError(field: string, message: string): ApiError {
  return new ApiError(422, { message, errors: { [field]: [message] } });
}

export function toApiUser(row: UserRow): User {
  const { password: _password, ...user } = row;
  const template = getDb().roleTemplates.find((t) => t.id === row.role_template_id) ?? null;
  return { ...user, role_template: template };
}

/** Resolves the caller from the bearer token, like the `auth` middleware. */
export function requireUser(): UserRow {
  const token = getAuthToken();
  const id = token?.startsWith('mock|') ? Number(token.split('|')[1]) : NaN;
  const user = getDb().users.find((u) => u.id === id);
  if (!user || !user.is_active) {
    throw new ApiError(401, { message: 'Unauthenticated.' });
  }
  return user;
}

/** FeatureMiddleware: module gate + "view" level blocks writes. */
export function requireFeature(user: UserRow, feature: ModuleKey, write = false): void {
  const u = toApiUser(user);
  if (!canAccessFeature(u, feature)) {
    throw new ApiError(403, { message: 'You do not have access to this feature.' });
  }
  if (write && levelFor(u, feature) === 'view') {
    throw new ApiError(403, { message: 'Your access to this area is view-only.' });
  }
}

export function staffDisplayName(user: Pick<UserRow, 'first_name' | 'last_name'>): string {
  return `${user.first_name} ${user.last_name}`;
}
