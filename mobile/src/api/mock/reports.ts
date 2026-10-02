/**
 * Mock of ReportController::reportData() (`feature:reports`): every section
 * of the Reports & analytics page, computed live from the mock database.
 */

import { addMonths, formatMonthLong, isoToYmd, monthEndOf, monthStartOf, todayYmd } from '@/utils/dates';

import type { ApiClient } from '../client';
import type { ReportAmountRow, ReportLeadSource, ReportsData } from '../types';
import { autoCompletePastSessions, NON_THERAPY_TYPES, THERAPY_TYPES } from './presenters';
import type { InvoiceRow, MockDb } from './rows';
import { laravelIso } from './seed';
import { delay, getDb, requireFeature, requireUser } from './server';

/** config/billing.php `vat_rate` and config/clinic.php `trn`. */
const VAT_RATE = 5;
const CLINIC_TRN = '100447288910003';
const DAY_MS = 86400_000;

const ACTIVITY_COLORS: Record<string, string> = {
  ABA: '#C8355F',
  Speech: '#24619C',
  OT: '#B97F24',
  Assessment: '#6E4FA8',
  Supervision: '#2E7D5B',
  'Parent training': '#8A5A10',
};
const DEFAULT_COLOR = '#8A7D6C';
const HOURS_LABELS: Record<string, string> = {
  ABA: 'ABA (1:1)',
  Speech: 'Speech therapy',
  OT: 'Occupational therapy',
  Assessment: 'Assessments',
};
const SERVICE_LABELS: Record<string, string> = { Assessment: 'Assessments' };
/** Assigned by position so two sources never share a colour. */
const SOURCE_PALETTE = ['#C8355F', '#16436E', '#1FA855', '#B97F24', '#6E4FA8', '#C13584', '#1F8FA8', '#A8461F', '#2E7D5B', '#8A7D6C'];

const round = (value: number, decimals = 0) => Math.round(value * 10 ** decimals) / 10 ** decimals;
const sum = <T>(rows: T[], pick: (row: T) => number) => rows.reduce((total, row) => total + pick(row), 0);
const issuedBetween = (i: InvoiceRow, from: string, to: string) => {
  const day = isoToYmd(i.issue_date);
  return day >= from && day <= to;
};
/** Group rows by a key and total an amount, largest first. */
function totalsBy<T>(rows: T[], key: (row: T) => string, amount: (row: T) => number): ReportAmountRow[] {
  const totals = new Map<string, number>();
  for (const row of rows) totals.set(key(row), (totals.get(key(row)) ?? 0) + amount(row));
  return [...totals].map(([label, total]) => ({ label, amount: total })).sort((a, b) => b.amount - a.amount);
}

/** revenueByMonth(): trailing six months through the current (partial) month, on `subtotal`. */
function revenueByMonth(db: MockDb, today: string) {
  const months = [5, 4, 3, 2, 1, 0].map((i) => addMonths(today, -i));
  const rows = months.map((start, i) => {
    const amount = sum(
      db.invoices.filter((inv) => issuedBetween(inv, start, monthEndOf(start))),
      (inv) => Number(inv.subtotal),
    );
    return { label: formatMonthLong(start).slice(0, 3), amount, thousands: round(amount / 1000, 1), is_current: i === 5 };
  });
  const total = sum(rows, (r) => r.amount);
  const current = rows[5];
  const previous = rows[4];
  return {
    revenue_by_month: rows,
    revenue_total_6mo: total,
    revenue_average_6mo: total / rows.length,
    revenue_best_month: [...rows].sort((a, b) => b.amount - a.amount)[0],
    revenue_mom_delta: previous.amount > 0 ? Math.round(((current.amount - previous.amount) / previous.amount) * 100) : null,
  };
}

/**
 * leadFunnel(): leads created in the last 90 days. A lead "reaches" a stage
 * when its current status is that stage or a later one; a terminated lead
 * only counts as captured.
 */
function leadFunnel(db: MockDb, now: Date) {
  const since = new Date(now.getTime() - 90 * DAY_MS).toISOString();
  const leads = db.leads.filter((l) => l.created_at >= since);
  const captured = leads.length;
  const count = (statuses: string[]) => leads.filter((l) => statuses.includes(l.status)).length;
  const enrolled = count(['enrolled']);

  const stages = [
    { label: 'Leads captured', count: captured },
    { label: 'Contacted', count: count(['contacted', 'assessment_booked', 'assessment_done', 'enrolled']) },
    { label: 'Assessment done', count: count(['assessment_done', 'enrolled']) },
    { label: 'Enrolled', count: enrolled },
  ].map((s) => ({ ...s, pct: captured > 0 ? Math.round((s.count / captured) * 100) : 0 }));

  const bySource = new Map<string, typeof leads>();
  for (const lead of leads) {
    const source = lead.source || 'Other';
    bySource.set(source, [...(bySource.get(source) ?? []), lead]);
  }
  const sources: ReportLeadSource[] = [...bySource]
    .map(([source, group]) => ({ source, count: group.length, enrolled: group.filter((l) => l.status === 'enrolled').length }))
    .sort((a, b) => b.count - a.count)
    .map((s, i) => ({
      ...s,
      pct: captured > 0 ? Math.round((s.count / captured) * 100) : 0,
      conversion_rate: s.count > 0 ? Math.round((s.enrolled / s.count) * 100) : null,
      color: SOURCE_PALETTE[i % SOURCE_PALETTE.length],
    }));
  // Only meaningful once more than one source has leads to compare.
  const ranked = [...sources].sort((a, b) => (b.conversion_rate ?? 0) - (a.conversion_rate ?? 0));

  // Days from lead creation to becoming a patient; backdated (negative) rows are excluded.
  const enrollDays = leads
    .filter((l) => l.status === 'enrolled')
    .map((l) => {
      const patient = db.patients.find((p) => p.lead_id === l.id);
      return patient ? (new Date(patient.enrolled_at).getTime() - new Date(l.created_at).getTime()) / DAY_MS : null;
    })
    .filter((days): days is number => days !== null && days >= 0)
    .sort((a, b) => a - b);
  const mid = Math.floor(enrollDays.length / 2);
  const median =
    enrollDays.length === 0 ? null : enrollDays.length % 2 === 1 ? enrollDays[mid] : round((enrollDays[mid - 1] + enrollDays[mid]) / 2, 1);

  return {
    funnel_stages: stages,
    captured_count: captured,
    conversion_rate: captured > 0 ? Math.round((enrolled / captured) * 100) : null,
    lead_sources: sources,
    best_source: sources.length > 1 ? ranked[0] : null,
    worst_source: sources.length > 1 ? ranked[ranked.length - 1] : null,
    median_enroll_days: median === null ? null : round(median, 1),
  };
}

/** therapyHours(): completed clinical sessions this month, by activity type. */
function therapyHours(db: MockDb, today: string) {
  const monthStart = monthStartOf(today);
  const sessions = db.sessions.filter(
    (s) =>
      s.session_date >= monthStart &&
      s.session_date <= monthEndOf(today) &&
      s.status === 'completed' &&
      !NON_THERAPY_TYPES.includes(s.activity_type),
  );
  const byType = totalsBy(sessions, (s) => s.activity_type, (s) => s.duration_minutes);
  return {
    therapy_month_label: formatMonthLong(today).split(' ')[0],
    therapy_total_hours: Math.round(sum(sessions, (s) => s.duration_minutes) / 60),
    therapy_hours_by_type: byType
      .map((row) => ({
        type: HOURS_LABELS[row.label] ?? row.label,
        hours: round(row.amount / 60, 1),
        color: ACTIVITY_COLORS[row.label] ?? DEFAULT_COLOR,
      }))
      .sort((a, b) => b.hours - a.hours),
  };
}

/** vatReturnSummary(): this month's non-voided invoices. */
function vatReturn(db: MockDb, today: string) {
  const invoices = db.invoices.filter((i) => issuedBetween(i, monthStartOf(today), monthEndOf(today)) && i.voided_at === null);
  const outputTax = sum(invoices, (i) => Number(i.vat_amount));
  // credit_amount is VAT-inclusive, so its tax share is backed out: amount x rate / (100 + rate).
  const credits = sum(invoices, (i) => Number(i.credit_amount));
  return {
    vat_filing_label: formatMonthLong(today),
    vat_trn: CLINIC_TRN,
    vat_rate: VAT_RATE,
    vat_standard_rated_supplies: sum(invoices, (i) => Number(i.subtotal)),
    vat_output_tax: outputTax,
    vat_credit_notes_issued: credits,
    vat_net_payable: round(outputTax - round((credits * VAT_RATE) / (100 + VAT_RATE), 2), 2),
  };
}

/** collectionRate(): collected vs invoiced over the period, and revenue per billable therapy hour. */
function collectionRate(db: MockDb, invoices: InvoiceRow[], from: string, to: string) {
  const invoiced = sum(invoices, (i) => Number(i.total));
  const collected = sum(invoices, (i) => Number(i.amount_paid));
  const minutes = sum(
    db.sessions.filter(
      (s) => s.session_date >= from && s.session_date <= to && s.status === 'completed' && THERAPY_TYPES.includes(s.activity_type),
    ),
    (s) => s.duration_minutes,
  );
  const hours = round(minutes / 60, 1);
  return {
    collection_invoiced_total: invoiced,
    collection_collected_total: collected,
    collection_rate_pct: invoiced > 0 ? Math.round((collected / invoiced) * 100) : 0,
    collection_billable_hours: hours,
    collection_revenue_per_hour: hours > 0 ? round(collected / hours, 2) : null,
  };
}

/** whyLeadsAreLost(): terminated leads in the period (or with no termination date). */
function lostLeads(db: MockDb, from: string, to: string) {
  const terminated = db.leads.filter(
    (l) => l.status === 'terminated' && (l.terminated_at === null || (isoToYmd(l.terminated_at) >= from && isoToYmd(l.terminated_at) <= to)),
  );
  // Lead::getEstimatedValueNumericAttribute()
  const value = (v: string | null) => Number((v ?? '').replace(/[^0-9.]/g, '')) || 0;
  const reasons = new Map<string, { count: number; value: number }>();
  for (const lead of terminated) {
    const reason = lead.termination_reason || 'No reason given';
    const row = reasons.get(reason) ?? { count: 0, value: 0 };
    reasons.set(reason, { count: row.count + 1, value: row.value + value(lead.estimated_value) });
  }
  return {
    lost_leads_count: terminated.length,
    lost_leads_value: sum(terminated, (l) => value(l.estimated_value)),
    lost_reasons: [...reasons].map(([reason, row]) => ({ reason, ...row })).sort((a, b) => b.count - a.count),
  };
}

/** GET /reports */
function index(): ReportsData {
  const user = requireUser();
  requireFeature(user, 'reports');
  const db = getDb();
  const now = new Date();
  autoCompletePastSessions(db, now);
  const today = todayYmd(now);

  // reportingPeriod(): the same trailing six months the revenue chart shows.
  const periodStart = addMonths(today, -5);
  const periodEnd = monthEndOf(today);
  const invoices = db.invoices.filter((i) => issuedBetween(i, periodStart, periodEnd) && i.voided_at === null);
  const invoiceIds = new Set(invoices.map((i) => i.id));
  const lineItems = db.invoiceLineItems.filter((li) => invoiceIds.has(li.invoice_id));
  const therapistName = (id: number | null) => {
    const t = db.users.find((u) => u.id === id);
    return t ? `${t.first_name} ${t.last_name}`.trim() : 'Unassigned';
  };

  return {
    period_start_label: formatMonthLong(periodStart).slice(0, 3),
    period_end_label: formatMonthLong(today),
    updated_at: laravelIso(now.toISOString()),
    ...vatReturn(db, today),
    ...collectionRate(db, invoices, periodStart, periodEnd),
    revenue_by_service: totalsBy(lineItems, (li) => li.activity_type ?? 'Other', (li) => Number(li.amount)).map((row) => ({
      label: SERVICE_LABELS[row.label] ?? row.label,
      amount: row.amount,
      color: ACTIVITY_COLORS[row.label] ?? DEFAULT_COLOR,
    })),
    revenue_by_setting: totalsBy(lineItems, (li) => li.setting || 'Not specified', (li) => Number(li.amount)),
    revenue_by_therapist: totalsBy(lineItems, (li) => therapistName(li.therapist_id), (li) => Number(li.amount)),
    ...revenueByMonth(db, today),
    ...leadFunnel(db, now),
    ...lostLeads(db, periodStart, periodEnd),
    ...therapyHours(db, today),
  };
}

export function createReportsApi(): ApiClient['reports'] {
  return { index: () => delay(index) };
}
