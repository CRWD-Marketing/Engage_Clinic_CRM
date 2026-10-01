import type { Lead } from '../types';

/** Lead::INTAKE_STEPS — step key → its completion column, in checklist order. */
export const INTAKE_STEP_COLUMNS = {
  parent_contact: 'parent_contact_completed_at',
  child_details: 'child_details_completed_at',
  intake_form: 'intake_form_completed_at',
  assessment: 'assessment_completed_at',
  funding: 'funding_completed_at',
  package: 'package_completed_at',
  consent: 'consent_completed_at',
} as const satisfies Record<string, keyof Lead>;

export type IntakeStepKey = keyof typeof INTAKE_STEP_COLUMNS;

/** Lead::getIntakeStepsCompleteAttribute() */
export function intakeStepsComplete(lead: Lead): number {
  return Object.values(INTAKE_STEP_COLUMNS).filter((column) => lead[column] !== null).length;
}

/** A `leads` row with every column at its database default. */
export function blankLead(fields: Pick<Lead, 'id' | 'created_at'> & Partial<Lead>): Lead {
  return {
    child_name: null,
    child_age: null,
    parent_guardian_name: null,
    phone: null,
    email: null,
    source: null,
    campaign: null,
    city: null,
    child_age_band: null,
    interested_in: null,
    insurance: null,
    estimated_value: null,
    notes: null,
    status: 'new',
    assigned_to: null,
    follow_up_due_at: null,
    assessment_report_summary: null,
    ad_name: null,
    lead_form_name: null,
    main_concern: null,
    diagnosis_suspected: null,
    termination_reason: null,
    termination_note: null,
    terminated_at: null,
    status_before_termination: null,
    parent_contact_completed_at: null,
    child_details_completed_at: null,
    intake_form_completed_at: null,
    assessment_completed_at: null,
    funding_completed_at: null,
    package_completed_at: null,
    consent_completed_at: null,
    updated_at: fields.created_at,
    assigned_to_name: null,
    intake_steps_complete: 0,
    ...fields,
  };
}
