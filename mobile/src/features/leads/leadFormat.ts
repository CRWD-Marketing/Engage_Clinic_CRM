import type { BoardLead, Lead, LeadStatus } from '@/api/types';
import { leadColors, type ChipColors } from '@/theme';
import { diffDays, isoToYmd, todayYmd } from '@/utils/dates';

/** Board columns (lead/index.blade.php `$columnDefs`): Initial assessment absorbs two statuses. */
export const COLUMNS = [
  { key: 'new', label: 'New', short: 'New', statuses: ['new'] },
  { key: 'contacted', label: 'Contacted / Follow-up', short: 'Contacted', statuses: ['contacted'] },
  { key: 'assessment', label: 'Initial assessment', short: 'Assessment', statuses: ['assessment_booked', 'assessment_done'] },
  { key: 'enrolled', label: 'Enrolled', short: 'Enrolled', statuses: ['enrolled'] },
] as const satisfies readonly { key: string; label: string; short: string; statuses: readonly LeadStatus[] }[];

export type ColumnKey = (typeof COLUMNS)[number]['key'];

/** Card tag labels (`$statusTagLabels`). */
export const STATUS_TAG_LABEL: Record<LeadStatus, string> = {
  new: 'New',
  contacted: 'Contacted',
  assessment_booked: 'Booked',
  assessment_done: 'Assessment done',
  enrolled: 'Enrolled',
  terminated: 'Terminated',
};

/** Lead::getStatuses() labels (used in "Lead moved to …" toasts). */
export const STATUS_LABEL: Record<LeadStatus, string> = {
  new: 'New',
  contacted: 'Contacted',
  assessment_booked: 'Assessment Booked',
  assessment_done: 'Assessment Done',
  enrolled: 'Enrolled',
  terminated: 'Terminated',
};

/** The stage the "Success" button moves a lead to. */
export const NEXT_STATUS: Partial<Record<LeadStatus, LeadStatus>> = {
  new: 'contacted',
  contacted: 'assessment_booked',
  assessment_booked: 'assessment_done',
  assessment_done: 'enrolled',
};

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

/** New Lead modal source options. */
export const LEAD_SOURCES = ['Walk-in', 'Phone call', 'WhatsApp', 'Website', 'Instagram', 'Referral', 'Google', 'Event'];

/** Follow-up presets on the action panel (days from today; null = none). */
export const FOLLOW_UP_PRESETS: { label: string; days: number | null }[] = [
  { label: 'No follow-up scheduled', days: null },
  { label: 'Tomorrow', days: 1 },
  { label: 'In 2 days', days: 2 },
  { label: 'In 3 days', days: 3 },
  { label: 'In 1 week', days: 7 },
];

export function sourceBadge(source: string | null): ChipColors {
  return (source && leadColors.sourceBadge[source]) || leadColors.defaultBadge;
}

/** The due tag on a card: "Overdue 2d" / "Due today" / "Due in 3d". */
export function dueTag(lead: Pick<Lead, 'follow_up_due_at'>): { label: string; colors: ChipColors } | null {
  if (!lead.follow_up_due_at) return null;
  const days = diffDays(todayYmd(), isoToYmd(lead.follow_up_due_at));
  if (days < 0) return { label: `Overdue ${Math.abs(days)}d`, colors: leadColors.overdue };
  return { label: days === 0 ? 'Due today' : `Due in ${days}d`, colors: leadColors.due };
}

export function valueNumber(lead: Pick<Lead, 'estimated_value'>): number {
  return Number(lead.estimated_value ?? 0) || 0;
}

/** "AED 12,000" (number_format with 0 decimals). */
export function formatAed(amount: number): string {
  return `AED ${Math.round(amount).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',')}`;
}

/** Leads shown on the board: not terminated, not yet converted to a patient. */
export function activeLeads(leads: BoardLead[]): BoardLead[] {
  return leads.filter((l) => l.status !== 'terminated' && !l.has_patient);
}
