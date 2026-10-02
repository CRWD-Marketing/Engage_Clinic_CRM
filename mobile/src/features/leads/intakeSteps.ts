import type { IntakeOptions, IntakeStepKey, Lead, LeadDetail, LeadUpdateRequest } from '@/api/types';
import { formatDayMonthYearShort, isoToYmd } from '@/utils/dates';

type FieldName = keyof LeadUpdateRequest;

type BaseField = { name: FieldName; label: string; required?: boolean; placeholder?: string };

/**
 * One input on an intake step form (the "ic-modal" forms in
 * lead/index.blade.php). `options` is either a fixed list or the name of a
 * list in IntakeOptions.
 */
export type IntakeField =
  | (BaseField & { kind: 'text' | 'multiline' | 'date' | 'number' | 'email' | 'phone' })
  | (BaseField & { kind: 'select'; options: string[] })
  | (BaseField & { kind: 'select-insurer' | 'select-clinician' | 'select-location' })
  | { kind: 'services'; name: 'funding_services_needed'; label: string; required: true }
  | { kind: 'packages'; name: 'package_ids'; label: string; required: true };

export type IntakeStep = {
  key: IntakeStepKey;
  /** Completion column on the lead. */
  column: keyof Lead;
  title: string;
  subtitle: string;
  fields: IntakeField[];
  /** The one-line summary shown on the checklist once the step is done. */
  summary: (detail: LeadDetail) => string;
};

const date = (iso: string | null) => (iso ? formatDayMonthYearShort(isoToYmd(iso)) : null);
const join = (parts: (string | null | undefined)[]) => parts.filter(Boolean).join(' · ');

export const INTAKE_STEPS: IntakeStep[] = [
  {
    key: 'parent_contact',
    column: 'parent_contact_completed_at',
    title: 'Parent contact verified',
    subtitle: 'Phone and email confirmed with the parent',
    fields: [
      { kind: 'text', name: 'parent_guardian_name', label: 'Parent / guardian name', required: true },
      { kind: 'select', name: 'parent_relationship', label: 'Relationship', required: true, options: ['Mother', 'Father', 'Guardian'] },
      { kind: 'phone', name: 'phone', label: 'Mobile number', required: true },
      { kind: 'phone', name: 'parent_alternate_phone', label: 'Alternate number' },
      { kind: 'email', name: 'email', label: 'Email', required: true },
      { kind: 'select', name: 'preferred_language', label: 'Preferred language', required: true, options: ['Arabic', 'English', 'Both'] },
    ],
    summary: ({ lead }) => join([lead.parent_guardian_name, lead.parent_relationship, lead.phone]),
  },
  {
    key: 'child_details',
    column: 'child_details_completed_at',
    title: 'Child details complete',
    subtitle: 'Full name, date of birth, diagnosis / concern',
    fields: [
      { kind: 'text', name: 'child_name', label: 'Child full name', required: true },
      { kind: 'date', name: 'child_date_of_birth', label: 'Date of birth', required: true },
      { kind: 'select', name: 'child_gender', label: 'Gender', required: true, options: ['Male', 'Female'] },
      { kind: 'text', name: 'child_emirates_id', label: 'Emirates ID number', required: true },
      { kind: 'date', name: 'child_emirates_id_expiry', label: 'Emirates ID expiry' },
      { kind: 'text', name: 'diagnosis_suspected', label: 'Diagnosis / suspected', required: true },
      { kind: 'text', name: 'nursery_school', label: 'Nursery / school' },
      { kind: 'multiline', name: 'main_concern', label: 'Main concern', required: true },
    ],
    summary: ({ lead }) => join([lead.child_name, date(lead.child_date_of_birth), lead.child_gender]),
  },
  {
    key: 'intake_form',
    column: 'intake_form_completed_at',
    title: 'Intake form received',
    subtitle: 'Signed intake form returned by the family',
    fields: [
      { kind: 'date', name: 'intake_form_received_on', label: 'Form received on', required: true },
      { kind: 'select', name: 'intake_form_received_via', label: 'Received via', required: true, options: ['Email', 'WhatsApp', 'In person'] },
      { kind: 'text', name: 'allergies', label: 'Allergies' },
      { kind: 'multiline', name: 'medical_history', label: 'Medical history', required: true },
    ],
    summary: ({ lead }) => join([date(lead.intake_form_received_on), lead.intake_form_received_via, lead.allergies]),
  },
  {
    key: 'assessment',
    column: 'assessment_completed_at',
    title: 'Consultation / assessment done',
    subtitle: 'Clinical report filed against the child',
    fields: [
      { kind: 'date', name: 'assessment_date', label: 'Assessment date', required: true },
      { kind: 'select-clinician', name: 'assessment_clinician_id', label: 'Clinician', required: true },
      { kind: 'select', name: 'assessment_tool', label: 'Assessment tool', required: true, options: ['ADOS-2', 'VB-MAPP', 'PLS-5', 'Clinical observation'] },
      { kind: 'text', name: 'assessment_report_reference', label: 'Report reference' },
      { kind: 'multiline', name: 'assessment_report_summary', label: 'Report summary', required: true },
    ],
    summary: (d) => join([date(d.lead.assessment_date), d.lead.assessment_tool, d.assessment_clinician_name]),
  },
  {
    key: 'funding',
    column: 'funding_completed_at',
    title: 'Funding confirmed',
    subtitle: 'Insurance approval or self-pay agreed in writing',
    fields: [
      { kind: 'select', name: 'funding_type', label: 'Funding type', required: true, options: ['Insurance', 'Self pay', 'Mixed — insurance + self pay'] },
      { kind: 'select-insurer', name: 'funding_insurer', label: 'Insurer / payer' },
      { kind: 'date', name: 'funding_approval_valid_until', label: 'Approval valid until' },
      { kind: 'text', name: 'funding_policy_number', label: 'Policy number' },
      { kind: 'services', name: 'funding_services_needed', label: 'Services needed — who pays for each', required: true },
      { kind: 'multiline', name: 'funding_notes', label: 'Funding notes' },
    ],
    summary: ({ lead }) => join([lead.funding_type, lead.funding_insurer]),
  },
  {
    key: 'package',
    column: 'package_completed_at',
    title: 'Package agreed',
    subtitle: 'Hours, location and rate signed off by the parent',
    fields: [
      { kind: 'select-location', name: 'package_location_id', label: 'Location', required: true },
      { kind: 'packages', name: 'package_ids', label: 'Package(s) agreed — pick one or more', required: true },
      { kind: 'date', name: 'package_start_date', label: 'Start date', required: true },
      { kind: 'number', name: 'package_sessions_per_week', label: 'Sessions / week', required: true },
      { kind: 'text', name: 'package_agreed_by', label: 'Agreed by (parent)', required: true },
      { kind: 'multiline', name: 'package_scheduling_notes', label: 'Scheduling notes' },
    ],
    summary: (d) =>
      join([
        d.agreed_packages.map((p) => p.name).join(', ') || null,
        d.lead.package_sessions_per_week ? `${d.lead.package_sessions_per_week}x/week` : null,
        d.lead.package_agreed_by,
      ]),
  },
  {
    key: 'consent',
    column: 'consent_completed_at',
    title: 'Consent & terms signed',
    subtitle: 'Service agreement and data consent on file',
    fields: [
      { kind: 'date', name: 'consent_signed_date', label: 'Service agreement signed', required: true },
      { kind: 'text', name: 'consent_signed_by', label: 'Signed by', required: true },
      { kind: 'select', name: 'consent_data_photo', label: 'Data & photo consent', required: true, options: ['Yes', 'No'] },
      { kind: 'select', name: 'consent_signature_method', label: 'Signature method', options: ['In person', 'e-Sign', 'Scanned copy'] },
      { kind: 'multiline', name: 'consent_notes', label: 'Notes' },
    ],
    summary: ({ lead }) => join([date(lead.consent_signed_date), lead.consent_signed_by, lead.consent_signature_method]),
  },
];

/** Options for a `select-*` field, as {value, label} pairs. */
export function optionsFor(field: IntakeField, options: IntakeOptions): { value: string | number; label: string }[] {
  switch (field.kind) {
    case 'select':
      return field.options.map((o) => ({ value: o, label: o }));
    case 'select-insurer':
      return options.insurers.map((o) => ({ value: o, label: o }));
    case 'select-clinician':
      return options.clinicians.map((c) => ({ value: c.id, label: c.name }));
    case 'select-location':
      return options.locations.map((l) => ({ value: l.id, label: l.name }));
    default:
      return [];
  }
}
