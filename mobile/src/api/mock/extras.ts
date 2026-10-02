/**
 * Mock of three smaller web features: the topbar bell
 * (App\Support\TopbarNotifications), staff leave on the calendar
 * (CalendarController::leaveImpact / markLeave / removeLeave) and "Add
 * patient" (PatientController::store).
 */

import { canAccessFeature, canManageCalendar } from '@/auth/permissions';
import { timeAgo } from '@/utils/dates';

import type { ApiClient } from '../client';
import { ApiError } from '../errors';
import type {
  LeaveRequest,
  LeaveResponse,
  NotificationItem,
  NotificationsResponse,
  PatientCreateOptions,
  PatientStoreRequest,
} from '../types';
import { INSURANCES } from './catalog';
import { blankLead } from './leadDefaults';
import { editOptions } from './patientExtras';
import type { MockDb, UserRow } from './rows';
import { dateCast, laravelIso } from './seed';
import { delay, getDb, requireFeature, requireUser, toApiUser } from './server';

/** StaffLeave::TYPES */
export const LEAVE_TYPES = ['Annual leave', 'Sick leave', 'Public holiday', 'Emergency leave', 'Unpaid leave'];

const now = () => laravelIso(new Date().toISOString());
const fail = (field: string, message: string, summary = message) => new ApiError(422, { message: summary, errors: { [field]: [message] } });
const isDate = (v: unknown): v is string => typeof v === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(v);

// ---------------------------------------------------------------------------
// The bell
// ---------------------------------------------------------------------------

const PER_FEED = 8;
const MAX_ITEMS = 15;

/** Per-user read state: clicked item keys, and when "Mark all as read" was last used. */
const readKeys = new Map<number, Set<string>>();
const clearedAt = new Map<number, string>();

type Feed = { item: Omit<NotificationItem, 'created_at'>; at: string; unread: boolean }[];

/** New leads, new website submissions and unread conversations, each only if the user has that module. */
function feeds(db: MockDb, user: UserRow): Feed {
  const api = toApiUser(user);
  const keys = readKeys.get(user.id) ?? new Set<string>();
  const cleared = clearedAt.get(user.id) ?? '';
  const rows: Feed = [];

  if (canAccessFeature(api, 'leads')) {
    for (const lead of db.leads.filter((l) => l.status === 'new')) {
      const id = `lead:${lead.id}`;
      const unread = lead.created_at > cleared && !keys.has(id);
      rows.push({
        at: lead.created_at,
        unread,
        item: { id, icon: 'fa-filter', title: `New lead: ${lead.child_name || lead.parent_guardian_name}`, subtitle: lead.phone, url: `/admin/leads?lead=${lead.id}`, read: !unread, weight: 1 },
      });
    }
  }
  if (canAccessFeature(api, 'contacts')) {
    for (const contact of db.contacts.filter((c) => c.status === 'new')) {
      const id = `contact:${contact.id}`;
      const unread = contact.created_at > cleared && !keys.has(id);
      rows.push({
        at: contact.created_at,
        unread,
        item: { id, icon: 'fa-envelope', title: `New message from ${contact.name}`, subtitle: contact.phone || contact.email, url: `/admin/contacts?contact=${contact.id}`, read: !unread, weight: 1 },
      });
    }
  }
  if (canAccessFeature(api, 'whatsapp')) {
    for (const c of db.whatsappContacts.filter((x) => x.unread_count > 0)) {
      const at = c.last_message_at ?? c.created_at;
      // Keyed by the latest message time, so a read thread comes back when a new message arrives.
      const id = `whatsapp:${c.id}:${Math.floor(new Date(at).getTime() / 1000)}`;
      const unread = at > cleared && !keys.has(id);
      rows.push({
        at,
        unread,
        item: { id, icon: 'fa-whatsapp', title: `${c.name || c.wa_id} · ${c.unread_count} unread`, subtitle: c.last_message_preview, url: `/whatsapp?contact=${c.id}`, read: !unread, weight: c.unread_count },
      });
    }
  }
  return rows;
}

function bell(): NotificationsResponse {
  const user = requireUser();
  const rows = feeds(getDb(), user);
  const byKind = (prefix: string) => rows.filter((r) => r.item.id.startsWith(prefix)).sort((a, b) => b.at.localeCompare(a.at)).slice(0, PER_FEED);
  return {
    count: rows.filter((r) => r.unread).reduce((sum, r) => sum + r.item.weight, 0),
    items: [...byKind('lead:'), ...byKind('contact:'), ...byKind('whatsapp:')]
      .sort((a, b) => b.at.localeCompare(a.at))
      .slice(0, MAX_ITEMS)
      .map((r) => ({ ...r.item, created_at: timeAgo(r.at) })),
  };
}

function markRead(id: string): { ok: true; count: number } {
  const user = requireUser();
  if (/^(lead|contact):\d+$|^whatsapp:\d+:\d+$/.test(id)) {
    readKeys.set(user.id, new Set([...(readKeys.get(user.id) ?? []), id]));
  }
  return { ok: true, count: bell().count };
}

function markAllRead(): { ok: true; count: number } {
  const user = requireUser();
  clearedAt.set(user.id, now());
  readKeys.delete(user.id);
  return { ok: true, count: bell().count };
}

// ---------------------------------------------------------------------------
// Staff leave
// ---------------------------------------------------------------------------

function leaveImpact(userId: number, date: string): { count: number } {
  const user = requireUser();
  requireFeature(user, 'calendar');
  return {
    count: getDb().sessions.filter((s) => s.therapist_id === userId && s.session_date === date && s.status === 'scheduled').length,
  };
}

/** Marks a staff member on leave for a day and cancels (clinic-side) their still-scheduled sessions that day. */
function markLeave(input: LeaveRequest): LeaveResponse {
  const user = requireUser();
  requireFeature(user, 'calendar', true);
  if (!canManageCalendar(toApiUser(user))) throw new ApiError(403, { message: 'Only the Clinical Supervisor can change the schedule.' });
  const db = getDb();
  const summary = 'Check the leave details.';
  const staff = db.users.find((u) => u.id === input.user_id);
  if (!staff) throw fail('user_id', 'The selected user id is invalid.', summary);
  if (!isDate(input.leave_date)) throw fail('leave_date', 'The leave date field must be a valid date.', summary);
  if (!LEAVE_TYPES.includes(input.leave_type)) throw fail('leave_type', 'The selected leave type is invalid.', summary);
  if (!input.reason.trim()) throw fail('reason', 'The reason field is required.', summary);
  if (input.reason.length > 500) throw fail('reason', 'The reason field must not be greater than 500 characters.', summary);

  let leave = db.staffLeaves.find((l) => l.user_id === input.user_id && l.leave_date === input.leave_date);
  if (leave) Object.assign(leave, { leave_type: input.leave_type, reason: input.reason, created_by: user.id });
  else {
    leave = {
      id: Math.max(0, ...db.staffLeaves.map((l) => l.id)) + 1,
      user_id: input.user_id,
      leave_date: input.leave_date,
      leave_type: input.leave_type,
      reason: input.reason,
      created_by: user.id,
    };
    db.staffLeaves.push(leave);
  }

  const name = `${staff.first_name} ${staff.last_name}`.trim();
  const affected = db.sessions.filter((s) => s.therapist_id === input.user_id && s.session_date === input.leave_date && s.status === 'scheduled');
  for (const s of affected) {
    s.status = 'cancelled';
    s.cancel_reason = 'clinic';
    s.notes = `${s.notes ? `${s.notes}\n` : ''}Cancelled — ${name} on ${input.leave_type}.`.trim();
    s.updated_at = now();
  }
  return {
    message: `${name} marked on ${input.leave_type}.${affected.length ? ` ${affected.length} session(s) cancelled — arrange cover.` : ''}`,
    leave: { id: leave.id, user_id: leave.user_id, leave_date: leave.leave_date, leave_type: leave.leave_type, reason: leave.reason },
    cancelled: affected.length,
  };
}

function removeLeave(id: number): { message: string } {
  const user = requireUser();
  requireFeature(user, 'calendar', true);
  if (!canManageCalendar(toApiUser(user))) throw new ApiError(403, { message: 'Only the Clinical Supervisor can change the schedule.' });
  const db = getDb();
  const index = db.staffLeaves.findIndex((l) => l.id === id);
  if (index === -1) throw new ApiError(404, { message: 'Not found.' });
  db.staffLeaves.splice(index, 1);
  return { message: 'Leave removed.' };
}

// ---------------------------------------------------------------------------
// Add patient
// ---------------------------------------------------------------------------

function createOptions(): PatientCreateOptions {
  const user = requireUser();
  requireFeature(user, 'patients');
  const { packages, insurances } = editOptions();
  return { packages, insurances };
}

/** Creates an already-enrolled lead plus its patient record in one step. */
function storePatient(input: PatientStoreRequest): { success: true; message: string; patient_id: number } {
  const user = requireUser();
  requireFeature(user, 'patients', true);
  if (user.role === 'COORDINATOR' || user.role === 'THERAPIST') {
    throw new ApiError(403, { message: 'You do not have permission to add a patient.' });
  }

  const errors: Record<string, string[]> = {};
  const required = (field: keyof PatientStoreRequest, label: string) => {
    if (!String(input[field] ?? '').trim()) errors[field] = [`The ${label} field is required.`];
  };
  required('child_name', 'child name');
  required('diagnosis', 'diagnosis');
  required('programme', 'programme');
  required('phone', 'phone');
  if (input.child_age != null && (!Number.isInteger(input.child_age) || input.child_age < 0 || input.child_age > 25)) {
    errors.child_age = ['The child age field must be between 0 and 25.'];
  }
  if (input.authorized_hours_total != null && (!Number.isInteger(input.authorized_hours_total) || input.authorized_hours_total < 0)) {
    errors.authorized_hours_total = ['The authorized hours total field must be at least 0.'];
  }
  for (const field of ['authorization_renews_at', 'enrolled_at'] as const) {
    if (input[field] && !isDate(input[field])) errors[field] = [`The ${field.replace(/_/g, ' ')} field must be a valid date.`];
  }
  if (Object.keys(errors).length > 0) throw new ApiError(422, { message: Object.values(errors)[0][0], errors });

  const db = getDb();
  const stamp = now();
  const lead = blankLead({
    id: Math.max(0, ...db.leads.map((l) => l.id)) + 1,
    created_at: stamp,
    updated_at: stamp,
    child_name: input.child_name.trim(),
    child_age: input.child_age != null ? String(input.child_age) : null,
    parent_guardian_name: input.parent_guardian_name?.trim() || null,
    phone: input.phone.trim(),
    source: 'Manual entry',
    status: 'enrolled',
  });
  db.leads.push(lead);

  const patientId = Math.max(0, ...db.patients.map((p) => p.id)) + 1;
  db.patients.push({
    id: patientId,
    lead_id: lead.id,
    diagnosis: input.diagnosis.trim(),
    programme: input.programme.trim(),
    treatment_plan_review_due_at: null,
    enrolled_at: input.enrolled_at ? dateCast(input.enrolled_at) : stamp,
    created_at: stamp,
    updated_at: stamp,
  });

  if (input.payer_name) {
    db.authorizations.push({
      id: Math.max(0, ...db.authorizations.map((a) => a.id)) + 1,
      patient_id: patientId,
      payer_name: input.payer_name,
      coverage_percent: INSURANCES[input.payer_name] ?? 0,
      covers_services: ['ABA'],
      policy_number: null,
      approval_reference: null,
      authorized_hours_total: input.authorized_hours_total ?? null,
      renews_at: input.authorization_renews_at ? dateCast(input.authorization_renews_at) : null,
      sort_order: 0,
      created_at: stamp,
      updated_at: stamp,
    });
  }
  if (input.clinical_note?.trim()) {
    db.patientNotes.push({
      id: Math.max(0, ...db.patientNotes.map((n) => n.id)) + 1,
      patient_id: patientId,
      user_id: user.id,
      body: input.clinical_note.trim(),
      flagged: false,
      flag_reason: null,
      signed_off_at: null,
      signed_off_by: null,
      created_at: stamp,
      updated_at: stamp,
    });
  }
  return { success: true, message: 'Patient added.', patient_id: patientId };
}

export function createNotificationsApi(): ApiClient['notifications'] {
  return {
    list: () => delay(bell),
    read: (id) => delay(() => markRead(id)),
    readAll: () => delay(markAllRead),
  };
}

export function createLeaveApi(): Pick<ApiClient['calendar'], 'leaveImpact' | 'markLeave' | 'removeLeave'> {
  return {
    leaveImpact: (userId, date) => delay(() => leaveImpact(userId, date)),
    markLeave: (input) => delay(() => markLeave(input)),
    removeLeave: (id) => delay(() => removeLeave(id)),
  };
}

export function createPatientCreationApi(): Pick<ApiClient['patients'], 'createOptions' | 'store'> {
  return {
    createOptions: () => delay(createOptions),
    store: (input) => delay(() => storePatient(input)),
  };
}
