import type { FundingServiceRow } from '@/api/types';

/**
 * A funding row comes in two shapes: the intake form saves `approved_hours`
 * and `approval_reference`, while older / seeded rows carry a free-text
 * `cover` ("96 h approved") and `approval_ref`. These read either.
 */

/** Approved hours as a number, or null when the row has none. */
export function approvedHours(row: FundingServiceRow): number | null {
  if (row.approved_hours != null) return Number(row.approved_hours);
  const match = /(\d+)/.exec(row.cover ?? '');
  return match ? Number(match[1]) : null;
}

/** "96 h approved", or null. */
export function approvedLabel(row: FundingServiceRow): string | null {
  if (row.approved_hours != null) return `${row.approved_hours} h approved`;
  return row.cover || null;
}

export function approvalReference(row: FundingServiceRow): string | null {
  return row.approval_reference || row.approval_ref || null;
}
