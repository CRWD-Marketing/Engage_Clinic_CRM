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
  LoginRequest,
  LoginResponse,
  MyCalendarWeek,
  MySessionPayload,
  ProfileUpdateRequest,
  ProfileUpdateResponse,
  TherapistDashboard,
  TherapistNoteResponse,
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
  };
  calendar: {
    /** GET /calendar?date= for "own"-level users (CalendarController::myCalendar). */
    myWeek(date?: string): Promise<MyCalendarWeek>;
    /** GET /calendar/{id} */
    show(id: number): Promise<MySessionPayload>;
    /** PATCH /calendar/{id}/therapist-note — only the session's own therapist. */
    saveTherapistNote(id: number, note: string | null): Promise<TherapistNoteResponse>;
  };
}

export const api: ApiClient = createMockApi();
