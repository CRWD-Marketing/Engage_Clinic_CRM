/**
 * Mock of the billing page (BillingController::index) and the invoice
 * actions (InvoiceController: payments, credit notes, void / reissue, email).
 *
 * The real money rules live on the server. This mock keeps the same
 * permissions, validation messages and status logic so the screens behave
 * the same, but its amounts come from simple seeded invoices.
 *
 * The second half is the new-invoice flow (session ledger, preview, issue),
 * prepaid top-ups, aging and family statements. It prices sessions the way
 * the server's SessionLedger does, with two simplifications: every client
 * is billed at the default hourly rate, and an authorization with hours on
 * it is never treated as used up.
 */

import { canDo, levelFor } from '@/auth/permissions';
import {
  addDays,
  diffDays,
  formatDayMonthYear,
  formatDayMonthYearShort,
  formatMonthLong,
  formatMonthYear,
  isoToYmd,
  monthStartOf,
  todayYmd,
} from '@/utils/dates';

import type { ApiClient } from '../client';
import { ApiError } from '../errors';
import type {
  AttendanceState,
  BillingAging,
  BillingInvoice,
  BillingOverview,
  BillingPatient,
  FamilyStatement,
  InvoicePreview,
  InvoicePreviewLine,
  LedgerRow,
  NewInvoiceRequest,
  PatientLedger,
  InvoiceCreditRequest,
  InvoiceEmailRequest,
  InvoicePaymentRequest,
  InvoiceVoidRequest,
} from '../types';
import type { CalendarSessionRow, InvoiceRow, MockDb, PatientRow, UserRow } from './rows';
import { NON_THERAPY_TYPES } from './presenters';
import { dateCast, laravelIso } from './seed';
import { delay, EMAIL_PATTERN, getDb, requireFeature, requireUser, toApiUser } from './server';

/** Payment::METHODS */
export const PAYMENT_METHODS = ['Bank transfer', 'Card', 'Cash', 'Cheque', 'Insurance remittance'];
/** config/billing.php `due_days` */
const DUE_DAYS = 30;

const now = () => laravelIso(new Date().toISOString());
const round2 = (n: number) => Math.round(n * 100) / 100;
const money = (n: number) => n.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
const fail = (field: string, message: string) => new ApiError(422, { message, errors: { [field]: [message] } });

const paidAmount = (db: MockDb, i: InvoiceRow) => db.payments.filter((p) => p.invoice_id === i.id).reduce((sum, p) => sum + Number(p.amount), 0);
/** Invoice::balance(): total − credit − paid. */
const balanceOf = (db: MockDb, i: InvoiceRow) => round2(Number(i.total) - Number(i.credit_amount) - paidAmount(db, i));

/** Invoice::billingStatus() */
function billingStatus(db: MockDb, i: InvoiceRow): BillingInvoice['status'] {
  if (i.voided_at) return 'voided';
  if (balanceOf(db, i) <= 0.01) return 'paid';
  return paidAmount(db, i) > 0 ? 'partly_paid' : 'outstanding';
}

/** Invoice::syncPaymentColumns() */
function syncPaymentColumns(db: MockDb, i: InvoiceRow) {
  const payments = db.payments.filter((p) => p.invoice_id === i.id);
  i.amount_paid = paidAmount(db, i).toFixed(2);
  i.payment_method = payments.at(-1)?.method ?? null;
  if (!i.voided_at) {
    if (balanceOf(db, i) <= 0.01 && i.status !== 'rejected') i.status = 'paid';
    else if (i.status === 'paid') i.status = 'issued';
  }
}

/** InvoicePresenter::row() */
export function invoiceRow(db: MockDb, i: InvoiceRow): BillingInvoice {
  const patient = db.patients.find((p) => p.id === i.patient_id);
  const lead = db.leads.find((l) => l.id === patient?.lead_id);
  const status = billingStatus(db, i);
  const credit = Number(i.credit_amount);
  const due = isoToYmd(i.due_date);
  const days = diffDays(due, todayYmd());
  const numberOf = (id: number | null) => db.invoices.find((x) => x.id === id)?.invoice_number ?? null;
  return {
    id: i.id,
    number: i.invoice_number,
    patient_id: i.patient_id,
    patient: lead?.child_name ?? '—',
    parent: lead?.parent_guardian_name ?? '',
    parent_email: lead?.email ?? null,
    payer: i.payer || 'Self-pay',
    period: formatMonthYear(isoToYmd(i.period)),
    issued: isoToYmd(i.issue_date),
    issued_label: formatDayMonthYear(isoToYmd(i.issue_date)),
    due,
    due_label: formatDayMonthYear(due),
    net: Number(i.subtotal),
    vat: Number(i.vat_amount),
    total: Number(i.total),
    insurer_share: Number(i.insurance_coverage_amount),
    family_share: round2(Number(i.total) - Number(i.insurance_coverage_amount)),
    paid: paidAmount(db, i),
    credit,
    credit_reason: i.credit_reason,
    balance: Math.max(0, balanceOf(db, i)),
    status,
    status_label: status === 'voided' ? 'Voided' : status === 'paid' ? (credit > 0 ? 'Paid · credited' : 'Paid') : status === 'partly_paid' ? 'Partly paid' : 'Outstanding',
    claim_status: i.status,
    claim_reference: i.claim_reference,
    voided: i.voided_at !== null,
    voided_on: i.voided_at ? formatDayMonthYear(isoToYmd(i.voided_at)) : null,
    void_reason: i.void_reason,
    replaces: numberOf(i.replaces_invoice_id),
    replaced_by: numberOf(i.replaced_by_invoice_id),
    sent_to: i.sent_to,
    sent_at: i.sent_at ? formatDayMonthYear(isoToYmd(i.sent_at)) : null,
    reminder_sent_at: i.reminder_sent_at ? formatDayMonthYear(isoToYmd(i.reminder_sent_at)) : null,
    reminders_count: i.reminders_count,
    days_past_due: days,
    age_label: days > 0 ? `${days} d overdue` : `${Math.abs(days)} d to due`,
    receipts: db.payments
      .filter((p) => p.invoice_id === i.id)
      .map((p) => ({ id: p.id, number: p.receipt_number, amount: Number(p.amount), method: p.method, date: formatDayMonthYear(isoToYmd(p.received_on)), reference: p.reference })),
  };
}

const canInvoice = (user: UserRow) => canDo(toApiUser(user), 'create_invoice') && levelFor(toApiUser(user), 'billing') !== 'view';

/** GET /billing — the parts of the page the app shows so far. */
function overview(): BillingOverview {
  const user = requireUser();
  requireFeature(user, 'billing');
  const db = getDb();
  const today = todayYmd();
  const monthStart = monthStartOf(today);
  const live = db.invoices.filter((i) => !i.voided_at);
  const mtd = live.filter((i) => isoToYmd(i.issue_date) >= monthStart);
  const openClaims = live.filter((i) => i.payer !== 'Self-pay' && (i.status === 'submitted' || i.status === 'pending_info'));

  // Revenue by payer this month: the insurer's share, and the family's share as Self-pay.
  const byPayer = new Map<string, number>();
  const add = (payer: string, amount: number) => byPayer.set(payer, (byPayer.get(payer) ?? 0) + amount);
  for (const i of mtd) {
    if (Number(i.insurance_coverage_amount) > 0) add(i.payer, Number(i.insurance_coverage_amount));
    add('Self-pay', Number(i.total) - Number(i.insurance_coverage_amount));
  }
  const payerTotal = [...byPayer.values()].reduce((a, b) => a + b, 0) || 1;
  const insurers = [...new Set(db.authorizations.map((a) => a.payer_name).filter((n) => !/self/i.test(n)))].sort();

  return {
    can_invoice: canInvoice(user),
    month_label: formatMonthLong(today),
    payer_summary: `${insurers.slice(0, 3).join(', ')} claims`,
    tiles: {
      invoiced_mtd: mtd.reduce((sum, i) => sum + Number(i.total), 0),
      collected_mtd: db.payments.filter((p) => isoToYmd(p.received_on) >= monthStart).reduce((sum, p) => sum + Number(p.amount), 0),
      outstanding_claims: openClaims.reduce((sum, i) => sum + Number(i.insurance_coverage_amount), 0),
      avg_claim_cycle:
        openClaims.length > 0 ? Math.round(openClaims.reduce((sum, i) => sum + diffDays(isoToYmd(i.issue_date), today), 0) / openClaims.length) : 0,
    },
    invoices: [...db.invoices]
      .sort((a, b) => b.issue_date.localeCompare(a.issue_date) || b.id - a.id)
      .slice(0, 120)
      .map((i) => invoiceRow(db, i)),
    revenue_by_payer: [...byPayer]
      .map(([payer, amount]) => ({ payer, amount: round2(amount), pct: Math.round((amount / payerTotal) * 100) }))
      .sort((a, b) => b.amount - a.amount),
    aging: aging(db),
    patients: db.patients.map((p) => billingPatient(db, p)).sort((a, b) => a.name.localeCompare(b.name)),
    methods: PAYMENT_METHODS,
    cancel_policy: CANCEL_POLICY,
    clinic_name: 'Engage Behavior Clinic',
  };
}

/** DocumentNumbers::peekInvoice(): the next number in this year's sequence. */
function nextInvoiceNumber(db: MockDb): string {
  const year = todayYmd().slice(0, 4);
  const max = Math.max(0, ...db.invoices.filter((i) => i.invoice_number.startsWith(`INV-${year}-`)).map((i) => Number(i.invoice_number.split('-')[2])));
  return `INV-${year}-${String(max + 1).padStart(4, '0')}`;
}

function findInvoice(db: MockDb, id: number): InvoiceRow {
  const invoice = db.invoices.find((i) => i.id === id);
  if (!invoice) throw new ApiError(404, { message: 'Not found.' });
  return invoice;
}

/** Billing writes: module access (not view-only) plus the `create_invoice` action. */
function requireInvoicer(): UserRow {
  const user = requireUser();
  requireFeature(user, 'billing', true);
  if (!canDo(toApiUser(user), 'create_invoice')) throw new ApiError(403, { message: 'Invoices are raised by Finance.' });
  return user;
}

function show(id: number): BillingInvoice {
  const user = requireUser();
  requireFeature(user, 'billing');
  const db = getDb();
  return invoiceRow(db, findInvoice(db, id));
}

function storePayment(id: number, input: InvoicePaymentRequest) {
  requireInvoicer();
  const db = getDb();
  const invoice = findInvoice(db, id);
  if (invoice.voided_at) throw new ApiError(422, { message: 'A voided invoice can’t take payments.' });
  if (!(Number(input.amount) >= 0.01)) throw fail('amount', 'The amount field must be at least 0.01.');
  if (!PAYMENT_METHODS.includes(input.method)) throw fail('method', 'The selected method is invalid.');
  if (!/^\d{4}-\d{2}-\d{2}$/.test(input.received_on ?? '')) throw fail('received_on', 'The received on field must be a valid date.');
  if ((input.reference ?? '').length > 100) throw fail('reference', 'The reference field must not be greater than 100 characters.');

  const amount = round2(Number(input.amount));
  const receipt = `RCT-${String(db.payments.length + 1).padStart(3, '0')}`;
  db.payments.push({
    id: Math.max(0, ...db.payments.map((p) => p.id)) + 1,
    invoice_id: invoice.id,
    receipt_number: receipt,
    amount: amount.toFixed(2),
    method: input.method,
    received_on: dateCast(input.received_on),
    reference: input.reference?.trim() || null,
  });
  syncPaymentColumns(db, invoice);
  return { message: `Receipt ${receipt} recorded — AED ${money(amount)}.`, invoice: invoiceRow(db, invoice) };
}

function storeCredit(id: number, input: InvoiceCreditRequest) {
  requireInvoicer();
  const db = getDb();
  const invoice = findInvoice(db, id);
  if (invoice.voided_at) throw new ApiError(422, { message: 'A voided invoice can’t be credited further.' });
  const max = Math.max(0.01, Number(invoice.total) - Number(invoice.credit_amount));
  if (!(Number(input.amount) >= 0.01)) throw fail('amount', 'The amount field must be at least 0.01.');
  if (Number(input.amount) > max) throw fail('amount', `The amount field must not be greater than ${max}.`);
  if (!input.reason?.trim()) throw fail('reason', 'The reason field is required.');
  if (input.reason.length > 255) throw fail('reason', 'The reason field must not be greater than 255 characters.');

  invoice.credit_amount = round2(Number(invoice.credit_amount) + Number(input.amount)).toFixed(2);
  invoice.credit_reason = `${invoice.credit_reason ? `${invoice.credit_reason}\n` : ''}${input.reason.trim()}`;
  syncPaymentColumns(db, invoice);
  return { message: `Credit note of AED ${money(Number(input.amount))} issued.`, invoice: invoiceRow(db, invoice) };
}

/**
 * An issued tax invoice is never edited or deleted: void raises a credit
 * note for the open balance and, unless told otherwise, reissues a corrected
 * document under a fresh number linked back to this one.
 */
function voidInvoice(id: number, input: InvoiceVoidRequest) {
  requireInvoicer();
  const db = getDb();
  const invoice = findInvoice(db, id);
  if (invoice.voided_at) throw new ApiError(422, { message: 'Already voided.' });
  if (!input.reason?.trim()) throw fail('reason', 'The reason field is required.');
  if (input.reason.length > 255) throw fail('reason', 'The reason field must not be greater than 255 characters.');

  const open = Math.max(0, balanceOf(db, invoice));
  invoice.credit_amount = round2(Number(invoice.credit_amount) + open).toFixed(2);
  invoice.credit_reason = `${invoice.credit_reason ? `${invoice.credit_reason}\n` : ''}Voided — ${input.reason.trim()}`;
  invoice.voided_at = now();
  invoice.void_reason = input.reason.trim();

  let reissued: InvoiceRow | null = null;
  if (input.reissue ?? true) {
    const nextId = Math.max(0, ...db.invoices.map((i) => i.id)) + 1;
    reissued = {
      ...invoice,
      id: nextId,
      invoice_number: nextInvoiceNumber(db),
      issue_date: dateCast(todayYmd()),
      due_date: dateCast(addDays(todayYmd(), DUE_DAYS)),
      status: Number(invoice.insurance_coverage_amount) > 0 ? 'submitted' : 'issued',
      amount_paid: '0.00',
      payment_method: null,
      credit_amount: '0.00',
      credit_reason: null,
      voided_at: null,
      void_reason: null,
      replaces_invoice_id: invoice.id,
      replaced_by_invoice_id: null,
      sent_to: null,
      sent_at: null,
      reminder_sent_at: null,
      reminders_count: 0,
      claim_reference: null,
    };
    db.invoices.push(reissued);
    for (const line of db.invoiceLineItems.filter((l) => l.invoice_id === invoice.id)) {
      db.invoiceLineItems.push({ ...line, id: db.invoiceLineItems.length + 1, invoice_id: nextId });
    }
    invoice.replaced_by_invoice_id = nextId;
  } else {
    // Sessions go back to unbilled so they can be picked again.
    for (const session of db.sessions) if (session.invoice_id === invoice.id) session.invoice_id = null;
  }
  syncPaymentColumns(db, invoice);

  return {
    message: `${invoice.invoice_number} voided${reissued ? ` and reissued as ${reissued.invoice_number}.` : '.'}`,
    invoice: invoiceRow(db, invoice),
    reissued: reissued ? invoiceRow(db, reissued) : null,
  };
}

/** Email the invoice, or a payment reminder. Nothing is actually sent by the mock. */
function send(id: number, input: InvoiceEmailRequest) {
  requireInvoicer();
  const db = getDb();
  const invoice = findInvoice(db, id);
  if (!input.to?.trim()) throw fail('to', 'The to field is required.');
  if (!EMAIL_PATTERN.test(input.to.trim())) throw fail('to', 'The to field must be a valid email address.');
  if (input.cc && !EMAIL_PATTERN.test(input.cc.trim())) throw fail('cc', 'The cc field must be a valid email address.');
  if (!input.subject?.trim()) throw fail('subject', 'The subject field is required.');
  if (input.subject.length > 200) throw fail('subject', 'The subject field must not be greater than 200 characters.');
  if (!input.message?.trim()) throw fail('message', 'The message field is required.');
  if (input.message.length > 5000) throw fail('message', 'The message field must not be greater than 5000 characters.');

  let message: string;
  if (input.kind === 'reminder') {
    invoice.reminder_sent_at = now();
    invoice.reminders_count += 1;
    message = `Reminder for ${invoice.invoice_number} sent to ${input.to.trim()}.`;
  } else {
    invoice.sent_to = input.to.trim();
    invoice.sent_at = now();
    message = `${invoice.invoice_number} emailed to ${input.to.trim()}.`;
  }
  return { message, invoice: invoiceRow(db, invoice) };
}

// ---------------------------------------------------------------------------
// New invoice from delivered sessions, prepaid top-up, aging, statements
// ---------------------------------------------------------------------------

/** config/billing.php */
const CANCEL_POLICY = { notice_hours: 24, late_pct: 50, no_show_pct: 100 };
const VAT_RATE = 0.05;
const DEFAULT_RATE = 300;
const SERVICE_LABELS: Record<string, string> = {
  ABA: 'ABA therapy — 1:1 session',
  Speech: 'Speech therapy — individual',
  OT: 'Occupational therapy — individual',
  Assessment: 'Assessment session',
  'Parent training': 'Parent training session',
  'Social group': 'Social skills group',
};
const AGING_BUCKETS: { key: string; label: string; max: number | null }[] = [
  { key: 'current', label: 'Current — not yet due', max: 0 },
  { key: 'd1_30', label: '1 – 30 days overdue', max: 30 },
  { key: 'd31_60', label: '31 – 60 days', max: 60 },
  { key: 'd61_90', label: '61 – 90 days', max: 90 },
  { key: 'd90', label: '90+ days', max: null },
];

const isSelfPay = (name: string) => /self/i.test(name);
const trimHours = (n: number) => String(Math.round(n * 10) / 10);
/** "09:00:00" → "9:00 AM" (PHP `g:i A`). */
function clock12(time: string): string {
  const [h, m] = time.split(':').map(Number);
  return `${h % 12 === 0 ? 12 : h % 12}:${String(m).padStart(2, '0')} ${h < 12 ? 'AM' : 'PM'}`;
}

/** SessionLedger::profile(): what billing knows about a client that is not on the session. */
function profileOf(db: MockDb, patient: PatientRow) {
  const lead = db.leads.find((l) => l.id === patient.lead_id);
  const auths = db.authorizations.filter((a) => a.patient_id === patient.id).sort((a, b) => a.sort_order - b.sort_order);
  const prepaidAuth = auths.find((a) => isSelfPay(a.payer_name) && (a.authorized_hours_total ?? 0) > 0);
  const insurers = auths.filter((a) => !isSelfPay(a.payer_name));
  return {
    lead,
    // MOCK: every client is billed at config `default_rate`.
    rate: DEFAULT_RATE,
    setting: patient.id % 4 === 0 ? 'Client home' : 'Clinic',
    insurers,
    primary_payer: insurers[0]?.payer_name ?? 'Self-pay',
    prepaid: prepaidAuth ? { authorization_id: prepaidAuth.id, total: prepaidAuth.authorized_hours_total ?? 0 } : null,
  };
}
type Profile = ReturnType<typeof profileOf>;

/** InvoicePresenter::patient() */
function billingPatient(db: MockDb, patient: PatientRow): BillingPatient {
  const profile = profileOf(db, patient);
  return {
    id: patient.id,
    name: profile.lead?.child_name ?? `Patient #${patient.id}`,
    parent: profile.lead?.parent_guardian_name ?? null,
    email: profile.lead?.email ?? null,
    phone: profile.lead?.phone ?? null,
    payer: profile.primary_payer,
    rate: profile.rate,
    vat_rate: VAT_RATE,
    setting: profile.setting,
    prepaid: profile.prepaid,
    package: '',
    insurers: profile.insurers.map((a) => ({ payer: a.payer_name, pct: a.coverage_percent, covers: a.covers_services ?? [] })),
  };
}

/** SessionLedger::billing(): attendance → charge. Whole hours; the percentage applies to the price of each hour. */
function attendanceOf(s: CalendarSessionRow) {
  const hours = Math.max(1, Math.round(s.duration_minutes / 60));
  const notice = s.cancel_notice_hours;
  let state: AttendanceState | 'scheduled' = 'scheduled';
  let factor = 0;
  let label = 'Scheduled';
  let rule = 'Not yet delivered';
  if (s.status === 'completed') {
    [state, factor, label, rule] = ['completed', 1, 'Completed', 'Attended — 100% charged'];
  } else if (s.status === 'no_show') {
    [state, factor, label, rule] = ['no_show', CANCEL_POLICY.no_show_pct / 100, 'No-show — charged', `No-show — ${CANCEL_POLICY.no_show_pct}% charged`];
  } else if (s.status === 'cancelled' && s.cancel_reason === 'clinic') {
    [state, label, rule] = ['cancelled_clinic', 'Cancelled — clinic', 'Cancelled by clinic — not charged'];
  } else if (s.status === 'cancelled' && notice !== null && notice >= CANCEL_POLICY.notice_hours) {
    [state, label, rule] = ['cancelled_notice', 'Cancelled — with notice', `Cancelled ${trimHours(notice)} h ahead — not charged`];
  } else if (s.status === 'cancelled' && notice !== null) {
    [state, factor, label, rule] = [
      'cancelled_late',
      CANCEL_POLICY.late_pct / 100,
      'Cancelled — late',
      `Cancelled ${trimHours(notice)} h ahead — ${CANCEL_POLICY.late_pct}% charged`,
    ];
  } else if (s.status === 'cancelled') {
    [state, label, rule] = ['cancelled_notice', 'Cancelled — with notice', 'Cancelled by family — notice not recorded, not charged'];
  }
  return { hours, factor, state: state as AttendanceState, label, rule, bill_hours: factor === 0 ? 0 : hours };
}

/**
 * SessionLedger::routeSession(): the first insurer that covers this service and has hours authorized.
 * MOCK: an authorization with hours on it always covers the whole session (the server counts hours used).
 */
function routeOf(s: CalendarSessionRow, billHours: number, profile: Profile) {
  const type = s.activity_type.toLowerCase();
  let blocked: string | null = null;
  for (const auth of profile.insurers) {
    const covers = (auth.covers_services ?? []).some((c) => type.includes(c.toLowerCase()) || c.toLowerCase().includes(type));
    if (!covers) continue;
    if ((auth.authorized_hours_total ?? 0) <= 0) {
      blocked ??= auth.payer_name;
      continue;
    }
    return { payer: auth.payer_name, pct: auth.coverage_percent, authorization_id: auth.id as number | null, covered: billHours, excess: 0, blocked };
  }
  return { payer: 'Self-pay', pct: 0, authorization_id: null, covered: 0, excess: billHours, blocked };
}

/** SessionLedger::row() */
function ledgerRow(db: MockDb, s: CalendarSessionRow, profile: Profile): LedgerRow {
  const b = attendanceOf(s);
  const route = routeOf(s, b.bill_hours, profile);
  const net = b.bill_hours * profile.rate * b.factor;
  const gross = net * (1 + VAT_RATE);
  const coveredGross = route.covered * profile.rate * (1 + VAT_RATE) * b.factor;
  const excessGross = route.excess * profile.rate * (1 + VAT_RATE) * b.factor;
  const insufficient = route.excess > 0 && route.blocked !== null;
  const therapist = db.users.find((u) => u.id === s.therapist_id);
  const supervisor = db.users.find((u) => u.id === s.supervised_by);
  const invoice = db.invoices.find((i) => i.id === s.invoice_id);
  return {
    id: s.id,
    session_date: s.session_date,
    date_label: formatDayMonthYearShort(s.session_date),
    start_time: s.start_time.slice(0, 5),
    end_time: s.end_time.slice(0, 5),
    duration_minutes: s.duration_minutes,
    hours: b.hours,
    bill_hours: b.bill_hours,
    attendance: b.state,
    attendance_label: b.label,
    charge_rule: b.rule,
    factor: b.factor,
    notice_hours: s.cancel_notice_hours,
    activity_type: s.activity_type,
    service_label: SERVICE_LABELS[s.activity_type] ?? s.activity_type,
    therapist_id: s.therapist_id,
    therapist_name: therapist ? `${therapist.first_name} ${therapist.last_name}`.trim() : '—',
    trainee_note: supervisor ? `supervised by ${supervisor.first_name} ${supervisor.last_name}`.trim() : null,
    setting: profile.setting,
    rate: profile.rate,
    net,
    vat: net * VAT_RATE,
    gross,
    payer: route.payer,
    coverage_pct: route.pct,
    authorization_id: route.authorization_id,
    covered_bill_hours: route.covered,
    excess_bill_hours: route.excess,
    insufficient_authorization: insufficient,
    insufficient_message: insufficient
      ? `Only ${route.covered} of ${b.bill_hours} h covered — ${route.excess} h exceeds the ${route.blocked} authorization`
      : null,
    insurer_amount: (coveredGross * route.pct) / 100,
    family_amount: coveredGross * (1 - route.pct / 100) + excessGross,
    invoiced: s.invoice_id !== null,
    invoice_number: invoice?.invoice_number ?? null,
    billing_status: invoice ? (billingStatus(db, invoice) === 'paid' ? 'Paid' : 'Invoiced') : profile.prepaid ? 'Prepaid' : 'Pending',
  };
}

/** SessionLedger::rows(): prepaid families get the hours left after every delivered hour is drawn. */
function ledgerRows(db: MockDb, sessions: CalendarSessionRow[], profile: Profile): LedgerRow[] {
  const rows = sessions.map((s) => ledgerRow(db, s, profile));
  if (!profile.prepaid) return rows;
  const left = Math.max(0, profile.prepaid.total - rows.reduce((sum, r) => sum + r.bill_hours, 0));
  return rows.map((r) => ({ ...r, prepaid_left: left }));
}

/** SessionLedger::forPatient(): sessions that have happened and are client-facing, newest first. */
function deliveredSessions(db: MockDb, patient: PatientRow): CalendarSessionRow[] {
  const today = todayYmd();
  return db.sessions
    .filter(
      (s) =>
        s.patient_id === patient.lead_id &&
        ['completed', 'no_show', 'cancelled'].includes(s.status) &&
        !NON_THERAPY_TYPES.includes(s.activity_type) &&
        s.session_date <= today,
    )
    .sort((a, b) => b.session_date.localeCompare(a.session_date) || b.start_time.localeCompare(a.start_time));
}

function findPatient(db: MockDb, id: number): PatientRow {
  const patient = db.patients.find((p) => p.id === id);
  if (!patient) throw new ApiError(404, { message: 'Not found.' });
  return patient;
}

/** GET /billing/patients/{patient}/ledger */
function ledger(patientId: number): PatientLedger {
  const user = requireUser();
  requireFeature(user, 'billing');
  const db = getDb();
  const patient = findPatient(db, patientId);
  const profile = profileOf(db, patient);
  const rows = ledgerRows(db, deliveredSessions(db, patient), profile);
  const left = profile.prepaid ? (rows[0]?.prepaid_left ?? profile.prepaid.total) : null;
  return {
    patient: billingPatient(db, patient),
    rows,
    prepaid_left: left,
    prepaid_used: profile.prepaid && left !== null ? profile.prepaid.total - left : null,
  };
}

/** SessionLedger::applyAttendance(): the picker's attendance choice as calendar columns. */
function applyAttendance(s: CalendarSessionRow, state: AttendanceState, notice: number | null): void {
  // "Completed" and "no-show" assert the session happened: never true for one still ahead.
  if ((state === 'completed' || state === 'no_show') && s.session_date > todayYmd()) return;
  if (state === 'completed' || state === 'no_show') Object.assign(s, { status: state, cancel_reason: null, cancel_notice_hours: null });
  else if (state === 'cancelled_clinic') Object.assign(s, { status: 'cancelled', cancel_reason: 'clinic', cancel_notice_hours: null });
  else if (state === 'cancelled_late')
    Object.assign(s, { status: 'cancelled', cancel_reason: 'family', cancel_notice_hours: notice ?? Math.max(0, CANCEL_POLICY.notice_hours - 1) });
  else if (state === 'cancelled_notice')
    Object.assign(s, { status: 'cancelled', cancel_reason: 'family', cancel_notice_hours: notice ?? CANCEL_POLICY.notice_hours });
}

const noticeOf = (att: { notice_hours?: number } | undefined) =>
  att?.notice_hours === undefined || att.notice_hours === null || Number.isNaN(Number(att.notice_hours)) ? null : Number(att.notice_hours);

/** InvoiceController::resolveSelection(): the client and the chosen sessions that belong to them. */
function resolveSelection(db: MockDb, input: NewInvoiceRequest): { patient: PatientRow; sessions: CalendarSessionRow[] } {
  if (!input.patient_id) throw fail('patient_id', 'The patient id field is required.');
  const patient = db.patients.find((p) => p.id === Number(input.patient_id));
  if (!patient) throw fail('patient_id', 'The selected patient id is invalid.');
  if (!Array.isArray(input.session_ids) || input.session_ids.length === 0) throw fail('session_ids', 'The session ids field is required.');
  const sessions = db.sessions.filter((s) => s.patient_id === patient.lead_id && input.session_ids.includes(s.id));
  if (sessions.length === 0) throw new ApiError(422, { message: 'None of the selected sessions belong to this client.' });
  return { patient, sessions };
}

type ComposedLine = InvoicePreviewLine & { therapist_id: number; setting: string; activity_type: string };

/** InvoiceBuilder::compose(): one line per billable hour, then the totals and the payer split. */
function compose(db: MockDb, patient: PatientRow, sessions: CalendarSessionRow[]) {
  const profile = profileOf(db, patient);
  const rows = ledgerRows(db, sessions, profile);
  const billed = rows.filter((r) => r.bill_hours > 0).sort((a, b) => `${a.session_date} ${a.start_time}`.localeCompare(`${b.session_date} ${b.start_time}`));
  const lines: ComposedLine[] = [];
  const warnings: string[] = [];

  for (const r of billed) {
    if (r.insufficient_message) warnings.push(`${r.date_label} — ${r.insufficient_message}`);
    const startHour = Number(r.start_time.slice(0, 2));
    const minutes = r.start_time.slice(3, 5);
    for (let i = 0; i < r.bill_hours; i++) {
      // Only the hours inside the authorization bill to the insurer; the rest bill to the family.
      const excess = i >= r.covered_bill_hours;
      const pct = excess ? 0 : r.coverage_pct;
      const amount = r.rate * r.factor;
      const total = amount * (1 + VAT_RATE);
      const at = (hour: number) => `${formatDayMonthYear(r.session_date)} · ${clock12(`${String(hour % 24).padStart(2, '0')}:${minutes}`)}`;
      lines.push({
        line_no: lines.length + 1,
        calendar_session_id: r.id,
        therapist_id: r.therapist_id,
        setting: r.setting,
        activity_type: r.activity_type,
        description: r.service_label,
        note: `${r.setting === 'Clinic' ? 'Clinic' : 'Client home'} by ${r.therapist_name}${r.trainee_note ? ` (+ ${r.trainee_note}, in training)` : ''}`,
        from_label: at(startHour + i),
        to_label: at(startHour + i + 1),
        payer: excess ? 'Self-pay' : r.payer,
        coverage_percent: pct,
        exceeds_authorization: excess && r.insufficient_authorization,
        rate: amount,
        amount,
        vat_amount: amount * VAT_RATE,
        total,
        insurer_amount: (total * pct) / 100,
        family_amount: total * (1 - pct / 100),
      });
    }
  }

  const sum = (pick: (l: ComposedLine) => number) => lines.reduce((total, l) => total + pick(l), 0);
  const splits: InvoicePreview['splits'] = [];
  for (const l of lines) {
    if (l.payer === 'Self-pay' || l.insurer_amount <= 0) continue;
    const split = splits.find((x) => x.payer === l.payer);
    if (split) split.amount = round2(split.amount + l.insurer_amount);
    else splits.push({ payer: l.payer, pct: l.coverage_percent, amount: round2(l.insurer_amount) });
  }
  const today = todayYmd();
  const from = billed[0]?.session_date ?? today;
  const to = billed.at(-1)?.session_date ?? today;
  const gross = sum((l) => l.total);
  const insurer = sum((l) => l.insurer_amount);

  return {
    lines,
    warnings: [...new Set(warnings)],
    session_count: billed.length,
    bill_hours: billed.reduce((total, r) => total + r.bill_hours, 0),
    net: round2(sum((l) => l.amount)),
    vat: round2(sum((l) => l.vat_amount)),
    total: round2(gross),
    insurer_share: round2(insurer),
    family_share: round2(sum((l) => l.family_amount)),
    amount_due: round2(gross - insurer),
    splits,
    payer: splits[0]?.payer ?? 'Self-pay',
    period_from: from,
    period_label: formatMonthYear(from) === formatMonthYear(to) ? formatMonthYear(from) : `${formatMonthYear(from).slice(0, 3)} – ${formatMonthYear(to)}`,
    bill_to: profile.lead?.parent_guardian_name ?? null,
    child: profile.lead?.child_name ?? null,
  };
}

/** POST /billing/invoices/preview — attendance corrections are applied to copies; nothing is saved. */
function preview(input: NewInvoiceRequest): InvoicePreview {
  requireInvoicer();
  const db = getDb();
  const { patient, sessions } = resolveSelection(db, input);
  const probes = sessions.map((s) => {
    const probe = { ...s };
    const att = input.attendance?.[s.id];
    if (att?.state) applyAttendance(probe, att.state, noticeOf(att));
    return probe;
  });
  const today = todayYmd();
  const composed = compose(db, patient, probes);
  return {
    ...composed,
    // `activity_type` is only kept on the lines for the mock's own line items.
    lines: composed.lines.map(({ activity_type: _type, ...line }) => line),
    invoice_number: nextInvoiceNumber(db),
    issue_date: formatDayMonthYear(today),
    due_date: formatDayMonthYear(addDays(today, DUE_DAYS)),
    settled_count: sessions.filter((s) => s.invoice_id !== null).length,
  };
}

/** POST /billing/invoices — InvoiceController::store() + InvoiceBuilder::issue(). */
function store(input: NewInvoiceRequest) {
  requireInvoicer();
  const db = getDb();
  const { patient, sessions } = resolveSelection(db, input);

  const settled = sessions.filter((s) => s.invoice_id !== null);
  if (settled.length > 0 && !input.acknowledge_settled) {
    throw new ApiError(409, { message: `${settled.length} selected session(s) are already on an invoice.` });
  }

  // Attendance corrections made in the picker are written back to the calendar.
  for (const s of sessions) {
    const att = input.attendance?.[s.id];
    if (att?.state) applyAttendance(s, att.state, noticeOf(att));
  }

  const composed = compose(db, patient, sessions);
  if (composed.lines.length === 0) {
    throw new ApiError(422, { message: 'Nothing billable in the selection — every chosen session is unchargeable under the cancellation policy.' });
  }

  const today = todayYmd();
  const id = Math.max(0, ...db.invoices.map((i) => i.id)) + 1;
  const invoice: InvoiceRow = {
    id,
    patient_id: patient.id,
    payer: composed.payer,
    status: composed.insurer_share > 0 ? 'submitted' : 'issued',
    invoice_number: nextInvoiceNumber(db),
    issue_date: dateCast(today),
    period: dateCast(monthStartOf(composed.period_from)),
    payment_method: null,
    subtotal: composed.net.toFixed(2),
    vat_amount: composed.vat.toFixed(2),
    total: composed.total.toFixed(2),
    amount_paid: '0.00',
    credit_amount: '0.00',
    voided_at: null,
    insurance_coverage_amount: composed.insurer_share.toFixed(2),
    due_date: dateCast(addDays(today, DUE_DAYS)),
    credit_reason: null,
    void_reason: null,
    replaces_invoice_id: null,
    replaced_by_invoice_id: null,
    sent_to: null,
    sent_at: null,
    reminder_sent_at: null,
    reminders_count: 0,
    claim_reference: null,
  };
  db.invoices.push(invoice);
  for (const l of composed.lines) {
    db.invoiceLineItems.push({
      id: db.invoiceLineItems.length + 1,
      invoice_id: id,
      amount: l.amount.toFixed(2),
      setting: l.setting,
      therapist_id: l.therapist_id,
      calendar_session_id: l.calendar_session_id,
      activity_type: l.activity_type,
    });
  }
  const billedIds = new Set(composed.lines.map((l) => l.calendar_session_id));
  for (const s of sessions) if (billedIds.has(s.id)) s.invoice_id = id;

  return {
    message: `${invoice.invoice_number} raised — ${composed.session_count} session(s), ${composed.bill_hours} billable hour(s).`,
    invoice: invoiceRow(db, invoice),
  };
}

/** POST /billing/patients/{patient}/top-up — adds hours to the family's Self-pay authorization, creating it if needed. */
function topUp(patientId: number, hours: number) {
  requireInvoicer();
  const db = getDb();
  const patient = findPatient(db, patientId);
  const message =
    hours === undefined || hours === null || String(hours) === ''
      ? 'The hours field is required.'
      : !Number.isInteger(Number(hours))
        ? 'The hours field must be an integer.'
        : Number(hours) < 1
          ? 'The hours field must be at least 1.'
          : Number(hours) > 500
            ? 'The hours field must not be greater than 500.'
            : null;
  if (message) throw new ApiError(422, { message });

  const own = db.authorizations.filter((a) => a.patient_id === patient.id);
  let auth = own.find((a) => isSelfPay(a.payer_name));
  if (!auth) {
    auth = {
      id: Math.max(0, ...db.authorizations.map((a) => a.id)) + 1,
      patient_id: patient.id,
      payer_name: 'Self-pay',
      coverage_percent: 0,
      covers_services: ['ABA', 'Speech', 'OT'],
      policy_number: null,
      approval_reference: null,
      authorized_hours_total: 0,
      renews_at: null,
      sort_order: Math.max(-1, ...own.map((a) => a.sort_order)) + 1,
      created_at: now(),
      updated_at: now(),
    };
    db.authorizations.push(auth);
  }
  auth.authorized_hours_total = (auth.authorized_hours_total ?? 0) + Number(hours);
  return { message: `${Number(hours)} prepaid hour(s) added.`, prepaid_total: auth.authorized_hours_total };
}

/** GET /billing/patients/{patient}/statement — InvoiceController::statementData(). */
function statement(patientId: number): FamilyStatement {
  const user = requireUser();
  requireFeature(user, 'billing');
  const db = getDb();
  const patient = findPatient(db, patientId);
  const profile = profileOf(db, patient);
  const invoices = db.invoices.filter((i) => i.patient_id === patient.id);

  const entries: { date: string; ref: string; desc: string; charge: number; credit: number }[] = [];
  for (const inv of invoices) {
    entries.push({
      date: isoToYmd(inv.issue_date),
      ref: inv.invoice_number,
      desc: `Tax invoice · ${formatMonthYear(isoToYmd(inv.period))}${inv.voided_at ? ` (voided — ${inv.void_reason})` : ''}`,
      charge: inv.voided_at ? 0 : Number(inv.total),
      credit: 0,
    });
    if (Number(inv.credit_amount) > 0) {
      entries.push({
        // MOCK: a credit note on a live invoice is dated with the invoice (the server uses its last update).
        date: isoToYmd(inv.voided_at ?? inv.issue_date),
        ref: inv.invoice_number,
        desc: `Credit note — ${inv.credit_reason || 'credit'}`,
        charge: 0,
        credit: Number(inv.credit_amount),
      });
    }
    for (const p of db.payments.filter((x) => x.invoice_id === inv.id)) {
      entries.push({
        date: isoToYmd(p.received_on),
        ref: p.receipt_number,
        desc: `Payment received — ${p.method}${p.reference ? ` · ref ${p.reference}` : ''} · ${inv.invoice_number}`,
        charge: 0,
        credit: Number(p.amount),
      });
    }
  }

  let balance = 0;
  const rows = entries
    .sort((a, b) => `${a.date}${a.ref}`.localeCompare(`${b.date}${b.ref}`))
    .map((e) => {
      balance += e.charge - e.credit;
      return { date_label: formatDayMonthYear(e.date), ref: e.ref, desc: e.desc, charge: round2(e.charge), credit: round2(e.credit), balance: round2(balance) };
    });
  const oldest = invoices
    .filter((i) => !i.voided_at && balanceOf(db, i) > 0.01)
    .sort((a, b) => a.due_date.localeCompare(b.due_date))[0];
  const sessions = ledgerRows(db, deliveredSessions(db, patient), profile).reverse();

  return {
    patient_id: patient.id,
    child: profile.lead?.child_name ?? null,
    bill_to: profile.lead?.parent_guardian_name ?? null,
    payer: profile.primary_payer,
    as_of: formatDayMonthYear(todayYmd()),
    charged: round2(rows.reduce((sum, r) => sum + r.charge, 0)),
    credited: round2(rows.reduce((sum, r) => sum + r.credit, 0)),
    balance: round2(balance),
    oldest_open: oldest
      ? {
          number: oldest.invoice_number,
          due_label: formatDayMonthYear(isoToYmd(oldest.due_date)),
          days_past_due: diffDays(isoToYmd(oldest.due_date), todayYmd()),
        }
      : null,
    rows,
    sessions,
    session_hours: sessions.reduce((sum, r) => sum + r.bill_hours, 0),
    session_charged: round2(sessions.reduce((sum, r) => sum + r.gross, 0)),
  };
}

/** The "Aging & statements" tab of BillingController::index(). */
function aging(db: MockDb): BillingAging {
  const today = todayYmd();
  const pastDue = (i: InvoiceRow) => diffDays(isoToYmd(i.due_date), today);
  const open = db.invoices.filter((i) => !i.voided_at && balanceOf(db, i) > 0.01).sort((a, b) => pastDue(b) - pastDue(a));
  const total = (list: InvoiceRow[]) => round2(list.reduce((sum, i) => sum + balanceOf(db, i), 0));
  const late = open.filter((i) => pastDue(i) > 0);
  const monthStart = monthStartOf(today);

  const byPayer = new Map<string, InvoiceRow[]>();
  for (const i of open) byPayer.set(i.payer || 'Self-pay', [...(byPayer.get(i.payer || 'Self-pay') ?? []), i]);

  return {
    total_outstanding: total(open),
    open_count: open.length,
    past_due: total(late),
    past_due_count: late.length,
    oldest_days: open[0] ? Math.max(0, pastDue(open[0])) : 0,
    oldest_ref: open[0] ? `${open[0].invoice_number} · ${invoiceRow(db, open[0]).patient}` : null,
    reminders_month: db.invoices
      .filter((i) => i.reminder_sent_at && isoToYmd(i.reminder_sent_at) >= monthStart)
      .reduce((sum, i) => sum + i.reminders_count, 0),
    rows: open.map((i) => invoiceRow(db, i)),
    buckets: AGING_BUCKETS.map((b, idx) => {
      const prevMax = idx === 0 ? null : AGING_BUCKETS[idx - 1].max;
      const inBucket = open.filter((i) => {
        const d = pastDue(i);
        if (b.max === 0) return d <= 0;
        if (b.max === null) return d > (prevMax ?? 0);
        return d > (prevMax ?? 0) && d <= b.max;
      });
      return { key: b.key, label: b.label, count: inBucket.length, amount: total(inBucket) };
    }),
    by_payer: [...byPayer].map(([payer, list]) => ({ payer, amount: total(list) })).sort((a, b) => b.amount - a.amount),
    families: db.patients
      .filter((p) => db.invoices.some((i) => i.patient_id === p.id))
      .map((p) => {
        const profile = profileOf(db, p);
        const live = db.invoices.filter((i) => i.patient_id === p.id && !i.voided_at);
        return {
          patient_id: p.id,
          patient: profile.lead?.child_name ?? '—',
          parent: profile.lead?.parent_guardian_name ?? null,
          payer: profile.primary_payer,
          invoices: live.length,
          billed: round2(live.reduce((sum, i) => sum + Number(i.total), 0)),
          balance: total(live),
        };
      })
      .sort((a, b) => b.balance - a.balance),
  };
}

export function createBillingApi(): ApiClient['billing'] {
  return {
    overview: () => delay(overview),
    invoice: (id) => delay(() => show(id)),
    recordPayment: (id, input) => delay(() => storePayment(id, input)),
    creditNote: (id, input) => delay(() => storeCredit(id, input)),
    voidInvoice: (id, input) => delay(() => voidInvoice(id, input)),
    sendEmail: (id, input) => delay(() => send(id, input)),
    ledger: (patientId) => delay(() => ledger(patientId)),
    previewInvoice: (input) => delay(() => preview(input)),
    createInvoice: (input) => delay(() => store(input)),
    topUpPrepaid: (patientId, hours) => delay(() => topUp(patientId, hours)),
    statement: (patientId) => delay(() => statement(patientId)),
  };
}
