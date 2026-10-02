/**
 * Mock of the billing page (BillingController::index) and the invoice
 * actions (InvoiceController: payments, credit notes, void / reissue, email).
 *
 * The real money rules live on the server. This mock keeps the same
 * permissions, validation messages and status logic so the screens behave
 * the same, but its amounts come from simple seeded invoices.
 */

import { canDo, levelFor } from '@/auth/permissions';
import { addDays, diffDays, formatDayMonthYear, formatMonthLong, formatMonthYear, isoToYmd, monthStartOf, todayYmd } from '@/utils/dates';

import type { ApiClient } from '../client';
import { ApiError } from '../errors';
import type {
  BillingInvoice,
  BillingOverview,
  InvoiceCreditRequest,
  InvoiceEmailRequest,
  InvoicePaymentRequest,
  InvoiceVoidRequest,
} from '../types';
import type { InvoiceRow, MockDb, UserRow } from './rows';
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
    methods: PAYMENT_METHODS,
    clinic_name: 'Engage Behavior Clinic',
  };
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
    const year = todayYmd().slice(0, 4);
    const maxNumber = Math.max(0, ...db.invoices.filter((i) => i.invoice_number.startsWith(`INV-${year}-`)).map((i) => Number(i.invoice_number.split('-')[2])));
    reissued = {
      ...invoice,
      id: nextId,
      invoice_number: `INV-${year}-${String(maxNumber + 1).padStart(4, '0')}`,
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

export function createBillingApi(): ApiClient['billing'] {
  return {
    overview: () => delay(overview),
    invoice: (id) => delay(() => show(id)),
    recordPayment: (id, input) => delay(() => storePayment(id, input)),
    creditNote: (id, input) => delay(() => storeCredit(id, input)),
    voidInvoice: (id, input) => delay(() => voidInvoice(id, input)),
    sendEmail: (id, input) => delay(() => send(id, input)),
  };
}
