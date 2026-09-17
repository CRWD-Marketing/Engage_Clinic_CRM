<?php

namespace Database\Seeders;

use App\Models\Location;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    /**
     * Service zones offered when agreeing a client's package (Step 6 of the
     * lead intake checklist) - clinic-wide setup data, not patient data.
     */
    public function run(): void
    {
        $locations = [
            'Inside Abu Dhabi',
            'Inside Dubai',
            'Al Ain',
            'Clinic — Abu Dhabi',
            'Clinic — Dubai',
        ];

        foreach ($locations as $name) {
            Location::updateOrCreate(['name' => $name], ['is_active' => true]);
        }
    }
}
