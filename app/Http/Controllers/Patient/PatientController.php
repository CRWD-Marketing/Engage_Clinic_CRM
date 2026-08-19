<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
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
     * Update a patient's clinical/admin details (diagnosis, programme, insurance, etc).
     */
    public function update(Request $request, Patient $patient)
    {
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

        $patient->update($request->only([
            'diagnosis', 'programme', 'insurance_provider',
            'authorized_sessions_total', 'authorization_renews_at', 'enrolled_at',
        ]));

        // Child name/age and parent/guardian/phone all live on the lead record, not here.
        // Only touch fields actually submitted, so a partial save can't blank out the rest.
        $leadFields = collect($request->only(['child_name', 'child_age', 'parent_guardian_name', 'phone']))
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->all();

        if ($leadFields && $patient->lead) {
            $patient->lead->update($leadFields);
        }

        if ($request->filled('clinical_note')) {
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
}
