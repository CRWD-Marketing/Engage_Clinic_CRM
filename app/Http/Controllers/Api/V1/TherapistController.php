<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Therapist\TherapistController as WebTherapistController;
use App\Models\CalendarSession;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * "Therapists & schedules" for the mobile app: the roster with each
 * therapist's workload for the week, and the selected therapist's sessions.
 * Close / Reopen and Add / Edit session go through the calendar endpoints.
 */
class TherapistController extends WebTherapistController
{
    /** GET /therapists?therapist_id=&week= — TherapistController::index() view data as JSON. */
    public function index(Request $request)
    {
        $data = parent::index($request)->getData();
        $calendar = app(CalendarController::class);
        $allWeekSessions = $data['allWeekSessions'];

        return response()->json([
            'can_view_all' => $data['canViewAll'],
            'week_start' => $data['days']->first()->toDateString(),
            'week_end' => $data['days']->last()->toDateString(),
            'is_current_week' => $data['isCurrentWeek'],
            'selected_therapist_id' => $data['selectedTherapistId'],
            'therapists' => $data['therapists']->map(fn (User $therapist) => [
                'id' => $therapist->id,
                'name' => trim("{$therapist->first_name} {$therapist->last_name}"),
                'department_label' => Str::title(str_replace('_', ' ', $therapist->department ?? '')),
                // Booked hours, cancelled and closed sessions excluded.
                'weekly_hours' => round(($data['weeklyMinutes'][$therapist->id] ?? 0) / 60, 1),
                // Every session that week, whatever its status.
                'session_count' => $allWeekSessions->where('therapist_id', $therapist->id)->count(),
            ])->values(),
            'sessions' => $data['sessions']
                ->sortBy(fn (CalendarSession $s) => $s->session_date->toDateString().' '.$s->start_time)
                ->map(function (CalendarSession $session) use ($calendar) {
                    $session->loadMissing(['therapist', 'child', 'coverFor', 'supervisor']);

                    return $calendar->payloadFor($session);
                })->values(),
        ]);
    }
}
