import type { CalendarSessionPayload } from '@/api/types';
import { sessionStatusTagColors } from '@/theme';

/**
 * Status tag on a "My calendar" card (calendar/my.blade.php): cancelled and
 * no-show use statusLabel(), completed reads "Completed", anything else is
 * "Upcoming".
 */
export function sessionTag(s: CalendarSessionPayload): { label: string; color: string; cancelled: boolean } {
  if (s.status === 'cancelled') return { label: s.status_label, color: sessionStatusTagColors.cancelled, cancelled: true };
  if (s.status === 'no_show') return { label: s.status_label, color: sessionStatusTagColors.no_show, cancelled: false };
  if (s.status === 'completed') return { label: 'Completed', color: sessionStatusTagColors.completed, cancelled: false };
  return { label: 'Upcoming', color: sessionStatusTagColors.upcoming, cancelled: false };
}

/** CalendarSession::NON_THERAPY_TYPES */
const NON_THERAPY_TYPES = ['Supervision', 'Observation', 'Admin time', 'Training', 'Meeting'];

/** CalendarSession::scopeDirectTherapy(): therapy types with a real patient attached. */
export function isDirectTherapy(s: CalendarSessionPayload): boolean {
  return !NON_THERAPY_TYPES.includes(s.activity_type) && (s.patient_id !== null || s.patient_ids !== null);
}

/** Only completed sessions offer "+ Add session note" (the web UI rule). */
export function canAddTherapistNote(s: CalendarSessionPayload, viewerId: number): boolean {
  return s.status === 'completed' && s.therapist_id === viewerId;
}
