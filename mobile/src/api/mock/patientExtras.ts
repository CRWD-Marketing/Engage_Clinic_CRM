/**
 * Mock of the rest of patient/show.blade.php and PatientController: the
 * Payments, Documents and Profile & intake tabs, "Edit details"
 * (PatientController::update) and PatientDocumentController.
 */

import { diffDays, isoToYmd, todayYmd } from '@/utils/dates';

import { ApiError } from '../errors';
import type {
  PatientDocumentItem,
  PatientDocumentRequest,
  PatientEditOptions,
  PatientPayments,
  PatientProfile,
  PatientUpdateRequest,
} from '../types';
import { INSURANCES, LOCATIONS, PACKAGE_SEEDS } from './catalog';
import { assertAssignedTherapist, findPatientOr404 } from './patientAccess';
import type { InvoiceRow, MockDb, PatientDocumentRow, PatientRow, UserRow } from './rows';
import { dateCast, laravelIso } from './seed';
import { getDb, requireFeature, requireUser } from './server';

/** PatientDocument::TYPES / RENEWAL_WINDOW_DAYS */
export const DOCUMENT_TYPES = ['Assessment report', 'Progress review', 'Authorization', 'Consent', 'Identity', 'Invoice / receipt', 'Correspondence'];
const RENEWAL_WINDOW_DAYS = 45;

const now = () => laravelIso(new Date().toISOString());
const isDate = (v: unknown): v is string => typeof v === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(v);
const filled = (v: unknown) => v !== undefined && v !== null && v !== '';
function fail(field: string, message: string): ApiError {
  return new ApiError(422, { message, errors: { [field]: [message] } });
}

/** Invoice::paymentStatusLabel(): from what is still owed after receipts and credit. */
function paymentStatusLabel(i: InvoiceRow): string {
  if (i.voided_at) return 'Voided';
  const paid = Number(i.amount_paid);
  const balance = Math.round((Number(i.total) - Number(i.credit_amount) - paid) * 100) / 100;
  if (balance <= 0.01) return 'Paid';
  return paid > 0 ? 'Partly paid' : 'Unpaid';
}

/** Payments tab. Billed is VAT-exclusive (`subtotal`), as the web sums it. */
export function paymentsFor(db: MockDb, patient: PatientRow): PatientPayments {
  const invoices = db.invoices
    .filter((i) => i.patient_id === patient.id)
    .sort((a, b) => b.issue_date.localeCompare(a.issue_date));
  const billed = invoices.reduce((sum, i) => sum + Number(i.subtotal), 0);
  const collected = invoices.reduce((sum, i) => sum + Number(i.amount_paid), 0);
  return {
    billed_total: billed,
    collected_total: collected,
    outstanding_total: billed - collected,
    invoices: invoices.map((i) => ({
      id: i.id,
      invoice_number: i.invoice_number,
      issue_date: i.issue_date,
      period: i.period,
      subtotal: i.subtotal,
      amount_paid: i.amount_paid,
      payment_method: i.payment_method,
      payment_status_label: paymentStatusLabel(i),
    })),
  };
}

function presentDocument(db: MockDb, viewer: UserRow, d: PatientDocumentRow, today: string): PatientDocumentItem {
  const uploader = db.users.find((u) => u.id === d.uploaded_by);
  // PatientDocument::expiryStatus()
  let expiry: Pick<PatientDocumentItem, 'expiry_label' | 'expiry_variant'> = { expiry_label: 'No expiry', expiry_variant: 'neutral' };
  if (d.expires_at) {
    const day = isoToYmd(d.expires_at);
    const [y, m, dd] = day.split('-');
    const label = `${dd} ${['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'][Number(m) - 1]} ${y}`;
    expiry =
      diffDays(today, day) <= RENEWAL_WINDOW_DAYS
        ? { expiry_label: `Expires ${label} — renew`, expiry_variant: 'warn' }
        : { expiry_label: `Valid to ${label}`, expiry_variant: 'ok' };
  }
  return {
    id: d.id,
    name: d.name,
    type: d.type,
    // uploaderLabel()
    uploader_label: d.uploaded_by === viewer.id ? 'You' : uploader ? `${uploader.first_name} ${uploader.last_name}`.trim() : 'System',
    created_at: d.created_at,
    expires_at: d.expires_at,
    has_file: d.file_path !== null,
    ...expiry,
  };
}

/** Documents tab, newest first. */
export function documentsFor(db: MockDb, viewer: UserRow, patient: PatientRow, today: string): PatientDocumentItem[] {
  return db.patientDocuments
    .filter((d) => d.patient_id === patient.id)
    .sort((a, b) => b.created_at.localeCompare(a.created_at))
    .map((d) => presentDocument(db, viewer, d, today));
}

/** The values patient/show.blade.php computes for the Profile & intake tab. */
export function profileFor(db: MockDb, patient: PatientRow): PatientProfile {
  const lead = db.leads.find((l) => l.id === patient.lead_id);
  const packages = PACKAGE_SEEDS.filter((p) => (lead?.package_ids ?? []).includes(p.id));
  const unique = <T,>(values: T[]) => [...new Set(values.filter(Boolean))];
  const rates = unique(packages.map((p) => p.rate));
  const settings = unique(packages.map((p) => p.delivery_mode));
  const rows = lead?.funding_services_needed ?? [];
  const payers = unique(rows.map((r) => r.payer));
  const insuranceHours = rows
    .filter((r) => r.payer === 'Insurance')
    .reduce((sum, r) => sum + (r.approved_hours ?? r.hours_per_week ?? 0), 0);
  const nameOf = (id: number | null | undefined) => {
    const u = db.users.find((x) => x.id === id);
    return u ? `${u.first_name} ${u.last_name}`.trim() : null;
  };
  return {
    package_names: packages.map((p) => p.name),
    package_hours_per_week: packages.reduce((sum, p) => sum + p.hours_per_week, 0),
    package_rate_per_hour: rates.length === 1 ? rates[0] : rates.length > 1 ? 'Mixed' : null,
    // Package::total_excl_vat = hours_per_week x rate
    package_value_excl_vat: packages.reduce((sum, p) => sum + p.hours_per_week * p.rate, 0),
    package_location: LOCATIONS.find((l) => l.id === lead?.package_location_id)?.name ?? null,
    package_setting: settings.length === 1 ? settings[0] : settings.length > 1 ? 'Mixed' : null,
    funding_summary: payers.length > 1 ? 'Mixed — insurance + self pay' : (payers[0] ?? lead?.funding_type ?? null),
    funding_hours_needed: insuranceHours > 0 ? insuranceHours : null,
    assessment_clinician_name: nameOf(lead?.assessment_clinician_id),
    lead_owner_name: nameOf(lead?.assigned_to),
  };
}

/** Option lists for the "Client details" form: active packages and insurers. */
export function editOptions(): PatientEditOptions {
  return {
    packages: [...PACKAGE_SEEDS]
      .sort((a, b) => a.name.localeCompare(b.name))
      .map((p) => ({ name: p.name, label: `${p.name} — ${p.hours_per_week}h/wk @ AED ${p.rate}/hr` })),
    insurances: Object.keys(INSURANCES).sort(),
    document_types: DOCUMENT_TYPES,
  };
}

/** PUT /patient/{id} — "Edit details". Coordinators' clinical fields are silently dropped. */
export function updatePatient(id: number, input: PatientUpdateRequest): { success: true; message: string } {
  const user = requireUser();
  requireFeature(user, 'patients', true);
  const db = getDb();
  const patient = findPatientOr404(db, id);
  assertAssignedTherapist(db, user, patient);

  const max = (field: keyof PatientUpdateRequest, limit: number, label: string) => {
    const v = input[field];
    if (typeof v === 'string' && v.length > limit) throw fail(field, `The ${label} field must not be greater than ${limit} characters.`);
  };
  max('diagnosis', 255, 'diagnosis');
  max('programme', 255, 'programme');
  max('child_name', 255, 'child name');
  max('parent_guardian_name', 255, 'parent guardian name');
  max('phone', 20, 'phone');
  max('clinical_note', 2000, 'clinical note');
  max('insurance', 100, 'insurance');
  if (filled(input.enrolled_at) && !isDate(input.enrolled_at)) throw fail('enrolled_at', 'The enrolled at field must be a valid date.');
  if (filled(input.renews_at) && !isDate(input.renews_at)) throw fail('renews_at', 'The renews at field must be a valid date.');
  if (filled(input.child_age) && (!Number.isInteger(input.child_age) || input.child_age! < 0 || input.child_age! > 25)) {
    throw fail('child_age', 'The child age field must be between 0 and 25.');
  }
  if (filled(input.authorized_hours_total) && (!Number.isInteger(input.authorized_hours_total) || input.authorized_hours_total! < 0)) {
    throw fail('authorized_hours_total', 'The authorized hours total field must be at least 0.');
  }

  const stamp = now();
  const isCoordinator = user.role === 'COORDINATOR';
  if (!isCoordinator) {
    if (input.diagnosis !== undefined) patient.diagnosis = input.diagnosis || null;
    if (input.programme !== undefined) patient.programme = input.programme || null;
    if (filled(input.enrolled_at)) patient.enrolled_at = dateCast(input.enrolled_at as string);
    patient.updated_at = stamp;
  }

  // Name, age, parent and phone live on the lead; only non-empty values are written.
  const lead = db.leads.find((l) => l.id === patient.lead_id);
  if (lead) {
    if (filled(input.child_name)) lead.child_name = input.child_name!;
    if (filled(input.child_age)) lead.child_age = String(input.child_age);
    if (filled(input.parent_guardian_name)) lead.parent_guardian_name = input.parent_guardian_name!;
    if (filled(input.phone)) lead.phone = input.phone!;
    lead.updated_at = stamp;
  }

  // Insurance / hours / renewal update the primary authorization, or create one when a payer is known.
  if (filled(input.insurance) || filled(input.authorized_hours_total) || filled(input.renews_at)) {
    const primary = db.authorizations.filter((a) => a.patient_id === patient.id).sort((a, b) => a.sort_order - b.sort_order)[0];
    const payer = filled(input.insurance) ? input.insurance! : primary?.payer_name;
    if (payer) {
      const programme = patient.programme ?? '';
      const inferred = ['ABA', 'Speech', 'OT'].filter((t) => programme.includes(t));
      const values = {
        payer_name: payer,
        coverage_percent: primary?.coverage_percent ?? INSURANCES[payer] ?? 0,
        covers_services: primary?.covers_services ?? (inferred.length > 0 ? inferred : ['ABA']),
        authorized_hours_total: filled(input.authorized_hours_total) ? input.authorized_hours_total! : (primary?.authorized_hours_total ?? null),
        renews_at: filled(input.renews_at) ? dateCast(input.renews_at as string) : (primary?.renews_at ?? null),
        updated_at: stamp,
      };
      if (primary) Object.assign(primary, values);
      else {
        db.authorizations.push({
          id: Math.max(0, ...db.authorizations.map((a) => a.id)) + 1,
          patient_id: patient.id,
          policy_number: null,
          approval_reference: null,
          sort_order: 0,
          created_at: stamp,
          ...values,
        });
      }
    }
  }

  if (!isCoordinator && filled(input.clinical_note)) {
    db.patientNotes.push({
      id: Math.max(0, ...db.patientNotes.map((n) => n.id)) + 1,
      patient_id: patient.id,
      user_id: user.id,
      body: input.clinical_note!,
      flagged: false,
      flag_reason: null,
      signed_off_at: null,
      signed_off_by: null,
      created_at: stamp,
      updated_at: stamp,
    });
  }
  return { success: true, message: 'Patient details updated.' };
}

/** POST /patient/{id}/documents — a record (name, type, expiry), not a file upload. */
export function addDocument(id: number, input: PatientDocumentRequest): { success: true; document: PatientDocumentItem } {
  const user = requireUser();
  requireFeature(user, 'patients', true);
  const db = getDb();
  const patient = findPatientOr404(db, id);
  const name = input.name.trim();
  if (!name) throw fail('name', 'The name field is required.');
  if (name.length > 255) throw fail('name', 'The name field must not be greater than 255 characters.');
  if ((input.type ?? '').length > 100) throw fail('type', 'The type field must not be greater than 100 characters.');
  if (filled(input.expires_at) && !isDate(input.expires_at)) throw fail('expires_at', 'The expires at field must be a valid date.');

  const stamp = now();
  const row: PatientDocumentRow = {
    id: Math.max(0, ...db.patientDocuments.map((d) => d.id)) + 1,
    patient_id: patient.id,
    uploaded_by: user.id,
    name,
    type: input.type || null,
    expires_at: filled(input.expires_at) ? dateCast(input.expires_at as string) : null,
    file_path: null,
    created_at: stamp,
    updated_at: stamp,
  };
  db.patientDocuments.push(row);
  return { success: true, document: presentDocument(db, user, row, todayYmd()) };
}

/** DELETE /patient/{id}/documents/{document} */
export function deleteDocument(id: number, documentId: number): { success: true } {
  const user = requireUser();
  requireFeature(user, 'patients', true);
  const db = getDb();
  const patient = findPatientOr404(db, id);
  const index = db.patientDocuments.findIndex((d) => d.id === documentId && d.patient_id === patient.id);
  if (index === -1) throw new ApiError(404, { message: 'Not found.' });
  db.patientDocuments.splice(index, 1);
  return { success: true };
}
