<?php

namespace Database\Seeders;

use App\Models\Patient;
use App\Models\PatientGoal;
use Illuminate\Database\Seeder;

class PatientGoalSeeder extends Seeder
{
    public function run(): void
    {
        $goalPool = [
            'Increase eye contact during structured play to 80% of trials',
            'Expand expressive vocabulary to 50 spontaneous words',
            'Reduce self-injurious behaviour frequency by 50%',
            'Tolerate transitions between activities without prompting',
            'Independently request preferred items using PECS',
            'Increase joint attention responses during name calling',
            'Follow 2-step instructions independently',
            'Improve fine motor skills for pencil grip',
            'Increase tolerance for group activities to 15 minutes',
            'Independently complete a 5-step visual schedule',
        ];

        Patient::all()->each(function (Patient $patient) use ($goalPool) {
            $goals = collect($goalPool)->shuffle()->take(random_int(2, 3));

            foreach ($goals as $title) {
                PatientGoal::create([
                    'patient_id' => $patient->id,
                    'title' => $title,
                    'progress_percent' => random_int(10, 90),
                ]);
            }
        });
    }
}
