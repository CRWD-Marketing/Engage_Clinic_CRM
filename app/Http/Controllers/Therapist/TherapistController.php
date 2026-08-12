<?php

namespace App\Http\Controllers\Therapist;

use App\Http\Controllers\Controller;
use App\Models\CalendarSession;
use App\Models\Lead;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class TherapistController extends Controller
{
    /**
     * Roles allowed to browse every therapist's schedule and switch between
     * them. Everyone else (currently just THERAPIST) is scoped to their own
     * sessions only — see role_permissions.php's note on the THERAPIST role.
     */
    protected const FULL_VISIBILITY_ROLES = ['FULL_ADMIN', 'HR_STAFF', 'CLINICAL_SUPERVISOR'];

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $canViewAll = in_array($user->role, self::FULL_VISIBILITY_ROLES);

        $therapists = User::where('role', 'THERAPIST')->orderBy('first_name')->get();

        if (!$canViewAll) {
            // Non-admin therapists only ever see themselves — no one else's
            // sessions are queried or rendered, regardless of what's requested.
            $therapists = $therapists->where('id', $user->id)->values();
            $selectedTherapistId = $user->id;
        } else {
            // Admin-tier roles: default to the first therapist in the list
            // when none is explicitly requested (there's no "All" option).
            $selectedTherapistId = $request->filled('therapist_id')
                ? (int) $request->integer('therapist_id')
                : ($therapists->first()->id ?? null);
        }

        $days = $this->workWeekFor($request->input('week'));
        $weekStart = $days->first()->toDateString();
        $weekEnd = $days->last()->toDateString();
        $isCurrentWeek = $weekStart === Carbon::today()->startOfWeek(Carbon::MONDAY)->toDateString();
        $prevWeek = $days->first()->copy()->subWeek()->toDateString();
        $nextWeek = $days->first()->copy()->addWeek()->toDateString();

        // Every session this week within the caller's access scope — used
        // for the sidebar's per-therapist counts/hours, which must stay
        // accurate for ALL therapists regardless of which one is currently
        // selected for the day grid below.
        $weekSessionsQuery = CalendarSession::with(['therapist', 'child'])
            ->whereBetween('session_date', [$weekStart, $weekEnd]);

        if (!$canViewAll) {
            $weekSessionsQuery->where('therapist_id', $user->id);
        }

        $allWeekSessions = $weekSessionsQuery->get();

        // Sessions actually rendered in the day grid — additionally scoped
        // to the selected therapist, if one is chosen.
        $sessions = ($selectedTherapistId
            ? $allWeekSessions->where('therapist_id', $selectedTherapistId)
            : $allWeekSessions)->sortBy('start_time');

        // Real weekly workload per therapist, derived from actual booked
        // sessions (excluding cancelled ones) rather than a fixed field.
        $weeklyMinutes = $allWeekSessions
            ->where('status', '!=', 'cancelled')
            ->groupBy('therapist_id')
            ->map(fn ($group) => $group->sum('duration_minutes'));

        $therapistsForJs = collect();
        $leadsForJs = collect();

        if ($canViewAll) {
            $therapistsForJs = $therapists->map(fn ($t) => [
                'id' => $t->id,
                'name' => trim("{$t->first_name} {$t->last_name}"),
            ])->values();

            $leadsForJs = Lead::orderBy('child_name')->get(['id', 'child_name'])->map(fn ($l) => [
                'id' => $l->id,
                'name' => $l->child_name,
            ])->values();
        }

        return view('therapist.index', compact(
            'therapists', 'sessions', 'allWeekSessions', 'days', 'canViewAll', 'selectedTherapistId',
            'weeklyMinutes', 'therapistsForJs', 'leadsForJs', 'isCurrentWeek', 'prevWeek', 'nextWeek'
        ));
    }

    /**
     * Mon–Fri of the week containing $anchor (defaults to today when null
     * or unparseable), so the schedule can page into past/future weeks.
     *
     * @return \Illuminate\Support\Collection<int, \Carbon\Carbon>
     */
    protected function workWeekFor(?string $anchor)
    {
        try {
            $date = $anchor ? Carbon::parse($anchor) : Carbon::today();
        } catch (\Exception $e) {
            $date = Carbon::today();
        }

        $monday = $date->startOfWeek(Carbon::MONDAY);

        return collect(range(0, 4))->map(fn ($i) => $monday->copy()->addDays($i));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
