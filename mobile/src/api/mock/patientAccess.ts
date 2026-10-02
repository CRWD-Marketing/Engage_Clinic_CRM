/**
 * Patient lookups shared by the patient handlers (kept apart so the
 * handler files don't import each other).
 */

import { ApiError } from '../errors';
import type { MockDb, PatientRow, UserRow } from './rows';

/**
 * PatientController::assertAssignedTherapist(): a therapist may only reach a
 * patient they have a session with (patient_id match, as in Laravel).
 */
export function assertAssignedTherapist(db: MockDb, user: UserRow, patient: PatientRow): void {
  if (user.role !== 'THERAPIST') return;
  const assigned = db.sessions.some((s) => s.therapist_id === user.id && s.patient_id === patient.lead_id);
  if (!assigned) throw new ApiError(403, { message: 'This patient is not assigned to you.' });
}

export function findPatientOr404(db: MockDb, id: number): PatientRow {
  const patient = db.patients.find((p) => p.id === id);
  if (!patient) throw new ApiError(404, { message: 'Not found.' });
  return patient;
}
