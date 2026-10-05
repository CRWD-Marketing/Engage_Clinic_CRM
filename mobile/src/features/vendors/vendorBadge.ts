import type { ChipColors } from '@/theme';
import { vendorBadgeColors } from '@/theme';

/** vendor/partials/status-badge.blade.php: vendor, compliance and document states onto one colour set. */
export function vendorBadge(state: string): ChipColors {
  switch (state) {
    case 'under_review':
    case 'pending':
      return vendorBadgeColors.blue;
    case 'active':
    case 'valid':
    case 'complete':
      return vendorBadgeColors.green;
    case 'on_hold':
    case 'expiring':
      return vendorBadgeColors.amber;
    case 'suspended':
    case 'expired':
      return vendorBadgeColors.red;
    case 'critical':
      return vendorBadgeColors.critical;
    default:
      return vendorBadgeColors.grey;
  }
}

export const COMPLIANCE_LABELS = { complete: 'Complete', pending: 'Pending', expired: 'Expired' } as const;
