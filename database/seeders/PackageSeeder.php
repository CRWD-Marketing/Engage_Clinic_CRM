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
     */
    public function run(): void
    {
        $aba = Service::where('name', 'LIKE', 'ABA therapy%')->first();
        $speech = Service::where('name', 'LIKE', 'Speech therapy%')->first();

        $abuDhabi = Location::where('name', 'Inside Abu Dhabi')->first();
        $dubai = Location::where('name', 'Inside Dubai')->first();
        $clinicAbuDhabi = Location::where('name', 'Clinic — Abu Dhabi')->first();

        $packages = [
            [
                'name' => 'P — 20 hrs ABA + 10 hr speech',
                'service_id' => $aba?->id,
                'location_id' => $abuDhabi?->id,
                'delivery_mode' => 'Home base',
                'hours_per_week' => 30,
                'rate' => 337,
                'funding_type' => 'Insurance',
            ],
            [
                'name' => 'P — 40 hrs ABA',
                'service_id' => $aba?->id,
                'location_id' => $abuDhabi?->id,
                'delivery_mode' => 'Home base',
                'hours_per_week' => 40,
                'rate' => 300,
                'funding_type' => 'Insurance',
            ],
            [
                'name' => 'P — 10 hrs speech/OT',
                'service_id' => $speech?->id,
                'location_id' => $abuDhabi?->id,
                'delivery_mode' => 'Home base',
                'hours_per_week' => 10,
                'rate' => 375,
                'funding_type' => 'Insurance',
            ],
            [
                'name' => 'Clinic — 15 hrs ABA + 5 hr OT',
                'service_id' => $aba?->id,
                'location_id' => $clinicAbuDhabi?->id,
                'delivery_mode' => 'Clinic',
                'hours_per_week' => 20,
                'rate' => 320,
                'funding_type' => 'Self pay',
            ],
            [
                'name' => 'P — 15 hrs ABA + 3 hr speech (Dubai)',
                'service_id' => $aba?->id,
                'location_id' => $dubai?->id,
                'delivery_mode' => 'Home base',
                'hours_per_week' => 18,
                'rate' => 340,
                'funding_type' => 'Insurance',
            ],
        ];

        foreach ($packages as $package) {
            Package::updateOrCreate(
                ['name' => $package['name']],
                $package + ['is_active' => true]
            );
        }
    }
}
