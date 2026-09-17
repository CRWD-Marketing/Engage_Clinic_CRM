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
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        // An actual THERAPIST is always scoped to their own sessions,
        // regardless of any level grant - never someone else's caseload.
        // Everyone else who reaches this page (already gated by the
        // 'therapists' feature/module) sees the full roster by default, but
        // an admin can explicitly set their "Therapists" access level to
        // "Own records only" in Roles & access to scope them down too. Used
        // to be an allow-list of admin-tier role names, which meant a role
        // granted this module via a per-user override (e.g. HR_STAFF) still
        // got scoped to an empty "my schedule" for not being FULL_ADMIN/
        // CLINICAL_SUPERVISOR.
        $canViewAll = $user->role !== 'THERAPIST' && $user->levelFor('therapists') !== 'own';

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
            ->whereNotIn('status', CalendarSession::INACTIVE_STATUSES)
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

        // Week is still the default landing view - Month is opt-in via ?view=month,
        // matching the toggle on the full Calendar page.
        $view = $request->input('view') === 'month' ? 'month' : 'week';

        $monthAnchor = $this->monthAnchorFor($request->input('month'));
        $monthGridStart = $monthAnchor->copy()->startOfMonth()->startOfWeek(Carbon::MONDAY);
        $monthGridEnd = $monthAnchor->copy()->endOfMonth()->endOfWeek(Carbon::MONDAY);
        $monthDays = collect();
        for ($cursor = $monthGridStart->copy(); $cursor->lte($monthGridEnd); $cursor->addDay()) {
            $monthDays->push($cursor->copy());
        }
        $isCurrentMonth = $monthAnchor->isSameMonth(Carbon::today());
        $prevMonth = $monthAnchor->copy()->subMonth()->format('Y-m');
        $nextMonth = $monthAnchor->copy()->addMonth()->format('Y-m');

        $monthSessionsQuery = CalendarSession::with(['therapist', 'child'])
            ->whereBetween('session_date', [$monthGridStart->toDateString(), $monthGridEnd->toDateString()]);

        if (!$canViewAll) {
            $monthSessionsQuery->where('therapist_id', $user->id);
        }

        $allMonthSessions = $monthSessionsQuery->get();
        $monthSessions = ($selectedTherapistId
            ? $allMonthSessions->where('therapist_id', $selectedTherapistId)
            : $allMonthSessions)->sortBy('start_time');

        return view('therapist.index', compact(
            'therapists', 'sessions', 'allWeekSessions', 'days', 'canViewAll', 'selectedTherapistId',
            'weeklyMinutes', 'therapistsForJs', 'leadsForJs', 'isCurrentWeek', 'prevWeek', 'nextWeek',
            'view', 'monthAnchor', 'monthDays', 'monthSessions', 'isCurrentMonth', 'prevMonth', 'nextMonth'
        ));
    }

    /**
     * The 1st of the requested (or current) month - never lets a bad/missing
     * ?month= value break the page.
     */
    protected function monthAnchorFor(?string $month): Carbon
    {
        try {
            return $month ? Carbon::createFromFormat('Y-m', $month)->startOfMonth() : Carbon::today()->startOfMonth();
        } catch (\Exception $e) {
            return Carbon::today()->startOfMonth();
        }
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
