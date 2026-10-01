/**
 * Mock "database" rows: the Laravel table columns, before any controller
 * shapes them into a payload. Internal to the mock — screens only ever see
 * the payload types in src/api/types.ts.
 */

import type {
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
}
