/**
 * Mock of PatientController (index, show, addNote, updateGoalsForToday) and
 * the Patient / PatientAuthorization / PatientGoal helpers it relies on.
 */

import { nowTime, todayYmd } from '@/utils/dates';

import type { ApiClient } from '../client';
import { ApiError } from '../errors';
import type {
  CareTeamMember,
  PatientAuthorizationSummary,
  PatientDetail,
  PatientListItem,
  PatientNote,
  RecommendedGoal,
  TodayGoalsRequest,
  TodayGoalsResponse,
} from '../types';
import { presentNote } from './notes';
import {
  addDocument,
  deleteDocument,
  documentsFor,
  editOptions,
  paymentsFor,
  profileFor,
  updatePatient,
} from './patientExtras';
import { assertAssignedTherapist, findPatientOr404 } from './patientAccess';
import { authorizationHoursUsed, autoCompletePastSessions, sessionPayload, sessionsForLead } from './presenters';
import type { CalendarSessionRow, MockDb, PatientNoteRow, PatientRow } from './rows';
import { addMinutes, laravelIso } from './seed';
import { delay, getDb, requireFeature, requireUser } from './server';

const NOTE_MAX = 2000;
const GOAL_TITLE_MAX = 255;

// ---------------------------------------------------------------------------
// Model helpers
// ---------------------------------------------------------------------------

function leadFor(db: MockDb, patient: PatientRow) {
  const lead = db.leads.find((l) => l.id === patient.lead_id);
  if (!lead) throw new Error(`Lead ${patient.lead_id} missing for patient ${patient.id}`);
  return lead;
}

/** Patient::authorizations() (ordered by sort_order) with each card's computed values. */
function authorizationsFor(db: MockDb, patient: PatientRow, today: string): PatientAuthorizationSummary[] {
  return db.authorizations
    .filter((a) => a.patient_id === patient.id)
    .sort((a, b) => a.sort_order - b.sort_order)
    .map((a) => ({
      ...a,
      hours_used: authorizationHoursUsed(db, patient.lead_id, a.covers_services, today),
      covers_label: (a.covers_services ?? []).join(' · ') || '—',
    }));
}

/** Patient::attendanceRate(): completed / (completed + no_show + family cancellations). */
function attendanceRate(db: MockDb, patient: PatientRow): number | null {
  const counted = sessionsForLead(db, patient.lead_id).filter(
    (s) =>
      s.status === 'completed' ||
      s.status === 'no_show' ||
      (s.status === 'cancelled' && s.cancel_reason === 'family'),
  );
  if (counted.length === 0) return null;
  return Math.round((counted.filter((s) => s.status === 'completed').length / counted.length) * 100);
}

function hasAuthorization(db: MockDb, patient: PatientRow): boolean {
  return db.authorizations.some((a) => a.patient_id === patient.id);
}

/** Patient::isProfileIncomplete() */
function isProfileIncomplete(db: MockDb, patient: PatientRow): boolean {
  return !patient.diagnosis || !patient.programme || !hasAuthorization(db, patient) || !leadFor(db, patient).phone;
}

/** Patient::missingFieldsLabel() */
function missingFieldsLabel(db: MockDb, patient: PatientRow): string {
  const missing: string[] = [];
  if (!patient.diagnosis) missing.push('diagnosis');
  if (!patient.programme) missing.push('programme');
  if (!leadFor(db, patient).phone) missing.push('contact number');
  if (!hasAuthorization(db, patient)) missing.push('insurance authorization');
  return missing.join(', ');
}

/** Patient::careTeam(): therapists with any session for this child, by first name. */
function careTeam(db: MockDb, patient: PatientRow): CareTeamMember[] {
  const ids = new Set(sessionsForLead(db, patient.lead_id).map((s) => s.therapist_id));
  return db.users
    .filter((u) => ids.has(u.id))
    .sort((a, b) => a.first_name.localeCompare(b.first_name))
    .map((u) => ({ id: u.id, first_name: u.first_name, last_name: u.last_name, job_title: u.job_title }));
}

/** Newest first: session_date desc, then start_time desc. */
function byNewest(a: CalendarSessionRow, b: CalendarSessionRow): number {
  return b.session_date.localeCompare(a.session_date) || b.start_time.localeCompare(a.start_time);
}



/** Laravel's `{success: false, errors}` 422, with the first error as the message. */
function validationFailed(errors: Record<string, string[]>): ApiError {
  const first = Object.values(errors)[0]?.[0] ?? 'The given data was invalid.';
  return new ApiError(422, { message: first, errors });
}

// ---------------------------------------------------------------------------
// Handlers
// ---------------------------------------------------------------------------

function list(search?: string): PatientListItem[] {
  const user = requireUser();
  requireFeature(user, 'patients');
  const db = getDb();
  const today = todayYmd();

  let patients = [...db.patients];

  const q = search?.trim().toLowerCase();
  if (q) {
    patients = patients.filter((p) => {
      const lead = leadFor(db, p);
      return [lead.child_name, lead.parent_guardian_name, lead.phone].some((v) => v?.toLowerCase().includes(q));
    });
  }

  if (user.role === 'THERAPIST') {
    const assignedLeadIds = new Set(db.sessions.filter((s) => s.therapist_id === user.id).map((s) => s.patient_id));
    patients = patients.filter((p) => assignedLeadIds.has(p.lead_id));
  }

  return patients
    .sort((a, b) => b.created_at.localeCompare(a.created_at))
    .map((p) => ({
      ...p,
      lead: leadFor(db, p),
      authorizations: authorizationsFor(db, p, today),
      attendance_rate: attendanceRate(db, p),
      is_profile_incomplete: isProfileIncomplete(db, p),
    }));
}

function show(id: number): PatientDetail {
  const user = requireUser();
  requireFeature(user, 'patients');
  const db = getDb();
  const patient = findPatientOr404(db, id);
  assertAssignedTherapist(db, user, patient);

  const now = new Date();
  const today = todayYmd(now);
  autoCompletePastSessions(db, now);

  const all = sessionsForLead(db, patient.lead_id);
  const sessions = all.filter((s) => s.status !== 'closed').sort(byNewest);

  const upcoming = sessions
    .filter((s) => s.session_date >= today)
    .sort((a, b) => a.session_date.localeCompare(b.session_date) || a.start_time.localeCompare(b.start_time))
    .slice(0, 5);
  const past = sessions.filter((s) => !(s.status === 'scheduled' && s.session_date >= today));
  const todays = sessions.find((s) => s.session_date === today) ?? null;

  // PatientGoal::sessionsInLast(10) / lastUsedAt(): the patient's last 10
  // sessions by date (any status), and the latest session a goal was used in.
  const lastTenIds = new Set([...all].sort(byNewest).slice(0, 10).map((s) => s.id));
  const recommended: RecommendedGoal[] = db.patientGoals
    .filter((g) => g.patient_id === patient.id)
    .map((goal) => {
      const linkedSessionIds = db.sessionGoals.filter((l) => l.patient_goal_id === goal.id).map((l) => l.calendar_session_id);
      const linked = db.sessions.filter((s) => linkedSessionIds.includes(s.id));
      const lastUsed = linked.map((s) => s.session_date).sort().at(-1) ?? null;
      return { goal, used: linkedSessionIds.filter((sid) => lastTenIds.has(sid)).length, last_used_at: lastUsed };
    })
    .sort((a, b) => b.used - a.used);

  const todaysGoalIds = todays
    ? db.sessionGoals.filter((l) => l.calendar_session_id === todays.id).map((l) => l.patient_goal_id)
    : [];

  return {
    patient: { ...patient, lead: leadFor(db, patient) },
    authorizations: authorizationsFor(db, patient, today),
    notes: db.patientNotes
      .filter((n) => n.patient_id === patient.id)
      .sort((a, b) => b.created_at.localeCompare(a.created_at))
      .map((n) => presentNote(db, n)),
    recommended_goals: recommended,
    todays_session: todays ? sessionPayload(db, todays, now) : null,
    todays_goal_ids: todaysGoalIds,
    care_team: careTeam(db, patient),
    upcoming_sessions: upcoming.map((s) => sessionPayload(db, s, now)),
    past_sessions: past.map((s) => sessionPayload(db, s, now)),
    attendance_rate: attendanceRate(db, patient),
    is_profile_incomplete: isProfileIncomplete(db, patient),
    missing_fields_label: missingFieldsLabel(db, patient),
    payments: paymentsFor(db, patient),
    documents: documentsFor(db, user, patient, today),
    profile: profileFor(db, patient),
    edit_options: editOptions(),
  };
}

function addNote(id: number, body: string): { success: true; note: PatientNote } {
  const user = requireUser();
  requireFeature(user, 'patients', true);
  const db = getDb();
  const patient = findPatientOr404(db, id);
  assertAssignedTherapist(db, user, patient);

  if (user.role === 'COORDINATOR') {
    throw new ApiError(403, { message: 'Coordinators cannot add clinical notes.' });
  }

  const text = body.trim();
  if (!text) throw validationFailed({ body: ['The body field is required.'] });
  if (text.length > NOTE_MAX) {
    throw validationFailed({ body: [`The body field must not be greater than ${NOTE_MAX} characters.`] });
  }

  const stamp = laravelIso(new Date().toISOString());
  const row: PatientNoteRow = {
    id: Math.max(0, ...db.patientNotes.map((n) => n.id)) + 1,
    patient_id: patient.id,
    user_id: user.id,
    body: text,
    flagged: false,
    flag_reason: null,
    signed_off_at: null,
    signed_off_by: null,
    created_at: stamp,
    updated_at: stamp,
  };
  db.patientNotes.push(row);
  return { success: true, note: presentNote(db, row) };
}

function saveTodayGoals(id: number, input: TodayGoalsRequest): TodayGoalsResponse {
  const user = requireUser();
  requireFeature(user, 'patients', true);
  const db = getDb();
  const patient = findPatientOr404(db, id);
  assertAssignedTherapist(db, user, patient);

  const errors: Record<string, string[]> = {};
  input.goal_ids.forEach((goalId, i) => {
    if (!db.patientGoals.some((g) => g.id === goalId)) errors[`goal_ids.${i}`] = [`The selected goal_ids.${i} is invalid.`];
  });
  input.new_goal_titles.forEach((title, i) => {
    if (title.length > GOAL_TITLE_MAX) {
      errors[`new_goal_titles.${i}`] = [`The new_goal_titles.${i} field must not be greater than ${GOAL_TITLE_MAX} characters.`];
    }
  });
  if (Object.keys(errors).length > 0) throw validationFailed(errors);

  const today = todayYmd();
  const stamp = laravelIso(new Date().toISOString());
  const forLead = sessionsForLead(db, patient.lead_id);
  let todays = forLead.filter((s) => s.session_date === today).sort((a, b) => a.id - b.id)[0];

  if (!todays) {
    // No calendar entry today: logging goals is evidence a session happened,
    // so record a completed 60-minute session (as Laravel does).
    const last = [...forLead].sort(byNewest)[0];
    const start = nowTime();
    todays = {
      id: Math.max(0, ...db.sessions.map((s) => s.id)) + 1,
      therapist_id: last?.therapist_id ?? user.id,
      cover_for_user_id: null,
      patient_id: patient.lead_id,
      patient_name: leadFor(db, patient).child_name,
      patient_ids: null,
      activity_label: null,
      activity_type: last?.activity_type ?? 'ABA',
      activity_types: null,
      session_date: today,
      start_time: start,
      duration_minutes: 60,
      end_time: addMinutes(start, 60),
      room: null,
      status: 'completed',
      cancel_reason: null,
      cancel_notice_hours: null,
      cancelled_at: null,
      follow_up_completed_at: null,
      notes: null,
      recurrence_group: null,
      supervised_by: null,
      supervised_at: null,
      supervision_notes: null,
      therapist_note: null,
      invoice_id: null,
      created_by: user.id,
      created_at: stamp,
      updated_at: stamp,
    };
    db.sessions.push(todays);
  }

  const goalIds = [...input.goal_ids];
  const created: { id: number; title: string }[] = [];
  for (const raw of input.new_goal_titles) {
    const title = raw.trim();
    if (!title) continue;
    // firstOrCreate: re-submitting the same custom title must not duplicate it.
    let goal = db.patientGoals.find((g) => g.patient_id === patient.id && g.title === title);
    if (!goal) {
      goal = {
        id: Math.max(0, ...db.patientGoals.map((g) => g.id)) + 1,
        patient_id: patient.id,
        title,
        progress_percent: 0,
        created_at: stamp,
        updated_at: stamp,
      };
      db.patientGoals.push(goal);
    }
    goalIds.push(goal.id);
    created.push({ id: goal.id, title: goal.title });
  }

  // $todaysSession->goals()->sync($goalIds)
  const sessionId = todays.id;
  const wanted = new Set(goalIds);
  db.sessionGoals = db.sessionGoals.filter((l) => l.calendar_session_id !== sessionId || wanted.has(l.patient_goal_id));
  for (const goalId of wanted) {
    if (!db.sessionGoals.some((l) => l.calendar_session_id === sessionId && l.patient_goal_id === goalId)) {
      db.sessionGoals.push({
        id: Math.max(0, ...db.sessionGoals.map((l) => l.id)) + 1,
        calendar_session_id: sessionId,
        patient_goal_id: goalId,
      });
    }
  }

  return { success: true, message: 'Session goals saved.', created_goals: created };
}

export function createPatientsApi(): Omit<ApiClient['patients'], 'createOptions' | 'store'> {
  return {
    list: (search) => delay(() => list(search)),
    show: (id) => delay(() => show(id)),
    addNote: (id, body) => delay(() => addNote(id, body)),
    saveTodayGoals: (id, input) => delay(() => saveTodayGoals(id, input)),
    update: (id, input) => delay(() => updatePatient(id, input)),
    addDocument: (id, input) => delay(() => addDocument(id, input)),
    deleteDocument: (id, documentId) => delay(() => deleteDocument(id, documentId)),
  };
}
