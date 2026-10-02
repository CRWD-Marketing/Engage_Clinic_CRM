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

/** Lead::INTAKE_STEPS keys, in checklist order. */
export type IntakeStepKey =
  | 'parent_contact'
  | 'child_details'
  | 'intake_form'
  | 'assessment'
  | 'funding'
  | 'package'
  | 'consent';

/** One row of `funding_services_needed` (the Funding step's "who pays for each service"). */
export interface FundingServiceRow {
  service: string;
  payer: 'Insurance' | 'Self pay';
  hours_per_week: number | null;
  /** Insurance rows only. */
  approved_hours: number | null;
  approval_reference: string | null;
}

/** `packages` row with Package::summaryLabel() appended for pickers. */
export interface PackageOption {
  id: number;
  name: string;
  location_id: number | null;
  /** "ABA therapy session · Home base · 30 h/wk · AED 337/hr" */
  summary: string;
}

/** Option lists the intake forms use (embedded in the web page by LeadController::index). */
export interface IntakeOptions {
  /** Active THERAPIST users. */
  clinicians: { id: number; name: string }[];
  /** Active insurances. */
  insurers: string[];
  /** Service names for the funding rows. */
  services: string[];
  locations: { id: number; name: string }[];
  packages: PackageOption[];
}

/** `leads` table, including the seven intake-checklist steps. */
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
  ad_name: string | null;
  lead_form_name: string | null;
  /** Intake step 2 fields shown on the lead panel / used at conversion. */
  main_concern: string | null;
  diagnosis_suspected: string | null;
  termination_reason: string | null;
  termination_note: string | null;
  terminated_at: IsoDateTime | null;
  status_before_termination: LeadStatus | null;
  // --- Intake step 1: parent contact ---
  parent_relationship: string | null;
  parent_alternate_phone: string | null;
  preferred_language: string | null;
  // --- Step 2: child details (plus child_name, diagnosis_suspected, main_concern above) ---
  child_date_of_birth: IsoDateTime | null;
  child_gender: string | null;
  child_emirates_id: string | null;
  child_emirates_id_expiry: IsoDateTime | null;
  nursery_school: string | null;
  // --- Step 3: intake form ---
  intake_form_received_on: IsoDateTime | null;
  intake_form_received_via: string | null;
  allergies: string | null;
  medical_history: string | null;
  // --- Step 4: assessment (plus assessment_report_summary above) ---
  assessment_date: IsoDateTime | null;
  assessment_clinician_id: number | null;
  assessment_tool: string | null;
  assessment_report_reference: string | null;
  // --- Step 5: funding ---
  funding_type: string | null;
  funding_insurer: string | null;
  funding_policy_number: string | null;
  funding_approval_valid_until: IsoDateTime | null;
  funding_services_needed: FundingServiceRow[] | null;
  funding_notes: string | null;
  // --- Step 6: package ---
  package_location_id: number | null;
  package_ids: number[] | null;
  package_start_date: IsoDateTime | null;
  package_sessions_per_week: number | null;
  package_agreed_by: string | null;
  package_scheduling_notes: string | null;
  // --- Step 7: consent ---
  consent_signed_date: IsoDateTime | null;
  consent_signed_by: string | null;
  consent_data_photo: string | null;
  consent_signature_method: string | null;
  consent_notes: string | null;
  /** Intake checklist: one completion stamp per step (Lead::INTAKE_STEPS). */
  parent_contact_completed_at: IsoDateTime | null;
  child_details_completed_at: IsoDateTime | null;
  intake_form_completed_at: IsoDateTime | null;
  assessment_completed_at: IsoDateTime | null;
  funding_completed_at: IsoDateTime | null;
  package_completed_at: IsoDateTime | null;
  consent_completed_at: IsoDateTime | null;
  created_at: IsoDateTime;
  updated_at: IsoDateTime;
  /** $appends */
  assigned_to_name: string | null;
  intake_steps_complete: number;
}

/** `lead_activities` table (notes + assignment log). */
export interface LeadActivity {
  id: number;
  lead_id: number;
  user_id: number | null;
  type: 'note' | 'assignment';
  body: string | null;
  created_at: IsoDateTime;
  updated_at: IsoDateTime;
  /** $appends */
  author_name: string;
}

/** A lead on the board, plus whether it already became a patient (those are hidden from the board). */
export interface BoardLead extends Lead {
  has_patient: boolean;
}

/**
 * GET /admin/leads (LeadController::index + lead/index.blade.php) as JSON:
 * every lead newest first, and the option lists the page embeds.
 */
export interface LeadsBoard {
  leads: BoardLead[];
  /** Active SALES_STAFF / FULL_ADMIN users (plus anyone already an owner). */
  assignable_users: { id: number; name: string }[];
  /** "Not sure yet", active insurances, "Self-pay". */
  insurance_options: string[];
  intake_options: IntakeOptions;
}

/** GET /admin/leads/{id} (JSON). */
export interface LeadDetail {
  success: true;
  lead: BoardLead;
  notes_log: LeadActivity[];
  assignment_log: LeadActivity[];
  /** Lead::packages() — the packages picked in the Package step. */
  agreed_packages: PackageOption[];
  /** `assessmentClinician` relation as a display name. */
  assessment_clinician_name: string | null;
}

/** PUT /admin/leads/{id} — every field optional; "" clears a value. */
export interface LeadUpdateRequest {
  child_name?: string | null;
  child_age?: string | null;
  parent_guardian_name?: string | null;
  phone?: string | null;
  email?: string | null;
  source?: string | null;
  interested_in?: string | null;
  insurance?: string | null;
  estimated_value?: string | null;
  notes?: string | null;
  status?: LeadStatus;
  assigned_to?: number | null;
  /** "YYYY-MM-DD", or null/"" to clear. */
  follow_up_due_at?: string | null;
  termination_reason?: string | null;
  termination_note?: string | null;
  /**
   * Saving an intake step: stamps that step's `*_completed_at` (the Package
   * step only if packages are picked, Funding only if a funding type is set).
   * Dates are "YYYY-MM-DD".
   */
  intake_step?: IntakeStepKey;
  parent_relationship?: string | null;
  parent_alternate_phone?: string | null;
  preferred_language?: string | null;
  child_date_of_birth?: string | null;
  child_gender?: string | null;
  child_emirates_id?: string | null;
  child_emirates_id_expiry?: string | null;
  diagnosis_suspected?: string | null;
  nursery_school?: string | null;
  main_concern?: string | null;
  intake_form_received_on?: string | null;
  intake_form_received_via?: string | null;
  allergies?: string | null;
  medical_history?: string | null;
  assessment_date?: string | null;
  assessment_clinician_id?: number | null;
  assessment_tool?: string | null;
  assessment_report_reference?: string | null;
  assessment_report_summary?: string | null;
  funding_type?: string | null;
  funding_insurer?: string | null;
  funding_policy_number?: string | null;
  funding_approval_valid_until?: string | null;
  funding_services_needed?: FundingServiceRow[] | null;
  funding_notes?: string | null;
  package_location_id?: number | null;
  package_ids?: number[] | null;
  package_start_date?: string | null;
  package_sessions_per_week?: number | null;
  package_agreed_by?: string | null;
  package_scheduling_notes?: string | null;
  consent_signed_date?: string | null;
  consent_signed_by?: string | null;
  consent_data_photo?: string | null;
  consent_signature_method?: string | null;
  consent_notes?: string | null;
}

/** POST /admin/leads (New Lead modal). */
export type LeadStoreRequest = Pick<
  LeadUpdateRequest,
  | 'child_name'
  | 'child_age'
  | 'parent_guardian_name'
  | 'phone'
  | 'source'
  | 'interested_in'
  | 'insurance'
  | 'estimated_value'
  | 'assigned_to'
  | 'follow_up_due_at'
  | 'notes'
>;

export interface LeadMutationResponse {
  success: true;
  message: string;
  lead: BoardLead;
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
  /** Payments tab. */
  payments: PatientPayments;
  /** Documents tab, newest first. */
  documents: PatientDocumentItem[];
  /** Values the Profile & intake tab computes from the lead's packages and funding rows. */
  profile: PatientProfile;
  /** Option lists for Edit details and Add document. */
  edit_options: PatientEditOptions;
}

/** One row of the Payments tab's "Payment history". */
export interface PatientInvoiceRow {
  id: number;
  invoice_number: string;
  issue_date: IsoDateTime;
  /** `date` cast: first day of the billed month. */
  period: IsoDateTime;
  subtotal: string;
  amount_paid: string;
  payment_method: string | null;
  /** Invoice::paymentStatusLabel(): Paid / Partly paid / Unpaid / Voided. */
  payment_status_label: string;
}

export interface PatientPayments {
  /** Sum of invoice subtotals (VAT excl.). */
  billed_total: number;
  collected_total: number;
  outstanding_total: number;
  invoices: PatientInvoiceRow[];
}

/** A document record on the patient (name, type, expiry) — not a file upload. */
export interface PatientDocumentItem {
  id: number;
  name: string;
  type: string | null;
  /** "You", the uploader's name, or "System". */
  uploader_label: string;
  created_at: IsoDateTime;
  expires_at: IsoDateTime | null;
  /** PatientDocument::expiryStatus() */
  expiry_label: string;
  expiry_variant: 'neutral' | 'warn' | 'ok';
  /** Legacy rows only: a file that can be downloaded on the web. */
  has_file: boolean;
}

export interface PatientProfile {
  package_names: string[];
  package_hours_per_week: number;
  package_rate_per_hour: number | 'Mixed' | null;
  package_value_excl_vat: number;
  package_location: string | null;
  package_setting: string | null;
  /** "Insurance" / "Self pay" / "Mixed — insurance + self pay" */
  funding_summary: string | null;
  funding_hours_needed: number | null;
  assessment_clinician_name: string | null;
  lead_owner_name: string | null;
}

export interface PatientEditOptions {
  /** Active packages for the Programme picker (value is the package name). */
  packages: { name: string; label: string }[];
  /** Active insurers. "Self-pay" is offered separately. */
  insurances: string[];
  document_types: string[];
}

/** PUT /patient/{id} — coordinators' clinical fields (diagnosis, programme, start, note) are ignored. */
export interface PatientUpdateRequest {
  child_name?: string | null;
  child_age?: number | null;
  diagnosis?: string | null;
  programme?: string | null;
  parent_guardian_name?: string | null;
  phone?: string | null;
  /** Updates (or creates) the primary authorization together with the next two. */
  insurance?: string | null;
  authorized_hours_total?: number | null;
  /** "YYYY-MM-DD" */
  renews_at?: string | null;
  /** "YYYY-MM-DD" */
  enrolled_at?: string | null;
  /** Adds a session note. */
  clinical_note?: string | null;
}

/** POST /patient/{id}/documents */
export interface PatientDocumentRequest {
  name: string;
  type?: string | null;
  /** "YYYY-MM-DD" */
  expires_at?: string | null;
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
// WhatsApp / social inbox
// ---------------------------------------------------------------------------

export type InboxChannel = 'whatsapp' | 'instagram' | 'facebook' | 'voice';
export type AiState = 'ai_active' | 'human_assigned' | 'human_takeover' | 'closed';

/** `whatsapp_contacts` table (one conversation per contact, any channel). */
export interface WhatsappContact {
  id: number;
  /** Phone number for WhatsApp, a platform id for Instagram/Facebook. */
  wa_id: string;
  channel: InboxChannel;
  name: string | null;
  avatar_url: string | null;
  child_name: string | null;
  interested_in: string | null;
  insurance: string | null;
  lead_id: number | null;
  last_message_preview: string | null;
  last_message_at: IsoDateTime | null;
  unread_count: number;
  ai_state: AiState;
  assigned_user_id: number | null;
  needs_human_attention: boolean;
  needs_human_reason: string | null;
  ai_state_changed_by: number | null;
  ai_state_changed_at: IsoDateTime | null;
  created_at: IsoDateTime;
  updated_at: IsoDateTime;
}

export type MessageDirection = 'inbound' | 'outbound';
export type MessageStatus = 'pending' | 'sent' | 'delivered' | 'read' | 'failed' | 'received';

/** `whatsapp_messages` table. */
export interface WhatsappMessage {
  id: number;
  whatsapp_contact_id: number;
  wa_message_id: string | null;
  direction: MessageDirection;
  /** text, image, video, audio, document, sticker, like, location, story_reply, … */
  type: string;
  sticker_id: string | null;
  media_url: string | null;
  body: string | null;
  status: MessageStatus | null;
  send_error: string | null;
  is_ai_generated: boolean;
  sent_by_user_id: number | null;
  ai_processing_status: 'pending' | 'processing' | 'completed' | 'failed' | 'skipped' | null;
  triggered_by_message_id: number | null;
  ai_error: string | null;
  voice_call_session_id: number | null;
  sent_at: IsoDateTime;
  created_at: IsoDateTime;
  updated_at: IsoDateTime;
}

/**
 * A conversation in the inbox list. Laravel renders the list as HTML
 * (whatsapp/partials/contact_row.blade.php); this is the same data as JSON.
 */
export interface InboxContact extends WhatsappContact {
  /** WhatsappMessage::responderLabel() of the latest message: "AI", a staff name, or null. */
  last_responder: string | null;
}

/** A message with the values the Blade partial derives (partials/message.blade.php). */
export interface InboxMessage extends WhatsappMessage {
  /** body, or WhatsappMessage::fallbackLabel(type) when there is no body. */
  display_body: string;
  /** Outbound only: "AI", the sender's name, or null. */
  responder_label: string | null;
  /** WhatsappMessage::statusLabel(): Sending / Sent / Delivered / Read / Not sent / "". */
  status_label: string;
}

/** The read-only "Family details" panel (whatsapp/index.blade.php). */
export interface FamilyDetails {
  child_name: string | null;
  child_age: string | null;
  interested_in: string | null;
  source: string | null;
  first_contact_at: IsoDateTime | null;
  insurance: string | null;
  /** True when the contact is linked to a lead ("In leads pipeline ✓"). */
  in_pipeline: boolean;
}

/** One open conversation (index with ?contact=). Opening it resets unread_count. */
export interface InboxThread {
  contact: InboxContact;
  /** Oldest first. */
  messages: InboxMessage[];
  family: FamilyDetails;
}

/**
 * GET /whatsapp/poll?contact=&after= — same semantics as the web's 4-second
 * poll, as JSON instead of rendered HTML.
 */
export interface InboxPoll {
  contacts: InboxContact[];
  /** Messages for `contact` with id > after. */
  messages: InboxMessage[];
  latest_message_id: number;
  /** Last 30 outbound messages of `contact`, for status ticks. */
  statuses: { id: number; status: MessageStatus | null; label: string; error: string | null }[];
}

/** Intake pipeline row: a "new" lead plus the overdue rule the Blade view applies. */
export interface IntakeLead extends Lead {
  /** follow_up_due_at in the past, or (with none set) enquired more than 2 days ago. */
  is_overdue: boolean;
}

/**
 * DashboardController coordinator view (leadMetrics + scheduleMetrics +
 * whatsappMetrics + patientMetrics + coordinatorMetrics) as JSON.
 */
export interface CoordinatorDashboard {
  user_full_name: string;
  location_label: string;
  /** Leads created in the rolling last 7 days, and % change vs the 7 before. */
  new_leads_count: number;
  new_leads_delta: number;
  pending_intake_calls_count: number;
  overdue_intake_calls_count: number;
  /** Oldest first, first 3. */
  intake_pipeline: IntakeLead[];
  no_shows_count: number;
  no_show_follow_ups_needed: number;
  /** This week's no-shows without follow_up_completed_at, first 3. */
  no_show_follow_up_list: CalendarSessionPayload[];
  waitlist_count: number;
  /** Estimate: distinct rooms x 8 slots x 5 days - sessions booked this week. */
  openings_this_week: number;
  sessions_today_count: number;
  rooms_in_use_count: number;
  active_therapists_count: number;
  attendance_rate: number | null;
  attendance_delta: number | null;
  today_sessions: CalendarSessionPayload[];
  /** Latest 3 conversations by last_message_at. */
  whatsapp_inbox: WhatsappContact[];
}

/** One bar on the admin dashboard's "Lead sources · last 7 days" chart. */
export interface LeadSourceRow {
  source: string;
  count: number;
}

/** An authorization renewing within 45 days (or overdue), with the values the admin card prints. */
export interface ExpiringAuthorization {
  id: number;
  patient_id: number;
  child_name: string;
  payer_name: string;
  /** max(0, authorized_hours_total - hoursUsed()) */
  hours_left: number;
  renews_at: IsoDateTime;
  /** Whole days from today to renews_at (negative when overdue). */
  days_to_renew: number;
}

/**
 * DashboardController admin view (leadMetrics + scheduleMetrics +
 * billingMetrics + patientMetrics + whatsappMetrics) as JSON.
 */
export interface AdminDashboard {
  user_full_name: string;
  location_label: string;
  new_leads_count: number;
  new_leads_delta: number;
  /** Highest count first; chats and website enquiries count even if never converted to a lead. */
  lead_sources: LeadSourceRow[];
  /** Bar axis maximum: 10, growing in steps of 10. */
  lead_sources_scale: number;
  sessions_today_count: number;
  rooms_in_use_count: number;
  active_therapists_count: number;
  attendance_rate: number | null;
  attendance_delta: number | null;
  today_sessions: CalendarSessionPayload[];
  /** Sum of invoice subtotals issued this calendar month. */
  revenue_mtd: number;
  /** % change vs last month's total; null when last month had no invoices. */
  revenue_delta: number | null;
  last_month_name: string;
  claims_pending_amount: number;
  claims_pending_count: number;
  oldest_claim_days: number | null;
  waitlist_count: number;
  avg_wait_weeks: number | null;
  waitlist_next_up: WaitlistEntry[];
  /** Soonest renewal first, first 4. */
  authorizations_expiring: ExpiringAuthorization[];
  /** Latest 3 conversations by last_message_at. */
  whatsapp_inbox: WhatsappContact[];
}

// ---------------------------------------------------------------------------
// Contacts (website contact form + booking widget submissions)
// ---------------------------------------------------------------------------

export type ContactStatus = 'new' | 'approved' | 'rejected' | 'contacted' | 'converted' | 'closed';

/** `contacts` table (App\Models\Contact). */
export interface Contact {
  id: number;
  name: string | null;
  child_name: string | null;
  child_age: string | null;
  email: string | null;
  phone: string | null;
  interested_in: string | null;
  insurance: string | null;
  message: string | null;
  /** `date` cast; set with booking_time when the family asked for a consultation slot. */
  booking_date: IsoDateTime | null;
  /** One of Contact::CONSULTATION_TIMES, e.g. "10:00 AM". */
  booking_time: string | null;
  /** The approve/reject outcome, kept after status moves on to "contacted". */
  booking_decision: string | null;
  status_email_sent_at: IsoDateTime | null;
  status: ContactStatus;
  converted_lead_id: number | null;
  converted_at: IsoDateTime | null;
  created_at: IsoDateTime;
  updated_at: IsoDateTime;
}

/** A contact plus the model checks the web dialog relies on. */
export interface ContactItem extends Contact {
  /** Contact::isSlotEditable() — until the decision has been emailed. */
  can_edit_slot: boolean;
  /** Contact::canSendStatusEmail() — approved or rejected. */
  can_send_email: boolean;
  /** Contact::canConvertToLead() — approved or contacted, and not yet converted. */
  can_convert: boolean;
}

/** GET /admin/contacts as JSON (ContactController::index view variables). */
export interface ContactsIndex {
  /** Every submission, newest first. */
  contacts: ContactItem[];
  /** Contact::getStatuses(): status → label. */
  statuses: Record<ContactStatus, string>;
  new_count: number;
  consultation_times: string[];
}

/** PATCH /admin/contacts/{contact} — both null removes the slot. */
export interface ContactSlotRequest {
  /** "YYYY-MM-DD" */
  booking_date: string | null;
  booking_time: string | null;
}

/** POST /admin/contacts/{contact}/send-email */
export interface ContactEmailRequest {
  to: string;
  subject: string;
  message: string;
}

// ---------------------------------------------------------------------------
// Therapists & schedules
// ---------------------------------------------------------------------------

/** A therapist on the Therapists page, with their workload for the shown week. */
export interface TherapistRosterRow {
  id: number;
  name: string;
  /** "Clinical" (title-cased department). */
  department_label: string;
  /** Booked hours that week, cancelled and closed sessions excluded (1 decimal). */
  weekly_hours: number;
  /** Every session that week, whatever its status. */
  session_count: number;
}

/** GET /therapist?therapist_id=&week= as JSON (TherapistController::index view variables). */
export interface TherapistsIndex {
  /** False for therapists and "own"-level users, who only get themselves. */
  can_view_all: boolean;
  /** Monday and Sunday of the shown week. */
  week_start: YmdString;
  week_end: YmdString;
  is_current_week: boolean;
  therapists: TherapistRosterRow[];
  selected_therapist_id: number | null;
  /** The selected therapist's sessions that week, closed and cancelled ones included, by date then time. */
  sessions: CalendarSessionPayload[];
}

/** A patient in the booking panel (CalendarController::leadsPayload()). `id` is the lead id. */
export interface BookingLead {
  id: number;
  name: string;
  /** Scheduled sessions from today on. */
  upcoming_count: number;
  next_session: { date: YmdString; time: string } | null;
  /** The furthest-out scheduled session, when there is more than one. */
  last_session: { date: YmdString; time: string } | null;
  /** Primary insurance authorization, when it has an hours total. */
  auth: { payer: string; total: number; left: number; covers: string[] } | null;
  /** Packages agreed at intake, each with the activity types it covers and its hour balance. */
  packages: { id: number; name: string; service: string | null; types: string[]; total: number; used: number; left: number }[];
}

/** What the booking form needs: roster, patients and option lists. */
export interface BookingOptions {
  /** Active therapists (rosterStaff()). */
  therapists: { id: number; name: string }[];
  leads: BookingLead[];
  activity_types: string[];
  durations: number[];
}

/** POST /calendar */
export interface SessionStoreRequest {
  /** One session is created per therapist per occurrence. */
  therapist_ids: number[];
  /** Lead ids. Empty with a custom_patient for a non-patient block. */
  patient_ids?: number[];
  custom_patient?: string | null;
  activity_label?: string | null;
  activity_types: string[];
  session_date: YmdString;
  /** "HH:mm" */
  start_time: string;
  duration_minutes: number;
  room?: string | null;
  notes?: string | null;
  repeats?: 'none' | 'weekly' | 'biweekly';
  /** Weekly only: days of the week, 0 = Sunday. Omitted = the start date's weekday. */
  weekdays?: number[];
  /** Repeating only. Null sizes the series to the patient's remaining package hours. */
  occurrences?: number | null;
}

/** POST /calendar response (201). When every slot was skipped Laravel answers 422 with the same message. */
export interface SessionStoreResponse {
  message: string;
  created: number;
  /** Slots where the therapist was already booked. */
  skipped: { therapist_id: number; date: YmdString }[];
  authorization_warning: string | null;
  sessions: CalendarSessionPayload[];
}

/** PUT /calendar/{id} — partial; anything left out is unchanged. */
export interface SessionUpdateRequest {
  therapist_id?: number;
  patient_ids?: number[];
  custom_patient?: string | null;
  activity_label?: string | null;
  activity_types?: string[];
  session_date?: YmdString;
  start_time?: string;
  duration_minutes?: number;
  room?: string | null;
  notes?: string | null;
  /** "completed" can't be set by hand. */
  status?: Exclude<SessionStatus, 'completed'> | null;
  cancel_reason?: CancelReason | null;
  cancel_notice_hours?: number | null;
}

/** PUT /calendar/{id} JSON response. */
export interface SessionUpdateResponse {
  message: string;
  session: CalendarSessionPayload;
}

// ---------------------------------------------------------------------------
// Reports & analytics
// ---------------------------------------------------------------------------

export interface ReportAmountRow {
  label: string;
  amount: number;
}

export interface ReportLeadSource {
  source: string;
  count: number;
  enrolled: number;
  /** Share of all captured leads, whole percent. */
  pct: number;
  conversion_rate: number | null;
  color: string;
}

export interface ReportMonthRevenue {
  /** "Oct" */
  label: string;
  amount: number;
  thousands: number;
  /** The month still in progress (the web marks it with *). */
  is_current: boolean;
}

/**
 * GET /reports as JSON (ReportController::reportData(), snake_case). Money
 * sections cover the trailing six months; VAT and therapy hours cover the
 * current month; the lead funnel covers the last 90 days.
 */
export interface ReportsData {
  period_start_label: string;
  /** "October 2026" */
  period_end_label: string;
  updated_at: IsoDateTime;

  vat_filing_label: string;
  vat_trn: string;
  vat_rate: number;
  vat_standard_rated_supplies: number;
  vat_output_tax: number;
  vat_credit_notes_issued: number;
  vat_net_payable: number;

  collection_invoiced_total: number;
  collection_collected_total: number;
  collection_rate_pct: number;
  collection_billable_hours: number;
  collection_revenue_per_hour: number | null;

  /** Largest first. */
  revenue_by_service: (ReportAmountRow & { color: string })[];
  revenue_by_setting: ReportAmountRow[];
  revenue_by_therapist: ReportAmountRow[];

  /** Oldest first, six rows. */
  revenue_by_month: ReportMonthRevenue[];
  revenue_total_6mo: number;
  revenue_average_6mo: number;
  revenue_best_month: ReportMonthRevenue;
  /** This month vs last month, whole percent; null when last month was zero. */
  revenue_mom_delta: number | null;

  funnel_stages: { label: string; count: number; pct: number }[];
  captured_count: number;
  conversion_rate: number | null;
  lead_sources: ReportLeadSource[];
  best_source: ReportLeadSource | null;
  worst_source: ReportLeadSource | null;
  median_enroll_days: number | null;

  lost_leads_count: number;
  lost_leads_value: number;
  /** Most leads first; the first row is the "biggest driver". */
  lost_reasons: { reason: string; count: number; value: number }[];

  therapy_month_label: string;
  therapy_total_hours: number;
  therapy_hours_by_type: { type: string; hours: number; color: string }[];
}

// ---------------------------------------------------------------------------
// Errors
// ---------------------------------------------------------------------------

/** Laravel validation / error body: `{message, errors?}`. */
export interface ApiErrorBody {
  message: string;
  errors?: Record<string, string[]>;
}
