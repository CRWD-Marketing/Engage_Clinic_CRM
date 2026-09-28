<?php

namespace App\Http\Controllers\Calendar;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\CalendarSession;
use App\Models\Lead;
use App\Models\Package;
use App\Models\PatientAuthorization;
use App\Models\PatientNote;
use App\Models\StaffLeave;
use App\Models\User;
use App\Notifications\SessionAssigned;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CalendarController extends Controller
{
    /**
     * Roles allowed to change a session once it's on the calendar (reschedule,
     * cancel, reassign, supervise) and to mark staff leave.
     */
    private const MANAGE_ROLES = ['CLINICAL_SUPERVISOR', 'FULL_ADMIN'];

    /**
     * Staff who appear as rows/columns on the roster - Clinical Standard Access
     * (THERAPIST) only. Clinical Admin Access (CLINICAL_SUPERVISOR) oversees the
     * schedule via MANAGE_ROLES but doesn't get a bookable column of their own.
     */
    private const ROSTER_ROLES = ['THERAPIST'];

    private const DURATIONS = [30, 45, 60, 90, 120, 150, 180];

    public function index(Request $request)
    {
        $user = auth()->user();

        // A therapist ("Clinical Standard Access") only ever sees their own
        // booked hours, never the full roster - a lightweight read-only
        // weekly agenda instead of the grid a supervisor/admin manages.
        if ($user->levelFor('calendar') === 'own') {
            return $this->myCalendar($request, $user);
        }

        $staff = $this->rosterStaff();

        $customTypes = CalendarSession::query()
            ->distinct()
            ->pluck('activity_type')
            ->filter()
            ->diff(CalendarSession::DEFAULT_TYPES)
            ->values();

        // Deep-link support: ?session={id} (used by topbar search/notifications) opens
        // that session's detail panel on the day it falls on, instead of always "today".
        $initialSession = $request->filled('session')
            ? CalendarSession::find($request->integer('session'))
            : null;

        return view('calendar.index', [
            'initialSessionId' => $initialSession?->id,
            'initialDate' => $initialSession?->session_date?->toDateString(),
            'staffForJs' => $staff->map(fn (User $t) => [
                'id' => $t->id,
                'name' => $this->staffName($t),
                'designation' => $this->designation($t),
            ])->values(),
            // Only converted clients (a Lead with a Patient record) are bookable
            // here - a lead still in the pipeline isn't an enrolled client yet.
            // "+ Add custom patient" stays the escape hatch for anyone else.
            'leadsForJs' => $this->leadsPayload(),
            'types' => array_values(array_unique(array_merge(CalendarSession::DEFAULT_TYPES, $customTypes->all()))),
            'leaveTypes' => StaffLeave::TYPES,
            'durations' => self::DURATIONS,
            'canManage' => $this->canManage(),
            'canBook' => $user->canDo('book_modify_session'),
            'capabilities' => $this->capabilitiesForUser($user),
            'currentUserId' => $user->id,
            'currentUserName' => $this->staffName($user),
        ]);
    }

    /**
     * "My calendar": a therapist's own week, read-only, one column per day.
     */
    private function myCalendar(Request $request, User $user)
    {
        // Same catch-up feed() does, so a session that ended since the page
        // was last loaded already shows as completed, not still "upcoming".
        CalendarSession::pastDueScheduled()->update(['status' => 'completed']);

        $anchor = $request->filled('date') ? Carbon::parse($request->date) : now();
        $monday = $anchor->copy()->startOfWeek(Carbon::MONDAY);
        $sunday = $monday->copy()->addDays(6);

        $sessions = CalendarSession::with('supervisor')
            ->forTherapist($user->id)
            ->notClosed()
            ->whereBetween('session_date', [$monday->toDateString(), $sunday->toDateString()])
            ->orderBy('start_time')
            ->get()
            ->groupBy(fn (CalendarSession $s) => $s->session_date->toDateString());

        $leaves = StaffLeave::where('user_id', $user->id)
            ->whereBetween('leave_date', [$monday->toDateString(), $sunday->toDateString()])
            ->get()
            ->keyBy(fn ($l) => $l->leave_date->toDateString());

        $therapyHours = CalendarSession::forTherapist($user->id)
            ->directTherapy()
            ->whereIn('status', ['completed', 'scheduled'])
            ->whereBetween('session_date', [$monday->toDateString(), $sunday->toDateString()])
            ->sum('duration_minutes') / 60;

        $days = collect(range(0, 6))->map(function (int $i) use ($monday, $sessions, $leaves) {
            $date = $monday->copy()->addDays($i);
            $iso = $date->toDateString();

            return [
                'date' => $date,
                'iso' => $iso,
                'is_today' => $iso === now()->toDateString(),
                'is_weekend' => $i >= 5,
                'sessions' => $sessions->get($iso, collect()),
                'leave' => $leaves->get($iso),
            ];
        });

        return view('calendar.my', [
            'user' => $user,
            'therapistName' => $this->staffName($user),
            'designation' => $this->designation($user),
            'capabilities' => $this->capabilitiesForUser($user),
            'monday' => $monday,
            'sunday' => $sunday,
            'prevDate' => $monday->copy()->subWeek()->toDateString(),
            'nextDate' => $monday->copy()->addWeek()->toDateString(),
            'therapyHours' => round($therapyHours, 1),
            'days' => $days,
        ]);
    }

    /**
     * A therapist's own quick note on a session that's already happened -
     * private to them, distinct from the scheduling `notes` field and from
     * the supervisor's `supervision_notes`.
     */
    public function therapistNote(Request $request, CalendarSession $calendarSession)
    {
        abort_unless((int) $calendarSession->therapist_id === (int) auth()->id(), 403, 'Only your own sessions.');

        $validator = Validator::make($request->all(), [
            'note' => ['nullable', 'string', 'max:2000'],
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $calendarSession->update(['therapist_note' => $request->input('note') ?: null]);

        return response()->json(['message' => 'Note saved.', 'therapist_note' => $calendarSession->therapist_note]);
    }

    /**
     * JSON feed of sessions + staff leave for a date range.
     */
    public function feed(Request $request)
    {
        $start = $request->input('start', now()->toDateString());
        $end = $request->input('end', $start);

        // Same catch-up the scheduled command does, so attendance is right the
        // moment anyone opens the calendar even if the server cron is late.
        CalendarSession::pastDueScheduled()->update(['status' => 'completed']);

        // Closed slots (Therapists & schedules -> Close) are discontinued and
        // never appear on the calendar; reopening brings them straight back.
        $sessions = CalendarSession::with(['therapist', 'child', 'coverFor', 'supervisor'])
            ->notClosed()
            ->whereBetween('session_date', [$start, $end]);
        $leaves = StaffLeave::with('user')->whereBetween('leave_date', [$start, $end]);

        if (auth()->user()->role === 'THERAPIST') {
            // Enforced server-side regardless of the request - a therapist can't
            // see anyone else's schedule by tampering with the query string.
            $sessions->forTherapist(auth()->id());
            $leaves->where('user_id', auth()->id());
        } elseif ($request->filled('therapist_id')) {
            $sessions->forTherapist($request->therapist_id);
            $leaves->where('user_id', $request->therapist_id);
        }

        return response()->json([
            'sessions' => $sessions->get()->map(fn (CalendarSession $s) => $this->sessionPayload($s))->values(),
            'leaves' => $leaves->get()->map(fn (StaffLeave $l) => [
                'id' => $l->id,
                'user_id' => $l->user_id,
                'user_name' => $l->user ? $this->staffName($l->user) : null,
                'leave_date' => $l->leave_date->format('Y-m-d'),
                'leave_type' => $l->leave_type,
                'reason' => $l->reason,
            ])->values(),
        ]);
    }

    /**
     * Fresh copy of leadsForJs (package/authorization hours, upcoming-session
     * status) for the booking panel to re-pull whenever it opens. index()'s
     * own copy is only ever a snapshot from when the page was last loaded -
     * without this, a session booked earlier in the same page visit (by this
     * user or anyone else) wouldn't show as "used" until a full page reload.
     */
    public function leads()
    {
        return response()->json(['leads' => $this->leadsPayload()]);
    }

    /**
     * Book session(s). One session is created per selected therapist per
     * occurrence (a single date, or every week / every 2 weeks for either an
     * explicit number of occurrences or, left blank, however many the
     * patient's remaining package hours allow at this duration - see
     * packageSessionBudget()). Slots that would double-book a therapist are
     * skipped and reported rather than failing the whole batch.
     */
    public function store(Request $request)
    {
        abort_unless(auth()->user()->canDo('book_modify_session'), 403, 'Your access level can’t book sessions.');

        $input = $this->normaliseLegacyInput($request->all());

        if (count($input['therapist_ids'] ?? []) > 1 && ! auth()->user()->canDo('assign_multiple_therapists')) {
            return $request->wantsJson()
                ? response()->json(['message' => 'Your access level can’t assign multiple therapists to one booking.'], 403)
                : back()->withErrors(['therapist_ids' => 'Your access level can’t assign multiple therapists.'])->withInput();
        }

        $validator = Validator::make($input, [
            'therapist_ids' => ['required', 'array', 'min:1'],
            'therapist_ids.*' => ['integer', 'exists:users,id'],
            'patient_ids' => ['nullable', 'array'],
            'patient_ids.*' => ['integer', 'exists:leads,id'],
            'custom_patient' => ['nullable', 'string', 'max:120'],
            'activity_label' => ['nullable', 'string', 'max:120'],
            'activity_types' => ['required', 'array', 'min:1'],
            'activity_types.*' => ['string', 'max:50'],
            'session_date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'duration_minutes' => ['required', 'integer', 'in:'.implode(',', self::DURATIONS)],
            'room' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:'.implode(',', CalendarSession::MANUAL_STATUSES)],
            'cancel_reason' => ['nullable', 'in:'.implode(',', CalendarSession::CANCEL_REASONS)],
            'cancel_notice_hours' => ['nullable', 'numeric', 'between:0,720'],
            'notes' => ['nullable', 'string'],
            'repeats' => ['nullable', 'in:none,weekly,biweekly'],
            // Weekly bookings can run on several weekdays (Mon–Thu, say) - one
            // series per day, all starting on/after session_date. 0 = Sunday.
            'weekdays' => ['nullable', 'array'],
            'weekdays.*' => ['integer', 'between:0,6'],
            // How many occurrences a recurring booking should create right now.
            // Optional - left blank, the series is auto-sized from the single
            // selected patient's remaining package hours for this duration (see
            // packageSessionBudget()); given explicitly, exactly that many
            // occurrences are created and no more, whatever the weekday pattern
            // works out to. Either way the series always stops on its own -
            // there is no "repeat until" date to bound it instead.
            'occurrences' => ['nullable', 'integer', 'min:1', 'max:520'],
        ]);

        $validator->after(function ($v) use ($input) {
            if (empty($input['patient_ids']) && blank($input['custom_patient'] ?? null) && blank($input['activity_label'] ?? null)) {
                $v->errors()->add('patient_ids', 'Pick a patient, or type a custom patient / activity.');
            }
            if (($input['repeats'] ?? 'none') === 'weekly' && array_key_exists('weekdays', $input) && empty($input['weekdays'])) {
                $v->errors()->add('weekdays', 'Pick at least one day of the week.');
            }
        });

        if ($validator->fails()) {
            return $this->validationFailed($request, $validator);
        }

        $data = $validator->validated();
        $attributes = $this->sessionAttributes($data);

        $repeats = $data['repeats'] ?? 'none';
        $dates = [$data['session_date']];

        if ($repeats !== 'none') {
            $base = Carbon::parse($data['session_date']);
            $weekdays = ! empty($data['weekdays']) ? array_map('intval', $data['weekdays']) : [$base->dayOfWeek];
            $maxOccurrences = $data['occurrences'] ?? null;

            if ($maxOccurrences === null) {
                // "Using patient hours": no explicit count given, so size the
                // series to exactly what the single selected patient's
                // remaining package balance allows at this duration (e.g. 30h
                // left at 60 min/session -> 30 sessions, at 30 min/session ->
                // 60 sessions). Null here means there's nothing to size
                // against at all (group/custom booking, or no package on file
                // covering this type) - staff must give an explicit count
                // instead, so the series still always has a defined stop.
                $maxOccurrences = $this->packageSessionBudget($data, count($data['therapist_ids']));

                if ($maxOccurrences === null) {
                    $message = 'Set a number of sessions — this patient has no package on file to size a repeating booking automatically.';

                    return $request->wantsJson()
                        ? response()->json(['message' => $message, 'errors' => ['occurrences' => [$message]]], 422)
                        : back()->withErrors(['occurrences' => $message])->withInput();
                }

                if ($maxOccurrences === 0) {
                    $message = 'No package hours remain for this type — nothing to book.';

                    return $request->wantsJson()
                        ? response()->json(['message' => $message, 'errors' => ['occurrences' => [$message]]], 422)
                        : back()->withErrors(['occurrences' => $message])->withInput();
                }

                // Same safety ceiling as an explicit `occurrences` value - a
                // mistyped package total (e.g. 10,000h instead of 100h)
                // shouldn't be able to spin up thousands of rows in one request.
                $maxOccurrences = min($maxOccurrences, 520);
            }

            // The occurrence count (explicit, or just sized above) is the real
            // stop; this date is only a backstop so the loop below terminates
            // even for a large count spread across few weekdays.
            $end = $base->copy()->addWeeks((int) ceil($maxOccurrences / max(1, count($weekdays))) + 2);
            $everyOtherWeek = $repeats === 'biweekly';
            $dates = [];

            for ($cursor = $base->copy(); $cursor->lte($end); $cursor->addDay()) {
                if (! in_array($cursor->dayOfWeek, $weekdays, true)) {
                    continue;
                }
                if ($everyOtherWeek && intdiv($base->diffInDays($cursor), 7) % 2 === 1) {
                    continue;
                }
                $dates[] = $cursor->toDateString();
                if (count($dates) >= $maxOccurrences) {
                    break;
                }
            }
        }

        $group = $repeats !== 'none' ? (string) Str::uuid() : null;

        // Hard stop: this booking (whatever combination of therapists, dates
        // and duration it works out to) must not ask for more hours than the
        // patient's matching package has left. Checked against the exact
        // $dates about to be created, not an estimate, and before anything is
        // written - so a rejected booking creates nothing at all.
        $packageError = $this->packageOverbookError($data, $dates, count($data['therapist_ids']));
        if ($packageError) {
            return $request->wantsJson()
                ? response()->json(['message' => $packageError, 'errors' => ['duration_minutes' => [$packageError]]], 422)
                : back()->withErrors(['duration_minutes' => $packageError])->withInput();
        }

        // Nothing above caps $dates to fit remaining hours anymore (booking
        // only ever creates what staff explicitly asked for) - warn (but
        // don't block) when it would still push the patient past what's
        // authorized by insurance specifically, a separate, billing-routing
        // concern from the package-hours check above.
        $authWarning = $this->authorizationWarning($data, $dates, count($data['therapist_ids']));

        $created = [];
        $skipped = [];

        foreach ($data['therapist_ids'] as $therapistId) {
            foreach ($dates as $date) {
                $slot = ['therapist_id' => $therapistId, 'session_date' => $date] + $attributes;

                if ($this->hasConflict($slot)) {
                    $skipped[] = ['therapist_id' => (int) $therapistId, 'date' => $date];
                    continue;
                }

                $created[] = CalendarSession::create($slot + [
                    'recurrence_group' => $group,
                    'created_by' => auth()->id(),
                ]);
            }
        }

        // Notify each newly-booked therapist once per booking action, not
        // once per recurring occurrence - they don't need eight pings for an
        // eight-week weekly series.
        foreach (collect($created)->groupBy('therapist_id') as $therapistId => $therapistSessions) {
            if ((int) $therapistId === (int) auth()->id()) {
                continue;
            }
            User::find($therapistId)?->notify(new SessionAssigned($therapistSessions->first()));
        }

        // A note typed while booking is otherwise stuck on the CalendarSession
        // row and never seen again - log it once (not per recurring
        // occurrence) onto the patient's own clinical record, where
        // Patient::notes()'s ->latest() puts it straight at the top.
        if ($created && $attributes['patient_id'] && $attributes['notes']) {
            $patient = Lead::find($attributes['patient_id'])?->patient;
            if ($patient) {
                PatientNote::create([
                    'patient_id' => $patient->id,
                    'user_id' => auth()->id(),
                    'body' => $attributes['notes'],
                ]);
            }
        }

        $message = count($created).' session'.(count($created) === 1 ? '' : 's').' booked.';
        if ($created) {
            $packageNote = $this->packageRemainingNote($data);
            if ($packageNote) {
                $message .= ' '.$packageNote.'.';
            }
        }
        if ($skipped) {
            $message .= ' '.count($skipped).' skipped — therapist already booked in that slot.';
        }
        if ($authWarning && $created) {
            $message .= ' '.$authWarning;
        }

        if ($request->wantsJson()) {
            return response()->json([
                'message' => $message,
                'created' => count($created),
                'skipped' => $skipped,
                'authorization_warning' => $created ? $authWarning : null,
                'sessions' => collect($created)->map(fn ($s) => $this->sessionPayload($s->fresh(['therapist', 'child']))),
            ], $created ? 201 : 422);
        }

        return redirect()->route('calendar.index')->with('success', $message);
    }

    public function show(CalendarSession $calendarSession)
    {
        $calendarSession->load(['therapist', 'child', 'creator', 'coverFor', 'supervisor']);

        return response()->json($this->sessionPayload($calendarSession));
    }

    /**
     * Change a booked session. Accepts partial updates (a drag-to-reassign
     * only sends therapist_id) - anything not sent is left as-is.
     */
    public function update(Request $request, CalendarSession $calendarSession)
    {
        $this->assertCanManageSchedule();

        $input = $this->normaliseLegacyInput($request->all());

        $validator = Validator::make($input, [
            'therapist_id' => ['sometimes', 'required', 'integer', 'exists:users,id'],
            'patient_ids' => ['sometimes', 'nullable', 'array'],
            'patient_ids.*' => ['integer', 'exists:leads,id'],
            'custom_patient' => ['sometimes', 'nullable', 'string', 'max:120'],
            'activity_label' => ['sometimes', 'nullable', 'string', 'max:120'],
            'activity_types' => ['sometimes', 'required', 'array', 'min:1'],
            'activity_types.*' => ['string', 'max:50'],
            'session_date' => ['sometimes', 'required', 'date'],
            'start_time' => ['sometimes', 'required', 'date_format:H:i'],
            'duration_minutes' => ['sometimes', 'required', 'integer', 'in:'.implode(',', self::DURATIONS)],
            'room' => ['sometimes', 'nullable', 'string', 'max:100'],
            'status' => ['sometimes', 'required', 'in:'.implode(',', CalendarSession::MANUAL_STATUSES)],
            'cancel_reason' => ['sometimes', 'nullable', 'in:'.implode(',', CalendarSession::CANCEL_REASONS)],
            'cancel_notice_hours' => ['sometimes', 'nullable', 'numeric', 'between:0,720'],
            'notes' => ['sometimes', 'nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return $this->validationFailed($request, $validator);
        }

        $data = $validator->validated();
        $changes = [];

        foreach (['therapist_id', 'session_date', 'start_time', 'duration_minutes', 'room', 'status', 'cancel_reason', 'cancel_notice_hours', 'notes'] as $key) {
            if (array_key_exists($key, $data)) {
                $changes[$key] = $data[$key];
            }
        }

        // Only a family cancellation keeps notice hours; every other status
        // drops them so a stale number can't price the next invoice.
        if (array_key_exists('status', $changes)
            && ! ($changes['status'] === 'cancelled' && ($changes['cancel_reason'] ?? $calendarSession->cancel_reason) === 'family')) {
            $changes['cancel_notice_hours'] = null;
        }

        if (array_key_exists('patient_ids', $data) || array_key_exists('custom_patient', $data) || array_key_exists('activity_label', $data)) {
            $changes += $this->patientAttributes(
                $data['patient_ids'] ?? [],
                $data['custom_patient'] ?? null,
                array_key_exists('activity_label', $data) ? $data['activity_label'] : $calendarSession->activity_label
            );
        }

        if (array_key_exists('activity_types', $data)) {
            $changes += $this->typeAttributes($data['activity_types']);
        }

        $oldTherapistId = $calendarSession->therapist_id;
        $oldDay = $calendarSession->session_date->format('D');
        $therapistChanged = isset($changes['therapist_id']) && (int) $changes['therapist_id'] !== (int) $oldTherapistId;

        // Reassigning away from someone who's on leave that day makes this a
        // "covered shift" for whoever picks it up; moving it back clears that.
        if ($therapistChanged) {
            $date = $changes['session_date'] ?? $calendarSession->session_date->format('Y-m-d');
            $originalOnLeave = StaffLeave::where('user_id', $calendarSession->therapist_id)->whereDate('leave_date', $date)->exists();
            $changes['cover_for_user_id'] = $originalOnLeave ? $calendarSession->therapist_id : null;
        }

        $merged = array_merge($calendarSession->only(['therapist_id', 'session_date', 'start_time', 'duration_minutes', 'status']), $changes);
        $merged['session_date'] = $merged['session_date'] instanceof Carbon ? $merged['session_date']->format('Y-m-d') : $merged['session_date'];
        $merged['start_time'] = substr((string) $merged['start_time'], 0, 5);

        if (! in_array($merged['status'] ?? 'scheduled', CalendarSession::INACTIVE_STATUSES, true) && $this->hasConflict($merged, $calendarSession->id)) {
            $message = 'This therapist already has a session that overlaps this time slot.';

            if ($request->wantsJson()) {
                return response()->json(['message' => $message, 'errors' => ['start_time' => [$message]]], 422);
            }

            return back()->withErrors(['start_time' => $message])->withInput();
        }

        $calendarSession->update($changes);

        if ($therapistChanged) {
            $fresh = $calendarSession->fresh();
            $oldTherapist = User::find($oldTherapistId);
            $newTherapist = User::find($fresh->therapist_id);

            Activity::log(
                'session_moved',
                sprintf(
                    'Session moved: %s · %s (%s) — %s (%s) → %s (%s)',
                    substr($fresh->start_time, 0, 5),
                    $fresh->displayName(),
                    $fresh->activity_type,
                    $oldTherapist ? $this->staffName($oldTherapist) : 'Unassigned',
                    $oldDay,
                    $newTherapist ? $this->staffName($newTherapist) : 'Unassigned',
                    $fresh->session_date->format('D')
                ),
                route('calendar.index', ['session' => $fresh->id])
            );

            if ($newTherapist && (int) $newTherapist->id !== (int) auth()->id()) {
                $newTherapist->notify(new SessionAssigned($fresh, reassigned: true));
            }
        }

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Session updated.',
                'session' => $this->sessionPayload($calendarSession->fresh(['therapist', 'child', 'coverFor', 'supervisor'])),
            ]);
        }

        return redirect()->route('calendar.index')->with('success', 'Session updated.');
    }

    public function destroy(Request $request, CalendarSession $calendarSession)
    {
        $this->assertCanManageSchedule();

        $calendarSession->delete();

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Session removed.']);
        }

        return redirect()->route('calendar.index')->with('success', 'Session removed.');
    }

    /**
     * "Log supervision" - attach the supervisor's observation notes to a
     * session that has already taken place.
     */
    public function supervise(Request $request, CalendarSession $calendarSession)
    {
        $this->assertCanManageSchedule();

        $validator = Validator::make($request->all(), [
            'notes' => ['required', 'string', 'max:2000'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Add a supervision note.', 'errors' => $validator->errors()], 422);
        }

        if ($calendarSession->isCancelled() || $calendarSession->isClosed()) {
            return response()->json(['message' => 'A cancelled or closed session can’t be supervised.'], 422);
        }

        if (! $calendarSession->session_date->lt(now()->startOfDay())) {
            return response()->json(['message' => 'Only a session from a previous day can be supervised.'], 422);
        }

        $calendarSession->update([
            'supervised_by' => auth()->id(),
            'supervised_at' => now(),
            'supervision_notes' => $request->notes,
        ]);

        return response()->json([
            'message' => 'Supervision logged.',
            'session' => $this->sessionPayload($calendarSession->fresh(['therapist', 'child', 'coverFor', 'supervisor'])),
        ]);
    }

    public function unsupervise(CalendarSession $calendarSession)
    {
        $this->assertCanManageSchedule();

        $calendarSession->update([
            'supervised_by' => null,
            'supervised_at' => null,
            'supervision_notes' => null,
        ]);

        return response()->json(['message' => 'Supervision removed.']);
    }

    /**
     * How many booked sessions a leave day would cancel - shown as a warning
     * in the "Mark leave" modal before the supervisor commits.
     */
    public function leaveImpact(Request $request)
    {
        $count = CalendarSession::where('therapist_id', $request->input('user_id'))
            ->whereDate('session_date', $request->input('date'))
            ->where('status', 'scheduled')
            ->count();

        return response()->json(['count' => $count]);
    }

    /**
     * Mark a staff member on leave for a day. Any still-scheduled sessions
     * that day are cancelled (clinic-side) so the roster reflects reality;
     * the supervisor then reassigns cover where needed.
     */
    public function markLeave(Request $request)
    {
        $this->assertCanManageSchedule();

        $validator = Validator::make($request->all(), [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'leave_date' => ['required', 'date'],
            'leave_type' => ['required', 'in:'.implode(',', StaffLeave::TYPES)],
            'reason' => ['required', 'string', 'max:500'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Check the leave details.', 'errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();

        $leave = StaffLeave::updateOrCreate(
            ['user_id' => $data['user_id'], 'leave_date' => $data['leave_date']],
            ['leave_type' => $data['leave_type'], 'reason' => $data['reason'], 'created_by' => auth()->id()]
        );

        $staffName = $this->staffName(User::find($data['user_id']));
        $affected = CalendarSession::where('therapist_id', $data['user_id'])
            ->whereDate('session_date', $data['leave_date'])
            ->where('status', 'scheduled')
            ->get();

        foreach ($affected as $session) {
            $session->update([
                'status' => 'cancelled',
                'cancel_reason' => 'clinic',
                'notes' => trim(($session->notes ? $session->notes."\n" : '')."Cancelled — {$staffName} on {$data['leave_type']}."),
            ]);
        }

        return response()->json([
            'message' => "{$staffName} marked on {$data['leave_type']}.".($affected->count() ? ' '.$affected->count().' session(s) cancelled — arrange cover.' : ''),
            'leave' => $leave,
            'cancelled' => $affected->count(),
        ]);
    }

    public function removeLeave(StaffLeave $leave)
    {
        $this->assertCanManageSchedule();

        $leave->delete();

        return response()->json(['message' => 'Leave removed.']);
    }

    /**
     * Direct therapy hours delivered per staff member per week of a month -
     * supervision, admin time, leave and cancelled sessions are excluded.
     */
    public function utilisation(Request $request)
    {
        $month = Carbon::createFromFormat('Y-m', $request->input('month', now()->format('Y-m')))->startOfMonth();
        $monthEnd = $month->copy()->endOfMonth();

        // Mon-Sun weeks, the first running from the 1st to the first Sunday.
        $weeks = [];
        $cursor = $month->copy();
        while ($cursor->lte($monthEnd)) {
            $weekEnd = $cursor->copy()->endOfWeek(Carbon::SUNDAY)->min($monthEnd);
            $weeks[] = [
                'label' => 'Week '.(count($weeks) + 1),
                'range' => $cursor->format('M j').'–'.$weekEnd->format('j'),
                'start' => $cursor->toDateString(),
                'end' => $weekEnd->toDateString(),
            ];
            $cursor = $weekEnd->copy()->addDay();
        }

        $staff = $this->rosterStaff();

        $sessions = CalendarSession::whereBetween('session_date', [$month->toDateString(), $monthEnd->toDateString()])
            ->whereIn('therapist_id', $staff->pluck('id'))
            ->notCancelled()
            ->directTherapy()
            ->get(['therapist_id', 'session_date', 'duration_minutes']);

        $rows = $staff->map(function (User $t) use ($weeks, $sessions) {
            $hours = collect($weeks)->map(fn ($w) => round(
                $sessions->where('therapist_id', $t->id)
                    ->filter(fn ($s) => $s->session_date->between($w['start'], $w['end']))
                    ->sum('duration_minutes') / 60,
                1
            ))->values();

            return [
                'name' => $this->staffName($t),
                'position' => $this->designation($t),
                'hours' => $hours,
                'total' => round($hours->sum(), 1),
            ];
        })->values();

        $totals = collect($weeks)->keys()->map(fn ($i) => round($rows->sum(fn ($r) => $r['hours'][$i]), 1))->values();

        return response()->json([
            'month' => $month->format('F Y'),
            'weeks' => $weeks,
            'rows' => $rows,
            'totals' => $totals,
            'grand_total' => round($totals->sum(), 1),
        ]);
    }

    // ------------------------------------------------------------------

    /**
     * Per-patient data the booking panel needs: package/authorization hours
     * and upcoming-session status - all computed live from CalendarSession,
     * never a stored counter, so it's only ever as stale as the last time it
     * was fetched (see leads(), which lets the panel re-pull this on demand
     * instead of only once at page load).
     */
    private function leadsPayload()
    {
        return Lead::with('patient.authorizations')->whereHas('patient')->orderBy('child_name')->get()
            ->map(function (Lead $l) {
                $auth = $l->patient?->primaryAuthorization();

                // Whether this patient already has session(s) on the calendar -
                // shown in the booking panel so staff don't have to leave the
                // panel to check before deciding whether/how much more to book.
                $upcoming = $l->calendarSessions()
                    ->where('status', 'scheduled')
                    ->where('session_date', '>=', now()->toDateString())
                    ->orderBy('session_date')->orderBy('start_time')
                    ->get(['session_date', 'start_time']);

                return [
                    'id' => $l->id,
                    'name' => $l->child_name,
                    'upcoming_count' => $upcoming->count(),
                    'next_session' => $upcoming->first() ? [
                        'date' => $upcoming->first()->session_date->format('Y-m-d'),
                        'time' => substr($upcoming->first()->start_time, 0, 5),
                    ] : null,
                    // The last (furthest-out) session already on the calendar -
                    // so staff can see exactly where the current schedule ends
                    // before deciding to extend it. Same list as next_session,
                    // ordered ascending, so the last entry is the latest date.
                    'last_session' => $upcoming->last() && $upcoming->count() > 1 ? [
                        'date' => $upcoming->last()->session_date->format('Y-m-d'),
                        'time' => substr($upcoming->last()->start_time, 0, 5),
                    ] : null,
                    // Drives the "books N sessions" preview in the booking panel.
                    'auth' => $auth && $auth->authorized_hours_total ? [
                        'payer' => $auth->payer_name,
                        'total' => (int) $auth->authorized_hours_total,
                        'left' => $auth->hoursLeft(),
                        'covers' => $auth->covers_services ?? [],
                    ] : null,
                    // Drives the Package info card in the booking panel (total/used/
                    // remaining hours) - one entry per package assigned at intake,
                    // each tagged with the calendar activity_type codes it covers so
                    // the panel can pick the right one once staff choose a Type.
                    'packages' => $l->packages()->get()->map(function (Package $p) use ($l) {
                        $types = $p->matchingActivityTypes();
                        $usedMinutes = $types
                            ? $l->calendarSessions()->notCancelled()->whereIn('activity_type', $types)->sum('duration_minutes')
                            : 0;
                        $total = (float) $p->hours_per_week;
                        $used = round($usedMinutes / 60, 1);

                        return [
                            'id' => $p->id,
                            'name' => $p->name,
                            'service' => $p->service?->name,
                            'types' => $types,
                            'total' => $total,
                            'used' => $used,
                            'left' => max(0, round($total - $used, 1)),
                        ];
                    })->values(),
                ];
            })->values();
    }

    private function rosterStaff()
    {
        $user = auth()->user();

        $query = User::where('is_active', true)->whereIn('role', self::ROSTER_ROLES);

        if ($user->role === 'THERAPIST') {
            $query->where('id', $user->id);
        }

        return $query
            ->orderBy('first_name')
            ->get();
    }

    private function staffName(User $u): string
    {
        return trim("{$u->first_name} {$u->last_name}");
    }

    private function designation(User $u): string
    {
        return $u->job_title ?: match ($u->role) {
            'CLINICAL_SUPERVISOR' => 'BCBA Supervisor',
            'THERAPIST' => 'Therapist',
            default => Str::title(str_replace('_', ' ', $u->department ?? $u->role)),
        };
    }

    private function canManage(): bool
    {
        $user = auth()->user();

        return $user->canDo('assign_change_schedule') || in_array($user->role, self::MANAGE_ROLES, true);
    }

    /**
     * The strip under the search bar: what this user's access level can and
     * can't do, read from the actions they hold (Roles & access).
     */
    private function capabilitiesForUser(User $user): array
    {
        $labels = \App\Models\RoleTemplate::ACTIONS;
        $held = $user->effectiveActions();

        if ($held === null) {
            return $this->capabilitiesFor($user->role);
        }

        $can = array_values(array_map(fn ($k) => lcfirst($labels[$k]), array_intersect(array_keys($labels), $held)));
        $locked = array_values(array_map(fn ($k) => lcfirst($labels[$k]), array_diff(array_keys($labels), $held)));

        return [
            'label' => $user->roleTemplate?->name ?? $user->roleLabel(),
            'can' => $can ?: ['view the schedule'],
            'locked' => $locked,
        ];
    }

    /**
     * Booking a brand-new session is open to anyone with calendar access
     * (Coordinators book sessions as part of intake/scheduling) - but once a
     * session is on the calendar, only the Clinical Supervisor (or an Admin)
     * reschedules, cancels, reassigns or supervises it, and marks leave.
     */
    private function assertCanManageSchedule(): void
    {
        abort_unless($this->canManage(), 403, 'Only the Clinical Supervisor can change the schedule.');
    }

    /**
     * What the signed-in role can and can't do - rendered as the strip under
     * the search bar so staff know why a control is missing.
     */
    private function capabilitiesFor(string $role): array
    {
        return match ($role) {
            'FULL_ADMIN' => [
                'label' => 'Admin',
                'can' => ['book sessions', 'change the schedule', 'assign multiple therapists', 'mark leave', 'log supervision', 'manage leads & clients'],
                'locked' => [],
            ],
            'CLINICAL_SUPERVISOR' => [
                'label' => 'Supervisor',
                'can' => ['add lead notes', 'book sessions', 'change the schedule', 'assign multiple therapists', 'mark leave', 'log supervision'],
                'locked' => ['assign leads', 'reassign leads', 'terminate leads', 'convert to client', 'delete leads', 'manage billing', 'manage staff'],
            ],
            'COORDINATOR' => [
                'label' => 'Coordinator',
                'can' => ['book sessions', 'add lead notes', 'update intake details'],
                'locked' => ['change the schedule', 'cancel sessions', 'mark leave', 'log supervision', 'convert to client', 'delete leads'],
            ],
            'THERAPIST' => [
                'label' => 'Therapist',
                'can' => ['view your own schedule', 'add session notes'],
                'locked' => ['book sessions', 'change the schedule', 'reassign sessions', 'mark leave', 'log supervision'],
            ],
            default => [
                'label' => Str::title(str_replace('_', ' ', $role)),
                'can' => ['view the schedule', 'book sessions'],
                'locked' => ['change the schedule', 'mark leave', 'log supervision'],
            ],
        };
    }

    /**
     * Older callers (the Therapists page) still post the single-value shape -
     * fold it into the multi-select shape the new form uses.
     */
    private function normaliseLegacyInput(array $input): array
    {
        if (! isset($input['therapist_ids']) && isset($input['therapist_id'])) {
            $input['therapist_ids'] = [$input['therapist_id']];
        }
        if (! isset($input['patient_ids']) && isset($input['patient_id'])) {
            $input['patient_ids'] = [$input['patient_id']];
        }
        if (! isset($input['activity_types']) && isset($input['activity_type'])) {
            $input['activity_types'] = [$input['activity_type']];
        }

        // The form sends "cancelled:family" as a single status choice.
        if (isset($input['status']) && str_contains($input['status'], ':')) {
            [$input['status'], $input['cancel_reason']] = explode(':', $input['status'], 2);
        }

        return $input;
    }

    private function sessionAttributes(array $data): array
    {
        $status = $data['status'] ?? 'scheduled';

        return $this->patientAttributes($data['patient_ids'] ?? [], $data['custom_patient'] ?? null, $data['activity_label'] ?? null)
            + $this->typeAttributes($data['activity_types'])
            + [
                'start_time' => $data['start_time'],
                'duration_minutes' => $data['duration_minutes'],
                'room' => $data['room'] ?? null,
                'status' => $status,
                'cancel_reason' => $status === 'cancelled' ? ($data['cancel_reason'] ?? null) : null,
                // Notice hours belong to a family cancellation only - that's
                // the one case billing prices off them.
                'cancel_notice_hours' => $status === 'cancelled' && ($data['cancel_reason'] ?? null) === 'family'
                    ? ($data['cancel_notice_hours'] ?? null)
                    : null,
                'notes' => $data['notes'] ?? null,
            ];
    }

    /**
     * One child -> patient_id (name syncs from the lead). Several children, or
     * a typed-in custom patient/group -> no patient_id, the ids in patient_ids
     * and a label in patient_name.
     */
    private function patientAttributes(array $patientIds, ?string $custom, ?string $activityLabel): array
    {
        $ids = array_values(array_unique(array_map('intval', $patientIds)));
        $custom = trim((string) $custom) ?: null;

        if (count($ids) === 1 && ! $custom) {
            return [
                'patient_id' => $ids[0],
                'patient_ids' => null,
                'patient_name' => Lead::whereKey($ids[0])->value('child_name'),
                'activity_label' => $activityLabel,
            ];
        }

        $label = $custom;
        if (count($ids) > 0) {
            $names = Lead::whereIn('id', $ids)->orderBy('child_name')->pluck('child_name');
            $label = $custom
                ? "{$custom} (".count($ids).')'
                : ($names->count() > 2 ? "{$names[0]}, {$names[1]} +".($names->count() - 2) : $names->implode(', '));
        }

        return [
            'patient_id' => null,
            'patient_ids' => $ids ?: null,
            'patient_name' => $label,
            'activity_label' => $activityLabel,
        ];
    }

    private function typeAttributes(array $types): array
    {
        $types = array_values(array_unique(array_filter(array_map('trim', $types))));

        return [
            'activity_type' => $types[0] ?? 'ABA',
            'activity_types' => count($types) > 1 ? $types : null,
        ];
    }

    /**
     * Non-blocking check for a single-patient booking that would push a
     * covered service's committed hours past what's left on the matching
     * insurance authorization. Staff can still book it - clinical scheduling
     * shouldn't be hard-blocked by a billing constraint - but sees why the
     * excess will end up billed to the family instead of insurance. This is
     * separate from, and checked in addition to, packageOverbookError()'s
     * hard block on the package-hours balance itself.
     */
    private function authorizationWarning(array $data, array $dates, int $therapistCount): ?string
    {
        $ids = $data['patient_ids'] ?? [];
        if (count($ids) !== 1 || ! $dates) {
            return null;
        }

        $lead = Lead::with('patient.authorizations')->find($ids[0]);
        $patient = $lead?->patient;
        if (! $patient) {
            return null;
        }

        $type = trim($data['activity_types'][0] ?? '');
        $auth = $patient->authorizations->first(function (PatientAuthorization $a) use ($type) {
            if (! $a->authorized_hours_total) {
                return false;
            }
            foreach ($a->covers_services ?? [] as $cover) {
                if (stripos($type, $cover) !== false || stripos($cover, $type) !== false) {
                    return true;
                }
            }

            return false;
        });
        if (! $auth) {
            return null;
        }

        $newMinutes = count($dates) * max(1, $therapistCount) * (int) $data['duration_minutes'];
        $leftMinutes = max(0, $auth->authorized_hours_total * 60 - $auth->minutesCommitted());

        if ($newMinutes <= $leftMinutes) {
            return null;
        }

        return sprintf(
            'This books %s but %s only has %s left on the %s authorization — the excess will bill to the family, not insurance.',
            $this->fmtHours($newMinutes / 60),
            $lead->child_name,
            $this->fmtHours($leftMinutes / 60),
            $auth->payer_name
        );
    }

    /**
     * How many occurrences a recurring booking with no explicit `occurrences`
     * should create, sized to exactly use up the single selected patient's
     * remaining package hours at this duration (e.g. 30h left / 60 min ->
     * 30 sessions, 30h left / 30 min -> 60 sessions) - this is what "using
     * patient hours" means. Null when there's nothing to size against
     * (group/custom booking, or no package on file covers this activity
     * type) - the caller then requires an explicit count instead, so a
     * series never gets created without a defined stop either way.
     */
    private function packageSessionBudget(array $data, int $therapistCount): ?int
    {
        $ids = $data['patient_ids'] ?? [];
        if (count($ids) !== 1) {
            return null;
        }

        $lead = Lead::find($ids[0]);
        $budget = $lead?->packageHours(trim($data['activity_types'][0] ?? ''));
        if (! $budget) {
            return null;
        }

        $leftMinutes = (int) round($budget['left'] * 60);
        $perOccurrence = (int) $data['duration_minutes'] * max(1, $therapistCount);

        return intdiv($leftMinutes, max(1, $perOccurrence));
    }

    /**
     * Hard stop for the core scheduling rule: a booking action must never ask
     * for more hours than the single selected patient's matching package has
     * left. Null when there's nothing to check against (group/custom
     * booking, or nothing assigned covers this activity type) - in that
     * case scheduling proceeds uncapped, same as before this feature existed.
     */
    private function packageOverbookError(array $data, array $dates, int $therapistCount): ?string
    {
        $ids = $data['patient_ids'] ?? [];
        if (count($ids) !== 1 || ! $dates) {
            return null;
        }

        $lead = Lead::find($ids[0]);
        if (! $lead) {
            return null;
        }

        $type = trim($data['activity_types'][0] ?? '');
        $budget = $lead->packageHours($type);
        if (! $budget) {
            return null;
        }

        $newMinutes = count($dates) * max(1, $therapistCount) * (int) $data['duration_minutes'];
        $leftMinutes = (int) round($budget['left'] * 60);

        if ($newMinutes <= $leftMinutes) {
            return null;
        }

        return sprintf(
            'This books %s but %s only has %s left on the %s package (%s total). Reduce the hours or number of sessions to fit what remains.',
            $this->fmtHours($newMinutes / 60),
            $lead->child_name,
            $this->fmtHours($budget['left']),
            $budget['label'],
            $this->fmtHours($budget['total'])
        );
    }

    /**
     * "10.5 hours left on the ABA package" - appended to the success message so
     * staff immediately see the balance a booking just left behind, without
     * reopening the panel. Computed fresh (post-create), same source
     * Lead::packageHours() and the panel's own preview both use, so what's
     * shown here can never drift from what the next booking attempt sees.
     */
    private function packageRemainingNote(array $data): ?string
    {
        $ids = $data['patient_ids'] ?? [];
        if (count($ids) !== 1) {
            return null;
        }

        $lead = Lead::find($ids[0]);
        $budget = $lead?->packageHours(trim($data['activity_types'][0] ?? ''));
        if (! $budget) {
            return null;
        }

        return sprintf('%s left on the %s package', $this->fmtHours($budget['left']), $budget['label']);
    }

    /**
     * "1 hour" / "10.5 hours" - spelled out rather than the "h"/"hr"
     * abbreviation, for every package/authorization hours message above.
     */
    private function fmtHours(float $hours): string
    {
        $n = rtrim(rtrim(number_format($hours, 1), '0'), '.');

        return $n.' '.($n === '1' ? 'hour' : 'hours');
    }

    private function sessionPayload(CalendarSession $s): array
    {
        return [
            'id' => $s->id,
            'therapist_id' => $s->therapist_id,
            'therapist_name' => $s->therapist ? $this->staffName($s->therapist) : null,
            'cover_for_user_id' => $s->cover_for_user_id,
            'cover_for_name' => $s->coverFor ? $this->staffName($s->coverFor) : null,
            'patient_id' => $s->patient_id,
            'patient_ids' => $s->patient_ids,
            'patient_name' => $s->displayName(),
            'is_custom_patient' => ! $s->patient_id && ! $s->patient_ids && (bool) $s->patient_name,
            'activity_label' => $s->activity_label,
            'activity_type' => $s->activity_type,
            'activity_types' => $s->activity_types ?: [$s->activity_type],
            'session_date' => $s->session_date->format('Y-m-d'),
            'start_time' => substr($s->start_time, 0, 5),
            'end_time' => substr($s->end_time, 0, 5),
            'duration_minutes' => $s->duration_minutes,
            'room' => $s->room,
            'status' => $s->status,
            'cancel_reason' => $s->cancel_reason,
            'cancel_notice_hours' => $s->cancel_notice_hours,
            'status_label' => $s->statusLabel(),
            'category' => $s->category(),
            'notes' => $s->notes,
            'recurrence_group' => $s->recurrence_group,
            'is_past' => $s->isPast(),
            // Supervision is only logged in hindsight, once the whole day has
            // wrapped - not the instant a session's own end time passes.
            'can_supervise' => $s->session_date->lt(now()->startOfDay()) && ! $s->isCancelled() && ! $s->isClosed(),
            'supervised' => $s->isSupervised(),
            'supervised_by_name' => $s->supervisor ? $this->staffName($s->supervisor) : null,
            'supervised_at' => optional($s->supervised_at)->format('Y-m-d H:i'),
            'supervision_notes' => $s->supervision_notes,
        ];
    }

    private function validationFailed(Request $request, $validator)
    {
        if ($request->wantsJson()) {
            return response()->json([
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        return back()->withErrors($validator)->withInput();
    }

    /**
     * Shared overlap check used by store() and update().
     */
    protected function hasConflict(array $slot, ?int $ignoreId = null): bool
    {
        $newStart = Carbon::parse($slot['start_time']);
        $newEnd = $newStart->copy()->addMinutes((int) $slot['duration_minutes']);

        $query = CalendarSession::where('therapist_id', $slot['therapist_id'])
            ->where('session_date', $slot['session_date'])
            ->notCancelled()
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
