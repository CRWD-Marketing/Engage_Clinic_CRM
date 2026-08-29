<?php

namespace Database\Seeders;

use App\Models\Patient;
use App\Models\PatientNote;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PatientNoteSeeder extends Seeder
{
    public function run(): void
    {
        $supervisor = User::where('role', 'CLINICAL_SUPERVISOR')->first();

        $bodies = [
            'Great session today - stayed regulated through the full 2 hours and initiated play twice.',
            'Worked on requesting preferred items using PECS cards; 8/10 independent trials.',
            'Some difficulty with transitions between activities, used visual schedule to help.',
            'Parent reports improved sleep routine at home this week.',
            'Introduced new mand target "more" during snack time, good progress.',
            'Session focused on joint attention tasks - responded well to name call 7/10 trials.',
        ];

        $flagReasons = [
            'Increase in self-injurious behaviour observed, flagging for BCBA review.',
            'Family requested a change in session times - needs scheduling follow-up.',
        ];

        Patient::all()->each(function (Patient $patient) use ($supervisor, $bodies, $flagReasons) {
            $therapistIds = DB::table('patient_therapist')->where('patient_id', $patient->id)->pluck('therapist_id');

            if ($therapistIds->isEmpty()) {
                return;
            }

            $noteCount = random_int(2, 4);

            for ($i = 0; $i < $noteCount; $i++) {
                $authorId = $therapistIds->random();
                $createdAt = $patient->enrolled_at->copy()->addDays(random_int(1, 40));
                $flagged = $i === 0 && random_int(0, 4) === 0;
                $isSignedOff = $supervisor && random_int(0, 2) > 0;

                PatientNote::create([
                    'patient_id' => $patient->id,
                    'user_id' => $authorId,
                    'body' => $bodies[array_rand($bodies)],
                    'flagged' => $flagged,
                    'flag_reason' => $flagged ? $flagReasons[array_rand($flagReasons)] : null,
                    'signed_off_at' => $isSignedOff ? $createdAt->copy()->addDay() : null,
                    'signed_off_by' => $isSignedOff ? $supervisor->id : null,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);
            }
        });
    }
}
