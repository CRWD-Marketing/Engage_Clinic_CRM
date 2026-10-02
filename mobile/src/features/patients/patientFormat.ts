import type { PatientAuthorizationSummary } from '@/api/types';

/**
 * Hours left on an authorization card / list row, as the web computes it:
 * max(0, authorized_hours_total - hoursUsed()).
 */
export function hoursLeft(auth: PatientAuthorizationSummary): number {
  return Math.max(0, (auth.authorized_hours_total ?? 0) - auth.hours_used);
}

/** "Age 5 · Autism Spectrum Disorder (Level 1)" */
export function ageAndDiagnosis(age: string | null, diagnosis: string | null): string {
  return `Age ${age ?? '—'}${diagnosis ? ` · ${diagnosis}` : ''}`;
}
