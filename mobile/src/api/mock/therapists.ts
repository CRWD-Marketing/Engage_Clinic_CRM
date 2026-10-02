/**
 * Mock of TherapistController::index (`feature:therapists`) and the session
 * status change its Close / Reopen buttons send to CalendarController::update.
 */

import { canManageCalendar, levelFor } from '@/auth/permissions';
import { addDays, mondayOf, todayYmd } from '@/utils/dates';

import type { ApiClient } from '../client';
import { ApiError } from '../errors';
import type { SessionUpdateResponse, TherapistsIndex } from '../types';
import { autoCompletePastSessions, sessionPayload } from './presenters';
import type { CalendarSessionRow } from './rows';
import { laravelIso } from './seed';
import { delay, getDb, requireFeature, requireUser, toApiUser } from './server';

const isInactive = (s: CalendarSessionRow) => s.status === 'cancelled' || s.status === 'closed';

/** Str::title(str_replace('_', ' ', $department)) */
function departmentLabel(department: string | null): string {
  return (department ?? '')
    .toLowerCase()
    .split('_')
    .map((w) => (w ? w[0].toUpperCase() + w.slice(1) : w))
    .join(' ');
}

/** GET /therapist?therapist_id=&week= */
function index(params: { therapist_id?: number; week?: string } = {}): TherapistsIndex {
  const user = requireUser();
  requireFeature(user, 'therapists');
  const db = getDb();
  const now = new Date();
  // A therapist only ever sees their own schedule; others can be scoped down with the "own" level.
  const canViewAll = user.role !== 'THERAPIST' && levelFor(toApiUser(user), 'therapists') !== 'own';

  let therapists = db.users
    .filter((u) => u.role === 'THERAPIST')
    .sort((a, b) => a.first_name.localeCompare(b.first_name));
  if (!canViewAll) therapists = therapists.filter((t) => t.id === user.id);
  // No "All" option: default to the first therapist when none is asked for.
  const selectedId = canViewAll ? (params.therapist_id ?? therapists[0]?.id ?? null) : user.id;

  // workWeekFor(): Mon–Sun of the week containing the anchor (today when missing or invalid).
  const anchor = params.week && /^\d{4}-\d{2}-\d{2}$/.test(params.week) ? params.week : todayYmd(now);
  const weekStart = mondayOf(anchor);
  const weekEnd = addDays(weekStart, 6);

  const weekSessions = db.sessions
    .filter((s) => s.session_date >= weekStart && s.session_date <= weekEnd)
    .filter((s) => canViewAll || s.therapist_id === user.id);

  return {
    can_view_all: canViewAll,
    week_start: weekStart,
    week_end: weekEnd,
    is_current_week: weekStart === mondayOf(todayYmd(now)),
    selected_therapist_id: selectedId,
    therapists: therapists.map((t) => {
      const mine = weekSessions.filter((s) => s.therapist_id === t.id);
      const minutes = mine.filter((s) => !isInactive(s)).reduce((sum, s) => sum + s.duration_minutes, 0);
      return {
        id: t.id,
        name: `${t.first_name} ${t.last_name}`.trim(),
        department_label: departmentLabel(t.department),
        // Booked hours, cancelled and closed sessions excluded.
        weekly_hours: Math.round((minutes / 60) * 10) / 10,
        // Every session that week, whatever its status (as the web counts it).
        session_count: mine.length,
      };
    }),
    sessions: weekSessions
      .filter((s) => selectedId === null || s.therapist_id === selectedId)
      .sort((a, b) => a.session_date.localeCompare(b.session_date) || a.start_time.localeCompare(b.start_time))
      .map((s) => sessionPayload(db, s, now)),
  };
}

/** CalendarController::hasConflict(): another active session of the same therapist overlapping the slot. */
function hasConflict(session: CalendarSessionRow): boolean {
  return getDb().sessions.some(
    (s) =>
      s.id !== session.id &&
      s.therapist_id === session.therapist_id &&
      s.session_date === session.session_date &&
      !isInactive(s) &&
      s.start_time < session.end_time &&
      s.end_time > session.start_time,
  );
}

/**
 * PUT /calendar/{id} {status} — only the part the Therapists page uses:
 * Close discontinues the slot (it leaves the calendar), Reopen puts it back
 * as scheduled.
 */
function setStatus(id: number, status: 'closed' | 'scheduled'): SessionUpdateResponse {
  const user = requireUser();
  requireFeature(user, 'calendar', true);
  if (!canManageCalendar(toApiUser(user))) {
    throw new ApiError(403, { message: 'Only the Clinical Supervisor can change the schedule.' });
  }
  if (status !== 'closed' && status !== 'scheduled') {
    const message = 'The selected status is invalid.';
    throw new ApiError(422, { message, errors: { status: [message] } });
  }
  const db = getDb();
  const session = db.sessions.find((s) => s.id === id);
  if (!session) throw new ApiError(404, { message: 'Not found.' });

  if (status === 'scheduled' && hasConflict(session)) {
    const message = 'This therapist already has a session that overlaps this time slot.';
    throw new ApiError(422, { message, errors: { start_time: [message] } });
  }

  session.status = status;
  // Only a family cancellation keeps notice hours.
  session.cancel_notice_hours = null;
  session.updated_at = laravelIso(new Date().toISOString());
  // A reopened session whose time has already passed counts as attended again.
  autoCompletePastSessions(db);
  return { message: 'Session updated.', session: sessionPayload(db, session) };
}

export function createTherapistsApi(): ApiClient['therapists'] {
  return { index: (params) => delay(() => index(params)) };
}

export const setSessionStatus = (id: number, status: 'closed' | 'scheduled') => delay(() => setStatus(id, status));
