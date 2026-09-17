<?php

namespace Database\Seeders;

use App\Models\Insurance;
use Illuminate\Database\Seeder;

class InsuranceSeeder extends Seeder
{
    /**
     * Seed the clinic's insurance payer list and each one's default
     * coverage % (the starting point for a new authorization).
     */
    public function run(): void
    {
        $insurances = [
            ['name' => 'Daman', 'default_coverage_percent' => 80],
            ['name' => 'Daman Enhanced', 'default_coverage_percent' => 100],
            ['name' => 'Thiqa', 'default_coverage_percent' => 100],
            ['name' => 'ADNIC', 'default_coverage_percent' => 80],
            ['name' => 'AXA / GIG', 'default_coverage_percent' => 70],
            ['name' => 'Daman Basic', 'default_coverage_percent' => 50],
            ['name' => 'NAS (Neuron)', 'default_coverage_percent' => 70],
            ['name' => 'MetLife', 'default_coverage_percent' => 60],
        ];

        foreach ($insurances as $insurance) {
            Insurance::updateOrCreate(['name' => $insurance['name']], $insurance + ['is_active' => true]);
        }
    }
}
