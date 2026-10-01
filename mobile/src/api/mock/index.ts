/**
 * Mock implementation of ApiClient. Each handler follows the Laravel
 * controller it stands in for (validation, throttling, role scoping, 403s),
 * so screens exercise the real rules before the backend exists.
 *
 * Mock-only differences are marked "MOCK:".
 */

import { canAccessFeature, levelFor } from '@/auth/permissions';
import type { ModuleKey } from '@/auth/roles';
import { addDays, mondayOf, todayYmd } from '@/utils/dates';
import { fullName } from '@/utils/format';

import type { ApiClient } from '../client';
import { ApiError } from '../errors';
import { getAuthToken } from '../token';
import type {
  LoginRequest,
  LoginResponse,
  MyCalendarDay,
  MyCalendarWeek,
  Patient,
  PatientNote,
  ProfileUpdateRequest,
  ProfileUpdateResponse,
  TherapistDashboard,
  User,
} from '../types';
import {
  autoCompletePastSessions,
  isDirectTherapy,
  mySessionPayload,
  sessionPayload,
} from './presenters';
import type { MockDb, UserRow } from './rows';
import { buildSeed, laravelIso } from './seed';

/** Simulated network latency so loading states are real. */
const LATENCY_MS: [number, number] = [250, 600];

/** DashboardController header: clinic location line. */
const LOCATION_LABEL = 'Khalifa City, Abu Dhabi';

let db: MockDb | null = null;
function getDb(): MockDb {
  db ??= buildSeed();
  return db;
}

function delay<T>(value: () => T): Promise<T> {
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
const EMAIL_PATTERN = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

function validationError(field: string, message: string): ApiError {
  return new ApiError(422, { message, errors: { [field]: [message] } });
}

// ---------------------------------------------------------------------------
// Auth
// ---------------------------------------------------------------------------

/** MOCK: tokens encode the user id; a real backend would issue Sanctum tokens. */
function issueToken(userId: number): string {
  return `mock|${userId}|${Math.random().toString(36).slice(2)}`;
}

function toApiUser(row: UserRow): User {
  const { password: _password, ...user } = row;
  const template = getDb().roleTemplates.find((t) => t.id === row.role_template_id) ?? null;
  return { ...user, role_template: template };
}

/** Resolves the caller from the bearer token, like the `auth` middleware. */
function requireUser(): UserRow {
  const token = getAuthToken();
  const id = token?.startsWith('mock|') ? Number(token.split('|')[1]) : NaN;
  const user = getDb().users.find((u) => u.id === id);
  if (!user || !user.is_active) {
    throw new ApiError(401, { message: 'Unauthenticated.' });
  }
  return user;
}

/** FeatureMiddleware: module gate + "view" level blocks writes. */
function requireFeature(user: UserRow, feature: ModuleKey, write = false): void {
  const u = toApiUser(user);
  if (!canAccessFeature(u, feature)) {
    throw new ApiError(403, { message: 'You do not have access to this feature.' });
  }
  if (write && levelFor(u, feature) === 'view') {
    throw new ApiError(403, { message: 'Your access to this area is view-only.' });
  }
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
// Dashboard (DashboardController::scheduleMetrics + therapistMetrics)
// ---------------------------------------------------------------------------

function attendance(rows: { status: string }[]): number | null {
  const counted = rows.filter((s) => s.status === 'completed' || s.status === 'no_show');
  if (counted.length === 0) return null;
  return Math.round((counted.filter((s) => s.status === 'completed').length / counted.length) * 100);
}

function therapistDashboard(): TherapistDashboard {
  const user = requireUser();
  requireFeature(user, 'dashboard');
  if (user.role !== 'THERAPIST') {
    throw new ApiError(403, { message: 'This dashboard is for therapists.' });
  }

  const data = getDb();
  const now = new Date();
  const today = todayYmd(now);
  autoCompletePastSessions(data, now);

  const mine = data.sessions.filter((s) => s.therapist_id === user.id);

  const todayList = mine
    .filter((s) => s.session_date === today && s.status !== 'cancelled' && s.status !== 'closed')
    .sort((a, b) => a.start_time.localeCompare(b.start_time));
  const rooms = new Set(todayList.map((s) => s.room).filter(Boolean));

  const inRange = (from: string, to: string) => mine.filter((s) => s.session_date >= from && s.session_date <= to);
  const rate = attendance(inRange(addDays(today, -30), today));
  const prevRate = attendance(inRange(addDays(today, -60), addDays(today, -31)));

  // NOTE: Laravel plucks distinct patient_id including NULL for non-patient
  // blocks; nulls are excluded here (see CLAUDE.md known issues).
  const myLeadIds = [...new Set(mine.map((s) => s.patient_id).filter((id): id is number => id !== null))];

  const withLead = (p: Omit<Patient, 'lead'>): Patient => ({
    ...p,
    lead: data.leads.find((l) => l.id === p.lead_id),
  });

  const pendingNotes: PatientNote[] = data.patientNotes
    .filter((n) => n.user_id === user.id && n.signed_off_at === null)
    .sort((a, b) => a.created_at.localeCompare(b.created_at))
    .map((n) => {
      const patient = data.patients.find((p) => p.id === n.patient_id)!;
      return { ...n, author_name: `${user.first_name} ${user.last_name}`, patient: withLead(patient) };
    });

  const dueCutoff = new Date(now.getTime() + 7 * 86400_000).toISOString();
  const plansDue = data.patients
    .filter((p) => myLeadIds.includes(p.lead_id))
    .filter((p) => p.treatment_plan_review_due_at !== null && p.treatment_plan_review_due_at <= dueCutoff)
    .sort((a, b) => a.treatment_plan_review_due_at!.localeCompare(b.treatment_plan_review_due_at!))
    .map(withLead);

  return {
    user_full_name: fullName(user),
    location_label: LOCATION_LABEL,
    sessions_today_count: todayList.length,
    rooms_in_use_count: rooms.size,
    attendance_rate: rate,
    attendance_delta: rate !== null && prevRate !== null ? rate - prevRate : null,
    today_sessions: todayList.map((s) => sessionPayload(data, s, now)),
    my_active_patients_count: myLeadIds.length,
    my_pending_notes_count: pendingNotes.length,
    my_notes_awaiting_signoff_list: pendingNotes.slice(0, 3),
    my_treatment_plans_due_count: plansDue.length,
    my_treatment_plans_due_list: plansDue.slice(0, 3),
  };
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
    dashboard: {
      therapist: () => delay(therapistDashboard),
    },
    calendar: {
      myWeek: (date) => delay(() => myWeek(date)),
      show: (id) => delay(() => showSession(id)),
      saveTherapistNote: (id, note) => delay(() => saveTherapistNote(id, note)),
    },
  };
}
