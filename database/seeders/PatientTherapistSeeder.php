<?php

namespace Database\Seeders;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PatientTherapistSeeder extends Seeder
{
    /**
     * Assign 1-2 therapists to each patient's care team via the patient_therapist
     * pivot table. Note: Patient::careTeam() actually derives the care team from
     * CalendarSession rows instead of this pivot - CalendarSessionSeeder is what
     * the UI reads from. This just keeps the pivot table (used for reporting/
     * future features) populated consistently with who's really assigned.
     */
    public function run(): void
    {
        $therapists = User::where('role', 'THERAPIST')->pluck('id')->all();

        if (empty($therapists)) {
            return;
        }

        Patient::all()->each(function (Patient $patient) use ($therapists) {
            $assigned = collect($therapists)->shuffle()->take(random_int(1, 2));

            foreach ($assigned as $therapistId) {
                DB::table('patient_therapist')->insertOrIgnore([
                    'patient_id' => $patient->id,
                    'therapist_id' => $therapistId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });
    }
}
