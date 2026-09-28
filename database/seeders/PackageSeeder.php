<?php

namespace Database\Seeders;

use App\Models\Location;
use App\Models\Package;
use App\Models\Service;
use Illuminate\Database\Seeder;

class PackageSeeder extends Seeder
{
    /**
     * Sellable package bundles offered when agreeing a client's package
     * (Step 6 of the lead intake checklist) - clinic-wide setup data.
     * Authoritative: anything not in this list is removed, so the catalog
     * always matches exactly.
     */
    public function run(): void
    {
        $aba = Service::where('name', 'ABA therapy session')->first();
        $speech = Service::where('name', 'Speech & language therapy')->first();
        $ot = Service::where('name', 'Occupational therapy')->first();

        $insideAbuDhabi = Location::where('name', 'Inside Abu Dhabi')->first();
        $aroundCityBoundary = Location::where('name', 'Abu Dhabi around city boundary')->first();

        $packages = [
            [
                'name' => 'P-20 hrs ABA and 10 hr speech',
                'service_id' => $aba?->id,
                'location_id' => $insideAbuDhabi?->id,
                'delivery_mode' => 'Home base',
                'hours_per_week' => 30,
                'rate' => 337,
                'funding_type' => 'Self pay',
            ],
            [
                'name' => 'Package — 40 hrs ABA',
                'service_id' => $aba?->id,
                'location_id' => $insideAbuDhabi?->id,
                'delivery_mode' => 'Clinic',
                'hours_per_week' => 40,
                'rate' => 250,
                'funding_type' => 'Self pay',
            ],
            [
                'name' => 'P-10 hrs speech / OT',
                'service_id' => $speech?->id,
                'location_id' => $insideAbuDhabi?->id,
                'delivery_mode' => 'Home base',
                'hours_per_week' => 10,
                'rate' => 625,
                'funding_type' => 'Self pay',
            ],
            [
                'name' => 'Early intervention starter',
                'service_id' => $ot?->id,
                'location_id' => $aroundCityBoundary?->id,
                'delivery_mode' => 'Home base',
                'hours_per_week' => 12,
                'rate' => 705,
                'funding_type' => 'Insurance',
            ],
        ];

        $names = collect($packages)->pluck('name');
        Package::whereNotIn('name', $names)->delete();

        foreach ($packages as $package) {
            Package::updateOrCreate(
                ['name' => $package['name']],
                $package + ['is_active' => true]
            );
        }
    }
}
