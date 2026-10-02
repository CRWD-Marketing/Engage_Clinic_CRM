import type { BillingInvoice } from '@/api/types';
import { billingStatusColors, type ChipColors } from '@/theme';
import { formatMoney } from '@/utils/format';

export const aed = (amount: number, decimals = 2) => `AED ${formatMoney(amount, decimals)}`;

export function statusColors(invoice: Pick<BillingInvoice, 'status'>): ChipColors {
  return billingStatusColors[invoice.status];
}

export const STATUS_FILTERS: { value: BillingInvoice['status'] | 'all'; label: string }[] = [
  { value: 'all', label: 'All' },
  { value: 'outstanding', label: 'Outstanding' },
  { value: 'partly_paid', label: 'Partly paid' },
  { value: 'paid', label: 'Paid' },
  { value: 'voided', label: 'Voided' },
];

/** The pre-filled invoice / reminder email (openEmail() in billing/index.blade.php). */
export function invoiceEmail(inv: BillingInvoice, kind: 'invoice' | 'reminder', clinic: string): { subject: string; message: string } {
  if (kind === 'reminder') {
    return {
      subject: `Payment reminder — tax invoice ${inv.number} (${inv.patient})`,
      message: `Dear ${inv.parent},\n\nOur records show tax invoice ${inv.number} for ${inv.patient} remains open with a balance of AED ${formatMoney(inv.balance)}. It fell due on ${inv.due_label} — ${Math.max(0, inv.days_past_due)} days ago.\n\nIf payment has already been sent, please share the transfer slip so we can close the invoice. Bank details are on the invoice.\n\nWith thanks,\n${clinic}`,
    };
  }
  return {
    subject: `Tax invoice ${inv.number} — ${inv.patient} (${inv.period})`,
    message: `Dear ${inv.parent},\n\nPlease find attached tax invoice ${inv.number} for ${inv.patient}'s sessions in ${inv.period}.\n\nAmount due: AED ${formatMoney(inv.balance)}, payable by ${inv.due_label}.\n\nWith thanks,\n${clinic}`,
  };
}
