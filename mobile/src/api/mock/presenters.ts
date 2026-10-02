/**
 * Ports of the CalendarSession model helpers and
 * CalendarController::sessionPayload(), so mock payloads match the server's.
 */

import { clinicToIso, todayYmd } from '@/utils/dates';

import type { CalendarSessionPayload, MySessionPayload, SessionCategory } from '../types';
import type { CalendarSessionRow, MockDb } from './rows';

/** config('billing.cancel_policy.notice_hours') */
const CANCEL_NOTICE_HOURS = 24;

/** CalendarSession::NON_THERAPY_TYPES */
export const NON_THERAPY_TYPES = ['Supervision', 'Observation', 'Admin time', 'Training', 'Meeting'];

/** CalendarSession::THERAPY_TYPES */
export const THERAPY_TYPES = ['ABA', 'Speech', 'OT', 'Assessment', 'Parent training'];

/**
 * Patient::calendarSessions(): sessions whose patient_id is the lead, or
 * group bookings whose patient_ids contain it.
 */
export function sessionsForLead(db: MockDb, leadId: number): CalendarSessionRow[] {
  return db.sessions.filter((s) => s.patient_id === leadId || (s.patient_ids ?? []).includes(leadId));
}

/**
 * PatientAuthorization::matchingActivityTypes(): covers_services may hold
 * full labels ("ABA therapy"), so match by case-insensitive substring
 * either way against the therapy activity codes.
 */
export function matchingActivityTypes(covers: string[] | null): string[] {
  if (!covers || covers.length === 0) return [];
  return THERAPY_TYPES.filter((type) =>
    covers.some((cover) => {
      const t = type.toLowerCase();
      const c = cover.toLowerCase();
      return t.includes(c) || c.includes(t);
    }),
  );
}

/** PatientAuthorization::hoursUsed(): past-or-today sessions of covered types, any status. */
export function authorizationHoursUsed(
  db: MockDb,
  leadId: number,
  covers: string[] | null,
  today: string,
): number {
  if (!covers || covers.length === 0) return 0;
  const types = matchingActivityTypes(covers);
  const minutes = sessionsForLead(db, leadId)
    .filter((s) => s.session_date <= today && types.includes(s.activity_type))
    .reduce((sum, s) => sum + s.duration_minutes, 0);
  return Math.round(minutes / 60);
}

export function displayName(s: CalendarSessionRow): string {
  return s.patient_name || s.activity_label || 'Unassigned';
}

export function category(s: CalendarSessionRow): SessionCategory {
  if (s.status === 'cancelled') return 'cancelled';
  if (s.cover_for_user_id) return 'covered';
  switch (s.activity_type) {
    case 'Supervision':
      return 'supervision';
    case 'Observation':
      return 'observation';
    case 'Admin time':
    case 'Training':
    case 'Meeting':
      return 'admin';
    default:
      return 'therapy';
  }
}

export function statusLabel(s: CalendarSessionRow): string {
  if (s.status === 'cancelled') {
    if (s.cancel_reason === 'clinic') return 'Cancelled — clinic';
    if (s.cancel_reason === 'family') {
      return s.cancel_notice_hours === null || s.cancel_notice_hours >= CANCEL_NOTICE_HOURS
        ? 'Cancelled — with notice'
        : 'Cancelled — late';
    }
    return 'Cancelled';
  }
  if (s.status === 'no_show') return 'No-show';
  if (s.status === 'closed') return 'Closed';
  return s.status.charAt(0).toUpperCase() + s.status.slice(1);
}

/** scopeDirectTherapy(): therapy types with a real patient attached. */
export function isDirectTherapy(s: CalendarSessionRow): boolean {
  return !NON_THERAPY_TYPES.includes(s.activity_type) && (s.patient_id !== null || s.patient_ids !== null);
}

function endsAtIso(s: CalendarSessionRow): string {
  return clinicToIso(s.session_date, s.end_time);
}

/**
 * scopePastDueScheduled()->update(['status' => 'completed']) — the catch-up
 * Laravel runs on every calendar/dashboard load and every 15 minutes by cron.
 */
export function autoCompletePastSessions(db: MockDb, now: Date = new Date()): void {
  const nowIso = now.toISOString();
  for (const s of db.sessions) {
    if (s.status === 'scheduled' && endsAtIso(s) < nowIso) {
      s.status = 'completed';
    }
  }
}

function staffName(db: MockDb, id: number | null): string | null {
  if (id === null) return null;
  const u = db.users.find((x) => x.id === id);
  return u ? `${u.first_name} ${u.last_name}` : null;
}

function supervisedAtLabel(iso: string | null): string | null {
  if (!iso) return null;
  // "Y-m-d H:i" in clinic time
  const local = new Date(new Date(iso).getTime() + 4 * 3600_000).toISOString();
  return `${local.slice(0, 10)} ${local.slice(11, 16)}`;
}

export function sessionPayload(db: MockDb, s: CalendarSessionRow, now: Date = new Date()): CalendarSessionPayload {
  const today = todayYmd(now);
  const inactive = s.status === 'cancelled' || s.status === 'closed';
  return {
    id: s.id,
    therapist_id: s.therapist_id,
    therapist_name: staffName(db, s.therapist_id),
    cover_for_user_id: s.cover_for_user_id,
    cover_for_name: staffName(db, s.cover_for_user_id),
    patient_id: s.patient_id,
    patient_ids: s.patient_ids,
    patient_name: displayName(s),
    is_custom_patient: !s.patient_id && !s.patient_ids && Boolean(s.patient_name),
    activity_label: s.activity_label,
    activity_type: s.activity_type,
    activity_types: s.activity_types?.length ? s.activity_types : [s.activity_type],
    session_date: s.session_date,
    start_time: s.start_time.slice(0, 5),
    end_time: s.end_time.slice(0, 5),
    duration_minutes: s.duration_minutes,
    room: s.room,
    status: s.status,
    cancel_reason: s.cancel_reason,
    cancel_notice_hours: s.cancel_notice_hours,
    status_label: statusLabel(s),
    category: category(s),
    notes: s.notes,
    recurrence_group: s.recurrence_group,
    is_past: endsAtIso(s) < now.toISOString(),
    can_supervise: s.session_date < today && !inactive,
    supervised: s.supervised_at !== null,
    supervised_by_name: staffName(db, s.supervised_by),
    supervised_at: supervisedAtLabel(s.supervised_at),
    supervision_notes: s.supervision_notes,
  };
}

/** sessionPayload() + the therapist's own note (only for their own sessions). */
export function mySessionPayload(
  db: MockDb,
  s: CalendarSessionRow,
  viewerId: number,
  now: Date = new Date(),
): MySessionPayload {
  return {
    ...sessionPayload(db, s, now),
    therapist_note: s.therapist_id === viewerId ? s.therapist_note : null,
  };
}
