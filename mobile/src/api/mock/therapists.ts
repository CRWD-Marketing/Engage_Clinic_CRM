/**
 * Mock of TherapistController::index (`feature:therapists`). Its Close /
 * Reopen and Add / Edit buttons go through the calendar endpoints (booking.ts).
 */

import { levelFor } from '@/auth/permissions';
import { addDays, mondayOf, todayYmd } from '@/utils/dates';

import type { ApiClient } from '../client';
import type { TherapistsIndex } from '../types';
import { sessionPayload } from './presenters';
import type { CalendarSessionRow } from './rows';
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

export function createTherapistsApi(): ApiClient['therapists'] {
  return { index: (params) => delay(() => index(params)) };
}
