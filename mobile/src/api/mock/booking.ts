/**
 * Mock of the booking side of CalendarController: the booking panel's option
 * lists (rosterStaff + leadsPayload), store() and update(), with the same
 * permission, conflict, package-hours and authorization rules.
 */

import { canDo, canManageCalendar } from '@/auth/permissions';
import { addDays, diffDays, todayYmd, weekdayIndex } from '@/utils/dates';

import type { ApiClient } from '../client';
import { ApiError } from '../errors';
import type {
  BookingLead,
  BookingOptions,
  SessionStatus,
  SessionStoreRequest,
  SessionStoreResponse,
  SessionUpdateRequest,
  SessionUpdateResponse,
} from '../types';
import { PACKAGE_SEEDS, SERVICES } from './catalog';
import { autoCompletePastSessions, matchingActivityTypes, sessionPayload, sessionsForLead, THERAPY_TYPES } from './presenters';
import type { CalendarSessionRow, LeadRow, MockDb, PatientAuthorizationRow } from './rows';
import { addMinutes, laravelIso } from './seed';
import { delay, getDb, requireFeature, requireUser, toApiUser } from './server';

/** CalendarController::DURATIONS, CalendarSession::DEFAULT_TYPES / MANUAL_STATUSES. */
const DURATIONS = [30, 45, 60, 90, 120, 150, 180];
const DEFAULT_TYPES = ['ABA', 'Speech', 'OT', 'Assessment', 'Supervision', 'Parent training', 'Observation', 'Admin time', 'Training'];
const MANUAL_STATUSES: SessionStatus[] = ['scheduled', 'cancelled', 'no_show', 'closed'];

const isInactive = (s: Pick<CalendarSessionRow, 'status'>) => s.status === 'cancelled' || s.status === 'closed';
const now = () => laravelIso(new Date().toISOString());
const invalid = (field: string, message: string) => new ApiError(422, { message, errors: { [field]: [message] } });
const isDate = (v: unknown): v is string => typeof v === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(v);
const isTime = (v: unknown): v is string => typeof v === 'string' && /^([01]\d|2[0-3]):[0-5]\d$/.test(v);
const round1 = (n: number) => Math.round(n * 10) / 10;

/** "1 hour" / "10.5 hours" (fmtHours). */
function fmtHours(hours: number): string {
  const n = String(round1(hours));
  return `${n} ${n === '1' ? 'hour' : 'hours'}`;
}

/** Package::matchingActivityTypes(): therapy types whose code appears in the package's service name. */
function packageTypes(serviceId: number): string[] {
  const label = SERVICES.find((s) => s.id === serviceId)?.name;
  if (!label) return [];
  const l = label.toLowerCase();
  return THERAPY_TYPES.filter((type) => l.includes(type.toLowerCase()) || type.toLowerCase().includes(l));
}

const activeMinutes = (db: MockDb, leadId: number, types: string[]) =>
  sessionsForLead(db, leadId)
    .filter((s) => !isInactive(s) && types.includes(s.activity_type))
    .reduce((sum, s) => sum + s.duration_minutes, 0);

/**
 * Lead::packageHours(): total / used / left for the lead's packages covering
 * this activity type. "Used" is every non-cancelled session, past or future.
 * NOTE: Laravel treats a package's `hours_per_week` as its whole hour balance.
 */
function packageHours(db: MockDb, lead: LeadRow, activityType: string) {
  const type = activityType.trim();
  if (!type) return null;
  const packages = PACKAGE_SEEDS.filter((p) => (lead.package_ids ?? []).includes(p.id) && packageTypes(p.service_id).includes(type));
  if (packages.length === 0) return null;
  const types = [...new Set(packages.flatMap((p) => packageTypes(p.service_id)))];
  const total = packages.reduce((sum, p) => sum + p.hours_per_week, 0);
  const used = round1(activeMinutes(db, lead.id, types) / 60);
  return { label: packages.map((p) => p.name).join(' + ') || 'package', total, used, left: Math.max(0, round1(total - used)) };
}

/** PatientAuthorization::minutesCommitted(): non-cancelled minutes on the covered services (all, when none are listed). */
function minutesCommitted(db: MockDb, leadId: number, auth: PatientAuthorizationRow): number {
  const covers = auth.covers_services ?? [];
  const sessions = sessionsForLead(db, leadId).filter((s) => !isInactive(s));
  const counted = covers.length > 0 ? sessions.filter((s) => matchingActivityTypes(covers).includes(s.activity_type)) : sessions;
  return counted.reduce((sum, s) => sum + s.duration_minutes, 0);
}

/** CalendarController::leadsPayload(): every lead that is a patient, by child name. */
function leadsPayload(db: MockDb, today: string): BookingLead[] {
  return db.patients
    .map((patient) => ({ patient, lead: db.leads.find((l) => l.id === patient.lead_id) }))
    .filter((x): x is { patient: (typeof db.patients)[number]; lead: LeadRow } => !!x.lead)
    .sort((a, b) => (a.lead.child_name ?? '').localeCompare(b.lead.child_name ?? ''))
    .map(({ patient, lead }) => {
      const upcoming = sessionsForLead(db, lead.id)
        .filter((s) => s.status === 'scheduled' && s.session_date >= today)
        .sort((a, b) => a.session_date.localeCompare(b.session_date) || a.start_time.localeCompare(b.start_time));
      const slot = (s: CalendarSessionRow) => ({ date: s.session_date, time: s.start_time.slice(0, 5) });
      const auth = db.authorizations
        .filter((a) => a.patient_id === patient.id)
        .sort((a, b) => a.sort_order - b.sort_order)[0];
      return {
        id: lead.id,
        name: lead.child_name ?? 'Unnamed',
        upcoming_count: upcoming.length,
        next_session: upcoming[0] ? slot(upcoming[0]) : null,
        last_session: upcoming.length > 1 ? slot(upcoming[upcoming.length - 1]) : null,
        auth:
          auth && auth.authorized_hours_total
            ? {
                payer: auth.payer_name,
                total: auth.authorized_hours_total,
                left: Math.max(0, round1(auth.authorized_hours_total - minutesCommitted(db, lead.id, auth) / 60)),
                covers: auth.covers_services ?? [],
              }
            : null,
        packages: PACKAGE_SEEDS.filter((p) => (lead.package_ids ?? []).includes(p.id)).map((p) => {
          const types = packageTypes(p.service_id);
          const used = round1(activeMinutes(db, lead.id, types) / 60);
          return {
            id: p.id,
            name: p.name,
            service: SERVICES.find((s) => s.id === p.service_id)?.name ?? null,
            types,
            total: p.hours_per_week,
            used,
            left: Math.max(0, round1(p.hours_per_week - used)),
          };
        }),
      };
    });
}

/** GET /calendar/leads plus the roster and option lists the booking panel is rendered with. */
function options(): BookingOptions {
  const user = requireUser();
  requireFeature(user, 'calendar');
  const db = getDb();
  return {
    // rosterStaff(): active therapists (a therapist only gets themselves).
    therapists: db.users
      .filter((u) => u.is_active && u.role === 'THERAPIST' && (user.role !== 'THERAPIST' || u.id === user.id))
      .sort((a, b) => a.first_name.localeCompare(b.first_name))
      .map((u) => ({ id: u.id, name: `${u.first_name} ${u.last_name}`.trim() })),
    leads: leadsPayload(db, todayYmd()),
    activity_types: DEFAULT_TYPES,
    durations: DURATIONS,
    leave_types: ['Annual leave', 'Sick leave', 'Public holiday', 'Emergency leave', 'Unpaid leave'],
  };
}

/** CalendarController::hasConflict(): another active session of the same therapist overlapping the slot. */
function hasConflict(db: MockDb, slot: { therapist_id: number; session_date: string; start_time: string; duration_minutes: number }, ignoreId?: number) {
  const start = slot.start_time.slice(0, 5);
  const end = addMinutes(start, slot.duration_minutes).slice(0, 5);
  return db.sessions.some(
    (s) =>
      s.id !== ignoreId &&
      s.therapist_id === slot.therapist_id &&
      s.session_date === slot.session_date &&
      !isInactive(s) &&
      s.start_time.slice(0, 5) < end &&
      s.end_time.slice(0, 5) > start,
  );
}

/** patientAttributes(): one child → patient_id; several, or a typed-in label → patient_ids + a label. */
function patientAttributes(db: MockDb, patientIds: number[], custom: string | null | undefined, activityLabel: string | null) {
  const ids = [...new Set(patientIds.map(Number))];
  const label = (custom ?? '').trim() || null;
  const nameOf = (id: number) => db.leads.find((l) => l.id === id)?.child_name ?? null;
  if (ids.length === 1 && !label) {
    return { patient_id: ids[0], patient_ids: null, patient_name: nameOf(ids[0]), activity_label: activityLabel };
  }
  let name = label;
  if (ids.length > 0) {
    const names = ids.map(nameOf).filter((n): n is string => !!n).sort();
    name = label ? `${label} (${ids.length})` : names.length > 2 ? `${names[0]}, ${names[1]} +${names.length - 2}` : names.join(', ');
  }
  return { patient_id: null, patient_ids: ids.length > 0 ? ids : null, patient_name: name, activity_label: activityLabel };
}

/** typeAttributes() */
function typeAttributes(types: string[]) {
  const clean = [...new Set(types.map((t) => t.trim()).filter(Boolean))];
  return { activity_type: clean[0] ?? 'ABA', activity_types: clean.length > 1 ? clean : null };
}

function validateCommon(db: MockDb, input: SessionUpdateRequest & { therapist_ids?: number[] }) {
  for (const id of input.therapist_ids ?? (input.therapist_id !== undefined ? [input.therapist_id] : [])) {
    if (!db.users.some((u) => u.id === id)) throw invalid('therapist_ids', 'The selected therapist is invalid.');
  }
  for (const id of input.patient_ids ?? []) {
    if (!db.leads.some((l) => l.id === id)) throw invalid('patient_ids', 'The selected patient is invalid.');
  }
  if ((input.custom_patient ?? '').length > 120) throw invalid('custom_patient', 'The custom patient field must not be greater than 120 characters.');
  if (input.session_date !== undefined && !isDate(input.session_date)) throw invalid('session_date', 'The session date field must be a valid date.');
  if (input.start_time !== undefined && !isTime(input.start_time)) throw invalid('start_time', 'The start time field must match the format H:i.');
  if (input.duration_minutes !== undefined && !DURATIONS.includes(input.duration_minutes)) {
    throw invalid('duration_minutes', 'The selected duration minutes is invalid.');
  }
  if ((input.room ?? '').length > 100) throw invalid('room', 'The room field must not be greater than 100 characters.');
  if (input.status !== undefined && input.status !== null && !MANUAL_STATUSES.includes(input.status)) {
    throw invalid('status', 'The selected status is invalid.');
  }
  if (input.cancel_notice_hours != null && (input.cancel_notice_hours < 0 || input.cancel_notice_hours > 720)) {
    throw invalid('cancel_notice_hours', 'The cancel notice hours field must be between 0 and 720.');
  }
}

/** POST /calendar — one session per therapist per occurrence; double-booked slots are skipped, not fatal. */
function store(input: SessionStoreRequest): SessionStoreResponse {
  const user = requireUser();
  requireFeature(user, 'calendar', true);
  const apiUser = toApiUser(user);
  if (!canDo(apiUser, 'book_modify_session')) throw new ApiError(403, { message: 'Your access level can’t book sessions.' });
  if (input.therapist_ids.length > 1 && !canDo(apiUser, 'assign_multiple_therapists')) {
    throw new ApiError(403, { message: 'Your access level can’t assign multiple therapists to one booking.' });
  }

  const db = getDb();
  if (input.therapist_ids.length === 0) throw invalid('therapist_ids', 'The therapist ids field is required.');
  if (input.activity_types.length === 0) throw invalid('activity_types', 'The activity types field is required.');
  if (!input.session_date) throw invalid('session_date', 'The session date field is required.');
  if (!input.start_time) throw invalid('start_time', 'The start time field is required.');
  validateCommon(db, input);
  const patientIds = input.patient_ids ?? [];
  if (patientIds.length === 0 && !(input.custom_patient ?? '').trim() && !(input.activity_label ?? '').trim()) {
    throw invalid('patient_ids', 'Pick a patient, or type a custom patient / activity.');
  }
  const repeats = input.repeats ?? 'none';
  if (!['none', 'weekly', 'biweekly'].includes(repeats)) throw invalid('repeats', 'The selected repeats is invalid.');
  if (repeats === 'weekly' && input.weekdays !== undefined && input.weekdays.length === 0) {
    throw invalid('weekdays', 'Pick at least one day of the week.');
  }
  if (input.occurrences != null && (!Number.isInteger(input.occurrences) || input.occurrences < 1 || input.occurrences > 520)) {
    throw invalid('occurrences', 'The occurrences field must be between 1 and 520.');
  }

  const therapistCount = Math.max(1, input.therapist_ids.length);
  const type = (input.activity_types[0] ?? '').trim();
  const lead = patientIds.length === 1 ? db.leads.find((l) => l.id === patientIds[0]) : undefined;
  const budget = () => (lead ? packageHours(db, lead, type) : null);

  // Occurrence dates: just the one, or a weekly / every-other-week series.
  let dates = [input.session_date];
  if (repeats !== 'none') {
    // Carbon dayOfWeek: 0 = Sunday.
    const dow = (date: string) => (weekdayIndex(date) + 1) % 7;
    const weekdays = input.weekdays && input.weekdays.length > 0 ? input.weekdays : [dow(input.session_date)];
    let max = input.occurrences ?? null;
    if (max === null) {
      // "Using patient hours": size the series to the remaining package balance at this duration.
      const b = budget();
      if (!b) throw invalid('occurrences', 'Set a number of sessions — this patient has no package on file to size a repeating booking automatically.');
      max = Math.floor(Math.round(b.left * 60) / Math.max(1, input.duration_minutes * therapistCount));
      if (max === 0) throw invalid('occurrences', 'No package hours remain for this type — nothing to book.');
      max = Math.min(max, 520);
    }
    const end = addDays(input.session_date, (Math.ceil(max / Math.max(1, weekdays.length)) + 2) * 7);
    dates = [];
    for (let cursor = input.session_date; cursor <= end && dates.length < max; cursor = addDays(cursor, 1)) {
      if (!weekdays.includes(dow(cursor))) continue;
      if (repeats === 'biweekly' && Math.floor(diffDays(input.session_date, cursor) / 7) % 2 === 1) continue;
      dates.push(cursor);
    }
  }

  // Hard stop: never ask for more hours than the patient's matching package has left.
  const newMinutes = dates.length * therapistCount * input.duration_minutes;
  const b = budget();
  if (lead && b && newMinutes > Math.round(b.left * 60)) {
    throw invalid(
      'duration_minutes',
      `This books ${fmtHours(newMinutes / 60)} but ${lead.child_name} only has ${fmtHours(b.left)} left on the ${b.label} package (${fmtHours(b.total)} total). Reduce the hours or number of sessions to fit what remains.`,
    );
  }

  // Soft warning: past what insurance has authorized, the excess bills to the family.
  let authWarning: string | null = null;
  const patient = lead ? db.patients.find((p) => p.lead_id === lead.id) : undefined;
  if (lead && patient) {
    const auth = db.authorizations
      .filter((a) => a.patient_id === patient.id)
      .sort((a, b2) => a.sort_order - b2.sort_order)
      .find(
        (a) =>
          a.authorized_hours_total &&
          (a.covers_services ?? []).some((c) => type.toLowerCase().includes(c.toLowerCase()) || c.toLowerCase().includes(type.toLowerCase())),
      );
    if (auth) {
      const left = Math.max(0, auth.authorized_hours_total! * 60 - minutesCommitted(db, lead.id, auth));
      if (newMinutes > left) {
        authWarning = `This books ${fmtHours(newMinutes / 60)} but ${lead.child_name} only has ${fmtHours(left / 60)} left on the ${auth.payer_name} authorization — the excess will bill to the family, not insurance.`;
      }
    }
  }

  const stamp = now();
  const attributes = {
    ...patientAttributes(db, patientIds, input.custom_patient, input.activity_label ?? null),
    ...typeAttributes(input.activity_types),
    start_time: `${input.start_time}:00`,
    duration_minutes: input.duration_minutes,
    end_time: addMinutes(`${input.start_time}:00`, input.duration_minutes),
    room: input.room?.trim() || null,
    status: 'scheduled' as const,
    cancel_reason: null,
    cancel_notice_hours: null,
    notes: input.notes?.trim() || null,
  };
  const group = repeats !== 'none' ? `mock-${Date.now().toString(36)}` : null;
  const created: CalendarSessionRow[] = [];
  const skipped: { therapist_id: number; date: string }[] = [];
  for (const therapistId of input.therapist_ids) {
    for (const date of dates) {
      const slot = { therapist_id: therapistId, session_date: date, start_time: input.start_time, duration_minutes: input.duration_minutes };
      if (hasConflict(db, slot)) {
        skipped.push({ therapist_id: therapistId, date });
        continue;
      }
      const row: CalendarSessionRow = {
        id: Math.max(0, ...db.sessions.map((s) => s.id)) + 1,
        therapist_id: therapistId,
        cover_for_user_id: null,
        session_date: date,
        ...attributes,
        cancelled_at: null,
        follow_up_completed_at: null,
        recurrence_group: group,
        supervised_by: null,
        supervised_at: null,
        supervision_notes: null,
        therapist_note: null,
        invoice_id: null,
        created_by: user.id,
        created_at: stamp,
        updated_at: stamp,
      };
      db.sessions.push(row);
      created.push(row);
    }
  }

  // A note typed while booking is logged once on the patient's clinical record.
  if (created.length > 0 && attributes.patient_id && attributes.notes && patient) {
    db.patientNotes.push({
      id: Math.max(0, ...db.patientNotes.map((n) => n.id)) + 1,
      patient_id: patient.id,
      user_id: user.id,
      body: attributes.notes,
      flagged: false,
      flag_reason: null,
      signed_off_at: null,
      signed_off_by: null,
      created_at: stamp,
      updated_at: stamp,
    });
  }

  let message = `${created.length} session${created.length === 1 ? '' : 's'} booked.`;
  const after = created.length > 0 ? budget() : null;
  if (after) message += ` ${fmtHours(after.left)} left on the ${after.label} package.`;
  if (skipped.length > 0) message += ` ${skipped.length} skipped — therapist already booked in that slot.`;
  if (authWarning && created.length > 0) message += ` ${authWarning}`;

  // Laravel answers 422 (with the same body) when every slot was skipped.
  if (created.length === 0) throw new ApiError(422, { message });
  autoCompletePastSessions(db);
  return {
    message,
    created: created.length,
    skipped,
    authorization_warning: authWarning,
    sessions: created.map((s) => sessionPayload(db, s)),
  };
}

/** PUT /calendar/{id} — partial update; anything not sent is left as-is. Managers only. */
export function update(id: number, input: SessionUpdateRequest): SessionUpdateResponse {
  const user = requireUser();
  requireFeature(user, 'calendar', true);
  if (!canManageCalendar(toApiUser(user))) {
    throw new ApiError(403, { message: 'Only the Clinical Supervisor can change the schedule.' });
  }
  const db = getDb();
  const session = db.sessions.find((s) => s.id === id);
  if (!session) throw new ApiError(404, { message: 'Not found.' });
  validateCommon(db, input);
  if (input.activity_types !== undefined && input.activity_types.length === 0) {
    throw invalid('activity_types', 'The activity types field is required.');
  }

  const changes: Partial<CalendarSessionRow> = {};
  if (input.therapist_id !== undefined) changes.therapist_id = input.therapist_id;
  if (input.session_date !== undefined) changes.session_date = input.session_date;
  if (input.start_time !== undefined) changes.start_time = `${input.start_time}:00`;
  if (input.duration_minutes !== undefined) changes.duration_minutes = input.duration_minutes;
  if (input.room !== undefined) changes.room = input.room?.trim() || null;
  if (input.notes !== undefined) changes.notes = input.notes?.trim() || null;
  if (input.cancel_reason !== undefined) changes.cancel_reason = input.cancel_reason;
  if (input.cancel_notice_hours !== undefined) changes.cancel_notice_hours = input.cancel_notice_hours;
  if (input.status != null) {
    changes.status = input.status;
    // Only a family cancellation keeps notice hours.
    if (!(input.status === 'cancelled' && (changes.cancel_reason ?? session.cancel_reason) === 'family')) changes.cancel_notice_hours = null;
  }
  if (input.patient_ids !== undefined || input.custom_patient !== undefined || input.activity_label !== undefined) {
    Object.assign(
      changes,
      patientAttributes(db, input.patient_ids ?? [], input.custom_patient, input.activity_label !== undefined ? input.activity_label : session.activity_label),
    );
  }
  if (input.activity_types !== undefined) Object.assign(changes, typeAttributes(input.activity_types));

  // Reassigning away from someone on leave that day makes it a covered shift; moving it back clears that.
  if (changes.therapist_id !== undefined && changes.therapist_id !== session.therapist_id) {
    const date = changes.session_date ?? session.session_date;
    const onLeave = db.staffLeaves.some((l) => l.user_id === session.therapist_id && l.leave_date === date);
    changes.cover_for_user_id = onLeave ? session.therapist_id : null;
  }

  const merged = { ...session, ...changes };
  if (!isInactive(merged) && hasConflict(db, merged, session.id)) {
    throw invalid('start_time', 'This therapist already has a session that overlaps this time slot.');
  }

  Object.assign(session, changes);
  session.end_time = addMinutes(session.start_time, session.duration_minutes);
  session.updated_at = now();
  // A session put back to "scheduled" whose time has passed counts as attended again.
  autoCompletePastSessions(db);
  return { message: 'Session updated.', session: sessionPayload(db, session) };
}

export function createBookingApi(): Pick<ApiClient['calendar'], 'bookingOptions' | 'store' | 'update' | 'setStatus'> {
  return {
    bookingOptions: () => delay(options),
    store: (input) => delay(() => store(input)),
    update: (id, input) => delay(() => update(id, input)),
    setStatus: (id, status) => delay(() => update(id, { status })),
  };
}
