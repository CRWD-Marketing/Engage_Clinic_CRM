/**
 * The single data-access surface for the app. Screens import `api` from here
 * and never fetch directly. There are two implementations: the mock
 * (src/api/mock, in-memory demo data) and the real one (src/api/http, the
 * Laravel mobile API); the export at the bottom picks one.
 *
 * Endpoint notes name the Laravel route each method corresponds to (or would
 * need, where the web app only renders HTML today).
 */

import { createHttpApi } from './http';
import { createMockApi } from './mock';
import type {
  ApplicationResponse,
  ApplicationsPage,
  ApplicationStatus,
  AccessGrantInput,
  AccessTemplate,
  AccessUser,
  AccessUserInput,
  BillingInvoice,
  BillingOverview,
  BulkRunGroup,
  BulkRunQuery,
  BulkRunResponse,
  ClaimUpdateRequest,
  ClaimUpdateResponse,
  FamilyStatement,
  InvoiceActionResponse,
  InvoiceCreditRequest,
  InvoiceEmailRequest,
  InvoicePaymentRequest,
  InvoicePreview,
  JobPosting,
  JobPostingInput,
  InvoiceVoidRequest,
  NewInvoiceRequest,
  PackageInput,
  PackageRow,
  PackagesPage,
  PatientLedger,
  PreAuthorization,
  PreAuthRequest,
  PrepaidTopUpResponse,
  RemoteFile,
  RolesAccessPage,
  SettingsItem,
  SettingsItemInput,
  SettingsKind,
  SettingsPage,
  TemplateInput,
  UserInput,
  UserListQuery,
  UserListResponse,
  UserProfile,
  UserSaveResponse,
  VendorListQuery,
  VendorPage,
  VendorsPage,
  VendorDetail,
  VendorUpdateInput,
  LeaveRequest,
  LeaveResponse,
  NotificationsResponse,
  PatientCreateOptions,
  PatientStoreRequest,
  FinanceDashboard,
  HrDashboard,
  OtherStaffDashboard,
  SalesDashboard,
  PatientDocumentItem,
  PatientDocumentRequest,
  PatientUpdateRequest,
  BookingOptions,
  SessionStoreRequest,
  SessionStoreResponse,
  SessionUpdateRequest,
  ReportsData,
  SessionUpdateResponse,
  TherapistsIndex,
  ContactEmailRequest,
  ContactItem,
  ContactsIndex,
  ContactSlotRequest,
  ContactStatus,
  AdminDashboard,
  AiState,
  CalendarFeed,
  InboxContact,
  InboxMessage,
  InboxPoll,
  InboxThread,
  CoordinatorDashboard,
  Lead,
  LeadActivity,
  LeadDetail,
  LeadMutationResponse,
  LeadsBoard,
  LeadStatus,
  LeadStoreRequest,
  LeadUpdateRequest,
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
    /** GET /dashboard for COORDINATOR (coordinatorMetrics etc.). 403 for other roles. */
    coordinator(): Promise<CoordinatorDashboard>;
    /** GET /dashboard for FULL_ADMIN (lead, schedule, billing, patient and inbox metrics). 403 for other roles. */
    admin(): Promise<AdminDashboard>;
    /** GET /dashboard for SALES_STAFF. 403 for other roles. */
    sales(): Promise<SalesDashboard>;
    /** GET /dashboard for HR_STAFF. 403 for other roles. */
    hr(): Promise<HrDashboard>;
    /** GET /dashboard for FINANCE_STAFF. 403 for other roles. */
    finance(): Promise<FinanceDashboard>;
    /** GET /dashboard for OTHER_STAFF. 403 for other roles. */
    otherStaff(): Promise<OtherStaffDashboard>;
  };
  /** The bell: new leads, new website submissions, unread conversations and "you were assigned" alerts. */
  notifications: {
    /** GET /notifications */
    list(): Promise<NotificationsResponse>;
    /** POST /notifications/{id}/read — returns the new unread count. */
    read(id: string): Promise<{ ok: true; count: number }>;
    /** POST /notifications/read-all */
    readAll(): Promise<{ ok: true; count: number }>;
  };
  patients: {
    /** GET /patients/create-options — pickers for "Add patient". */
    createOptions(): Promise<PatientCreateOptions>;
    /** POST /patients — 403 for coordinators and therapists. */
    store(input: PatientStoreRequest): Promise<{ success: true; message: string; patient_id: number }>;
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
    /** PUT /patient/{id} — "Edit details"; also updates the lead and the primary authorization. */
    update(id: number, input: PatientUpdateRequest): Promise<{ success: true; message: string }>;
    /** POST /patient/{id}/documents — files a record (name, type, expiry); no upload. */
    addDocument(id: number, input: PatientDocumentRequest): Promise<{ success: true; document: PatientDocumentItem }>;
    /** DELETE /patient/{id}/documents/{document} */
    deleteDocument(id: number, documentId: number): Promise<{ success: true }>;
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
  /**
   * Website submissions (ContactController, `feature:contacts`). Coordinators
   * can't convert or delete (403). The web returns HTML / redirects for most
   * of these; the mobile API needs JSON equivalents.
   */
  contacts: {
    /** GET /admin/contacts — every submission, newest first. */
    list(): Promise<ContactsIndex>;
    /** GET /admin/contacts/count — submissions still "new". */
    newCount(): Promise<{ success: true; count: number }>;
    /** PATCH /admin/contacts/{id} — set, move or remove the consultation slot. */
    updateSlot(id: number, input: ContactSlotRequest): Promise<{ success: true; message: string; contact: ContactItem }>;
    /** PATCH /admin/contacts/{id}/status — approved, rejected, closed (never "converted"). */
    updateStatus(id: number, status: ContactStatus): Promise<ContactItem>;
    /** POST /admin/contacts/{id}/send-email — emails the decision and moves the status to "contacted". */
    sendEmail(id: number, input: ContactEmailRequest): Promise<{ message: string; contact: ContactItem }>;
    /** POST /admin/contacts/{id}/convert-to-lead */
    convertToLead(id: number): Promise<ContactItem>;
    /** DELETE /admin/contacts/{id} */
    destroy(id: number): Promise<{ message: string }>;
  };
  /** Leads pipeline (LeadController, `feature:leads`). Action checks follow User::canDo(). */
  leads: {
    /** GET /admin/leads */
    board(): Promise<LeadsBoard>;
    /** GET /admin/leads/{id} */
    show(id: number): Promise<LeadDetail>;
    /** POST /admin/leads */
    store(input: LeadStoreRequest): Promise<LeadMutationResponse>;
    /**
     * PUT /admin/leads/{id}. 422 if the lead would be Contacted without an owner,
     * 403 for assign/reassign/terminate without the matching action, 422 for a
     * termination without a reason.
     */
    update(id: number, input: LeadUpdateRequest): Promise<LeadMutationResponse>;
    /** PATCH /admin/leads/{id}/status — 422 moving to contacted without an owner. */
    updateStatus(id: number, status: LeadStatus): Promise<LeadMutationResponse>;
    /** POST /admin/leads/{id}/notes — needs `add_lead_notes`. */
    addNote(id: number, body: string): Promise<{ success: true; note: LeadActivity }>;
    /** POST /admin/leads/{id}/restore — needs `terminate_lead`. */
    restore(id: number): Promise<LeadMutationResponse>;
    /**
     * POST /admin/leads/{id}/convert-to-patient — needs `convert_to_client`, an
     * enrolled lead and 7/7 intake steps. The web answers with a redirect URL;
     * `patient_id` is its JSON equivalent.
     */
    convertToPatient(id: number): Promise<{ success: true; message: string; patient_id: number }>;
  };
  /**
   * WhatsApp / Instagram / Facebook inbox (WhatsappController). Every user
   * with the `whatsapp` module can read, reply, change the AI state and
   * convert to a lead; "view" level blocks writes. The web returns HTML
   * fragments and redirects; these are the JSON equivalents.
   */
  inbox: {
    /** GET /whatsapp — all conversations, latest activity first. */
    list(): Promise<InboxContact[]>;
    /** GET /whatsapp?contact={id} — the conversation; marks it read. */
    thread(contactId: number): Promise<InboxThread>;
    /** GET /whatsapp/poll?contact=&after= */
    poll(contactId: number | null, after: number): Promise<InboxPoll>;
    /** POST /whatsapp/send {contact_id, message} — 422 empty/too long, 429 over 20/min. */
    send(contactId: number, message: string): Promise<{ message: InboxMessage; latest_message_id: number }>;
    /** POST /whatsapp/{id}/ai-state {ai_state} — also clears needs_human_attention. */
    setAiState(contactId: number, state: AiState): Promise<{ contact: InboxContact }>;
    /** POST /whatsapp/{id}/convert-to-lead — no-op if already linked. */
    convertToLead(contactId: number): Promise<{ contact: InboxContact; lead: Lead }>;
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
    /**
     * PUT /calendar/{id} {status} — Close (discontinue the slot) or Reopen it as
     * scheduled, from the Therapists page. Managers only (403 otherwise);
     * reopening into an overlapping session is a 422.
     */
    setStatus(id: number, status: 'closed' | 'scheduled'): Promise<SessionUpdateResponse>;
    /** GET /calendar/leads plus the roster and option lists the booking form needs. */
    bookingOptions(): Promise<BookingOptions>;
    /**
     * POST /calendar — needs the `book_modify_session` action. Double-booked
     * slots are skipped; going past the patient's package hours is a 422.
     */
    store(input: SessionStoreRequest): Promise<SessionStoreResponse>;
    /** PUT /calendar/{id} — managers only (403 otherwise); an overlapping slot is a 422. */
    update(id: number, input: SessionUpdateRequest): Promise<SessionUpdateResponse>;
    /** GET /calendar/leave/impact?user_id=&date= — scheduled sessions a leave day would cancel. */
    leaveImpact(userId: number, date: string): Promise<{ count: number }>;
    /** POST /calendar/leave — managers only; cancels that day's scheduled sessions (clinic-side). */
    markLeave(input: LeaveRequest): Promise<LeaveResponse>;
    /** DELETE /calendar/leave/{id} — managers only. */
    removeLeave(id: number): Promise<{ message: string }>;
  };
  /**
   * Billing & insurance (BillingController / InvoiceController, `feature:billing`).
   * Every change needs the `create_invoice` action; a view-only level blocks all of them.
   */
  /** User Management (CreateAccount, `feature:users`). Users are addressed by `public_id`. */
  users: {
    /** GET /users — 10 per page, newest first, with the stat tiles and form options. */
    list(query?: UserListQuery): Promise<UserListResponse>;
    /** GET /users/{public_id} — with manager and direct reports. */
    show(publicId: string): Promise<UserProfile>;
    /** POST /users — 403 when a non-admin gives FULL_ADMIN or CLINICAL_SUPERVISOR. */
    store(input: UserInput): Promise<UserSaveResponse>;
    /** PUT /users/{public_id} — every field optional; the password changes only when sent. */
    update(publicId: string, input: Partial<UserInput>): Promise<UserSaveResponse>;
    /** DELETE /users/{public_id} — Full Admin only (403 otherwise). */
    destroy(publicId: string): Promise<{ success: boolean; message: string }>;
  };
  /**
   * Roles & access (RoleController, `feature:roles_access`). Every change needs the
   * "Manage users & roles" action and a non-view-only Roles & access level. Users are
   * addressed by public_id; you can never lock yourself out (422).
   */
  roles: {
    /** GET /roles-access */
    page(): Promise<RolesAccessPage>;
    /** POST /roles-access/templates — Dashboard is always added. */
    storeTemplate(input: TemplateInput): Promise<{ message: string; template: AccessTemplate }>;
    /** PUT /roles-access/templates/{id} — system templates keep their name and base role; Full Admin is locked (422). */
    updateTemplate(id: number, input: TemplateInput): Promise<{ message: string; template: AccessTemplate }>;
    /** DELETE /roles-access/templates/{id} — custom only; its users fall back to their base role's system template. */
    destroyTemplate(id: number): Promise<{ message: string }>;
    /** POST /roles-access/users — 403 when a non-admin picks a Full Admin or Clinical Supervisor template. */
    storeUser(input: AccessUserInput): Promise<{ message: string; user: AccessUser }>;
    /** PUT /roles-access/users/{id} — a template change re-applies that template's access. */
    updateUser(publicId: string, input: AccessUserInput): Promise<{ message: string; user: AccessUser }>;
    /** PUT /roles-access/users/{id}/template */
    setTemplate(publicId: string, templateId: number): Promise<{ message: string; user: AccessUser }>;
    /** PUT /roles-access/users/{id}/access — the user's own modules, levels and actions. */
    setAccess(publicId: string, input: AccessGrantInput): Promise<{ message: string; user: AccessUser }>;
    /** PUT /roles-access/users/{id}/suspend — toggles. */
    toggleSuspend(publicId: string): Promise<{ message: string; user: AccessUser }>;
    /** DELETE /roles-access/users/{id} */
    destroyUser(publicId: string): Promise<{ message: string }>;
  };
  /** Job applications and postings (Career controllers, `feature:careers`). */
  careers: {
    /** GET /careers/applications — newest first; counts are across every application. */
    applications(status?: ApplicationStatus | 'all' | null): Promise<ApplicationsPage>;
    /** GET /careers/applications/count — applications still "new". */
    newCount(): Promise<{ success: boolean; count: number }>;
    /** PATCH /careers/applications/{id}/status */
    setStatus(id: number, status: ApplicationStatus): Promise<ApplicationResponse>;
    /** POST /careers/applications/{id}/notes */
    addNote(id: number, body: string): Promise<ApplicationResponse>;
    /** DELETE /careers/applications/{id} — also removes the stored resume. */
    destroy(id: number): Promise<{ message: string }>;
    /** GET /careers/applications/{id}/resume — the file, for utils/openPdf. The mock has none (503). */
    resume(id: number, filename: string): Promise<RemoteFile>;
    /** GET /careers/postings */
    postings(): Promise<{ postings: JobPosting[]; statuses: Record<JobPosting['status'], string> }>;
    /** POST /careers/postings */
    storePosting(input: JobPostingInput): Promise<{ message: string; posting: JobPosting }>;
    /** PUT /careers/postings/{id} */
    updatePosting(id: number, input: JobPostingInput): Promise<{ message: string; posting: JobPosting }>;
    /** DELETE /careers/postings/{id} — applications keep their job title. */
    destroyPosting(id: number): Promise<{ message: string }>;
  };
  /** Packages (PackageController, `feature:packages`). */
  packages: {
    /** GET /packages */
    list(): Promise<PackagesPage>;
    /** POST /packages — created active. */
    store(input: PackageInput): Promise<{ message: string; package: PackageRow }>;
    /** PUT /packages/{id} */
    update(id: number, input: PackageInput): Promise<{ message: string; package: PackageRow }>;
    /** DELETE /packages/{id} */
    destroy(id: number): Promise<{ message: string }>;
  };
  /** Vendors registered through the website form (VendorController, `feature:vendors`). */
  vendors: {
    /** GET /vendors — 20 per page, newest first. */
    list(query?: VendorListQuery): Promise<VendorsPage>;
    /** GET /vendors/count — registrations nobody has opened yet. */
    newCount(): Promise<{ success: boolean; count: number }>;
    /** GET /vendors/{id} — opening it clears the "new" marker (unless view-only). */
    show(id: number): Promise<VendorPage>;
    /** PATCH /vendors/{id} — status, critical flag, internal owner, notes. */
    update(id: number, input: VendorUpdateInput): Promise<{ message: string; vendor: VendorDetail }>;
    /** DELETE /vendors/{id} — and its uploaded files. */
    destroy(id: number): Promise<{ message: string }>;
    /** GET /vendors/{id}/documents/{document}?download=1 — the file, for utils/openPdf. The mock has none (503). */
    document(vendorId: number, documentId: number, filename: string): Promise<RemoteFile>;
  };
  /** Settings: services, locations and insurances (SettingsController, `feature:settings`). */
  settings: {
    /** GET /settings */
    page(): Promise<SettingsPage>;
    /** POST /settings/{kind} — added active. */
    store(kind: SettingsKind, input: SettingsItemInput): Promise<{ message: string; item: SettingsItem }>;
    /** PUT /settings/{kind}/{id} */
    update(kind: SettingsKind, id: number, input: SettingsItemInput): Promise<{ message: string; item: SettingsItem }>;
    /** PATCH /settings/{kind}/{id}/toggle — active ↔ inactive. */
    toggle(kind: SettingsKind, id: number): Promise<{ message: string; item: SettingsItem }>;
    /** DELETE /settings/{kind}/{id} — packages and invoice lines that used it keep going without it. */
    destroy(kind: SettingsKind, id: number): Promise<{ message: string }>;
  };
  billing: {
    /** GET /billing */
    overview(): Promise<BillingOverview>;
    /** GET /billing/invoices/{id} */
    invoice(id: number): Promise<BillingInvoice>;
    /** POST /billing/invoices/{id}/payments — 422 on a voided invoice. */
    recordPayment(id: number, input: InvoicePaymentRequest): Promise<InvoiceActionResponse>;
    /** POST /billing/invoices/{id}/credit — at most the invoice total less credits already issued. */
    creditNote(id: number, input: InvoiceCreditRequest): Promise<InvoiceActionResponse>;
    /** POST /billing/invoices/{id}/void — credits the open balance; `reissued` is the corrected invoice, if raised. */
    voidInvoice(id: number, input: InvoiceVoidRequest): Promise<InvoiceActionResponse & { reissued: BillingInvoice | null }>;
    /** POST /billing/invoices/{id}/send — emails the invoice PDF, or a payment reminder. */
    sendEmail(id: number, input: InvoiceEmailRequest): Promise<InvoiceActionResponse>;
    /** GET /billing/patients/{patient}/ledger — the client's delivered sessions, priced, newest first. */
    ledger(patientId: number): Promise<PatientLedger>;
    /** POST /billing/invoices/preview — the invoice for the chosen sessions; nothing is saved. */
    previewInvoice(input: NewInvoiceRequest): Promise<InvoicePreview>;
    /**
     * POST /billing/invoices — issues the invoice and saves attendance corrections to the calendar.
     * 409 (`settled_count`, `settled_ids`) when a session is already invoiced and not acknowledged;
     * 422 when nothing chosen is chargeable.
     */
    createInvoice(input: NewInvoiceRequest): Promise<InvoiceActionResponse>;
    /** POST /billing/patients/{patient}/top-up — adds prepaid hours (1–500). */
    topUpPrepaid(patientId: number, hours: number): Promise<PrepaidTopUpResponse>;
    /** GET /billing/patients/{patient}/statement */
    statement(patientId: number): Promise<FamilyStatement>;
    /** PATCH /billing/claims/{claim} — settling records the insurer's remittance as a receipt; moving off settled removes it. */
    updateClaim(id: number, input: ClaimUpdateRequest): Promise<ClaimUpdateResponse>;
    /** POST /billing/pre-authorizations — a new request, or a resubmission of a denied one. */
    requestPreAuth(input: PreAuthRequest): Promise<{ message: string; preauth: PreAuthorization }>;
    /** GET /billing/bulk-run — families with unbilled, chargeable sessions in the period, largest invoice first. */
    bulkPreview(query: BulkRunQuery): Promise<{ groups: BulkRunGroup[] }>;
    /** POST /billing/bulk-run — one invoice per chosen family. 422 when nothing is left to bill. */
    bulkIssue(input: BulkRunQuery & { patient_ids: number[] }): Promise<BulkRunResponse>;
    /** GET /billing/invoices/{id}/pdf — where to download the invoice PDF (see utils/openPdf). The mock has none (503). */
    invoicePdf(id: number): Promise<RemoteFile>;
    /** GET /billing/patients/{patient}/statement/pdf */
    statementPdf(patientId: number): Promise<RemoteFile>;
  };
  /** Reports & analytics (ReportController, `feature:reports`). Read-only; PDF export stays on the web. */
  reports: {
    /** GET /reports */
    index(): Promise<ReportsData>;
  };
  /** Therapists & schedules (TherapistController, `feature:therapists`). */
  therapists: {
    /** GET /therapist?therapist_id=&week= — roster with weekly workload plus one therapist's week. */
    index(params?: { therapist_id?: number; week?: string }): Promise<TherapistsIndex>;
  };
}

/**
 * Set EXPO_PUBLIC_API_URL (e.g. in `.env.local`: http://192.168.1.20:8000) to
 * talk to the Laravel mobile API. Without it the app runs on mock data.
 */
export const API_URL = process.env.EXPO_PUBLIC_API_URL?.trim() || null;

export const api: ApiClient = API_URL ? createHttpApi(API_URL) : createMockApi();
