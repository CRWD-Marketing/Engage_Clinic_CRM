<?php

namespace Database\Seeders;

use App\Models\CalendarSession;
use App\Models\StaffLeave;
use App\Models\User;
use Illuminate\Database\Seeder;

class StaffLeaveSeeder extends Seeder
{
    /**
     * A couple of leave days this week / next, mirroring what the Supervisor's
     * "Mark leave" action does: record the leave and cancel (clinic-side) any
     * sessions still scheduled for that person that day.
     */
    public function run(): void
    {
        $therapists = User::where('role', 'THERAPIST')->orderBy('first_name')->get();
        $supervisor = User::where('role', 'CLINICAL_SUPERVISOR')->first();

        if ($therapists->count() < 2) {
            return;
        }

        $leaves = [
            [$therapists[0], now()->startOfWeek()->addDay(), 'Sick leave', 'Called in sick — cover arranged with the RBT team.'],
            [$therapists[1], now()->startOfWeek()->addWeek()->addDays(3), 'Annual leave', 'Approved annual leave.'],
        ];

        foreach ($leaves as [$user, $date, $type, $reason]) {
            StaffLeave::updateOrCreate(
                ['user_id' => $user->id, 'leave_date' => $date->toDateString()],
                ['leave_type' => $type, 'reason' => $reason, 'created_by' => $supervisor?->id]
            );

            CalendarSession::where('therapist_id', $user->id)
                ->whereDate('session_date', $date->toDateString())
                ->where('status', 'scheduled')
                ->get()
                ->each(fn (CalendarSession $s) => $s->update([
                    'status' => 'cancelled',
                    'cancel_reason' => 'clinic',
                    'notes' => trim(($s->notes ? $s->notes."\n" : '')."Cancelled — {$user->first_name} on {$type}."),
                ]));
        }
    }
}
