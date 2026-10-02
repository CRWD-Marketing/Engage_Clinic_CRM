import type { AttendanceState, CancelPolicy, LedgerRow } from '@/api/types';

/** The attendance choices of the new-invoice picker (ATT_OPTIONS in billing/index.blade.php). */
export const ATTENDANCE_OPTIONS: { value: AttendanceState; label: string }[] = [
  { value: 'completed', label: 'Completed' },
  { value: 'no_show', label: 'No-show' },
  { value: 'cancelled_late', label: 'Cancelled — late' },
  { value: 'cancelled_notice', label: 'Cancelled — with notice' },
  { value: 'cancelled_clinic', label: 'Cancelled — clinic' },
];

/** An attendance correction made in the picker. */
export type AttendanceEdit = { state: AttendanceState; notice_hours: number | null };

/** Family cancellations are priced off the notice given, so those two states carry an hours box. */
export const takesNotice = (state: AttendanceState) => state === 'cancelled_late' || state === 'cancelled_notice';

const defaultNotice = (state: AttendanceState, policy: CancelPolicy) =>
  state === 'cancelled_notice' ? policy.notice_hours : Math.max(0, policy.notice_hours - 1);

/** Picking a state moves the hours with it: 24 h means free, 23 h means the late fee. */
export function editForState(state: AttendanceState, policy: CancelPolicy): AttendanceEdit {
  return { state, notice_hours: takesNotice(state) ? defaultNotice(state, policy) : null };
}

/** Typing the hours drives the state, because the hours are what price the cancellation. */
export function editForNotice(edit: AttendanceEdit, text: string, policy: CancelPolicy): AttendanceEdit {
  const hours = Number(text);
  if (text.trim() === '' || Number.isNaN(hours) || hours < 0) return { ...edit, notice_hours: null };
  return { state: hours >= policy.notice_hours ? 'cancelled_notice' : 'cancelled_late', notice_hours: hours };
}

/**
 * Re-price one picker row the way the ledger would (repriceRow() in
 * billing/index.blade.php), so the amount follows the attendance without a
 * round trip. The invoice itself is still composed by the server on preview.
 */
export function repriceRow(row: LedgerRow, edit: AttendanceEdit | undefined, policy: CancelPolicy, vatRate: number): LedgerRow {
  if (!edit) return row;
  const notice = takesNotice(edit.state) ? (edit.notice_hours ?? defaultNotice(edit.state, policy)) : null;
  const factor =
    notice !== null ? (notice >= policy.notice_hours ? 0 : policy.late_pct / 100) : edit.state === 'completed' ? 1 : edit.state === 'no_show' ? policy.no_show_pct / 100 : 0;
  const billHours = factor === 0 ? 0 : row.hours;
  const net = billHours * row.rate * factor;
  const rule =
    edit.notice_hours !== null && takesNotice(edit.state)
      ? `Cancelled ${edit.notice_hours} h ahead — ${factor === 0 ? 'not charged' : `${policy.late_pct}% charged`}`
      : {
          completed: 'Attended — 100% charged',
          no_show: `No-show — ${policy.no_show_pct}% charged`,
          cancelled_late: `Cancelled late — ${policy.late_pct}% charged`,
          cancelled_notice: 'Cancelled with notice — not charged',
          cancelled_clinic: 'Cancelled by clinic — not charged',
        }[edit.state];
  return { ...row, attendance: edit.state, notice_hours: edit.notice_hours, factor, bill_hours: billHours, net, vat: net * vatRate, gross: net * (1 + vatRate), charge_rule: rule };
}
