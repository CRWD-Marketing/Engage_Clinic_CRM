/**
 * Mock of Vendors (Vendor\VendorController): the list with status tabs,
 * search and category filter, the vendor page (opening it clears "new"),
 * staff fields and delete. Seeded with a few registrations. There are no
 * files, so a document download is refused like the PDFs.
 */

import { levelFor } from '@/auth/permissions';
import { addDays, diffDays, todayYmd } from '@/utils/dates';

import type { ApiClient } from '../client';
import { ApiError } from '../errors';
import type { VendorDetail, VendorDocumentRow, VendorListQuery, VendorPage, VendorRow, VendorsPage, VendorStatus, VendorUpdateInput } from '../types';
import { laravelIso } from './seed';
import { delay, getDb, requireFeature, requireUser, toApiUser } from './server';

const STATUSES: Record<VendorStatus, string> = {
  prospect: 'Prospect',
  under_review: 'Under Review',
  active: 'Active',
  on_hold: 'On Hold',
  suspended: 'Suspended',
  terminated: 'Terminated',
};
/** Vendor::CATEGORIES */
const CATEGORIES = [
  'Clinical Services and Clinical Suppliers',
  'Medical and Therapy Supplies',
  'Office and Administrative Supplies',
  'Cleaning and Housekeeping',
  'Maintenance and Facility Management',
  'IT, Software and Technology',
  'CCTV, Security and Access Control',
  'Fire and Life Safety',
  'Medical Waste Management',
  'Marketing, Advertising and Branding',
  'Printing and Production',
  'Transportation and Logistics',
  'Training and Consultancy',
  'Events and Community Partnerships',
  'Insurance, Legal and Professional Services',
  'Other',
];
const LABELS: Record<string, { label: string; expires: boolean }> = {
  trade_licence: { label: 'Trade Licence', expires: true },
  vat_certificate: { label: 'VAT Certificate', expires: true },
  insurance: { label: 'Insurance Certificate', expires: true },
  professional_licence: { label: 'Professional / Regulatory Licence', expires: true },
  agreement: { label: 'Signed Contract / Service Agreement', expires: false },
  other: { label: 'Other Supporting Documents', expires: false },
  bank_letter: { label: 'Bank Letter / Cancelled Cheque', expires: false },
};
const WARNING_DAYS = 30;
const PER_PAGE = 20;

type Doc = Omit<VendorDocumentRow, 'label' | 'state' | 'days_to_expiry'>;
type Row = Omit<VendorDetail, 'category_label' | 'compliance_status' | 'status_label' | 'is_new' | 'internal_owner' | 'documents' | 'reference'> & {
  viewed_at: string | null;
  category_other: string | null;
  documents: Doc[];
};

const daysAgo = (d: number) => laravelIso(new Date(Date.now() - d * 86400_000).toISOString());

let rows: Row[] | null = null;
function data(): Row[] {
  if (rows) return rows;
  const today = todayYmd();
  const doc = (id: number, type: string, expiry: string | null, number: string | null = null): Doc => ({
    id,
    type,
    document_number: number,
    issue_date: null,
    expiry_date: expiry,
    file_name: `${LABELS[type].label}.pdf`,
    file_mime: 'application/pdf',
    file_size: 184_320,
  });
  const base = {
    website: null,
    alt_contact: null,
    finance_email: null,
    description: null,
    years_in_business: null,
    referred_by: null,
    monthly_cost: null,
    vat_registered: true,
    trn: null,
    accepts_po: true,
    contract_start: null,
    contract_end: null,
    swift: null,
    internal_owner_id: null,
    notes: null,
    category_other: null,
    payment_method: 'Bank transfer',
  };
  rows = [
    {
      ...base,
      id: 4,
      legal_name: 'Gulf Medical Supplies LLC',
      trade_name: 'GMS',
      contact_name: 'Rami Haddad',
      position: 'Sales Manager',
      email: 'rami@gms.ae',
      phone: '+971 50 123 4567',
      address: 'Warehouse 12, Mussafah M-26',
      city: 'Abu Dhabi',
      emirate: 'Abu Dhabi',
      category: 'Medical and Therapy Supplies',
      primary_service: 'Sensory and therapy equipment',
      description: 'Weighted vests, swings, fine-motor kits and assessment materials.',
      years_in_business: '9',
      pricing: 'Per catalogue, 10% clinic discount',
      monthly_cost: 4200,
      payment_terms: '30 days',
      trn: '100234567800003',
      bank_name: 'ADCB',
      account_holder: 'Gulf Medical Supplies LLC',
      iban: 'AE07 0331 2345 6789 0123 456',
      swift: 'ADCBAEAA',
      signatory_name: 'Rami Haddad',
      declared_at: daysAgo(1),
      status: 'under_review',
      is_critical: true,
      viewed_at: null,
      created_at: daysAgo(1),
      documents: [doc(1, 'trade_licence', addDays(today, 12), 'CN-1234567'), doc(2, 'vat_certificate', addDays(today, 200)), doc(3, 'insurance', null)],
    },
    {
      ...base,
      id: 3,
      legal_name: 'Sparkle Facility Services',
      trade_name: 'Sparkle',
      contact_name: 'Aisha Khan',
      position: 'Account Manager',
      email: 'aisha@sparkle.ae',
      phone: '+971 55 222 3344',
      address: 'Office 401, Al Khalidiya',
      city: 'Abu Dhabi',
      emirate: 'Abu Dhabi',
      category: 'Cleaning and Housekeeping',
      primary_service: 'Daily clinic cleaning',
      pricing: 'AED 6,500 / month',
      monthly_cost: 6500,
      payment_terms: '30 days',
      bank_name: 'Emirates NBD',
      account_holder: 'Sparkle Facility Services',
      iban: 'AE46 0260 0010 1234 5678 901',
      signatory_name: 'Aisha Khan',
      declared_at: daysAgo(30),
      contract_start: addDays(today, -25),
      contract_end: addDays(today, 20),
      status: 'active',
      is_critical: true,
      internal_owner_id: 1,
      notes: 'Renewal quote requested.',
      viewed_at: daysAgo(29),
      created_at: daysAgo(30),
      documents: [doc(4, 'trade_licence', addDays(today, 300)), doc(5, 'insurance', addDays(today, -3)), doc(6, 'agreement', null)],
    },
    {
      ...base,
      id: 2,
      legal_name: 'BrightPrint Studio',
      trade_name: null,
      contact_name: 'Leo Martins',
      position: 'Owner',
      email: 'leo@brightprint.ae',
      phone: '+971 52 555 0101',
      address: 'Shop 3, Electra Street',
      city: 'Abu Dhabi',
      emirate: 'Abu Dhabi',
      category: 'Printing and Production',
      primary_service: 'Brochures and signage',
      pricing: 'Per job',
      payment_terms: 'On delivery',
      vat_registered: false,
      bank_name: 'FAB',
      account_holder: 'BrightPrint Studio',
      iban: 'AE12 0350 0000 0012 3456 789',
      signatory_name: 'Leo Martins',
      declared_at: daysAgo(12),
      status: 'prospect',
      is_critical: false,
      viewed_at: daysAgo(11),
      created_at: daysAgo(12),
      documents: [doc(7, 'trade_licence', addDays(today, 150)), doc(8, 'other', null)],
    },
    {
      ...base,
      id: 1,
      legal_name: 'TechCare IT Solutions',
      trade_name: 'TechCare',
      contact_name: 'Sameer Patel',
      position: 'Director',
      email: 'sameer@techcare.ae',
      phone: '+971 56 777 8899',
      address: 'Floor 9, Hamdan Street',
      city: 'Abu Dhabi',
      emirate: 'Abu Dhabi',
      category: 'IT, Software and Technology',
      primary_service: 'Network and device support',
      pricing: 'AED 3,000 / month retainer',
      monthly_cost: 3000,
      payment_terms: '15 days',
      bank_name: 'Mashreq',
      account_holder: 'TechCare IT Solutions',
      iban: 'AE33 0330 0000 1999 8888 777',
      signatory_name: 'Sameer Patel',
      declared_at: daysAgo(60),
      status: 'on_hold',
      is_critical: true,
      viewed_at: daysAgo(59),
      created_at: daysAgo(60),
      documents: [doc(9, 'trade_licence', addDays(today, 40)), doc(10, 'professional_licence', addDays(today, 90))],
    },
  ];
  return rows;
}

function presentDoc(d: Doc): VendorDocumentRow {
  const days = d.expiry_date ? diffDays(todayYmd(), d.expiry_date) : null;
  const state: VendorDocumentRow['state'] =
    days === null ? (LABELS[d.type]?.expires ? 'pending' : 'on_file') : days < 0 ? 'expired' : days <= WARNING_DAYS ? 'expiring' : 'valid';
  return { ...d, label: LABELS[d.type]?.label ?? d.type, state, days_to_expiry: days };
}

function detail(r: Row): VendorDetail {
  const documents = r.documents.map(presentDoc);
  const states = documents.map((d) => d.state);
  const owner = getDb().users.find((u) => u.id === r.internal_owner_id);
  const { viewed_at: _viewed, category_other, ...rest } = r;
  return {
    ...rest,
    reference: `ENG-VND-${r.created_at.slice(0, 4)}-${String(r.id).padStart(4, '0')}`,
    category_label: r.category === 'Other' && category_other ? category_other : r.category,
    compliance_status: states.includes('expired') ? 'expired' : states.includes('pending') || states.includes('expiring') ? 'pending' : 'complete',
    status_label: STATUSES[r.status],
    is_new: r.viewed_at === null,
    internal_owner: owner ? `${owner.first_name} ${owner.last_name}`.trim() : null,
    documents,
  };
}

/** VendorController::row(): what the list shows (no bank details or documents). */
function row(v: VendorDetail): VendorRow {
  return {
    id: v.id,
    reference: v.reference,
    legal_name: v.legal_name,
    trade_name: v.trade_name,
    contact_name: v.contact_name,
    email: v.email,
    phone: v.phone,
    category: v.category,
    category_label: v.category_label,
    primary_service: v.primary_service,
    compliance_status: v.compliance_status,
    status: v.status,
    status_label: v.status_label,
    is_critical: v.is_critical,
    is_new: v.is_new,
    created_at: v.created_at,
  };
}

function requireVendors(write = false) {
  const user = requireUser();
  requireFeature(user, 'vendors', write);
  return user;
}

function find(id: number): Row {
  const r = data().find((v) => v.id === id);
  if (!r) throw new ApiError(404, { message: 'Not found.' });
  return r;
}

/** GET /vendors */
function list(query: VendorListQuery = {}): VendorsPage {
  const user = requireVendors();
  const status = query.status ?? 'all';
  if (status !== 'all' && !(status in STATUSES)) throw new ApiError(422, { message: 'The selected status is invalid.', errors: { status: ['The selected status is invalid.'] } });
  const term = query.search?.trim().toLowerCase();
  const today = todayYmd();
  const warnBy = addDays(today, WARNING_DAYS);
  const all = [...data()].sort((a, b) => b.created_at.localeCompare(a.created_at) || b.id - a.id);
  const matches = all
    .filter((v) => status === 'all' || v.status === status)
    .filter((v) => !query.category || v.category === query.category)
    .map(detail)
    .filter((v) => !term || [v.legal_name, v.trade_name, v.reference, v.contact_name, v.email, v.phone, v.primary_service].some((x) => x?.toLowerCase().includes(term)));
  const page = Math.max(1, Number(query.page) || 1);
  const counts = Object.fromEntries(Object.keys(STATUSES).map((k) => [k, all.filter((v) => v.status === k).length])) as Record<VendorStatus, number>;
  return {
    vendors: matches.slice((page - 1) * PER_PAGE, page * PER_PAGE).map(row),
    meta: { current_page: page, last_page: Math.max(1, Math.ceil(matches.length / PER_PAGE)), per_page: PER_PAGE, total: matches.length },
    statuses: STATUSES,
    status_counts: counts,
    total_count: all.length,
    categories: CATEGORIES,
    stats: {
      new: all.filter((v) => !v.viewed_at).length,
      critical: all.filter((v) => v.is_critical).length,
      documents_expiring: all
        .filter((v) => v.status !== 'terminated')
        .flatMap((v) => v.documents)
        .filter((d) => d.expiry_date && d.expiry_date <= warnBy).length,
      contracts_expiring: all.filter((v) => v.contract_end && v.contract_end >= today && v.contract_end <= warnBy && v.status !== 'terminated').length,
    },
    registration_url: 'https://engagebehavior.com/vendor-registration',
    can_edit: levelFor(toApiUser(user), 'vendors') !== 'view',
  };
}

/** GET /vendors/{id} */
function show(id: number): VendorPage {
  const user = requireVendors();
  const r = find(id);
  const canEdit = levelFor(toApiUser(user), 'vendors') !== 'view';
  // First open by anyone who can act on it clears the "new" marker.
  if (r.viewed_at === null && canEdit) r.viewed_at = laravelIso(new Date().toISOString());
  const ids = data().map((v) => v.id);
  const newer = ids.filter((x) => x > id);
  const older = ids.filter((x) => x < id);
  return {
    ...detail(r),
    owners: getDb()
      .users.filter((u) => u.is_active)
      .sort((a, b) => a.first_name.localeCompare(b.first_name))
      .map((u) => ({ id: u.id, name: [u.first_name, u.middle_name, u.last_name].filter(Boolean).join(' ') })),
    neighbours: { newer: newer.length ? Math.min(...newer) : null, older: older.length ? Math.max(...older) : null },
    statuses: STATUSES,
    can_edit: canEdit,
  };
}

function update(id: number, input: VendorUpdateInput) {
  requireVendors(true);
  const r = find(id);
  if (!input.status) throw new ApiError(422, { message: 'The status field is required.', errors: { status: ['The status field is required.'] } });
  if (!(input.status in STATUSES)) throw new ApiError(422, { message: 'The selected status is invalid.', errors: { status: ['The selected status is invalid.'] } });
  if (input.internal_owner_id && !getDb().users.some((u) => u.id === input.internal_owner_id)) {
    throw new ApiError(422, { message: 'The selected internal owner id is invalid.', errors: { internal_owner_id: ['The selected internal owner id is invalid.'] } });
  }
  if ((input.notes ?? '').length > 5000) throw new ApiError(422, { message: 'The notes field must not be greater than 5000 characters.' });
  Object.assign(r, { status: input.status, is_critical: !!input.is_critical, internal_owner_id: input.internal_owner_id ?? null, notes: input.notes || null });
  return { message: 'Vendor updated.', vendor: detail(r) };
}

function destroy(id: number) {
  requireVendors(true);
  find(id);
  rows = data().filter((v) => v.id !== id);
  return { message: 'Vendor deleted.' };
}

export function createVendorsApi(): ApiClient['vendors'] {
  return {
    list: (query) => delay(() => list(query)),
    newCount: () => delay(() => ({ success: true, count: list().stats.new })),
    show: (id) => delay(() => show(id)),
    update: (id, input) => delay(() => update(id, input)),
    destroy: (id) => delay(() => destroy(id)),
    document: () => Promise.reject(new ApiError(503, { message: 'Vendor documents are stored on the server. Connect the app to the API to open one.' })),
  };
}
