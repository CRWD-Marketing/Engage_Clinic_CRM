<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Models\CalendarSession;
use App\Models\Insurance;
use App\Models\Lead;
use App\Models\Patient;
use App\Models\PatientAuthorization;
use App\Models\PatientGoal;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PatientController extends Controller
{
    /**
     * Display the patients list (plain table - Patient, Parent/Phone,
     * Programme, Insurance, Authorization, Attendance, Status).
     */
    public function index(Request $request)
    {
        $query = Patient::with(['lead', 'authorizations']);

        if ($search = $request->query('search')) {
            $query->whereHas('lead', function ($q) use ($search) {
                $q->where('child_name', 'LIKE', "%{$search}%")
                    ->orWhere('parent_guardian_name', 'LIKE', "%{$search}%")
                    ->orWhere('phone', 'LIKE', "%{$search}%");
            });
        }

        if (auth()->user()->role === 'THERAPIST') {
            // A therapist only ever sees patients they actually have a booked
            // session with — care team is derived from Calendar, same as
            // Patient::careTeam(), so this can't drift from who's really
            // assigned to them.
            $assignedLeadIds = CalendarSession::where('therapist_id', auth()->id())
                ->distinct()
                ->pluck('patient_id');

            $query->whereIn('lead_id', $assignedLeadIds);
        }

        $patients = $query->get()->sortBy(fn ($p) => $p->lead->child_name ?? '')->values();

        $insurances = Insurance::where('is_active', true)->orderBy('name')->get();

        return view('patient.index', compact('patients', 'insurances'));
    }

    /**
     * Display one patient's tabbed detail page (Overview, Session History,
     * Payments, Documents, Profile & Intake) - one full page load, tabs are
     * client-side show/hide.
     */
    public function show(Patient $patient)
    {
        $this->assertAssignedTherapist($patient);

        $patient->load(['lead', 'goals', 'notes.user', 'authorizations', 'documents.uploader', 'invoices.lineItems']);

        $sessions = $patient->calendarSessions()->notClosed()->with('therapist', 'goals')->orderByDesc('session_date')->orderByDesc('start_time')->get();
        $upcomingSessions = $sessions->filter(fn ($s) => $s->session_date->isToday() || $s->session_date->isFuture())
            ->sortBy(fn ($s) => $s->session_date->toDateString().' '.$s->start_time)
            ->take(5);

        $todaysSession = $sessions->first(fn ($s) => $s->session_date->isToday());

        // Goals ranked by how often they've been worked on in the last 10
        // sessions, for the "recommended from previous sessions" list.
        $recommendedGoals = $patient->goals
            ->map(fn (PatientGoal $goal) => [
                'goal' => $goal,
                'used' => $goal->sessionsInLast(10),
                'last_used_at' => $goal->lastUsedAt(),
            ])
            ->sortByDesc('used')
            ->values();

        $todaysGoalIds = $todaysSession ? $todaysSession->goals->pluck('id')->all() : [];

        $billedTotal = $patient->invoices->sum('subtotal');
        $collectedTotal = $patient->invoices->sum('amount_paid');
        $outstandingTotal = $billedTotal - $collectedTotal;

        $therapists = User::where('role', 'THERAPIST')->orderBy('first_name')->get(['id', 'first_name', 'last_name']);

        $insurances = Insurance::where('is_active', true)->orderBy('name')->get();

        return view('patient.show', compact(
            'patient',
            'sessions',
            'upcomingSessions',
            'todaysSession',
            'recommendedGoals',
            'todaysGoalIds',
            'billedTotal',
            'collectedTotal',
            'outstandingTotal',
            'therapists',
            'insurances'
        ));
    }

    /**
     * Manually create a new patient. Since a Patient always belongs to a Lead
     * (child name/age/parent/phone all live there, not here), this creates
     * both in one step: an already-enrolled Lead plus the linked Patient
     * record, rather than requiring the normal Lead-pipeline conversion.
     */
    public function store(Request $request)
    {
        abort_if(
            in_array(auth()->user()->role, ['COORDINATOR', 'THERAPIST'], true),
            403,
            'You do not have permission to add a patient.'
        );

        $validator = Validator::make($request->all(), [
            'child_name' => 'required|string|max:255',
            'child_age' => 'nullable|integer|min:0|max:25',
            'diagnosis' => 'required|string|max:255',
            'programme' => 'required|string|max:255',
            'parent_guardian_name' => 'nullable|string|max:255',
            'phone' => 'required|string|max:20',
            // Insurance is optional at creation time - a payer can be added
            // afterward from the patient's Overview tab once details are ready.
            'payer_name' => 'nullable|string|max:100',
            'authorized_hours_total' => 'nullable|integer|min:0',
            'authorization_renews_at' => 'nullable|date',
            'enrolled_at' => 'nullable|date',
            'clinical_note' => 'nullable|string|max:2000',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();

        $lead = Lead::create([
            'child_name' => $data['child_name'],
            'child_age' => $data['child_age'] ?? null,
            'parent_guardian_name' => $data['parent_guardian_name'] ?? null,
            'phone' => $data['phone'],
            'source' => 'Manual entry',
            'status' => Lead::STATUS_ENROLLED,
        ]);

        $patient = Patient::create([
            'lead_id' => $lead->id,
            'diagnosis' => $data['diagnosis'],
            'programme' => $data['programme'],
            'enrolled_at' => $data['enrolled_at'] ?? now(),
        ]);

        if (!empty($data['payer_name'])) {
            $patient->authorizations()->create([
                'payer_name' => $data['payer_name'],
                'coverage_percent' => Insurance::where('name', $data['payer_name'])->value('default_coverage_percent') ?? 0,
                'covers_services' => ['ABA'],
                'authorized_hours_total' => $data['authorized_hours_total'] ?? null,
                'renews_at' => $data['authorization_renews_at'] ?? null,
            ]);
        }

        if (!empty($data['clinical_note'])) {
            $patient->notes()->create([
                'user_id' => auth()->id(),
                'body' => $data['clinical_note'],
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Patient added.',
            'redirect' => route('patient.show', $patient),
        ], 201);
    }

    /**
     * Update a patient's clinical/admin details (diagnosis, programme, enrollment).
     * Also accepts a simplified insurance/authorized-hours/renewal set, which
     * upserts the patient's *primary* (first) PatientAuthorization row - for
     * more than one concurrent payer or a full coverage breakdown, use
     * storeAuthorization()/updateAuthorization() directly instead.
     */
    public function update(Request $request, Patient $patient)
    {
        $this->assertAssignedTherapist($patient);

        $validator = Validator::make($request->all(), [
            'diagnosis' => 'nullable|string|max:255',
            'programme' => 'nullable|string|max:255',
            'enrolled_at' => 'nullable|date',
            'child_name' => 'nullable|string|max:255',
            'child_age' => 'nullable|integer|min:0|max:25',
            'parent_guardian_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'clinical_note' => 'nullable|string|max:2000',
            'insurance' => 'nullable|string|max:100',
            'authorized_hours_total' => 'nullable|integer|min:0',
            'renews_at' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
            }
            return back()->withErrors($validator)->withInput();
        }

        // Coordinator's patient access is "limited" - intake/scheduling info
        // only, not clinical records - so clinical fields are silently
        // dropped rather than saved, regardless of what the request sent.
        $isCoordinator = auth()->user()->role === 'COORDINATOR';

        if (!$isCoordinator) {
            $patient->update($request->only(['diagnosis', 'programme', 'enrolled_at']));
        }

        // Child name/age and parent/guardian/phone all live on the lead record, not here.
        // Only touch fields actually submitted, so a partial save can't blank out the rest.
        $leadFields = collect($request->only(['child_name', 'child_age', 'parent_guardian_name', 'phone']))
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->all();

        if ($leadFields && $patient->lead) {
            $patient->lead->update($leadFields);
        }

        // Insurance/auth hrs/renewal update the primary authorization (the
        // first by sort_order) in place, or create one if this patient has
        // none yet - only touched when at least one of the three was sent,
        // so an unrelated save (e.g. just a clinical note) can't blank it out.
        // payer_name is required on the row, so with nothing to create one
        // from (no existing authorization and no "insurance" typed in) this
        // is silently skipped rather than saving a half-filled record.
        if ($request->filled('insurance') || $request->filled('authorized_hours_total') || $request->filled('renews_at')) {
            $primaryAuth = $patient->authorizations()->orderBy('sort_order')->first();
            $payerName = $request->filled('insurance') ? $request->input('insurance') : optional($primaryAuth)->payer_name;

            if ($payerName) {
                // hoursUsed() matches calendar sessions against covers_services -
                // leaving it empty on a newly-created authorization would leave
                // "hours used" stuck at 0 forever, even once real sessions
                // happen. This form doesn't collect a coverage breakdown, so
                // infer it from the programme text the same way the original
                // patient_authorizations backfill migration did.
                $coversServices = $primaryAuth->covers_services ?? array_values(array_filter([
                    str_contains((string) $patient->programme, 'ABA') ? 'ABA' : null,
                    str_contains((string) $patient->programme, 'Speech') ? 'Speech' : null,
                    str_contains((string) $patient->programme, 'OT') ? 'OT' : null,
                ])) ?: ['ABA'];

                $patient->authorizations()->updateOrCreate(
                    ['id' => $primaryAuth->id ?? 0],
                    [
                        'payer_name' => $payerName,
                        'coverage_percent' => $primaryAuth->coverage_percent
                            ?? Insurance::where('name', $payerName)->value('default_coverage_percent')
                            ?? 0,
                        'covers_services' => $coversServices,
                        'authorized_hours_total' => $request->filled('authorized_hours_total')
                            ? $request->input('authorized_hours_total')
                            : optional($primaryAuth)->authorized_hours_total,
                        'renews_at' => $request->filled('renews_at')
                            ? $request->input('renews_at')
                            : optional($primaryAuth)->renews_at,
                        'sort_order' => $primaryAuth->sort_order ?? 0,
                    ]
                );
            }
        }

        if (!$isCoordinator && $request->filled('clinical_note')) {
            $patient->notes()->create([
                'user_id' => auth()->id(),
                'body' => $request->clinical_note,
            ]);
        }

        if ($request->wantsJson()) {
            $patient->load('lead');
            return response()->json(['success' => true, 'message' => 'Patient details updated.', 'patient' => $patient]);
        }

        return redirect()->route('patient.show', $patient)->with('success', 'Patient details updated.');
    }

    /**
     * Add a timestamped session note.
     */
    public function addNote(Request $request, Patient $patient)
    {
        $this->assertAssignedTherapist($patient);

        // Session/clinical notes are clinical documentation, outside a
        // Coordinator's limited (view-mostly) patient access.
        abort_if(auth()->user()->role === 'COORDINATOR', 403, 'Coordinators cannot add clinical notes.');

        $validator = Validator::make($request->all(), [
            'body' => 'required|string|max:2000',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $note = $patient->notes()->create([
            'user_id' => auth()->id(),
            'body' => $request->body,
        ]);

        $note->load('user');

        return response()->json(['success' => true, 'note' => $note], 201);
    }

    /**
     * Save which goals were worked on in today's session - the "Session
     * goals — worked on today" widget. Accepts a mix of existing
     * patient_goal ids and brand-new goal titles typed inline (created on
     * the fly and linked in the same request).
     */
    public function updateGoalsForToday(Request $request, Patient $patient)
    {
        $this->assertAssignedTherapist($patient);

        $validator = Validator::make($request->all(), [
            'goal_ids' => 'nullable|array',
            'goal_ids.*' => 'integer|exists:patient_goals,id',
            'new_goal_titles' => 'nullable|array',
            'new_goal_titles.*' => 'string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $todaysSession = $patient->calendarSessions()->whereDate('session_date', now()->toDateString())->first();

        if (!$todaysSession) {
            // No calendar entry for today (e.g. a walk-in / unscheduled visit) -
            // logging goals for today is itself evidence a session happened,
            // so record it as a completed session rather than blocking the
            // therapist from saving what was worked on.
            $lastSession = $patient->calendarSessions()->orderByDesc('session_date')->orderByDesc('start_time')->first();

            $todaysSession = CalendarSession::create([
                'patient_id' => $patient->lead_id,
                'therapist_id' => $lastSession->therapist_id ?? auth()->id(),
                'activity_type' => $lastSession->activity_type ?? 'ABA',
                'session_date' => now()->toDateString(),
                'start_time' => now()->format('H:i:s'),
                'duration_minutes' => 60,
                'end_time' => now()->addHour()->format('H:i:s'),
                'status' => 'completed',
                'created_by' => auth()->id(),
            ]);
        }

        $goalIds = $request->input('goal_ids', []);
        $createdGoals = [];

        foreach ($request->input('new_goal_titles', []) as $title) {
            $title = trim($title);
            if ($title === '') {
                continue;
            }

            // firstOrCreate, not create: goals save on every checkbox toggle,
            // so the same custom title can be resubmitted more than once
            // before the page reloads - this keeps that idempotent instead
            // of spawning a duplicate goal each time.
            $goal = $patient->goals()->firstOrCreate(['title' => $title]);
            $goalIds[] = $goal->id;
            $createdGoals[] = ['id' => $goal->id, 'title' => $goal->title];
        }

        $todaysSession->goals()->sync($goalIds);

        return response()->json([
            'success' => true,
            'message' => 'Session goals saved.',
            'created_goals' => $createdGoals,
        ]);
    }

    /**
     * Add a new insurance authorization (payer) for this patient. A patient
     * can have more than one concurrent authorization.
     */
    public function storeAuthorization(Request $request, Patient $patient)
    {
        $this->assertAssignedTherapist($patient);

        $validator = Validator::make($request->all(), [
            'payer_name' => 'required|string|max:100',
            'coverage_percent' => 'required|integer|min:0|max:100',
            'covers_services' => 'required|array|min:1',
            'covers_services.*' => 'string|max:50',
            'policy_number' => 'nullable|string|max:100',
            'authorized_hours_total' => 'required|integer|min:0',
            'renews_at' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $data['sort_order'] = $patient->authorizations()->max('sort_order') + 1;

        $authorization = $patient->authorizations()->create($data);

        return response()->json(['success' => true, 'authorization' => $authorization], 201);
    }

    /**
     * Update an existing authorization.
     */
    public function updateAuthorization(Request $request, Patient $patient, PatientAuthorization $authorization)
    {
        $this->assertAssignedTherapist($patient);
        abort_unless($authorization->patient_id === $patient->id, 404);

        $validator = Validator::make($request->all(), [
            'payer_name' => 'required|string|max:100',
            'coverage_percent' => 'required|integer|min:0|max:100',
            'covers_services' => 'required|array|min:1',
            'covers_services.*' => 'string|max:50',
            'policy_number' => 'nullable|string|max:100',
            'authorized_hours_total' => 'required|integer|min:0',
            'renews_at' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $authorization->update($validator->validated());

        return response()->json(['success' => true, 'authorization' => $authorization]);
    }

    /**
     * Remove an authorization (e.g. a payer relationship that has ended).
     */
    public function destroyAuthorization(Patient $patient, PatientAuthorization $authorization)
    {
        $this->assertAssignedTherapist($patient);
        abort_unless($authorization->patient_id === $patient->id, 404);

        $authorization->delete();

        return redirect()->route('patient.show', $patient)->with('success', 'Authorization removed.');
    }

    /**
     * Route-model binding fetches by ID regardless of role, so index()'s
     * list-scoping alone doesn't stop a therapist reaching another patient's
     * record by guessing/editing the URL — this closes that gap on the
     * write endpoints.
     */
    private function assertAssignedTherapist(Patient $patient): void
    {
        if (auth()->user()->role !== 'THERAPIST') {
            return;
        }

        $isAssigned = CalendarSession::where('therapist_id', auth()->id())
            ->where('patient_id', $patient->lead_id)
            ->exists();

        abort_unless($isAssigned, 403, 'This patient is not assigned to you.');
    }
}
