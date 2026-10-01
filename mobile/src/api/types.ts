/**
 * TypeScript shapes of the Laravel JSON the app consumes.
 *
 * Rules (see docs/laravel-data-models.md):
 * - Field names are the Laravel column names (snake_case).
 * - `date`/`datetime` casts serialize as ISO strings; uncast `date` columns as "YYYY-MM-DD".
 * - Decimals are strings ("1100.00"). Times are "HH:mm:ss" on models, "HH:mm" in
 *   CalendarController::sessionPayload().
 * - Where a controller builds its own payload, the type mirrors that payload and
 *   says so in its doc comment.
 */

import type { ActionKey, Department, Level, ModuleKey, Role } from '@/auth/roles';

export type IsoDateTime = string;
export type YmdString = string;

// ---------------------------------------------------------------------------
// Users & auth
// ---------------------------------------------------------------------------

/** `role_templates` row (eager-loaded as `role_template` on the auth user). */
export interface RoleTemplate {
  id: number;
  key: string;
  name: string;
  description: string | null;
  base_role: Role;
  modules: ModuleKey[] | null;
  module_levels: Partial<Record<ModuleKey, Level>> | null;
  actions: ActionKey[] | null;
  is_system: boolean;
  sort_order: number;
}

/** `users` table (password/remember_token are hidden in Laravel). */
export interface User {
  id: number;
  public_id: string;
  first_name: string;
  middle_name: string | null;
  last_name: string;
  job_title: string | null;
  email: string;
  phone_number: string | null;
  timezone: string | null;
  message_signature: string | null;
  email_verified_at: IsoDateTime | null;
  department: Department;
  manager_id: number | null;
  role: Role;
  role_template_id: number | null;
  modules: ModuleKey[] | null;
  module_levels: Partial<Record<ModuleKey, Level>> | null;
  actions: ActionKey[] | null;
  start_date: IsoDateTime;
  notes: string | null;
  is_active: boolean;
  invited_at: IsoDateTime | null;
  last_login_at: IsoDateTime | null;
  created_at: IsoDateTime;
  updated_at: IsoDateTime;
  /** Eager-loaded so the client can resolve effective permissions. */
  role_template?: RoleTemplate | null;
}

export interface LoginRequest {
  email: string;
  password: string;
  remember: boolean;
}

/** Proposed token-auth response (Laravel has no API auth yet). */
export interface LoginResponse {
  token: string;
  user: User;
}

/** PUT /profile body (ProfileController::update). Empty strings arrive as null in Laravel. */
export interface ProfileUpdateRequest {
  first_name: string;
  last_name: string;
  job_title: string | null;
  email: string;
  phone_number: string | null;
  timezone: string | null;
  message_signature: string | null;
}

/** PUT /profile JSON response. */
export interface ProfileUpdateResponse {
  success: true;
  user: User;
}

// ---------------------------------------------------------------------------
// Leads & patients
// ---------------------------------------------------------------------------

export type LeadStatus =
  | 'new'
  | 'contacted'
  | 'assessment_booked'
  | 'assessment_done'
  | 'enrolled'
  | 'terminated';

/**
 * `leads` table — core fields. Of the intake-checklist columns (steps 1–7),
 * only those the app shows so far are typed; add the rest when the
 * "Profile & intake" tab is built.
 */
export interface Lead {
  id: number;
  child_name: string | null;
  child_age: string | null;
  parent_guardian_name: string | null;
  phone: string | null;
  email: string | null;
  source: string | null;
  campaign: string | null;
  city: string | null;
  child_age_band: string | null;
  interested_in: string | null;
  insurance: string | null;
  /** varchar with decimal:2 cast → "24000.00" */
  estimated_value: string | null;
  notes: string | null;
  status: LeadStatus;
  assigned_to: number | null;
  follow_up_due_at: IsoDateTime | null;
  /** Intake step 4; shown as the "Converted from lead — …" banner on a patient. */
  assessment_report_summary: string | null;
  created_at: IsoDateTime;
  updated_at: IsoDateTime;
  /** $appends */
  assigned_to_name: string | null;
  intake_steps_complete: number;
}

/** `patients` table. Child name/age/parent/phone live on the Lead. */
export interface Patient {
  id: number;
  lead_id: number;
  diagnosis: string | null;
  programme: string | null;
  treatment_plan_review_due_at: IsoDateTime | null;
  enrolled_at: IsoDateTime;
  created_at: IsoDateTime;
  updated_at: IsoDateTime;
  lead?: Lead;
}

/** `patient_notes` table. */
export interface PatientNote {
  id: number;
  patient_id: number;
  user_id: number | null;
  body: string;
  flagged: boolean;
  flag_reason: string | null;
  signed_off_at: IsoDateTime | null;
  signed_off_by: number | null;
  created_at: IsoDateTime;
  updated_at: IsoDateTime;
  /** $appends: user's first + last name, or "System". */
  author_name: string;
  /**
   * PROPOSED append (mobile sign-off; no Laravel equivalent yet): the
   * signer's first + last name, or null while unsigned.
   */
  signed_off_by_name?: string | null;
  patient?: Patient;
}

/** `patient_goals` table. */
export interface PatientGoal {
  id: number;
  patient_id: number;
  title: string;
  progress_percent: number;
  created_at: IsoDateTime;
  updated_at: IsoDateTime;
}

/** `patient_authorizations` table. `covers_services` has an array cast. */
export interface PatientAuthorization {
  id: number;
  patient_id: number;
  payer_name: string;
  coverage_percent: number;
  covers_services: string[] | null;
  policy_number: string | null;
  approval_reference: string | null;
  authorized_hours_total: number | null;
  renews_at: IsoDateTime | null;
  sort_order: number;
  created_at: IsoDateTime;
  updated_at: IsoDateTime;
}

/**
 * An authorization plus the values the web computes for its card
 * (PatientAuthorization::hoursUsed(), coversLabel()). Hours left on the
 * card is `authorized_hours_total - hours_used` (floored at 0).
 */
export interface PatientAuthorizationSummary extends PatientAuthorization {
  hours_used: number;
  covers_label: string;
}

/**
 * One row of the patients list (patient/index.blade.php). Laravel renders
 * these values server-side; a JSON endpoint must append them.
 */
export interface PatientListItem extends Patient {
  lead: Lead;
  /** Ordered by sort_order — the first is the primary payer. */
  authorizations: PatientAuthorizationSummary[];
  attendance_rate: number | null;
  is_profile_incomplete: boolean;
}

/** Patient::careTeam() row. */
export interface CareTeamMember {
  id: number;
  first_name: string;
  last_name: string;
  job_title: string | null;
}

/** One entry of PatientController::show() `$recommendedGoals`. */
export interface RecommendedGoal {
  goal: PatientGoal;
  /** sessionsInLast(10) */
  used: number;
  /** lastUsedAt(), "YYYY-MM-DD" */
  last_used_at: YmdString | null;
}

/**
 * PatientController::show() view data as JSON (patient/show.blade.php,
 * Overview + Session history tabs). Session lists use sessionPayload().
 */
export interface PatientDetail {
  patient: Patient & { lead: Lead };
  authorizations: PatientAuthorizationSummary[];
  /** Latest first. */
  notes: PatientNote[];
  recommended_goals: RecommendedGoal[];
  todays_session: CalendarSessionPayload | null;
  todays_goal_ids: number[];
  care_team: CareTeamMember[];
  /** Next 5, soonest first. */
  upcoming_sessions: CalendarSessionPayload[];
  /** Sessions with an outcome, newest first. */
  past_sessions: CalendarSessionPayload[];
  attendance_rate: number | null;
  is_profile_incomplete: boolean;
  /** missingFieldsLabel(), e.g. "diagnosis, insurance authorization" */
  missing_fields_label: string;
}

/** POST /patient/{id}/goals/today body. */
export interface TodayGoalsRequest {
  goal_ids: number[];
  new_goal_titles: string[];
}

export interface TodayGoalsResponse {
  success: true;
  message: string;
  created_goals: { id: number; title: string }[];
}

// ---------------------------------------------------------------------------
// Calendar
// ---------------------------------------------------------------------------

export type SessionStatus = 'scheduled' | 'completed' | 'cancelled' | 'no_show' | 'closed';
export type CancelReason = 'family' | 'clinic';
export type SessionCategory = 'therapy' | 'supervision' | 'observation' | 'admin' | 'covered' | 'cancelled';

/**
 * CalendarController::sessionPayload() — the JSON the calendar feed and
 * `GET /calendar/{id}` return. `patient_id` is a **lead id**.
 */
export interface CalendarSessionPayload {
  id: number;
  therapist_id: number;
  therapist_name: string | null;
  cover_for_user_id: number | null;
  cover_for_name: string | null;
  /** FK to leads.id, not patients.id */
  patient_id: number | null;
  patient_ids: number[] | null;
  /** displayName(): patient_name, else activity_label, else "Unassigned" */
  patient_name: string;
  is_custom_patient: boolean;
  activity_label: string | null;
  activity_type: string;
  activity_types: string[];
  session_date: YmdString;
  /** "HH:mm" */
  start_time: string;
  /** "HH:mm" */
  end_time: string;
  duration_minutes: number;
  room: string | null;
  status: SessionStatus;
  cancel_reason: CancelReason | null;
  cancel_notice_hours: number | null;
  status_label: string;
  category: SessionCategory;
  notes: string | null;
  recurrence_group: string | null;
  is_past: boolean;
  can_supervise: boolean;
  supervised: boolean;
  supervised_by_name: string | null;
  /** "Y-m-d H:i" */
  supervised_at: string | null;
  supervision_notes: string | null;
}

/**
 * The therapist's own view of a session. Laravel's sessionPayload() omits
 * `therapist_note` (calendar/my.blade.php reads it server-side), so a mobile
 * endpoint must add it; only the session's own therapist ever receives it.
 */
export interface MySessionPayload extends CalendarSessionPayload {
  therapist_note: string | null;
}

/** `staff_leaves` row as used on the therapist's week view. */
export interface StaffLeave {
  id: number;
  user_id: number;
  leave_date: YmdString;
  leave_type: string;
  reason: string;
}

/** One day of CalendarController::myCalendar() (`$days`). */
export interface MyCalendarDay {
  iso: YmdString;
  is_today: boolean;
  is_weekend: boolean;
  sessions: MySessionPayload[];
  leave: StaffLeave | null;
}

/**
 * CalendarController::myCalendar() view data as JSON (the web renders
 * calendar/my.blade.php with it; there is no JSON endpoint yet).
 */
export interface MyCalendarWeek {
  therapist_name: string;
  designation: string;
  monday: YmdString;
  sunday: YmdString;
  prev_date: YmdString;
  next_date: YmdString;
  therapy_hours: number;
  days: MyCalendarDay[];
}

/** A `leaves` entry of GET /calendar/feed. */
export interface CalendarFeedLeave {
  id: number;
  user_id: number;
  user_name: string | null;
  leave_date: YmdString;
  leave_type: string;
  reason: string;
}

/**
 * GET /calendar/feed?start=&end= (CalendarController::feed). Therapists only
 * ever receive their own sessions and leave. Sessions carry the viewer's own
 * `therapist_note` (see MySessionPayload).
 */
export interface CalendarFeed {
  sessions: MySessionPayload[];
  leaves: CalendarFeedLeave[];
}

/** PATCH /calendar/{id}/therapist-note response. */
export interface TherapistNoteResponse {
  message: string;
  therapist_note: string | null;
}

// ---------------------------------------------------------------------------
// Dashboard
// ---------------------------------------------------------------------------

/**
 * DashboardController::therapistMetrics() view variables as JSON
 * (camelCase PHP names converted to snake_case). No JSON endpoint exists yet.
 */
export interface TherapistDashboard {
  user_full_name: string;
  location_label: string;
  sessions_today_count: number;
  rooms_in_use_count: number;
  attendance_rate: number | null;
  attendance_delta: number | null;
  today_sessions: CalendarSessionPayload[];
  my_active_patients_count: number;
  my_pending_notes_count: number;
  /** Oldest first, first 3. Each note has `patient.lead` loaded. */
  my_notes_awaiting_signoff_list: PatientNote[];
  my_treatment_plans_due_count: number;
  /** Soonest first, first 3. Each patient has `lead` loaded. */
  my_treatment_plans_due_list: Patient[];
}

/** Lead on the waitlist (DashboardController::patientMetrics `$waitlistNextUp`). */
export interface WaitlistEntry extends Lead {
  /** Lead::waitingWeeks() */
  waiting_weeks: number;
}

/**
 * DashboardController::clinicalSupervisorMetrics() + scheduleMetrics() +
 * patientMetrics() view variables as JSON (snake_case). No JSON endpoint yet.
 */
export interface SupervisorDashboard {
  user_full_name: string;
  location_label: string;
  sessions_today_count: number;
  rooms_in_use_count: number;
  active_therapists_count: number;
  attendance_rate: number | null;
  attendance_delta: number | null;
  /** Clinic-wide, not cancelled/closed, by start time. */
  today_sessions: CalendarSessionPayload[];
  pending_notes_count: number;
  overdue_notes_count: number;
  /** Oldest first, first 3, each with `patient.lead`. */
  notes_awaiting_signoff: PatientNote[];
  /** Newest first, first 3, each with `patient.lead`. */
  flagged_notes: PatientNote[];
  active_treatment_plans_count: number;
  plans_due_for_review_count: number;
  treatment_plans_due_list: Patient[];
  avg_caseload_per_therapist: number | null;
  waitlist_count: number;
  avg_wait_weeks: number | null;
  waitlist_next_up: WaitlistEntry[];
}

/** PROPOSED: which notes the review screen lists. */
export type NoteReviewFilter = 'unsigned' | 'flagged';

/** PROPOSED: POST /patient-notes/sign-off response. */
export interface SignOffResponse {
  success: true;
  message: string;
  signed_off_count: number;
}

/** POST /calendar/{id}/supervision response. */
export interface SuperviseResponse {
  message: string;
  session: CalendarSessionPayload;
}

// ---------------------------------------------------------------------------
// Errors
// ---------------------------------------------------------------------------

/** Laravel validation / error body: `{message, errors?}`. */
export interface ApiErrorBody {
  message: string;
  errors?: Record<string, string[]>;
}
