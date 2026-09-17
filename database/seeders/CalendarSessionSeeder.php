<?php

namespace Database\Seeders;

use App\Models\CalendarSession;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CalendarSessionSeeder extends Seeder
{
    /**
     * Books ~4 weeks of sessions (3 weeks back through 1 week ahead) for every
     * enrolled patient, plus one assessment slot for leads still in the
     * assessment stage - matching the Day/Week/Month calendar views.
     */
    public function run(): void
    {
        $rooms = ['Room 1', 'Room 2', 'Room 3', 'Sensory gym'];
        $timeSlots = ['08:00', '09:00', '09:30', '10:00', '10:30', '11:00', '11:30', '13:00', '13:30', '14:00', '14:30', '15:00', '16:00'];
        $durations = [45, 60, 90, 120];
        $coordinator = User::whereIn('role', ['COORDINATOR', 'FULL_ADMIN'])->first();
        $supervisor = User::where('role', 'CLINICAL_SUPERVISOR')->first();

        // Tracks which start times are already taken per therapist per day, so
        // we don't accidentally double-book the same therapist on the same slot.
        $usedSlots = [];

        $reserveSlot = function (int $therapistId, string $date) use ($timeSlots, &$usedSlots) {
            $key = "{$therapistId}|{$date}";
            $usedSlots[$key] ??= [];
            $available = array_values(array_diff($timeSlots, $usedSlots[$key]));

            if (empty($available)) {
                return null;
            }

            $slot = $available[array_rand($available)];
            $usedSlots[$key][] = $slot;

            return $slot;
        };

        // --- Enrolled patients: recurring therapy sessions ---
        Lead::where('status', Lead::STATUS_ENROLLED)->with('patient')->get()->each(
            function (Lead $lead) use ($rooms, $durations, $coordinator, $reserveSlot) {
                $therapistIds = DB::table('patient_therapist')->where('patient_id', $lead->patient->id)->pluck('therapist_id');

                if ($therapistIds->isEmpty()) {
                    return;
                }

                $activityTypes = ['ABA', 'ABA', 'ABA', 'Speech', 'OT'];

                // Weekdays from 3 weeks ago through 1 week ahead.
                $cursor = now()->subWeeks(3)->startOfWeek();
                $end = now()->addWeek()->endOfWeek();

                while ($cursor->lte($end)) {
                    if ($cursor->isWeekend() || random_int(0, 2) === 0) {
                        $cursor->addDay();
                        continue;
                    }

                    $therapistId = $therapistIds->random();
                    $date = $cursor->toDateString();
                    $slot = $reserveSlot($therapistId, $date);

                    if ($slot) {
                        $isPast = $cursor->lt(now()->startOfDay());
                        $status = $isPast
                            ? (random_int(0, 9) === 0 ? 'no_show' : (random_int(0, 8) === 0 ? 'cancelled' : 'completed'))
                            : 'scheduled';

                        CalendarSession::create([
                            'therapist_id' => $therapistId,
                            'patient_id' => $lead->id,
                            'activity_type' => $activityTypes[array_rand($activityTypes)],
                            'session_date' => $date,
                            'start_time' => $slot,
                            'duration_minutes' => $durations[array_rand($durations)],
                            'room' => $rooms[array_rand($rooms)],
                            'status' => $status,
                            'cancel_reason' => $status === 'cancelled' ? (random_int(0, 1) ? 'family' : 'clinic') : null,
                            'follow_up_completed_at' => $status === 'no_show' && random_int(0, 1) === 1 ? now() : null,
                            'notes' => null,
                            'created_by' => $coordinator?->id,
                        ]);
                    }

                    $cursor->addDay();
                }
            }
        );

        // --- Assessment-stage leads: one assessment session each ---
        Lead::whereIn('status', [Lead::STATUS_ASSESSMENT_BOOKED, Lead::STATUS_ASSESSMENT_DONE])
            ->get()
            ->each(function (Lead $lead) use ($rooms, $coordinator, $supervisor, $reserveSlot) {
                $therapistId = $supervisor?->id ?? User::where('role', 'THERAPIST')->value('id');

                if (! $therapistId) {
                    return;
                }

                $isDone = $lead->status === Lead::STATUS_ASSESSMENT_DONE;
                $date = $isDone ? now()->subDays(random_int(3, 10))->toDateString() : now()->addDays(random_int(1, 6))->toDateString();
                $slot = $reserveSlot($therapistId, $date) ?? '10:00';

                CalendarSession::create([
                    'therapist_id' => $therapistId,
                    'patient_id' => $lead->id,
                    'activity_type' => 'Assessment',
                    'session_date' => $date,
                    'start_time' => $slot,
                    'duration_minutes' => 90,
                    'room' => $rooms[array_rand($rooms)],
                    'status' => $isDone ? 'completed' : 'scheduled',
                    'notes' => null,
                    'created_by' => $coordinator?->id,
                ]);
            });
    }
}
