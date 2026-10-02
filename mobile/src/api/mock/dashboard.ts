/**
 * Mock of DashboardController: the shared scheduleMetrics() / patientMetrics()
 * helpers and the per-role views built on them.
 */

import { addDays, addMonths, diffDays, formatMonthLong, isoToYmd, mondayOf, monthEndOf, monthStartOf, todayYmd } from '@/utils/dates';
import { fullName } from '@/utils/format';

import type { ApiClient } from '../client';
import { ApiError } from '../errors';
import type {
  AdminDashboard,
  FinanceDashboard,
  HrDashboard,
  OtherStaffDashboard,
  SalesDashboard,
  CoordinatorDashboard,
  ExpiringAuthorization,
  LeadSourceRow,
  IntakeLead,
  Patient,
  SupervisorDashboard,
  TherapistDashboard,
  WaitlistEntry,
} from '../types';
import { presentNoteWithPatient } from './notes';
import { authorizationHoursUsed, autoCompletePastSessions, sessionPayload } from './presenters';
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

// ---------------------------------------------------------------------------
// COORDINATOR
// ---------------------------------------------------------------------------

/** DashboardController::leadMetrics(): leads in the rolling last 7 days vs the 7 before. */
function newLeads(db: MockDb, now: Date) {
  const weekStart = new Date(now.getTime() - WEEK_MS).toISOString();
  const prevStart = new Date(now.getTime() - 2 * WEEK_MS).toISOString();
  const count = db.leads.filter((l) => l.created_at >= weekStart).length;
  const prev = db.leads.filter((l) => l.created_at >= prevStart && l.created_at <= weekStart).length;
  const delta = prev > 0 ? Math.round(((count - prev) / prev) * 100) : count > 0 ? 100 : 0;
  return { count, delta };
}

function coordinatorDashboard(): CoordinatorDashboard {
  const user = requireUser();
  requireFeature(user, 'dashboard');
  if (user.role !== 'COORDINATOR') {
    throw new ApiError(403, { message: 'This dashboard is for coordinators.' });
  }

  const db = getDb();
  const now = new Date();
  const today = todayYmd(now);
  const schedule = scheduleMetrics(db, now);
  const leads = newLeads(db, now);
  const wl = waitlist(db, now);

  // Intake calls: "new" leads, oldest first. Overdue when the follow-up date
  // has passed, or (with none set) the enquiry is more than 2 days old.
  const twoDaysAgo = new Date(now.getTime() - 2 * 86400_000).toISOString();
  const nowIso = now.toISOString();
  const pending: IntakeLead[] = db.leads
    .filter((l) => l.status === 'new')
    .sort((a, b) => a.created_at.localeCompare(b.created_at))
    .map((l) => ({
      ...l,
      is_overdue: l.follow_up_due_at ? l.follow_up_due_at < nowIso : l.created_at < twoDaysAgo,
    }));

  // This calendar week (Mon–Sun), like now()->startOfWeek()/endOfWeek().
  const weekStart = mondayOf(today);
  const weekEnd = addDays(weekStart, 6);
  const thisWeek = db.sessions.filter((s) => s.session_date >= weekStart && s.session_date <= weekEnd);
  const noShows = thisWeek.filter((s) => s.status === 'no_show');
  const needFollowUp = noShows.filter((s) => s.follow_up_completed_at === null);

  // Openings estimate (documented assumption in Laravel): rooms x 8 slots x 5 days - booked.
  const rooms = Math.max(1, new Set(db.sessions.map((s) => s.room).filter(Boolean)).size);
  const booked = thisWeek.filter((s) => s.status !== 'cancelled' && s.status !== 'closed').length;

  return {
    user_full_name: fullName(user),
    location_label: LOCATION_LABEL,
    new_leads_count: leads.count,
    new_leads_delta: leads.delta,
    pending_intake_calls_count: pending.length,
    overdue_intake_calls_count: pending.filter((l) => l.is_overdue).length,
    intake_pipeline: pending.slice(0, 3),
    no_shows_count: noShows.length,
    no_show_follow_ups_needed: needFollowUp.length,
    no_show_follow_up_list: needFollowUp.slice(0, 3).map((s) => sessionPayload(db, s, now)),
    waitlist_count: wl.count,
    openings_this_week: Math.max(0, rooms * 8 * 5 - booked),
    sessions_today_count: schedule.todayList.length,
    rooms_in_use_count: schedule.roomsInUse,
    active_therapists_count: schedule.activeTherapists,
    attendance_rate: schedule.attendanceRate,
    attendance_delta: schedule.attendanceDelta,
    today_sessions: schedule.todayList.map((s) => sessionPayload(db, s, now)),
    whatsapp_inbox: [...db.whatsappContacts]
      .sort((a, b) => (b.last_message_at ?? '').localeCompare(a.last_message_at ?? ''))
      .slice(0, 3),
  };
}

// ---------------------------------------------------------------------------
// FULL_ADMIN
// ---------------------------------------------------------------------------

const CHART_BUCKETS = ['whatsapp', 'instagram', 'facebook', 'website', 'referral', 'google'];

/** DashboardController::sourceBucket(): a lead's free-text source → chart channel. */
function sourceBucket(source: string | null): string {
  const key = (source ?? '').toLowerCase().replace(/[ \-_]/g, '');
  if (key === '') return 'Other';
  if (key.includes('whatsapp')) return 'whatsapp';
  if (key.includes('instagram')) return 'instagram';
  if (key.includes('facebook') || key.includes('messenger')) return 'facebook';
  if (key.includes('website') || key.includes('contactus') || key.includes('webform')) return 'website';
  if (key.includes('referral')) return 'referral';
  if (key.includes('google')) return 'google';
  return source as string;
}

/**
 * leadMetrics() source chart: every new conversation in the last 7 days by
 * channel, plus new leads not already counted through a conversation.
 * Website volume is every Contact form submission, converted or not.
 */
function leadSources(db: MockDb, now: Date): { rows: LeadSourceRow[]; scale: number } {
  const weekStart = new Date(now.getTime() - WEEK_MS).toISOString();
  const chats = db.whatsappContacts.filter((c) => c.created_at >= weekStart);
  const chatCount = (channel: string) => chats.filter((c) => c.channel === channel).length;
  const submissions = db.contacts.filter((c) => c.created_at >= weekStart);
  const alreadyCounted = new Set(
    [...chats.map((c) => c.lead_id), ...submissions.map((c) => c.converted_lead_id)].filter((id) => id !== null),
  );

  const leadCounts = new Map<string, number>();
  for (const lead of db.leads) {
    if (lead.created_at < weekStart || alreadyCounted.has(lead.id)) continue;
    const bucket = sourceBucket(lead.source);
    leadCounts.set(bucket, (leadCounts.get(bucket) ?? 0) + 1);
  }
  const leadCount = (bucket: string) => leadCounts.get(bucket) ?? 0;

  const rows: LeadSourceRow[] = [
    { source: 'WhatsApp', count: chatCount('whatsapp') + leadCount('whatsapp') },
    { source: 'Instagram', count: chatCount('instagram') + leadCount('instagram') },
    { source: 'Facebook', count: chatCount('facebook') + leadCount('facebook') },
    { source: 'Website', count: submissions.length + leadCount('website') },
    { source: 'Referral', count: leadCount('referral') },
    { source: 'Google', count: leadCount('google') },
    ...[...leadCounts].filter(([bucket]) => !CHART_BUCKETS.includes(bucket)).map(([source, count]) => ({ source, count })),
  ].sort((a, b) => b.count - a.count);

  const max = Math.max(0, ...rows.map((r) => r.count));
  return { rows, scale: Math.max(10, Math.ceil(max / 10) * 10) };
}

/** DashboardController::billingMetrics(). */
function billingMetrics(db: MockDb, now: Date) {
  const today = todayYmd(now);
  const lastMonthStart = addMonths(today, -1);
  const sumBetween = (from: string, to: string) =>
    db.invoices
      .filter((i) => isoToYmd(i.issue_date) >= from && isoToYmd(i.issue_date) <= to)
      .reduce((sum, i) => sum + Number(i.subtotal), 0);
  const mtd = sumBetween(monthStartOf(today), monthEndOf(today));
  const lastMonth = sumBetween(lastMonthStart, monthEndOf(lastMonthStart));

  const openClaims = db.invoices.filter((i) => i.payer !== 'Self-pay' && (i.status === 'submitted' || i.status === 'pending_info'));
  return {
    revenue_mtd: mtd,
    revenue_delta: lastMonth > 0 ? Math.round(((mtd - lastMonth) / lastMonth) * 100) : null,
    last_month_name: formatMonthLong(lastMonthStart).split(' ')[0],
    claims_pending_amount: openClaims.reduce((sum, i) => sum + Number(i.insurance_coverage_amount), 0),
    claims_pending_count: openClaims.length,
    oldest_claim_days:
      openClaims.length > 0 ? Math.max(...openClaims.map((i) => diffDays(isoToYmd(i.issue_date), today))) : null,
  };
}

/** patientMetrics() `$authorizationsExpiring`: renewing within 45 days (or overdue), soonest first, first 4. */
function authorizationsExpiring(db: MockDb, now: Date): ExpiringAuthorization[] {
  const today = todayYmd(now);
  const cutoff = new Date(now.getTime() + 45 * 86400_000).toISOString();
  return db.authorizations
    .filter((a) => a.renews_at !== null && a.renews_at <= cutoff)
    .sort((a, b) => a.renews_at!.localeCompare(b.renews_at!))
    .slice(0, 4)
    .map((a) => {
      const patient = db.patients.find((p) => p.id === a.patient_id);
      const lead = patient ? db.leads.find((l) => l.id === patient.lead_id) : undefined;
      const used = patient ? authorizationHoursUsed(db, patient.lead_id, a.covers_services, today) : 0;
      return {
        id: a.id,
        patient_id: a.patient_id,
        child_name: lead?.child_name ?? 'Unknown',
        payer_name: a.payer_name ?? 'Insurance',
        hours_left: Math.max(0, (a.authorized_hours_total ?? 0) - used),
        renews_at: a.renews_at!,
        days_to_renew: diffDays(today, isoToYmd(a.renews_at!)),
      };
    });
}

function adminDashboard(): AdminDashboard {
  const user = requireUser();
  requireFeature(user, 'dashboard');
  if (user.role !== 'FULL_ADMIN') {
    throw new ApiError(403, { message: 'This dashboard is for administrators.' });
  }

  const db = getDb();
  const now = new Date();
  const schedule = scheduleMetrics(db, now);
  const leads = newLeads(db, now);
  const sources = leadSources(db, now);
  const wl = waitlist(db, now);

  return {
    user_full_name: fullName(user),
    location_label: LOCATION_LABEL,
    new_leads_count: leads.count,
    new_leads_delta: leads.delta,
    lead_sources: sources.rows,
    lead_sources_scale: sources.scale,
    sessions_today_count: schedule.todayList.length,
    rooms_in_use_count: schedule.roomsInUse,
    active_therapists_count: schedule.activeTherapists,
    attendance_rate: schedule.attendanceRate,
    attendance_delta: schedule.attendanceDelta,
    today_sessions: schedule.todayList.map((s) => sessionPayload(db, s, now)),
    ...billingMetrics(db, now),
    waitlist_count: wl.count,
    avg_wait_weeks: wl.avgWaitWeeks,
    waitlist_next_up: wl.nextUp,
    authorizations_expiring: authorizationsExpiring(db, now),
    whatsapp_inbox: [...db.whatsappContacts]
      .sort((a, b) => (b.last_message_at ?? '').localeCompare(a.last_message_at ?? ''))
      .slice(0, 3),
  };
}

// ---------------------------------------------------------------------------
// SALES_STAFF, HR_STAFF, FINANCE_STAFF, OTHER_STAFF
// ---------------------------------------------------------------------------

function requireRole(role: string, who: string) {
  const user = requireUser();
  requireFeature(user, 'dashboard');
  if (user.role !== role) throw new ApiError(403, { message: `This dashboard is for ${who}.` });
  return user;
}

const latestInbox = (db: MockDb) =>
  [...db.whatsappContacts].sort((a, b) => (b.last_message_at ?? '').localeCompare(a.last_message_at ?? '')).slice(0, 3);

function salesDashboard(): SalesDashboard {
  const user = requireRole('SALES_STAFF', 'sales staff');
  const db = getDb();
  const now = new Date();
  const leads = newLeads(db, now);
  const sources = leadSources(db, now);
  // Lead::scopeActive(): not enrolled, not terminated.
  const mine = db.leads.filter((l) => l.assigned_to === user.id && l.status !== 'enrolled' && l.status !== 'terminated');
  return {
    user_full_name: fullName(user),
    location_label: LOCATION_LABEL,
    new_leads_count: leads.count,
    new_leads_delta: leads.delta,
    my_active_leads_count: mine.length,
    awaiting_contact_count: db.leads.filter((l) => l.status === 'new').length,
    my_leads_pipeline: [...mine].sort((a, b) => b.created_at.localeCompare(a.created_at)).slice(0, 4),
    lead_sources: sources.rows,
    lead_sources_scale: sources.scale,
    whatsapp_inbox: latestInbox(db),
  };
}

function hrDashboard(): HrDashboard {
  const user = requireRole('HR_STAFF', 'HR staff');
  const db = getDb();
  const now = new Date();
  const today = todayYmd(now);
  const schedule = scheduleMetrics(db, now);
  const startDay = (u: { start_date: string | null }) => (u.start_date ? isoToYmd(u.start_date) : '');

  const byRole = new Map<string, number>();
  for (const u of db.users) byRole.set(u.role, (byRole.get(u.role) ?? 0) + 1);

  return {
    user_full_name: fullName(user),
    location_label: LOCATION_LABEL,
    active_staff_count: db.users.filter((u) => u.is_active).length,
    new_hires_this_month: db.users.filter((u) => startDay(u) >= monthStartOf(today) && startDay(u) <= monthEndOf(today)).length,
    sessions_today_count: schedule.todayList.length,
    rooms_in_use_count: schedule.roomsInUse,
    active_therapists_count: schedule.activeTherapists,
    attendance_rate: schedule.attendanceRate,
    attendance_delta: schedule.attendanceDelta,
    today_sessions: schedule.todayList.map((s) => sessionPayload(db, s, now)),
    staff_by_role: [...byRole].map(([role, count]) => ({ role, count })).sort((a, b) => b.count - a.count),
    recent_staff: [...db.users]
      .sort((a, b) => startDay(b).localeCompare(startDay(a)))
      .slice(0, 4)
      .map((u) => ({ id: u.id, full_name: fullName(u), job_title: u.job_title, role: u.role, start_date: u.start_date })),
  };
}

const PAYER_COLORS = ['#C8355F', '#16436E', '#B97F24', '#6E4FA8', '#2E7D5B', '#24619C'];
const AGING: [string, number, number | null][] = [
  ['0-14 days', 0, 14],
  ['15-30 days', 15, 30],
  ['31-60 days', 31, 60],
  ['60+ days', 61, null],
];

function financeDashboard(): FinanceDashboard {
  const user = requireRole('FINANCE_STAFF', 'finance staff');
  const db = getDb();
  const now = new Date();
  const today = todayYmd(now);
  const thisMonth = db.invoices.filter((i) => isoToYmd(i.issue_date) >= monthStartOf(today) && isoToYmd(i.issue_date) <= monthEndOf(today));
  const openClaims = db.invoices.filter((i) => i.payer !== 'Self-pay' && (i.status === 'submitted' || i.status === 'pending_info'));

  const byPayer = new Map<string, number>();
  for (const i of thisMonth) byPayer.set(i.payer, (byPayer.get(i.payer) ?? 0) + Number(i.subtotal));
  const total = Math.max(0.01, [...byPayer.values()].reduce((a, b) => a + b, 0));

  return {
    user_full_name: fullName(user),
    location_label: LOCATION_LABEL,
    ...billingMetrics(db, now),
    collected_mtd: thisMonth.filter((i) => i.status === 'paid').reduce((sum, i) => sum + Number(i.subtotal), 0),
    aging_buckets: AGING.map(([label, min, max]) => {
      const claims = openClaims.filter((c) => {
        const age = diffDays(isoToYmd(c.issue_date), today);
        return age >= min && (max === null || age <= max);
      });
      return { label, amount: claims.reduce((sum, c) => sum + Number(c.insurance_coverage_amount), 0), count: claims.length };
    }),
    revenue_by_payer: [...byPayer]
      .sort((a, b) => b[1] - a[1])
      .map(([payer, amount], i) => ({
        payer,
        total: amount,
        percent: Math.round((amount / total) * 100),
        color: PAYER_COLORS[i % PAYER_COLORS.length],
      })),
  };
}

function otherStaffDashboard(): OtherStaffDashboard {
  const user = requireRole('OTHER_STAFF', 'invoice and quotation staff');
  const db = getDb();
  const today = todayYmd();
  const inMonth = (iso: string) => isoToYmd(iso) >= monthStartOf(today) && isoToYmd(iso) <= monthEndOf(today);
  return {
    user_full_name: fullName(user),
    location_label: LOCATION_LABEL,
    draft_quotations_count: db.invoices.filter((i) => i.status === 'draft').length,
    awaiting_payment_count: db.invoices.filter((i) => i.status === 'submitted' || i.status === 'pending_info').length,
    paid_this_month_count: db.invoices.filter((i) => inMonth(i.issue_date) && i.status === 'paid').length,
    recent_invoices: [...db.invoices]
      .sort((a, b) => b.issue_date.localeCompare(a.issue_date) || b.id - a.id)
      .slice(0, 6)
      .map((i) => {
        const patient = db.patients.find((p) => p.id === i.patient_id);
        return {
          id: i.id,
          invoice_number: i.invoice_number,
          name: db.leads.find((l) => l.id === patient?.lead_id)?.child_name ?? 'Unknown',
          payer: i.payer,
          issue_date: i.issue_date,
          subtotal: i.subtotal,
          status: i.status,
        };
      }),
  };
}

export function createDashboardApi(): ApiClient['dashboard'] {
  return {
    therapist: () => delay(therapistDashboard),
    supervisor: () => delay(supervisorDashboard),
    coordinator: () => delay(coordinatorDashboard),
    admin: () => delay(adminDashboard),
    sales: () => delay(salesDashboard),
    hr: () => delay(hrDashboard),
    finance: () => delay(financeDashboard),
    otherStaff: () => delay(otherStaffDashboard),
  };
}
