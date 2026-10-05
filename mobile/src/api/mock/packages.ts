/**
 * Mock of Packages (Package\PackageController): the list, add, edit and
 * remove, with the web's validation. Seeded from PackageSeeder. Changes made
 * here are not picked up by the lead intake mock, which reads the fixed
 * seeded list.
 */

import type { ApiClient } from '../client';
import { ApiError } from '../errors';
import type { PackageInput, PackageRow, PackagesPage } from '../types';
import { LOCATIONS, PACKAGE_SEEDS, SERVICES } from './catalog';
import { delay, requireFeature, requireUser } from './server';

const FUNDING_TYPES = ['Insurance', 'Self pay'];
const DELIVERY_MODES = ['Home base', 'Clinic'];
/** PackageSeeder: only the last package is insurance funded. */
const SEED_FUNDING: Record<number, string> = { 4: 'Insurance' };

type Row = Omit<PackageRow, 'service' | 'location' | 'total_excl_vat' | 'summary'>;
let rows: Row[] | null = null;
const data = () =>
  (rows ??= PACKAGE_SEEDS.map((p) => ({
    id: p.id,
    name: p.name,
    service_id: p.service_id,
    location_id: p.location_id,
    funding_type: SEED_FUNDING[p.id] ?? 'Self pay',
    delivery_mode: p.delivery_mode,
    hours_per_week: p.hours_per_week,
    rate: p.rate,
    is_active: true,
  })));

/** "30.0" → "30" (rtrim of zeros and the point). */
const trim = (n: number) => String(Number(n.toFixed(2)));

function present(r: Row): PackageRow {
  const service = SERVICES.find((s) => s.id === r.service_id)?.name ?? null;
  return {
    ...r,
    service,
    location: LOCATIONS.find((l) => l.id === r.location_id)?.name ?? null,
    total_excl_vat: r.hours_per_week * r.rate,
    // Package::summaryLabel()
    summary: [service, r.delivery_mode, r.hours_per_week ? `${trim(r.hours_per_week)} h/wk` : null, r.rate ? `AED ${trim(r.rate)}/hr` : null]
      .filter(Boolean)
      .join(' · '),
  };
}

function requirePackages(write = false) {
  const user = requireUser();
  requireFeature(user, 'packages', write);
}

/** PackageController::validated() */
function validate(input: PackageInput) {
  const errors: Record<string, string[]> = {};
  const add = (field: string, message: string) => (errors[field] ??= []).push(message);
  if (!input.name?.trim()) add('name', 'The name field is required.');
  else if (input.name.length > 255) add('name', 'The name field must not be greater than 255 characters.');
  if (input.service_id && !SERVICES.some((s) => s.id === Number(input.service_id))) add('service_id', 'The selected service id is invalid.');
  if (input.location_id && !LOCATIONS.some((l) => l.id === Number(input.location_id))) add('location_id', 'The selected location id is invalid.');
  if (input.funding_type && !FUNDING_TYPES.includes(input.funding_type)) add('funding_type', 'The selected funding type is invalid.');
  if (input.delivery_mode && !DELIVERY_MODES.includes(input.delivery_mode)) add('delivery_mode', 'The selected delivery mode is invalid.');
  for (const [key, label] of [
    ['hours_per_week', 'hours per week'],
    ['rate', 'rate'],
  ] as const) {
    const v = input[key];
    if (v !== undefined && v !== null && String(v) !== '') {
      if (Number.isNaN(Number(v))) add(key, `The ${label} field must be a number.`);
      else if (Number(v) < 0) add(key, `The ${label} field must be at least 0.`);
    }
  }
  const first = Object.values(errors)[0]?.[0];
  if (first) {
    const more = Object.values(errors).flat().length - 1;
    throw new ApiError(422, { message: more ? `${first} (and ${more} more error${more === 1 ? '' : 's'})` : first, errors });
  }
}

function list(): PackagesPage {
  requirePackages();
  return {
    packages: [...data()].sort((a, b) => a.name.localeCompare(b.name)).map(present),
    services: [...SERVICES].sort((a, b) => a.name.localeCompare(b.name)),
    locations: [...LOCATIONS].sort((a, b) => a.name.localeCompare(b.name)),
    funding_types: FUNDING_TYPES,
    delivery_modes: DELIVERY_MODES,
  };
}

const num = (v: number | string | null | undefined) => (v === undefined || v === null || String(v) === '' ? 0 : Number(v));

function store(input: PackageInput) {
  requirePackages(true);
  validate(input);
  const row: Row = {
    id: Math.max(0, ...data().map((r) => r.id)) + 1,
    name: input.name.trim(),
    service_id: input.service_id ? Number(input.service_id) : null,
    location_id: input.location_id ? Number(input.location_id) : null,
    funding_type: input.funding_type || null,
    delivery_mode: input.delivery_mode || null,
    hours_per_week: num(input.hours_per_week),
    rate: num(input.rate),
    is_active: true,
  };
  data().push(row);
  return { message: 'Package created.', package: present(row) };
}

function update(id: number, input: PackageInput) {
  requirePackages(true);
  const row = data().find((r) => r.id === id);
  if (!row) throw new ApiError(404, { message: 'Not found.' });
  validate(input);
  // Only the fields sent change, like $package->update($validated).
  row.name = input.name.trim();
  if ('service_id' in input) row.service_id = input.service_id ? Number(input.service_id) : null;
  if ('location_id' in input) row.location_id = input.location_id ? Number(input.location_id) : null;
  if ('funding_type' in input) row.funding_type = input.funding_type || null;
  if ('delivery_mode' in input) row.delivery_mode = input.delivery_mode || null;
  if ('hours_per_week' in input) row.hours_per_week = num(input.hours_per_week);
  if ('rate' in input) row.rate = num(input.rate);
  return { message: 'Package updated.', package: present(row) };
}

function destroy(id: number) {
  requirePackages(true);
  if (!data().some((r) => r.id === id)) throw new ApiError(404, { message: 'Not found.' });
  rows = data().filter((r) => r.id !== id);
  return { message: 'Package removed.' };
}

export function createPackagesApi(): ApiClient['packages'] {
  return {
    list: () => delay(list),
    store: (input) => delay(() => store(input)),
    update: (id, input) => delay(() => update(id, input)),
    destroy: (id) => delay(() => destroy(id)),
  };
}
