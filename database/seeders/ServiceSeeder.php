<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    /**
     * Seed the billing service catalog - the names available when building a
     * client package (Settings > Services). Authoritative: anything not in
     * this list is removed, so the catalog always matches exactly.
     */
    public function run(): void
    {
        $services = [
            [
                'name' => 'ABA therapy session',
                'cpt_code' => '97153',
                'description' => 'behaviour treatment by protocol',
                'default_rate' => 1100,
                'is_active' => true,
            ],
            [
                'name' => 'Speech & language therapy',
                'cpt_code' => '92507',
                'description' => 'speech/language treatment',
                'default_rate' => 450,
                'is_active' => true,
            ],
            [
                'name' => 'Occupational therapy',
                'cpt_code' => '97530',
                'description' => 'therapeutic activities',
                'default_rate' => 475,
                'is_active' => true,
            ],
            [
                'name' => 'Initial assessment',
                'cpt_code' => '97151',
                'description' => 'behaviour identification assessment',
                'default_rate' => 800,
                'is_active' => true,
            ],
            [
                'name' => 'Parent training',
                'cpt_code' => '97156',
                'description' => 'family guidance',
                'default_rate' => 600,
                'is_active' => false,
            ],
        ];

        $names = collect($services)->pluck('name');
        Service::whereNotIn('name', $names)->delete();

        foreach ($services as $service) {
            Service::updateOrCreate(['name' => $service['name']], $service);
        }
    }
}
