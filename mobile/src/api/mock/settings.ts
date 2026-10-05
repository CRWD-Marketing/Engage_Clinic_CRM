/**
 * Mock of Settings (Settings\SettingsController): services, locations and
 * insurances with add, rename, active toggle and remove. Seeded from the
 * catalogue; changes here are not seen by the other mocks' pickers, which
 * read the fixed catalogue.
 */

import type { ApiClient } from '../client';
import { ApiError } from '../errors';
import type { SettingsItem, SettingsKind, SettingsPage } from '../types';
import { INSURANCES, LOCATIONS, SERVICES } from './catalog';
import { delay, requireFeature, requireUser } from './server';

const NOUN: Record<SettingsKind, string> = { services: 'Service', locations: 'Location', insurances: 'Insurance' };

let lists: Record<SettingsKind, SettingsItem[]> | null = null;
const data = () =>
  (lists ??= {
    services: SERVICES.map((s): SettingsItem => ({ id: s.id, name: s.name, is_active: true })),
    locations: LOCATIONS.map((l): SettingsItem => ({ id: l.id, name: l.name, is_active: true })),
    insurances: Object.entries(INSURANCES).map(([name, pct], i): SettingsItem => ({ id: i + 1, name, is_active: true, default_coverage_percent: pct })),
  });

function requireSettings(write = false) {
  const user = requireUser();
  requireFeature(user, 'settings', write);
}

function find(kind: SettingsKind, id: number): SettingsItem {
  const item = data()[kind].find((x) => x.id === id);
  if (!item) throw new ApiError(404, { message: 'Not found.' });
  return item;
}

/** The web's validation for each kind. */
function validate(kind: SettingsKind, input: { name: string; default_coverage_percent?: number | string | null }) {
  const fail = (field: string, message: string) => new ApiError(422, { message, errors: { [field]: [message] } });
  if (!input.name?.trim()) throw fail('name', 'The name field is required.');
  if (input.name.length > 255) throw fail('name', 'The name field must not be greater than 255 characters.');
  if (kind === 'insurances') {
    const v = input.default_coverage_percent;
    if (v === undefined || v === null || String(v) === '') throw fail('default_coverage_percent', 'The default coverage percent field is required.');
    if (!Number.isInteger(Number(v))) throw fail('default_coverage_percent', 'The default coverage percent field must be an integer.');
    if (Number(v) < 0) throw fail('default_coverage_percent', 'The default coverage percent field must be at least 0.');
    if (Number(v) > 100) throw fail('default_coverage_percent', 'The default coverage percent field must not be greater than 100.');
  }
}

const byName = (a: SettingsItem, b: SettingsItem) => a.name.localeCompare(b.name);

function page(): SettingsPage {
  requireSettings();
  const d = data();
  return { services: [...d.services].sort(byName), locations: [...d.locations].sort(byName), insurances: [...d.insurances].sort(byName) };
}

function store(kind: SettingsKind, input: { name: string; default_coverage_percent?: number | string | null }) {
  requireSettings(true);
  validate(kind, input);
  const item: SettingsItem = {
    id: Math.max(0, ...data()[kind].map((x) => x.id)) + 1,
    name: input.name,
    is_active: true,
    ...(kind === 'insurances' ? { default_coverage_percent: Number(input.default_coverage_percent) } : {}),
  };
  data()[kind].push(item);
  return { message: `${NOUN[kind]} added.`, item };
}

function update(kind: SettingsKind, id: number, input: { name: string; default_coverage_percent?: number | string | null }) {
  requireSettings(true);
  const item = find(kind, id);
  validate(kind, input);
  item.name = input.name;
  if (kind === 'insurances') item.default_coverage_percent = Number(input.default_coverage_percent);
  return { message: `${NOUN[kind]} updated.`, item };
}

function toggle(kind: SettingsKind, id: number) {
  requireSettings(true);
  const item = find(kind, id);
  item.is_active = !item.is_active;
  return { message: item.is_active ? `${item.name} is active.` : `${item.name} is inactive — it no longer shows in pickers.`, item };
}

function destroy(kind: SettingsKind, id: number) {
  requireSettings(true);
  find(kind, id);
  data()[kind] = data()[kind].filter((x) => x.id !== id);
  return { message: `${NOUN[kind]} removed.` };
}

export function createSettingsApi(): ApiClient['settings'] {
  return {
    page: () => delay(page),
    store: (kind, input) => delay(() => store(kind, input)),
    update: (kind, id, input) => delay(() => update(kind, id, input)),
    toggle: (kind, id) => delay(() => toggle(kind, id)),
    destroy: (kind, id) => delay(() => destroy(kind, id)),
  };
}
