/**
 * The single data-access surface for the app. Screens import `api` from here
 * and never fetch directly. Swapping the mock for real endpoints means adding
 * an implementation under src/api/http/ and changing the export below.
 *
 * Endpoint notes name the Laravel route each method corresponds to (or would
 * need, where the web app only renders HTML today).
 */

import { createMockApi } from './mock';
import type {
  CalendarFeed,
  LoginRequest,
  LoginResponse,
  MyCalendarWeek,
  MySessionPayload,
  NoteReviewFilter,
  PatientDetail,
  PatientListItem,
  PatientNote,
  ProfileUpdateRequest,
  ProfileUpdateResponse,
  SignOffResponse,
  SuperviseResponse,
  SupervisorDashboard,
  TherapistDashboard,
  TherapistNoteResponse,
  TodayGoalsRequest,
  TodayGoalsResponse,
  User,
} from './types';

export interface ApiClient {
  auth: {
    /** POST /login (proposed token variant). 422 invalid, 429 throttled. */
    login(input: LoginRequest): Promise<LoginResponse>;
    /** Current user for the stored token (with `role_template`). 401 if invalid. */
    me(): Promise<User>;
    /** POST /logout */
    logout(): Promise<void>;
    /** POST /forgot-password — always the same message, whether or not the account exists. */
    forgotPassword(email: string): Promise<{ message: string }>;
  };
  profile: {
    /** PUT /profile — the user's own personal details. 422 on validation errors. */
    update(input: ProfileUpdateRequest): Promise<ProfileUpdateResponse>;
  };
  dashboard: {
    /** GET /dashboard for THERAPIST (DashboardController::therapistMetrics). 403 for other roles. */
    therapist(): Promise<TherapistDashboard>;
    /** GET /dashboard for CLINICAL_SUPERVISOR (clinicalSupervisorMetrics etc.). 403 for other roles. */
    supervisor(): Promise<SupervisorDashboard>;
  };
  patients: {
    /**
     * GET /patient?search= — therapists only get patients they have a
     * session with (PatientController::index). Newest first.
     */
    list(search?: string): Promise<PatientListItem[]>;
    /** GET /patient/{id} — Overview + Session history data. 403 if not assigned. */
    show(id: number): Promise<PatientDetail>;
    /** POST /patient/{id}/notes — 403 for coordinators and unassigned therapists. */
    addNote(id: number, body: string): Promise<{ success: true; note: PatientNote }>;
    /** POST /patient/{id}/goals/today — replaces today's session goals. */
    saveTodayGoals(id: number, input: TodayGoalsRequest): Promise<TodayGoalsResponse>;
  };
  /**
   * PROPOSED (no Laravel equivalent yet): reviewing therapists' session notes.
   * The web only lists unsigned/flagged notes on the supervisor dashboard;
   * these endpoints must be added to Laravel with the mobile API.
   * Allowed for CLINICAL_SUPERVISOR and FULL_ADMIN (see canReviewNotes).
   */
  notes: {
    /** GET /patient-notes/review?filter=unsigned|flagged — unsigned oldest first, flagged newest first. */
    review(filter: NoteReviewFilter): Promise<PatientNote[]>;
    /** POST /patient-notes/sign-off {note_ids} */
    signOff(noteIds: number[]): Promise<SignOffResponse>;
    /** POST /patient-notes/{id}/flag {flag_reason} */
    flag(id: number, reason: string): Promise<{ success: true; note: PatientNote }>;
    /** DELETE /patient-notes/{id}/flag */
    unflag(id: number): Promise<{ success: true; note: PatientNote }>;
  };
  calendar: {
    /** GET /calendar?date= for "own"-level users (CalendarController::myCalendar). */
    myWeek(date?: string): Promise<MyCalendarWeek>;
    /** GET /calendar/feed?start=&end= — sessions + leave in a date range (own only for therapists). */
    feed(start: string, end: string): Promise<CalendarFeed>;
    /** GET /calendar/{id} */
    show(id: number): Promise<MySessionPayload>;
    /** PATCH /calendar/{id}/therapist-note — only the session's own therapist. */
    saveTherapistNote(id: number, note: string | null): Promise<TherapistNoteResponse>;
    /** POST /calendar/{id}/supervision {notes} — managers only; past, non-cancelled sessions. */
    supervise(id: number, notes: string): Promise<SuperviseResponse>;
    /** DELETE /calendar/{id}/supervision */
    unsupervise(id: number): Promise<{ message: string }>;
  };
}

export const api: ApiClient = createMockApi();
