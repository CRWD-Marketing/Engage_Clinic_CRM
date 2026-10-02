import type { CalendarSessionPayload } from '@/api/types';
import { todayStatusColors, type ChipColors } from '@/theme';
import { nowTime } from '@/utils/dates';

/**
 * Status chip for "My schedule today", exactly as dashboard/therapist.blade.php
 * derives it (it compares clinic-local "H:i:s" strings).
 */
export function todayStatus(session: CalendarSessionPayload, at: Date = new Date()): { label: string; colors: ChipColors } {
  const now = nowTime(at);
  const start = `${session.start_time}:00`;
  const end = `${session.end_time}:00`;

  if (session.status === 'completed') return { label: 'Completed', colors: todayStatusColors.completed };
  if (session.status === 'cancelled') return { label: 'Cancelled', colors: todayStatusColors.cancelled };
  if (session.status === 'no_show') return { label: 'No-show', colors: todayStatusColors.no_show };
  if (start <= now && end >= now) return { label: 'In session', colors: todayStatusColors.in_session };
  if (end < now) return { label: 'Awaiting update', colors: todayStatusColors.awaiting_update };
  return { label: 'Upcoming', colors: todayStatusColors.upcoming };
}
