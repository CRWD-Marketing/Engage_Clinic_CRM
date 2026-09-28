<?php

namespace Database\Seeders;

use App\Models\Location;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    /**
     * Service zones offered when agreeing a client's package (Step 6 of the
     * lead intake checklist) - clinic-wide setup data, not patient data.
     * Authoritative: anything not in this list is removed, so the catalog
     * always matches exactly.
     */
    public function run(): void
    {
        $locations = [
            'Inside Abu Dhabi',
            'Abu Dhabi around city boundary',
            'Abu Dhabi remote and away from city boundary',
            'Clinic direct visit',
        ];

        Location::whereNotIn('name', $locations)->delete();

        foreach ($locations as $name) {
            Location::updateOrCreate(['name' => $name], ['is_active' => true]);
        }
    }
}
