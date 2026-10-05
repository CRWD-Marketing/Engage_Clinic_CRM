/**
 * Mock implementation of ApiClient. Each handler follows the Laravel
 * controller it stands in for (validation, throttling, role scoping, 403s),
 * so screens exercise the real rules before the backend exists.
 *
 * Mock-only differences are marked "MOCK:".
 */

import { canManageCalendar, levelFor } from '@/auth/permissions';
import { addDays, mondayOf, todayYmd } from '@/utils/dates';

import type { ApiClient } from '../client';
import { ApiError } from '../errors';
import type {
  CalendarFeed,
  LoginRequest,
  LoginResponse,
  MyCalendarDay,
  MyCalendarWeek,
  ProfileUpdateRequest,
  ProfileUpdateResponse,
  SuperviseResponse,
} from '../types';
import {
  autoCompletePastSessions,
  isDirectTherapy,
  mySessionPayload,
  sessionPayload,
} from './presenters';
import { createBillingApi } from './billing';
import { createBookingApi } from './booking';
import { createCareersApi } from './careers';
import { createContactsApi } from './contacts';
import { createDashboardApi } from './dashboard';
import { createLeaveApi, createNotificationsApi, createPatientCreationApi } from './extras';
import { createPackagesApi } from './packages';
import { createReportsApi } from './reports';
import { createRolesApi } from './roles';
import { createSettingsApi } from './settings';
import { createTherapistsApi } from './therapists';
import { createUsersApi } from './users';
import { createVendorsApi } from './vendors';
import { createInboxApi } from './inbox';
import { createLeadsApi } from './leads';
import { createNotesApi } from './notes';
import { createPatientsApi } from './patients';
import type { UserRow } from './rows';
import { laravelIso } from './seed';
import { delay, EMAIL_PATTERN, getDb, requireFeature, requireUser, toApiUser, validationError } from './server';

// ---------------------------------------------------------------------------
// Auth
// ---------------------------------------------------------------------------

/** MOCK: tokens encode the user id; a real backend would issue Sanctum tokens. */
function issueToken(userId: number): string {
  return `mock|${userId}|${Math.random().toString(36).slice(2)}`;
}

/** LoginController throttle: 5 failures per email (+IP) → locked for the rest of 60s. */
const MAX_ATTEMPTS = 5;
const DECAY_SECONDS = 60;
const attempts = new Map<string, { count: number; firstAt: number }>();

function throttleKey(email: string) {
  return email.trim().toLowerCase();
}

function secondsUntilUnlocked(key: string, now: number): number | null {
  const entry = attempts.get(key);
  if (!entry) return null;
  const remaining = Math.ceil((entry.firstAt + DECAY_SECONDS * 1000 - now) / 1000);
  if (remaining <= 0) {
    attempts.delete(key);
    return null;
  }
  return entry.count >= MAX_ATTEMPTS ? remaining : null;
}

function recordFailure(key: string, now: number) {
  const entry = attempts.get(key);
  if (!entry || now - entry.firstAt > DECAY_SECONDS * 1000) {
    attempts.set(key, { count: 1, firstAt: now });
  } else {
    entry.count += 1;
  }
}

function login(input: LoginRequest): LoginResponse {
  const email = input.email.trim();
  if (!email) throw validationError('email', 'The email field is required.');
  if (!EMAIL_PATTERN.test(email)) {
    throw validationError('email', 'The email field must be a valid email address.');
  }
  if (!input.password) throw validationError('password', 'The password field is required.');

  const key = throttleKey(email);
  const now = Date.now();
  const locked = secondsUntilUnlocked(key, now);
  if (locked !== null) {
    throw new ApiError(429, {
      message: `Too many login attempts. Please try again in ${locked} seconds.`,
      errors: { email: [`Too many login attempts. Please try again in ${locked} seconds.`] },
    });
  }

  const user = getDb().users.find((u) => u.email.toLowerCase() === key);
  if (!user || user.password !== input.password) {
    recordFailure(key, now);
    throw validationError('email', 'Invalid email or password.');
  }

  // MOCK: Laravel's LoginController does not check is_active (known issue);
  // the mobile API should refuse suspended accounts, so the mock does.
  if (!user.is_active) {
    throw validationError('email', 'This account has been suspended. Contact your administrator.');
  }

  attempts.delete(key);
  user.last_login_at = laravelIso(new Date().toISOString());
  return { token: issueToken(user.id), user: toApiUser(user) };
}

// ---------------------------------------------------------------------------
// Profile (ProfileController::update)
// ---------------------------------------------------------------------------

function updateProfile(input: ProfileUpdateRequest): ProfileUpdateResponse {
  const user = requireUser();
  const data = getDb();

  // Laravel's ConvertEmptyStringsToNull + TrimStrings middleware.
  const clean = (v: string | null | undefined) => {
    const t = (v ?? '').trim();
    return t === '' ? null : t;
  };
  const values = {
    first_name: clean(input.first_name),
    last_name: clean(input.last_name),
    job_title: clean(input.job_title),
    email: clean(input.email),
    phone_number: clean(input.phone_number),
    timezone: clean(input.timezone),
    message_signature: clean(input.message_signature),
  };

  const errors: Record<string, string[]> = {};
  const fail = (field: string, message: string) => {
    errors[field] ??= [message];
  };
  const label = (field: string) => field.replace(/_/g, ' ');
  const required = (field: keyof typeof values) => {
    if (values[field] === null) fail(field, `The ${label(field)} field is required.`);
  };
  const max = (field: keyof typeof values, limit: number) => {
    const v = values[field];
    if (v !== null && v.length > limit) {
      fail(field, `The ${label(field)} field must not be greater than ${limit} characters.`);
    }
  };

  required('first_name');
  max('first_name', 100);
  required('last_name');
  max('last_name', 100);
  max('job_title', 100);
  required('email');
  if (values.email !== null && !EMAIL_PATTERN.test(values.email)) {
    fail('email', 'The email field must be a valid email address.');
  }
  max('email', 255);
  if (
    values.email !== null &&
    data.users.some((u) => u.id !== user.id && u.email.toLowerCase() === values.email!.toLowerCase())
  ) {
    fail('email', 'The email has already been taken.');
  }
  max('phone_number', 30);
  max('timezone', 60);
  max('message_signature', 255);

  const fields = Object.keys(errors);
  if (fields.length > 0) {
    // Laravel answers `{success: false, errors}`; the message is the first error.
    throw new ApiError(422, { message: errors[fields[0]][0], errors });
  }

  Object.assign(user, values, { updated_at: laravelIso(new Date().toISOString()) });
  return { success: true, user: toApiUser(user) };
}

// ---------------------------------------------------------------------------
// Calendar (CalendarController)
// ---------------------------------------------------------------------------

/** CalendarController::designation() */
function designation(user: UserRow): string {
  if (user.job_title) return user.job_title;
  if (user.role === 'CLINICAL_SUPERVISOR') return 'BCBA Supervisor';
  if (user.role === 'THERAPIST') return 'Therapist';
  return (user.department ?? user.role)
    .toLowerCase()
    .split('_')
    .map((w) => w.charAt(0).toUpperCase() + w.slice(1))
    .join(' ');
}

function myWeek(date?: string): MyCalendarWeek {
  const user = requireUser();
  requireFeature(user, 'calendar');

  const data = getDb();
  const now = new Date();
  const today = todayYmd(now);
  autoCompletePastSessions(data, now);

  const anchor = date && /^\d{4}-\d{2}-\d{2}$/.test(date) ? date : today;
  const monday = mondayOf(anchor);
  const sunday = addDays(monday, 6);

  const mine = data.sessions
    .filter((s) => s.therapist_id === user.id && s.status !== 'closed')
    .filter((s) => s.session_date >= monday && s.session_date <= sunday)
    .sort((a, b) => a.start_time.localeCompare(b.start_time));

  const therapyMinutes = mine
    .filter((s) => isDirectTherapy(s) && (s.status === 'completed' || s.status === 'scheduled'))
    .reduce((sum, s) => sum + s.duration_minutes, 0);

  const days: MyCalendarDay[] = Array.from({ length: 7 }, (_, i) => {
    const iso = addDays(monday, i);
    const leave = data.staffLeaves.find((l) => l.user_id === user.id && l.leave_date === iso);
    return {
      iso,
      is_today: iso === today,
      is_weekend: i >= 5,
      sessions: mine.filter((s) => s.session_date === iso).map((s) => mySessionPayload(data, s, user.id, now)),
      leave: leave ? { id: leave.id, user_id: leave.user_id, leave_date: leave.leave_date, leave_type: leave.leave_type, reason: leave.reason } : null,
    };
  });

  return {
    therapist_name: `${user.first_name} ${user.last_name}`,
    designation: designation(user),
    monday,
    sunday,
    prev_date: addDays(monday, -7),
    next_date: addDays(monday, 7),
    therapy_hours: Math.round((therapyMinutes / 60) * 10) / 10,
    days,
  };
}

/** CalendarController::feed() */
function feed(start: string, end: string): CalendarFeed {
  const user = requireUser();
  requireFeature(user, 'calendar');
  const isDate = (v: string) => /^\d{4}-\d{2}-\d{2}$/.test(v);
  if (!isDate(start) || !isDate(end)) throw validationError('start', 'The start field must be a valid date.');

  const data = getDb();
  const now = new Date();
  autoCompletePastSessions(data, now);

  // Therapists are forced to their own sessions and leave, whatever is asked.
  const own = user.role === 'THERAPIST';
  const sessions = data.sessions
    .filter((s) => s.status !== 'closed' && s.session_date >= start && s.session_date <= end)
    .filter((s) => !own || s.therapist_id === user.id)
    .sort((a, b) => a.session_date.localeCompare(b.session_date) || a.start_time.localeCompare(b.start_time));
  const leaves = data.staffLeaves
    .filter((l) => l.leave_date >= start && l.leave_date <= end)
    .filter((l) => !own || l.user_id === user.id);

  return {
    sessions: sessions.map((s) => mySessionPayload(data, s, user.id, now)),
    leaves: leaves.map((l) => {
      const owner = data.users.find((u) => u.id === l.user_id);
      return {
        id: l.id,
        user_id: l.user_id,
        user_name: owner ? `${owner.first_name} ${owner.last_name}` : null,
        leave_date: l.leave_date,
        leave_type: l.leave_type,
        reason: l.reason,
      };
    }),
  };
}

function findSessionOr404(id: number) {
  const session = getDb().sessions.find((s) => s.id === id);
  if (!session) throw new ApiError(404, { message: 'Not found.' });
  return session;
}

function showSession(id: number) {
  const user = requireUser();
  requireFeature(user, 'calendar');
  const data = getDb();
  autoCompletePastSessions(data);
  const session = findSessionOr404(id);

  // MOCK: Laravel's GET /calendar/{id} has no ownership check (known issue).
  // "own"-level users may only open their own sessions here.
  if (levelFor(toApiUser(user), 'calendar') === 'own' && session.therapist_id !== user.id) {
    throw new ApiError(403, { message: 'Only your own sessions.' });
  }
  return mySessionPayload(data, session, user.id);
}

function saveTherapistNote(id: number, note: string | null) {
  const user = requireUser();
  requireFeature(user, 'calendar', true);
  const session = findSessionOr404(id);

  if (session.therapist_id !== user.id) {
    throw new ApiError(403, { message: 'Only your own sessions.' });
  }
  if (note !== null && note.length > 2000) {
    throw validationError('note', 'The note field must not be greater than 2000 characters.');
  }

  session.therapist_note = note ? note : null;
  session.updated_at = laravelIso(new Date().toISOString());
  return { message: 'Note saved.', therapist_note: session.therapist_note };
}

/** CalendarController::supervise() — managers log supervision on a past session. */
function supervise(id: number, notes: string): SuperviseResponse {
  const user = requireUser();
  requireFeature(user, 'calendar', true);
  if (!canManageCalendar(toApiUser(user))) {
    throw new ApiError(403, { message: 'Only the Clinical Supervisor can change the schedule.' });
  }
  const data = getDb();
  const session = findSessionOr404(id);

  const text = notes.trim();
  if (!text || text.length > 2000) {
    const field = text ? 'The notes field must not be greater than 2000 characters.' : 'The notes field is required.';
    throw new ApiError(422, { message: 'Add a supervision note.', errors: { notes: [field] } });
  }
  if (session.status === 'cancelled' || session.status === 'closed') {
    throw new ApiError(422, { message: 'A cancelled or closed session can’t be supervised.' });
  }
  if (!(session.session_date < todayYmd())) {
    throw new ApiError(422, { message: 'Only a session from a previous day can be supervised.' });
  }

  const stamp = laravelIso(new Date().toISOString());
  session.supervised_by = user.id;
  session.supervised_at = stamp;
  session.supervision_notes = text;
  session.updated_at = stamp;
  return { message: 'Supervision logged.', session: sessionPayload(data, session) };
}

/** CalendarController::unsupervise() */
function unsupervise(id: number): { message: string } {
  const user = requireUser();
  requireFeature(user, 'calendar', true);
  if (!canManageCalendar(toApiUser(user))) {
    throw new ApiError(403, { message: 'Only the Clinical Supervisor can change the schedule.' });
  }
  const session = findSessionOr404(id);
  session.supervised_by = null;
  session.supervised_at = null;
  session.supervision_notes = null;
  session.updated_at = laravelIso(new Date().toISOString());
  return { message: 'Supervision removed.' };
}

// ---------------------------------------------------------------------------

export function createMockApi(): ApiClient {
  return {
    auth: {
      login: (input) => delay(() => login(input)),
      me: () => delay(() => toApiUser(requireUser())),
      logout: () => delay(() => undefined),
      forgotPassword: (email) =>
        delay(() => {
          const value = email.trim();
          if (!value) throw validationError('email', 'The email field is required.');
          if (!EMAIL_PATTERN.test(value)) {
            throw validationError('email', 'The email field must be a valid email address.');
          }
          return { message: 'If an account exists for that email, a password reset link has been sent.' };
        }),
    },
    profile: {
      update: (input) => delay(() => updateProfile(input)),
    },
    dashboard: createDashboardApi(),
    notes: createNotesApi(),
    notifications: createNotificationsApi(),
    patients: { ...createPatientsApi(), ...createPatientCreationApi() },
    inbox: createInboxApi(),
    leads: createLeadsApi(),
    contacts: createContactsApi(),
    therapists: createTherapistsApi(),
    reports: createReportsApi(),
    billing: createBillingApi(),
    users: createUsersApi(),
    roles: createRolesApi(),
    careers: createCareersApi(),
    packages: createPackagesApi(),
    vendors: createVendorsApi(),
    settings: createSettingsApi(),
    calendar: {
      myWeek: (date) => delay(() => myWeek(date)),
      feed: (start, end) => delay(() => feed(start, end)),
      show: (id) => delay(() => showSession(id)),
      saveTherapistNote: (id, note) => delay(() => saveTherapistNote(id, note)),
      supervise: (id, notes) => delay(() => supervise(id, notes)),
      unsupervise: (id) => delay(() => unsupervise(id)),
      ...createBookingApi(),
      ...createLeaveApi(),
    },
  };
}
