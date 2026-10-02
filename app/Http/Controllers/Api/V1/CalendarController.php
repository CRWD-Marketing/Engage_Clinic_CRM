<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Calendar\CalendarController as WebCalendarController;
use App\Models\CalendarSession;
use App\Models\StaffLeave;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Calendar for the mobile app. Booking, editing, supervision and the
 * therapist's note are the web controller's own actions (they already answer
 * JSON); this adds JSON for the two screens the web renders as HTML - the
 * therapist's week and the booking form's option lists - and carries the
 * viewer's own `therapist_note` on the sessions it returns.
 */
class CalendarController extends WebCalendarController
{
    /** GET /calendar/my-week?date= — CalendarController::myCalendar() as JSON. */
    public function myWeek(Request $request)
    {
        $user = $request->user();

        CalendarSession::pastDueScheduled()->update(['status' => 'completed']);

        $anchor = $request->filled('date') ? Carbon::parse($request->date) : now();
        $monday = $anchor->copy()->startOfWeek(Carbon::MONDAY);
        $sunday = $monday->copy()->addDays(6);
        $range = [$monday->toDateString(), $sunday->toDateString()];

        $sessions = CalendarSession::with(['therapist', 'child', 'coverFor', 'supervisor'])
            ->forTherapist($user->id)
            ->notClosed()
            ->whereBetween('session_date', $range)
            ->orderBy('start_time')
            ->get()
            ->groupBy(fn (CalendarSession $s) => $s->session_date->toDateString());

        $leaves = StaffLeave::where('user_id', $user->id)
            ->whereBetween('leave_date', $range)
            ->get()
            ->keyBy(fn ($l) => $l->leave_date->toDateString());

        $therapyHours = CalendarSession::forTherapist($user->id)
            ->directTherapy()
            ->whereIn('status', ['completed', 'scheduled'])
            ->whereBetween('session_date', $range)
            ->sum('duration_minutes') / 60;

        return response()->json([
            'therapist_name' => $this->staffName($user),
            'designation' => $this->designation($user),
            'monday' => $monday->toDateString(),
            'sunday' => $sunday->toDateString(),
            'prev_date' => $monday->copy()->subWeek()->toDateString(),
            'next_date' => $monday->copy()->addWeek()->toDateString(),
            'therapy_hours' => round($therapyHours, 1),
            'days' => collect(range(0, 6))->map(function (int $i) use ($monday, $sessions, $leaves, $user) {
                $iso = $monday->copy()->addDays($i)->toDateString();
                $leave = $leaves->get($iso);

                return [
                    'iso' => $iso,
                    'is_today' => $iso === now()->toDateString(),
                    'is_weekend' => $i >= 5,
                    'sessions' => $sessions->get($iso, collect())
                        ->map(fn (CalendarSession $s) => $this->mySessionPayload($s, $user->id))->values(),
                    'leave' => $leave ? [
                        'id' => $leave->id,
                        'user_id' => $leave->user_id,
                        'leave_date' => $iso,
                        'leave_type' => $leave->leave_type,
                        'reason' => $leave->reason,
                    ] : null,
                ];
            }),
        ]);
    }

    /** GET /calendar/feed?start=&end= — the web feed, plus the viewer's own therapist notes. */
    public function feed(Request $request)
    {
        $request->validate(['start' => ['required', 'date'], 'end' => ['required', 'date']]);

        $data = parent::feed($request)->getData(true);

        $ownNotes = CalendarSession::where('therapist_id', $request->user()->id)
            ->whereIn('id', array_column($data['sessions'], 'id'))
            ->pluck('therapist_note', 'id');

        $data['sessions'] = array_map(
            fn (array $s) => $s + ['therapist_note' => $ownNotes[$s['id']] ?? null],
            $data['sessions']
        );

        return response()->json($data);
    }

    /**
     * GET /calendar/{id}. The web's show() has no ownership check; here a
     * user whose calendar access is "own records only" can open their own
     * sessions and nobody else's.
     */
    public function show(CalendarSession $calendarSession)
    {
        $user = auth()->user();

        abort_if(
            $user->levelFor('calendar') === 'own' && (int) $calendarSession->therapist_id !== (int) $user->id,
            403,
            'Only your own sessions.'
        );

        CalendarSession::pastDueScheduled()->update(['status' => 'completed']);
        $calendarSession->refresh()->load(['therapist', 'child', 'creator', 'coverFor', 'supervisor']);

        return response()->json($this->mySessionPayload($calendarSession, $user->id));
    }

    /** GET /calendar/booking-options — what calendar/index.blade.php embeds for its booking panel. */
    public function bookingOptions()
    {
        $customTypes = CalendarSession::query()->distinct()->pluck('activity_type')->filter()
            ->diff(CalendarSession::DEFAULT_TYPES)->values();

        return response()->json([
            'therapists' => $this->rosterStaff()->map(fn ($t) => ['id' => $t->id, 'name' => $this->staffName($t)])->values(),
            'leads' => $this->leadsPayload(),
            'activity_types' => array_values(array_unique(array_merge(CalendarSession::DEFAULT_TYPES, $customTypes->all()))),
            'durations' => self::DURATIONS,
            'leave_types' => StaffLeave::TYPES,
        ]);
    }

    /** sessionPayload() for other API controllers (dashboards, patients, therapists). */
    public function payloadFor(CalendarSession $session): array
    {
        return $this->sessionPayload($session);
    }

    /** sessionPayload() plus `therapist_note`, which only the session's own therapist ever receives. */
    protected function mySessionPayload(CalendarSession $s, int $viewerId): array
    {
        return $this->sessionPayload($s) + [
            'therapist_note' => (int) $s->therapist_id === $viewerId ? $s->therapist_note : null,
        ];
    }
}
