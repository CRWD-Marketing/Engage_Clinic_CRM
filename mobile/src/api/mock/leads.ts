/**
 * Mock of LeadController (index, show, store, update, updateStatus,
 * addNote, restore, convertToPatient). Permission checks use User::canDo(),
 * exactly as the controller does.
 */

import { canDo } from '@/auth/permissions';
import type { ActionKey } from '@/auth/roles';

import type { ApiClient } from '../client';
import { ApiError } from '../errors';
import type {
  BoardLead,
  Lead,
  LeadActivity,
  LeadDetail,
  LeadMutationResponse,
  LeadsBoard,
  LeadStatus,
  LeadStoreRequest,
  LeadUpdateRequest,
} from '../types';
import { INSURANCES, LOCATIONS, PACKAGES, SERVICES } from './catalog';
import { blankLead, INTAKE_STEP_COLUMNS, intakeStepsComplete } from './leadDefaults';
import type { LeadActivityRow, MockDb, UserRow } from './rows';
import { dateCast, laravelIso } from './seed';
import { delay, getDb, requireFeature, requireUser, staffDisplayName, toApiUser } from './server';

const STATUSES: LeadStatus[] = ['new', 'contacted', 'assessment_booked', 'assessment_done', 'enrolled', 'terminated'];

/** Lead::TERMINATION_REASONS */
export const TERMINATION_REASONS = [
  'Fees / budget',
  'No insurance coverage',
  'Chose another provider',
  'Unreachable — no response',
  'Distance / relocated',
  'Not a fit for our services',
  'Duplicate enquiry',
  'Other',
];

const OWNER_REQUIRED = 'A lead must have an owner before it can move to Contacted.';

/** Field limits from the store/update validators. */
const MAX_LENGTH: Partial<Record<keyof LeadUpdateRequest, number>> = {
  child_name: 255,
  child_age: 10,
  parent_guardian_name: 255,
  phone: 20,
  email: 255,
  source: 50,
  interested_in: 100,
  insurance: 50,
  estimated_value: 50,
};

function now(): string {
  return laravelIso(new Date().toISOString());
}

function nameOf(db: MockDb, userId: number | null): string | null {
  const user = db.users.find((u) => u.id === userId);
  return user ? staffDisplayName(user) : null;
}

function present(db: MockDb, lead: Lead): BoardLead {
  return {
    ...lead,
    assigned_to_name: nameOf(db, lead.assigned_to),
    intake_steps_complete: intakeStepsComplete(lead),
    has_patient: db.patients.some((p) => p.lead_id === lead.id),
  };
}

function presentActivity(db: MockDb, a: LeadActivityRow): LeadActivity {
  return { ...a, author_name: nameOf(db, a.user_id) ?? 'System' };
}

function findLeadOr404(db: MockDb, id: number): Lead {
  const lead = db.leads.find((l) => l.id === id);
  if (!lead) throw new ApiError(404, { message: 'Not found.' });
  return lead;
}

function requireAction(user: UserRow, action: ActionKey, message: string, field?: string): void {
  if (!canDo(toApiUser(user), action)) {
    throw new ApiError(403, { message, errors: field ? { [field]: [message] } : undefined });
  }
}

function logActivity(db: MockDb, leadId: number, userId: number, type: LeadActivityRow['type'], body: string): LeadActivityRow {
  const stamp = now();
  const row: LeadActivityRow = {
    id: Math.max(0, ...db.leadActivities.map((a) => a.id)) + 1,
    lead_id: leadId,
    user_id: userId,
    type,
    body,
    created_at: stamp,
    updated_at: stamp,
  };
  db.leadActivities.push(row);
  return row;
}

/** normalizeNullableFields(): "" becomes null. */
function blankToNull<T>(value: T): T | null {
  return typeof value === 'string' && value.trim() === '' ? null : value;
}

/** cleanEstimatedValue(): digits and the first decimal point only. */
function cleanEstimatedValue(value: string): string {
  const cleaned = value.replace(/[^0-9.]/g, '');
  const [head, ...rest] = cleaned.split('.');
  return rest.length > 0 ? `${head}.${rest.join('')}` : head;
}

function validateLengths(input: LeadUpdateRequest): void {
  const errors: Record<string, string[]> = {};
  for (const [field, max] of Object.entries(MAX_LENGTH) as [keyof LeadUpdateRequest, number][]) {
    const value = input[field];
    if (typeof value === 'string' && value.length > max) {
      errors[field] = [`The ${field.replace(/_/g, ' ')} field must not be greater than ${max} characters.`];
    }
  }
  if (input.follow_up_due_at && !/^\d{4}-\d{2}-\d{2}/.test(input.follow_up_due_at)) {
    errors.follow_up_due_at = ['The follow up due at field must be a valid date.'];
  }
  if (Object.keys(errors).length > 0) {
    throw new ApiError(422, { message: Object.values(errors)[0][0], errors });
  }
}

/** Copies the plain text fields present in the request onto the lead. */
function applyFields(lead: Lead, input: LeadUpdateRequest): void {
  const text = ['child_name', 'child_age', 'parent_guardian_name', 'phone', 'email', 'source', 'interested_in', 'insurance', 'notes'] as const;
  for (const field of text) {
    if (field in input) lead[field] = blankToNull(input[field]) ?? null;
  }
  if ('estimated_value' in input) {
    const value = blankToNull(input.estimated_value);
    lead.estimated_value = value ? cleanEstimatedValue(value) : null;
  }
  if ('follow_up_due_at' in input) {
    const value = blankToNull(input.follow_up_due_at);
    lead.follow_up_due_at = value ? dateCast(value.slice(0, 10)) : null;
  }
}

const INTAKE_TEXT_FIELDS = [
  'parent_relationship', 'parent_alternate_phone', 'preferred_language',
  'child_gender', 'child_emirates_id', 'diagnosis_suspected', 'nursery_school', 'main_concern',
  'intake_form_received_via', 'allergies', 'medical_history',
  'assessment_tool', 'assessment_report_reference', 'assessment_report_summary',
  'funding_type', 'funding_insurer', 'funding_policy_number', 'funding_notes',
  'package_agreed_by', 'package_scheduling_notes',
  'consent_signed_by', 'consent_data_photo', 'consent_signature_method', 'consent_notes',
] as const;

const INTAKE_DATE_FIELDS = [
  'child_date_of_birth', 'child_emirates_id_expiry', 'intake_form_received_on', 'assessment_date',
  'funding_approval_valid_until', 'package_start_date', 'consent_signed_date',
] as const;

/** Copies the intake-step fields present in the request onto the lead. */
function applyIntakeFields(lead: Lead, input: LeadUpdateRequest): void {
  for (const field of INTAKE_TEXT_FIELDS) {
    if (field in input) lead[field] = blankToNull(input[field]) ?? null;
  }
  for (const field of INTAKE_DATE_FIELDS) {
    if (field in input) {
      const value = blankToNull(input[field]);
      lead[field] = value ? dateCast(value.slice(0, 10)) : null;
    }
  }
  if ('assessment_clinician_id' in input) lead.assessment_clinician_id = input.assessment_clinician_id ?? null;
  if ('package_location_id' in input) lead.package_location_id = input.package_location_id ?? null;
  if ('package_sessions_per_week' in input) lead.package_sessions_per_week = input.package_sessions_per_week ?? null;
  if ('package_ids' in input) lead.package_ids = input.package_ids?.length ? input.package_ids : null;
  if ('funding_services_needed' in input) {
    // An empty list is stored as null, as in Laravel.
    lead.funding_services_needed = input.funding_services_needed?.length ? input.funding_services_needed : null;
  }
}

// ---------------------------------------------------------------------------
// Handlers
// ---------------------------------------------------------------------------

function board(): LeadsBoard {
  const user = requireUser();
  requireFeature(user, 'leads');
  const db = getDb();

  const ownerIds = new Set(db.leads.map((l) => l.assigned_to).filter((id): id is number => id !== null));
  const assignable = db.users
    .filter((u) => (u.is_active && (u.role === 'SALES_STAFF' || u.role === 'FULL_ADMIN')) || ownerIds.has(u.id))
    .map((u) => ({ id: u.id, name: staffDisplayName(u) }));

  return {
    leads: [...db.leads].sort((a, b) => b.created_at.localeCompare(a.created_at)).map((l) => present(db, l)),
    assignable_users: assignable,
    insurance_options: ['Not sure yet', ...Object.keys(INSURANCES), 'Self-pay'],
    intake_options: {
      clinicians: db.users
        .filter((u) => u.is_active && u.role === 'THERAPIST')
        .map((u) => ({ id: u.id, name: staffDisplayName(u) })),
      insurers: Object.keys(INSURANCES),
      services: SERVICES.map((sv) => sv.name),
      locations: LOCATIONS,
      packages: PACKAGES,
    },
  };
}

function show(id: number): LeadDetail {
  const user = requireUser();
  requireFeature(user, 'leads');
  const db = getDb();
  const lead = findLeadOr404(db, id);
  const log = db.leadActivities
    .filter((a) => a.lead_id === lead.id)
    .sort((a, b) => b.created_at.localeCompare(a.created_at) || b.id - a.id)
    .map((a) => presentActivity(db, a));
  return {
    success: true,
    lead: present(db, lead),
    notes_log: log.filter((a) => a.type === 'note'),
    assignment_log: log.filter((a) => a.type === 'assignment'),
    agreed_packages: PACKAGES.filter((pk) => (lead.package_ids ?? []).includes(pk.id)),
    assessment_clinician_name: nameOf(db, lead.assessment_clinician_id),
  };
}

function store(input: LeadStoreRequest): LeadMutationResponse {
  const user = requireUser();
  requireFeature(user, 'leads', true);
  validateLengths(input);
  const db = getDb();

  const lead = blankLead({ id: Math.max(0, ...db.leads.map((l) => l.id)) + 1, created_at: now() });
  applyFields(lead, input);
  // store() does no permission check on assigned_to (as in Laravel).
  lead.assigned_to = input.assigned_to ?? null;
  db.leads.push(lead);
  return { success: true, message: 'Lead created successfully!', lead: present(db, lead) };
}

function update(id: number, input: LeadUpdateRequest): LeadMutationResponse {
  const user = requireUser();
  requireFeature(user, 'leads', true);
  validateLengths(input);
  const db = getDb();
  const lead = findLeadOr404(db, id);

  if (input.status !== undefined && !STATUSES.includes(input.status)) {
    const message = 'The selected status is invalid.';
    throw new ApiError(422, { message, errors: { status: [message] } });
  }

  const incomingOwner = 'assigned_to' in input ? (input.assigned_to ?? null) : lead.assigned_to;
  if ((input.status ?? lead.status) === 'contacted' && !incomingOwner) {
    throw new ApiError(422, { message: OWNER_REQUIRED, errors: { assigned_to: [OWNER_REQUIRED] } });
  }

  const previousOwner = lead.assigned_to;
  if ('assigned_to' in input && (input.assigned_to ?? null) !== previousOwner) {
    if (previousOwner) {
      requireAction(user, 'reassign_lead_owner', 'Your access level can’t reassign a lead owner.', 'assigned_to');
    } else {
      requireAction(user, 'assign_lead_owner', 'Your access level can’t assign a lead owner.', 'assigned_to');
    }
  }

  const newlyTerminated = input.status === 'terminated' && lead.status !== 'terminated';
  if (newlyTerminated) {
    requireAction(user, 'terminate_lead', 'Your access level can’t terminate a lead.', 'status');
    if (!blankToNull(input.termination_reason)) {
      const message = 'Select a reason before terminating this lead.';
      throw new ApiError(422, { message, errors: { termination_reason: [message] } });
    }
    lead.status_before_termination = lead.status;
    lead.terminated_at = now();
  }

  applyFields(lead, input);
  if ('termination_reason' in input) lead.termination_reason = blankToNull(input.termination_reason) ?? null;
  if ('termination_note' in input) lead.termination_note = blankToNull(input.termination_note) ?? null;
  if (input.status !== undefined) lead.status = input.status;
  applyIntakeFields(lead, input);

  // Saving an intake step stamps it complete. Package needs packages picked
  // and Funding needs a funding type, otherwise the stamp is cleared.
  if (input.intake_step) {
    if (input.intake_step === 'package' && !('package_ids' in input)) lead.package_ids = null;
    const met =
      input.intake_step === 'package'
        ? (lead.package_ids ?? []).length > 0
        : input.intake_step === 'funding'
          ? !!lead.funding_type
          : true;
    lead[INTAKE_STEP_COLUMNS[input.intake_step]] = met ? now() : null;
  }

  if ('assigned_to' in input && (input.assigned_to ?? null) !== previousOwner) {
    lead.assigned_to = input.assigned_to ?? null;
    const newName = nameOf(db, lead.assigned_to);
    const body = !previousOwner && lead.assigned_to ? `Assigned to ${newName}` : previousOwner && !lead.assigned_to ? 'Unassigned' : `Reassigned to ${newName}`;
    logActivity(db, lead.id, user.id, 'assignment', body);
  }

  // All seven intake steps done → the lead becomes Enrolled automatically.
  if (intakeStepsComplete(lead) === 7 && lead.status !== 'enrolled' && lead.status !== 'terminated') {
    lead.status = 'enrolled';
  }
  lead.updated_at = now();
  return { success: true, message: 'Lead updated successfully!', lead: present(db, lead) };
}

function updateStatus(id: number, status: LeadStatus): LeadMutationResponse {
  const user = requireUser();
  requireFeature(user, 'leads', true);
  const db = getDb();
  const lead = findLeadOr404(db, id);

  if (!STATUSES.includes(status)) {
    throw new ApiError(422, { message: 'Invalid status provided', errors: { status: ['The selected status is invalid.'] } });
  }
  if (status === 'contacted' && !lead.assigned_to) {
    throw new ApiError(422, { message: OWNER_REQUIRED, errors: { assigned_to: [OWNER_REQUIRED] } });
  }
  lead.status = status;
  lead.updated_at = now();
  return { success: true, message: 'Lead status updated successfully!', lead: present(db, lead) };
}

function addNote(id: number, body: string): { success: true; note: LeadActivity } {
  const user = requireUser();
  requireFeature(user, 'leads', true);
  requireAction(user, 'add_lead_notes', 'Your access level can’t add lead notes.');
  const db = getDb();
  const lead = findLeadOr404(db, id);

  const text = body.trim();
  if (!text) {
    const message = 'The body field is required.';
    throw new ApiError(422, { message, errors: { body: [message] } });
  }
  if (text.length > 2000) {
    const message = 'The body field must not be greater than 2000 characters.';
    throw new ApiError(422, { message, errors: { body: [message] } });
  }
  return { success: true, note: presentActivity(db, logActivity(db, lead.id, user.id, 'note', text)) };
}

function restore(id: number): LeadMutationResponse {
  const user = requireUser();
  requireFeature(user, 'leads', true);
  requireAction(user, 'terminate_lead', 'Your access level can’t restore a terminated lead.');
  const db = getDb();
  const lead = findLeadOr404(db, id);
  if (lead.status !== 'terminated') {
    throw new ApiError(422, { message: 'This lead is not terminated.' });
  }
  lead.status = lead.status_before_termination ?? 'new';
  lead.termination_reason = null;
  lead.termination_note = null;
  lead.terminated_at = null;
  lead.status_before_termination = null;
  lead.updated_at = now();
  return { success: true, message: 'Lead restored.', lead: present(db, lead) };
}

function convertToPatient(id: number): { success: true; message: string; patient_id: number } {
  const user = requireUser();
  requireFeature(user, 'leads', true);
  requireAction(user, 'convert_to_client', 'Your access level can’t convert leads to clients.');
  const db = getDb();
  const lead = findLeadOr404(db, id);

  // Lead::canConvertToPatient()
  if (lead.status !== 'enrolled') {
    throw new ApiError(422, { message: 'Only enrolled leads can be converted to a patient.' });
  }
  if (intakeStepsComplete(lead) !== 7) {
    throw new ApiError(422, { message: 'Finish the remaining intake checklist steps before converting this lead.' });
  }
  if (db.patients.some((p) => p.lead_id === lead.id)) {
    throw new ApiError(422, { message: 'This lead has already been converted to a patient.' });
  }

  const stamp = now();
  const patient = {
    id: Math.max(0, ...db.patients.map((p) => p.id)) + 1,
    lead_id: lead.id,
    // Diagnosis from the Child details step, programme from the first agreed package.
    diagnosis: lead.diagnosis_suspected,
    programme: PACKAGES.find((pk) => (lead.package_ids ?? []).includes(pk.id))?.name ?? null,
    treatment_plan_review_due_at: null,
    enrolled_at: stamp,
    created_at: stamp,
    updated_at: stamp,
  };
  db.patients.push(patient);

  // The Funding step becomes the patient's first authorization — insurance only.
  if (lead.funding_insurer && (lead.funding_type ?? '').toLowerCase().includes('insurance')) {
    const rows = (lead.funding_services_needed ?? []).filter((r) => r.payer === 'Insurance');
    db.authorizations.push({
      id: Math.max(0, ...db.authorizations.map((a) => a.id)) + 1,
      patient_id: patient.id,
      payer_name: lead.funding_insurer,
      coverage_percent: INSURANCES[lead.funding_insurer] ?? 0,
      covers_services: [...new Set(rows.map((r) => r.service).filter(Boolean))],
      policy_number: lead.funding_policy_number,
      approval_reference: rows.map((r) => r.approval_reference).find(Boolean) ?? null,
      authorized_hours_total: Math.trunc(rows.reduce((sum, r) => sum + (r.approved_hours ?? 0), 0)),
      renews_at: lead.funding_approval_valid_until,
      sort_order: 0,
      created_at: stamp,
      updated_at: stamp,
    });
  }
  return { success: true, message: 'Converted to patient.', patient_id: patient.id };
}

export function createLeadsApi(): ApiClient['leads'] {
  return {
    board: () => delay(board),
    show: (id) => delay(() => show(id)),
    store: (input) => delay(() => store(input)),
    update: (id, input) => delay(() => update(id, input)),
    updateStatus: (id, status) => delay(() => updateStatus(id, status)),
    addNote: (id, body) => delay(() => addNote(id, body)),
    restore: (id) => delay(() => restore(id)),
    convertToPatient: (id) => delay(() => convertToPatient(id)),
  };
}
