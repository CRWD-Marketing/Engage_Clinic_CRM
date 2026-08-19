<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    /**
     * Seed the billing service catalog.
     */
    public function run(): void
    {
        $services = [
            [
                'name' => 'ABA therapy — 1:1 session (120 min)',
                'cpt_code' => '97153',
                'description' => 'behaviour treatment by protocol',
                'default_rate' => 1100,
            ],
            [
                'name' => 'BCBA supervision & program update',
                'cpt_code' => '97155',
                'description' => 'protocol modification',
                'default_rate' => 1400,
            ],
            [
                'name' => 'Speech therapy — individual (45 min)',
                'cpt_code' => '92507',
                'description' => 'speech/language treatment',
                'default_rate' => 450,
            ],
            [
                'name' => 'Parent training session (60 min)',
                'cpt_code' => '97156',
                'description' => 'family guidance',
                'default_rate' => 600,
            ],
        ];

        foreach ($services as $service) {
            Service::updateOrCreate(['name' => $service['name']], $service);
        }
    }
}
