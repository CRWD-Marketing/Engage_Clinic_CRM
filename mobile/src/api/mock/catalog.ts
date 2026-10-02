/**
 * Catalogue data from the Laravel seeders (ServiceSeeder, LocationSeeder,
 * PackageSeeder, InsuranceSeeder) that pickers and intake forms use.
 */

import type { PackageOption } from '../types';

/** ServiceSeeder (active services). */
export const SERVICES = [
  { id: 1, name: 'ABA therapy session' },
  { id: 2, name: 'Speech & language therapy' },
  { id: 3, name: 'Occupational therapy' },
  { id: 4, name: 'Initial assessment' },
];

/** LocationSeeder — service zones, not branches. */
export const LOCATIONS = [
  { id: 1, name: 'Inside Abu Dhabi' },
  { id: 2, name: 'Abu Dhabi around city boundary' },
  { id: 3, name: 'Abu Dhabi remote and away from city boundary' },
  { id: 4, name: 'Clinic direct visit' },
];

/** InsuranceSeeder: name → default coverage %. */
export const INSURANCES: Record<string, number> = {
  ADNIC: 80,
  'AXA / GIG': 70,
  Daman: 80,
  'Daman Basic': 50,
  'Daman Enhanced': 100,
  MetLife: 60,
  'NAS (Neuron)': 70,
  Thiqa: 100,
};

type PackageSeed = {
  id: number;
  name: string;
  service_id: number;
  location_id: number;
  delivery_mode: string;
  hours_per_week: number;
  rate: number;
};

/** PackageSeeder */
export const PACKAGE_SEEDS: PackageSeed[] = [
  { id: 1, name: 'P-20 hrs ABA and 10 hr speech', service_id: 1, location_id: 1, delivery_mode: 'Home base', hours_per_week: 30, rate: 337 },
  { id: 2, name: 'Package — 40 hrs ABA', service_id: 1, location_id: 1, delivery_mode: 'Clinic', hours_per_week: 40, rate: 250 },
  { id: 3, name: 'P-10 hrs speech / OT', service_id: 2, location_id: 1, delivery_mode: 'Home base', hours_per_week: 10, rate: 625 },
  { id: 4, name: 'Early intervention starter', service_id: 3, location_id: 2, delivery_mode: 'Home base', hours_per_week: 12, rate: 705 },
];

/** Packages with Package::summaryLabel() ("Service · mode · N h/wk · AED R/hr"). */
export const PACKAGES: PackageOption[] = PACKAGE_SEEDS.map((p) => ({
  id: p.id,
  name: p.name,
  location_id: p.location_id,
  summary: [
    SERVICES.find((s) => s.id === p.service_id)?.name,
    p.delivery_mode,
    `${p.hours_per_week} h/wk`,
    `AED ${p.rate}/hr`,
  ]
    .filter(Boolean)
    .join(' · '),
}));
