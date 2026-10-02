/**
 * Mock "database" rows: the Laravel table columns, before any controller
 * shapes them into a payload. Internal to the mock — screens only ever see
 * the payload types in src/api/types.ts.
 */

import type {
  Contact,
  CancelReason,
  Lead,
  LeadActivity,
  Patient,
  PatientAuthorization,
  PatientGoal,
  PatientNote,
  RoleTemplate,
  SessionStatus,
  StaffLeave,
  User,
  WhatsappContact,
  WhatsappMessage,
} from '../types';

export type UserRow = Omit<User, 'role_template'> & { password: string };

export type RoleTemplateRow = RoleTemplate;

export type LeadRow = Lead;

export type LeadActivityRow = Omit<LeadActivity, 'author_name'>;

export type PatientRow = Omit<Patient, 'lead'>;

export type PatientNoteRow = Omit<PatientNote, 'author_name' | 'patient'>;

export type StaffLeaveRow = StaffLeave & { created_by: number | null };

export type PatientGoalRow = PatientGoal;

export type PatientAuthorizationRow = PatientAuthorization;

/** `session_goals` pivot (CalendarSession ↔ PatientGoal). */
export interface SessionGoalRow {
  id: number;
  calendar_session_id: number;
  patient_goal_id: number;
}

/** `calendar_sessions` columns (times as "HH:mm:ss", like MySQL TIME). */
export interface CalendarSessionRow {
  id: number;
  therapist_id: number;
  cover_for_user_id: number | null;
  /** FK leads.id */
  patient_id: number | null;
  patient_name: string | null;
  patient_ids: number[] | null;
  activity_label: string | null;
  activity_type: string;
  activity_types: string[] | null;
  session_date: string;
  start_time: string;
  duration_minutes: number;
  end_time: string;
  room: string | null;
  status: SessionStatus;
  cancel_reason: CancelReason | null;
  cancel_notice_hours: number | null;
  cancelled_at: string | null;
  follow_up_completed_at: string | null;
  notes: string | null;
  recurrence_group: string | null;
  supervised_by: number | null;
  supervised_at: string | null;
  supervision_notes: string | null;
  therapist_note: string | null;
  invoice_id: number | null;
  created_by: number | null;
  created_at: string;
  updated_at: string;
}

/** `invoices` table: only the columns the dashboard and reports read (billing is a later milestone). */
export interface InvoiceRow {
  id: number;
  patient_id: number;
  payer: string;
  status: 'draft' | 'issued' | 'submitted' | 'pending_info' | 'paid' | 'rejected';
  invoice_number: string;
  issue_date: string;
  /** `date` cast: first day of the billed month. */
  period: string;
  payment_method: string | null;
  subtotal: string;
  vat_amount: string;
  /** subtotal + vat_amount */
  total: string;
  amount_paid: string;
  /** VAT-inclusive credit note amount. */
  credit_amount: string;
  voided_at: string | null;
  insurance_coverage_amount: string;
  due_date: string;
  credit_reason: string | null;
  void_reason: string | null;
  replaces_invoice_id: number | null;
  replaced_by_invoice_id: number | null;
  sent_to: string | null;
  sent_at: string | null;
  reminder_sent_at: string | null;
  reminders_count: number;
  claim_reference: string | null;
}

/** `payments` table: receipts against an invoice. */
export interface PaymentRow {
  id: number;
  invoice_id: number;
  receipt_number: string;
  amount: string;
  method: string;
  received_on: string;
  reference: string | null;
}

/** `invoice_line_items` table: only the columns the reports read. */
export interface InvoiceLineItemRow {
  id: number;
  invoice_id: number;
  amount: string;
  setting: string | null;
  therapist_id: number | null;
  calendar_session_id: number | null;
  /** MOCK: stands in for the join to the billed session's activity_type. */
  activity_type: string | null;
}

export type ContactRow = Contact;

/** `patient_documents` table. */
export interface PatientDocumentRow {
  id: number;
  patient_id: number;
  uploaded_by: number | null;
  name: string;
  type: string | null;
  expires_at: string | null;
  /** Only on legacy rows that had a file attached. */
  file_path: string | null;
  created_at: string;
  updated_at: string;
}

export interface MockDb {
  roleTemplates: RoleTemplateRow[];
  users: UserRow[];
  leads: LeadRow[];
  leadActivities: LeadActivityRow[];
  patients: PatientRow[];
  sessions: CalendarSessionRow[];
  staffLeaves: StaffLeaveRow[];
  patientNotes: PatientNoteRow[];
  patientGoals: PatientGoalRow[];
  sessionGoals: SessionGoalRow[];
  authorizations: PatientAuthorizationRow[];
  whatsappContacts: WhatsappContact[];
  whatsappMessages: WhatsappMessage[];
  invoices: InvoiceRow[];
  invoiceLineItems: InvoiceLineItemRow[];
  payments: PaymentRow[];
  contacts: ContactRow[];
  patientDocuments: PatientDocumentRow[];
}
