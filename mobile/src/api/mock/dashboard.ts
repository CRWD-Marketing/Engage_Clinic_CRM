/**
 * Mock of DashboardController: the shared scheduleMetrics() / patientMetrics()
 * helpers and the per-role views built on them.
 */

import { addDays, todayYmd } from '@/utils/dates';
import { fullName } from '@/utils/format';

import type { ApiClient } from '../client';
import { ApiError } from '../errors';
import type { Patient, SupervisorDashboard, TherapistDashboard, WaitlistEntry } from '../types';
import { presentNoteWithPatient } from './notes';
import { autoCompletePastSessions, sessionPayload } from './presenters';
import type { CalendarSessionRow, MockDb, PatientRow } from './rows';
import { delay, getDb, requireFeature, requireUser } from './server';

/** DashboardController header: clinic location line. */
const LOCATION_LABEL = 'Khalifa City, Abu Dhabi';
const WEEK_MS = 7 * 86400_000;

function attendance(rows: { status: string }[]): number | null {
  const counted = rows.filter((s) => s.status === 'completed' || s.status === 'no_show');
  if (counted.length === 0) return null;
  return Math.round((counted.filter((s) => s.status === 'completed').length / counted.length) * 100);
}

/**
 * DashboardController::scheduleMetrics(?therapistId): today's (non-cancelled,
 * non-closed) sessions, rooms/therapists in use, and 30-day attendance vs
 * the 30 days before. Runs the past-due catch-up first, like Laravel.
 */
function scheduleMetrics(db: MockDb, now: Date, therapistId?: number) {
  const today = todayYmd(now);
  autoCompletePastSessions(db, now);

  const scoped = therapistId === undefined ? db.sessions : db.sessions.filter((s) => s.therapist_id === therapistId);
  const todayList = scoped
    .filter((s) => s.session_date === today && s.status !== 'cancelled' && s.status !== 'closed')
    .sort((a, b) => a.start_time.localeCompare(b.start_time));

  const inRange = (from: string, to: string) => scoped.filter((s) => s.session_date >= from && s.session_date <= to);
  const rate = attendance(inRange(addDays(today, -30), today));
  const prevRate = attendance(inRange(addDays(today, -60), addDays(today, -31)));

  return {
    todayList,
    roomsInUse: new Set(todayList.map((s) => s.room).filter(Boolean)).size,
    activeTherapists: new Set(todayList.map((s) => s.therapist_id)).size,
    attendanceRate: rate,
    attendanceDelta: rate !== null && prevRate !== null ? rate - prevRate : null,
  };
}

function withLead(db: MockDb, p: PatientRow): Patient {
  return { ...p, lead: db.leads.find((l) => l.id === p.lead_id) };
}

/** Patients whose treatment-plan review is due within 7 days (or overdue), soonest first. */
function plansDue(db: MockDb, now: Date, patients: PatientRow[]): Patient[] {
  const cutoff = new Date(now.getTime() + WEEK_MS).toISOString();
  return patients
    .filter((p) => p.treatment_plan_review_due_at !== null && p.treatment_plan_review_due_at <= cutoff)
    .sort((a, b) => a.treatment_plan_review_due_at!.localeCompare(b.treatment_plan_review_due_at!))
    .map((p) => withLead(db, p));
}

/** Carbon 3 diffInWeeks(): fractional weeks between two instants. */
function weeksSince(iso: string, now: Date): number {
  return (now.getTime() - new Date(iso).getTime()) / WEEK_MS;
}

/** DashboardController::patientMetrics() waitlist: leads with status "new", oldest first. */
function waitlist(db: MockDb, now: Date) {
  const waiting = db.leads
    .filter((l) => l.status === 'new')
    .sort((a, b) => a.created_at.localeCompare(b.created_at));
  const avg =
    waiting.length > 0
      ? Math.round((waiting.reduce((sum, l) => sum + weeksSince(l.created_at, now), 0) / waiting.length) * 10) / 10
      : null;
  const nextUp: WaitlistEntry[] = waiting
    .slice(0, 3)
    .map((l) => ({ ...l, waiting_weeks: Math.round(weeksSince(l.created_at, now)) }));
  return { count: waiting.length, avgWaitWeeks: avg, nextUp };
}

// ---------------------------------------------------------------------------
// THERAPIST
// ---------------------------------------------------------------------------

function therapistDashboard(): TherapistDashboard {
  const user = requireUser();
  requireFeature(user, 'dashboard');
  if (user.role !== 'THERAPIST') {
    throw new ApiError(403, { message: 'This dashboard is for therapists.' });
  }

  const db = getDb();
  const now = new Date();
  const schedule = scheduleMetrics(db, now, user.id);

  // NOTE: Laravel plucks distinct patient_id including NULL for non-patient
  // blocks; nulls are excluded here (see CLAUDE.md known issues).
  const myLeadIds = new Set(
    db.sessions
      .filter((s) => s.therapist_id === user.id)
      .map((s) => s.patient_id)
      .filter((id): id is number => id !== null),
  );

  const pendingNotes = db.patientNotes
    .filter((n) => n.user_id === user.id && n.signed_off_at === null)
    .sort((a, b) => a.created_at.localeCompare(b.created_at))
    .map((n) => presentNoteWithPatient(db, n));

  const due = plansDue(db, now, db.patients.filter((p) => myLeadIds.has(p.lead_id)));

  return {
    user_full_name: fullName(user),
    location_label: LOCATION_LABEL,
    sessions_today_count: schedule.todayList.length,
    rooms_in_use_count: schedule.roomsInUse,
    attendance_rate: schedule.attendanceRate,
    attendance_delta: schedule.attendanceDelta,
    today_sessions: schedule.todayList.map((s) => sessionPayload(db, s, now)),
    my_active_patients_count: myLeadIds.size,
    my_pending_notes_count: pendingNotes.length,
    my_notes_awaiting_signoff_list: pendingNotes.slice(0, 3),
    my_treatment_plans_due_count: due.length,
    my_treatment_plans_due_list: due.slice(0, 3),
  };
}

// ---------------------------------------------------------------------------
// CLINICAL_SUPERVISOR
// ---------------------------------------------------------------------------

/** COUNT(DISTINCT patient_id) per therapist with any patients, averaged (1 dp). */
function avgCaseload(db: MockDb): number | null {
  const counts = db.users
    .filter((u) => u.role === 'THERAPIST')
    .map(
      (u) =>
        new Set(
          db.sessions
            .filter((s: CalendarSessionRow) => s.therapist_id === u.id && s.patient_id !== null)
            .map((s) => s.patient_id),
        ).size,
    )
    .filter((c) => c > 0);
  if (counts.length === 0) return null;
  return Math.round((counts.reduce((a, b) => a + b, 0) / counts.length) * 10) / 10;
}

function supervisorDashboard(): SupervisorDashboard {
  const user = requireUser();
  requireFeature(user, 'dashboard');
  if (user.role !== 'CLINICAL_SUPERVISOR') {
    throw new ApiError(403, { message: 'This dashboard is for the clinical supervisor.' });
  }

  const db = getDb();
  const now = new Date();
  const schedule = scheduleMetrics(db, now);

  const unsigned = db.patientNotes
    .filter((n) => n.signed_off_at === null)
    .sort((a, b) => a.created_at.localeCompare(b.created_at));
  const overdueCutoff = new Date(now.getTime() - 48 * 3600_000).toISOString();
  const flagged = db.patientNotes
    .filter((n) => n.flagged)
    .sort((a, b) => b.created_at.localeCompare(a.created_at));
  const due = plansDue(db, now, db.patients);
  const wl = waitlist(db, now);

  return {
    user_full_name: fullName(user),
    location_label: LOCATION_LABEL,
    sessions_today_count: schedule.todayList.length,
    rooms_in_use_count: schedule.roomsInUse,
    active_therapists_count: schedule.activeTherapists,
    attendance_rate: schedule.attendanceRate,
    attendance_delta: schedule.attendanceDelta,
    today_sessions: schedule.todayList.map((s) => sessionPayload(db, s, now)),
    pending_notes_count: unsigned.length,
    overdue_notes_count: unsigned.filter((n) => n.created_at <= overdueCutoff).length,
    notes_awaiting_signoff: unsigned.slice(0, 3).map((n) => presentNoteWithPatient(db, n)),
    flagged_notes: flagged.slice(0, 3).map((n) => presentNoteWithPatient(db, n)),
    active_treatment_plans_count: db.patients.filter((p) => p.programme !== null).length,
    plans_due_for_review_count: due.length,
    treatment_plans_due_list: due.slice(0, 3),
    avg_caseload_per_therapist: avgCaseload(db),
    waitlist_count: wl.count,
    avg_wait_weeks: wl.avgWaitWeeks,
    waitlist_next_up: wl.nextUp,
  };
}

export function createDashboardApi(): ApiClient['dashboard'] {
  return {
    therapist: () => delay(therapistDashboard),
    supervisor: () => delay(supervisorDashboard),
  };
}
