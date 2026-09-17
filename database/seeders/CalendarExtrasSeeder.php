<?php

namespace Database\Seeders;

use App\Models\CalendarSession;
use App\Models\User;
use Illuminate\Database\Seeder;

class CalendarExtrasSeeder extends Seeder
{
    /**
     * The non-therapy blocks the roster shows alongside client sessions
     * (admin time, team supervision, observation), a cancelled-by-family
     * example, and a few supervised sessions with notes. Idempotent - safe to
     * re-run on an already-seeded database.
     */
    public function run(): void
    {
        $supervisor = User::where('role', 'CLINICAL_SUPERVISOR')->first();
        $therapists = User::where('role', 'THERAPIST')->orderBy('first_name')->get();
        $coordinator = User::whereIn('role', ['COORDINATOR', 'FULL_ADMIN'])->first();

        if (! $supervisor || $therapists->isEmpty()) {
            return;
        }

        $monday = now()->startOfWeek();

        $blocks = [
            // [therapist, day offset, start, minutes, type, label]
            [$supervisor, 0, '14:00', 60, 'Supervision', 'RBT team'],
            [$supervisor, 1, '10:00', 90, 'Assessment', 'New assessments'],
            [$supervisor, 3, '11:00', 60, 'Observation', 'Program reviews'],
            [$therapists[0], 2, '13:00', 60, 'Admin time', 'Admin time'],
            [$therapists[1 % $therapists->count()], 4, '15:00', 45, 'Training', 'Data collection training'],
        ];

        foreach ($blocks as [$user, $offset, $start, $minutes, $type, $label]) {
            CalendarSession::firstOrCreate(
                [
                    'therapist_id' => $user->id,
                    'session_date' => $monday->copy()->addDays($offset)->toDateString(),
                    'start_time' => $start,
                    'activity_type' => $type,
                ],
                [
                    'patient_id' => null,
                    'patient_name' => $label,
                    'activity_label' => $label,
                    'duration_minutes' => $minutes,
                    'status' => 'scheduled',
                    'created_by' => $coordinator?->id,
                ]
            );
        }

        // A family-cancelled session, so the "Cancelled — family" reason shows.
        $familyCancel = CalendarSession::where('status', 'cancelled')->whereNull('cancel_reason')->first();
        if ($familyCancel) {
            $familyCancel->update(['cancel_reason' => 'family']);
        }

        // Supervise the three most recent completed therapy sessions.
        $notes = [
            'Observed 45 min. Prompt fading on target; mand training to continue at current level.',
            'Trainee shadowing — data collection practice only.',
            'Trainee led warm-up under supervision.',
        ];

        CalendarSession::whereNull('supervised_at')
            ->where('status', 'completed')
            ->whereIn('activity_type', CalendarSession::THERAPY_TYPES)
            ->whereNotNull('patient_id')
            ->orderByDesc('session_date')
            ->limit(3)
            ->get()
            ->each(fn (CalendarSession $s, int $i) => $s->update([
                'supervised_by' => $supervisor->id,
                'supervised_at' => $s->endsAt()->addMinutes(20),
                'supervision_notes' => $notes[$i],
            ]));
    }
}
