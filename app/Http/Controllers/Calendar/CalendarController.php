<?php

namespace App\Http\Controllers\Calendar;

use App\Http\Controllers\Controller;
use App\Models\CalendarSession;
use App\Models\Lead;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CalendarController extends Controller
{
    /**
     * Show the calendar page.
     */
    public function index()
    {
        $therapists = User::where('role', 'THERAPIST')->get();
        $leads = Lead::orderBy('child_name')->get(['id', 'child_name']);

        $therapistsForJs = $therapists->map(fn ($t) => [
            'id' => $t->id,
            'name' => trim("{$t->first_name} {$t->last_name}"),
            'subtitle' => \Illuminate\Support\Str::title(str_replace('_', ' ', $t->department)),
        ])->values();

        $leadsForJs = $leads->map(fn ($l) => [
            'id' => $l->id,
            'name' => $l->child_name,
        ])->values();

        return view('calendar.index', compact('therapists', 'leads', 'therapistsForJs', 'leadsForJs'));
    }

    /**
     * JSON feed of sessions for the calendar UI.
     */
    public function feed(Request $request)
    {
        $query = CalendarSession::with(['therapist', 'child']);

        if ($request->filled('therapist_id')) {
            $query->forTherapist($request->therapist_id);
        }

        if ($request->filled('start') && $request->filled('end')) {
            $query->whereBetween('session_date', [$request->start, $request->end]);
        }

        $sessions = $query->get()->map(function (CalendarSession $s) {
            return [
                'id' => $s->id,
                'therapist_id' => $s->therapist_id,
                'patient_id' => $s->patient_id,
                'patient_name' => $s->child->child_name ?? 'Unassigned',
                'activity_type' => $s->activity_type,
                'session_date' => $s->session_date->format('Y-m-d'),
                'start_time' => substr($s->start_time, 0, 5),
                'end_time' => substr($s->end_time, 0, 5),
                'duration_minutes' => $s->duration_minutes,
                'room' => $s->room,
                'status' => $s->status,
                'notes' => $s->notes,
            ];
        });

        return response()->json($sessions);
    }

    /**
     * Book a new session (from the "Book session" modal).
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'therapist_id' => ['required', 'exists:users,id'],
            'patient_id' => ['required', 'exists:leads,id'],
            'activity_type' => ['required', 'in:ABA,Speech,OT,Assessment,Supervision,Parent training'],
            'session_date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'duration_minutes' => ['required', 'integer', 'in:30,45,60,90,120'],
            'room' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            if ($request->wantsJson()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            return back()->withErrors($validator)->withInput();
        }

        $validated = $validator->validated();

        // Prevent double-booking the same therapist in the same slot
        if ($this->hasConflict($validated)) {
            $message = 'This therapist already has a session that overlaps this time slot.';

            if ($request->wantsJson()) {
                return response()->json(['errors' => ['start_time' => [$message]]], 422);
            }

            return back()->withErrors(['start_time' => $message])->withInput();
        }

        $session = CalendarSession::create([
            ...$validated,
            'created_by' => auth()->id(),
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Session booked successfully.',
                'session' => $session->fresh(['therapist', 'child']),
            ], 201);
        }

        return redirect()
            ->route('calendar.index')
            ->with('success', 'Session booked successfully.');
    }

    public function show(CalendarSession $calendarSession)
    {
        $calendarSession->load(['therapist', 'child', 'creator']);

        if (request()->wantsJson()) {
            return response()->json($calendarSession);
        }

        return view('calendar.show', compact('calendarSession'));
    }

    public function edit(CalendarSession $calendarSession)
    {
        $therapists = User::where('role', 'THERAPIST')->get();
        $leads = Lead::orderBy('child_name')->get(['id', 'child_name']);

        return view('calendar.edit', compact('calendarSession', 'therapists', 'leads'));
    }

    public function update(Request $request, CalendarSession $calendarSession)
    {
        $validator = Validator::make($request->all(), [
            'therapist_id' => ['required', 'exists:users,id'],
            'patient_id' => ['required', 'exists:leads,id'],
            'activity_type' => ['required', 'in:ABA,Speech,OT,Assessment,Supervision,Parent training'],
            'session_date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'duration_minutes' => ['required', 'integer', 'in:30,45,60,90,120'],
            'room' => ['nullable', 'string', 'max:100'],
            'status' => ['required', 'in:scheduled,completed,cancelled,no_show'],
            'notes' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            if ($request->wantsJson()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            return back()->withErrors($validator)->withInput();
        }

        $validated = $validator->validated();

        // Prevent double-booking when moving a session to a new therapist/slot,
        // ignoring the session being edited.
        if ($this->hasConflict($validated, $calendarSession->id)) {
            $message = 'This therapist already has a session that overlaps this time slot.';

            if ($request->wantsJson()) {
                return response()->json(['errors' => ['start_time' => [$message]]], 422);
            }

            return back()->withErrors(['start_time' => $message])->withInput();
        }

        $calendarSession->update($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Session updated.',
                'session' => $calendarSession->fresh(['therapist', 'child']),
            ]);
        }

        return redirect()
            ->route('calendar.index')
            ->with('success', 'Session updated.');
    }

    public function destroy(Request $request, CalendarSession $calendarSession)
    {
        $calendarSession->delete();

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Session cancelled.']);
        }

        return redirect()
            ->route('calendar.index')
            ->with('success', 'Session cancelled.');
    }

    /**
     * Shared overlap check used by store() and update().
     */
    protected function hasConflict(array $validated, ?int $ignoreId = null): bool
    {
        $newStart = Carbon::parse($validated['start_time']);
        $newEnd = $newStart->copy()->addMinutes((int) $validated['duration_minutes']);

        $query = CalendarSession::where('therapist_id', $validated['therapist_id'])
            ->where('session_date', $validated['session_date'])
            ->where('status', '!=', 'cancelled')
            ->where(function ($q) use ($newStart, $newEnd) {
                $q->whereTime('start_time', '<', $newEnd->format('H:i:s'))
                  ->whereTime('end_time', '>', $newStart->format('H:i:s'));
            });

        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        return $query->exists();
    }
}