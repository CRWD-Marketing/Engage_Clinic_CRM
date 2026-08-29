<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Models\CalendarSession;
use App\Models\Lead;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PatientController extends Controller
{
    /**
     * Display the patients list + the selected patient's detail panel.
     */
    public function index(Request $request)
    {
        $query = Patient::with(['lead', 'goals']);

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

        $activePatient = $request->filled('patient')
            ? $patients->firstWhere('id', (int) $request->query('patient'))
            : $patients->first();

        $sessions = collect();
        $upcomingSessions = collect();

        if ($activePatient) {
            $activePatient->load('notes.user');
            $sessions = $activePatient->calendarSessions()->with('therapist')->orderBy('session_date')->orderBy('start_time')->get();
            $upcomingSessions = $sessions->filter(fn ($s) => $s->session_date->isToday() || $s->session_date->isFuture())->take(5);
        }

        $therapists = User::where('role', 'THERAPIST')->orderBy('first_name')->get(['id', 'first_name', 'last_name']);

        return view('patient.index', compact('patients', 'activePatient', 'upcomingSessions', 'therapists'));
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
            'insurance_provider' => 'required|string|max:100',
            'authorized_sessions_total' => 'required|integer|min:0',
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
            'insurance_provider' => $data['insurance_provider'],
            'authorized_sessions_total' => $data['authorized_sessions_total'],
            'authorization_renews_at' => $data['authorization_renews_at'] ?? null,
            'enrolled_at' => $data['enrolled_at'] ?? now(),
        ]);

        if (!empty($data['clinical_note'])) {
            $patient->notes()->create([
                'user_id' => auth()->id(),
                'body' => $data['clinical_note'],
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Patient added.',
            'redirect' => route('patient.index', ['patient' => $patient->id]),
        ], 201);
    }

    /**
     * Update a patient's clinical/admin details (diagnosis, programme, insurance, etc).
     */
    public function update(Request $request, Patient $patient)
    {
        $this->assertAssignedTherapist($patient);

        $validator = Validator::make($request->all(), [
            'diagnosis' => 'nullable|string|max:255',
            'programme' => 'nullable|string|max:255',
            'insurance_provider' => 'nullable|string|max:100',
            'authorized_sessions_total' => 'nullable|integer|min:0',
            'authorization_renews_at' => 'nullable|date',
            'enrolled_at' => 'nullable|date',
            'child_name' => 'nullable|string|max:255',
            'child_age' => 'nullable|integer|min:0|max:25',
            'parent_guardian_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'clinical_note' => 'nullable|string|max:2000',
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
            $patient->update($request->only([
                'diagnosis', 'programme', 'insurance_provider',
                'authorized_sessions_total', 'authorization_renews_at', 'enrolled_at',
            ]));
        }

        // Child name/age and parent/guardian/phone all live on the lead record, not here.
        // Only touch fields actually submitted, so a partial save can't blank out the rest.
        $leadFields = collect($request->only(['child_name', 'child_age', 'parent_guardian_name', 'phone']))
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->all();

        if ($leadFields && $patient->lead) {
            $patient->lead->update($leadFields);
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

        return redirect()->route('patient.index', ['patient' => $patient->id])->with('success', 'Patient details updated.');
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
