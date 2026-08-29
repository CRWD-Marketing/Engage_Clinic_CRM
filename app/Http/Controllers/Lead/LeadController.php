<?php

namespace App\Http\Controllers\Lead;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\Patient;
use App\Models\PatientNote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LeadController extends Controller
{
    /**
     * Display a listing of the leads.
     */
    public function index(Request $request)
    {
        $query = Lead::query()->with(['owner', 'patient']);

        // Filter by status
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Search
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('child_name', 'LIKE', "%{$search}%")
                    ->orWhere('parent_guardian_name', 'LIKE', "%{$search}%")
                    ->orWhere('phone', 'LIKE', "%{$search}%")
                    ->orWhere('notes', 'LIKE', "%{$search}%");
            });
        }

        $leads = $query->latest()->get();

        $statuses = array_keys(Lead::getStatuses());

        // Converted leads have graduated to the Patients module - they no longer
        // belong in the pipeline, even though the underlying Lead row stays put
        // (it remains the scheduling identity for Calendar sessions).
        $activeLeads = $leads->reject(fn ($lead) => $lead->status === Lead::STATUS_TERMINATED || $lead->patient);

        $totalValue = $activeLeads->sum(function ($lead) {
            // Clean the value before summing
            $value = preg_replace('/[^0-9.]/', '', $lead->estimated_value);
            return (float) $value;
        });

        $unassignedCount = $activeLeads->whereNull('assigned_to')->count();
        $followUpCount = $activeLeads->whereNotNull('follow_up_due_at')->count();
        $terminatedCount = $leads->where('status', Lead::STATUS_TERMINATED)->count();

        $assignableUsers = \App\Models\User::where('is_active', true)
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name']);

        return view('lead.index', compact(
            'leads',
            'statuses',
            'totalValue',
            'activeLeads',
            'unassignedCount',
            'followUpCount',
            'terminatedCount',
            'assignableUsers'
        ));
    }

    /**
     * Show create lead form.
     */
    public function create()
    {
        return view('lead.create');
    }

    /**
     * Store new lead.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'child_name' => 'nullable|string|max:255',
            'child_age' => 'nullable|string|max:10',
            'parent_guardian_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'source' => 'nullable|string|max:50',
            'interested_in' => 'nullable|string|max:100',
            'insurance' => 'nullable|string|max:50',
            'estimated_value' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
            'assigned_to' => 'nullable|exists:users,id',
            'follow_up_due_at' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }
            return back()->withErrors($validator)->withInput();
        }

        $data = $this->normalizeNullableFields($request->all());

        // Clean estimated_value - only numbers and decimals
        if (isset($data['estimated_value'])) {
            $data['estimated_value'] = $this->cleanEstimatedValue($data['estimated_value']);
        }

        $lead = Lead::create($data);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Lead created successfully!',
                'lead' => $lead
            ], 201);
        }

        return redirect()
            ->route('leads.index')
            ->with('success', 'Lead created successfully!');
    }

    /**
     * Display lead.
     */
    public function show(Request $request, Lead $lead)
    {
        if ($request->ajax() || $request->wantsJson()) {
            $lead->load(['owner', 'notesLog.user', 'assignmentLog.user']);

            return response()->json([
                'success' => true,
                'lead' => $lead,
                'notes_log' => $lead->notesLog,
                'assignment_log' => $lead->assignmentLog,
            ]);
        }
        return view('lead.show', compact('lead'));
    }

    /**
     * Edit lead.
     */
    public function edit(Lead $lead)
    {
        return view('lead.edit', compact('lead'));
    }

    /**
     * Update lead.
     */
    public function update(Request $request, Lead $lead)
    {
        $validator = Validator::make($request->all(), [
            'child_name' => 'nullable|string|max:255',
            'child_age' => 'nullable|string|max:10',
            'parent_guardian_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'source' => 'nullable|string|max:50',
            'interested_in' => 'nullable|string|max:100',
            'insurance' => 'nullable|string|max:50',
            'estimated_value' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
            'status' => 'nullable|string|in:new,contacted,assessment_booked,assessment_done,enrolled,terminated',
            'assigned_to' => 'nullable|exists:users,id',
            'follow_up_due_at' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }
            return back()->withErrors($validator)->withInput();
        }

        $data = $this->normalizeNullableFields($request->all());

        // Clean estimated_value - only numbers and decimals
        if (isset($data['estimated_value'])) {
            $data['estimated_value'] = $this->cleanEstimatedValue($data['estimated_value']);
        }

        // A lead needs an owner before it can move to Contacted - check the value
        // this request would actually leave in place, not just what's already saved.
        // (If the request explicitly clears assigned_to, that empty value must win,
        // not silently fall back to the lead's current owner.)
        $incomingAssignedTo = array_key_exists('assigned_to', $data) ? $data['assigned_to'] : $lead->assigned_to;

        if (($data['status'] ?? $lead->status) === Lead::STATUS_CONTACTED && ! $incomingAssignedTo) {
            $message = 'A lead must have an owner before it can move to Contacted.';

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'errors' => ['assigned_to' => [$message]],
                    'message' => $message,
                ], 422);
            }
            return back()->withErrors(['assigned_to' => $message])->withInput();
        }

        $previousAssignedTo = $lead->assigned_to;

        $lead->update($data);

        if ($lead->wasChanged('assigned_to')) {
            $this->logAssignmentChange($lead, $previousAssignedTo, $lead->assigned_to);
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Lead updated successfully!',
                'lead' => $lead
            ]);
        }

        return redirect()
            ->route('leads.show', $lead)
            ->with('success', 'Lead updated successfully!');
    }

    /**
     * Delete lead.
     */
    public function destroy(Lead $lead)
    {
        // Coordinator's lead access is intake/communication support, not full
        // pipeline ownership - deleting a lead stays with Full Admin/Sales.
        abort_if(auth()->user()->role === 'COORDINATOR', 403, 'Coordinators cannot delete leads.');

        $lead->delete();
        return redirect()
            ->route('leads.index')
            ->with('success', 'Lead deleted successfully!');
    }

    /**
     * Update lead status.
     */
    public function updateStatus(Request $request, Lead $lead)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|string|in:new,contacted,assessment_booked,assessment_done,enrolled,terminated',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
                'message' => 'Invalid status provided'
            ], 422);
        }

        if ($request->status === Lead::STATUS_CONTACTED && ! $lead->assigned_to) {
            $message = 'A lead must have an owner before it can move to Contacted.';

            return response()->json([
                'success' => false,
                'errors' => ['assigned_to' => [$message]],
                'message' => $message,
            ], 422);
        }

        $lead->update([
            'status' => $request->status
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Lead status updated successfully!',
            'lead' => $lead
        ]);
    }

    /**
     * Add a timestamped note to a lead's activity log.
     */
    public function addNote(Request $request, Lead $lead)
    {
        $validator = Validator::make($request->all(), [
            'body' => 'required|string|max:2000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $activity = $lead->activities()->create([
            'user_id' => auth()->id(),
            'type' => LeadActivity::TYPE_NOTE,
            'body' => $request->body,
        ]);

        $activity->load('user');

        return response()->json([
            'success' => true,
            'note' => $activity,
        ], 201);
    }

    /**
     * Convert an enrolled lead into a real patient record. The lead itself is
     * left untouched (it stays the "child identity" scheduling still uses) -
     * this just creates the linked clinical/enrollment record.
     */
    public function convertToPatient(Lead $lead)
    {
        // Same reasoning as destroy() - conversion is a pipeline-ownership
        // decision, outside a Coordinator's "limited" lead access.
        abort_if(auth()->user()->role === 'COORDINATOR', 403, 'Coordinators cannot convert leads to patients.');

        if (! $lead->canConvertToPatient()) {
            $message = $lead->status !== Lead::STATUS_ENROLLED
                ? 'Only enrolled leads can be converted to a patient.'
                : 'This lead has already been converted to a patient.';

            return response()->json(['success' => false, 'message' => $message], 422);
        }

        $patient = Patient::create([
            'lead_id' => $lead->id,
            'enrolled_at' => now(),
        ]);

        PatientNote::create([
            'patient_id' => $patient->id,
            'user_id' => auth()->id(),
            'body' => 'Converted from lead — '.($lead->notes ?: 'no enquiry notes on file'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Converted to patient.',
            'redirect' => route('patient.index', ['patient' => $patient->id]),
        ]);
    }

    /**
     * Record an assignment change in the lead's activity log.
     */
    private function logAssignmentChange(Lead $lead, ?int $previousUserId, ?int $newUserId): void
    {
        $newUser = $newUserId ? \App\Models\User::find($newUserId) : null;
        $newName = $newUser ? trim($newUser->first_name.' '.$newUser->last_name) : null;

        $body = match (true) {
            ! $previousUserId && $newUserId => "Assigned to {$newName}",
            $previousUserId && ! $newUserId => 'Unassigned',
            default => "Reassigned to {$newName}",
        };

        $lead->activities()->create([
            'user_id' => auth()->id(),
            'type' => LeadActivity::TYPE_ASSIGNMENT,
            'body' => $body,
        ]);
    }

    /**
     * Store lead from public frontend form.
     */
    public function apiStore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'child_name' => 'nullable|string|max:255',
            'child_age' => 'nullable|string|max:10',
            'parent_guardian_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'source' => 'nullable|string|max:50',
            'interested_in' => 'nullable|string|max:100',
            'insurance' => 'nullable|string|max:50',
            'estimated_value' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
            'assigned_to' => 'nullable|exists:users,id',
            'follow_up_due_at' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $this->normalizeNullableFields($request->all());

        // Clean estimated_value - only numbers and decimals
        if (isset($data['estimated_value'])) {
            $data['estimated_value'] = $this->cleanEstimatedValue($data['estimated_value']);
        }

        $lead = Lead::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Lead saved successfully!',
            'lead' => $lead
        ], 201);
    }

    /**
     * Get lead count for dashboard badges
     */
    public function getLeadCount(Request $request)
    {
        // Get count by status
        $statusCounts = [
            'new' => Lead::where('status', 'new')->count(),
            'contacted' => Lead::where('status', 'contacted')->count(),
            'assessment_booked' => Lead::where('status', 'assessment_booked')->count(),
            'assessment_done' => Lead::where('status', 'assessment_done')->count(),
            'enrolled' => Lead::where('status', 'enrolled')->count(),
            'terminated' => Lead::where('status', 'terminated')->count(),
        ];

        // The sidebar badge only flags leads still in "New" - once a lead is being
        // contacted or assessed it's no longer something that needs attention.
        $count = $statusCounts['new'];

        return response()->json([
            'success' => true,
            'count' => $count,
            'status_counts' => $statusCounts
        ]);
    }

    /**
     * Kanban lead data.
     */
    public function getKanbanData()
    {
        $leads = Lead::all();

        $statuses = [
            'new',
            'contacted',
            'assessment_booked',
            'assessment_done',
            'enrolled',
            'terminated',
        ];

        $data = [];
        foreach ($statuses as $status) {
            $data[$status] = $leads
                ->where('status', $status)
                ->values();
        }

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    /**
     * The edit/create forms always submit assigned_to and follow_up_due_at,
     * even when left blank, as empty strings. assigned_to is a nullable FK
     * (bigint) and follow_up_due_at a nullable datetime column, and MySQL's
     * strict mode rejects '' for both - so it must become null before it
     * ever reaches Eloquent, or the update/create throws an uncaught
     * QueryException.
     */
    private function normalizeNullableFields(array $data): array
    {
        foreach (['assigned_to', 'follow_up_due_at'] as $field) {
            if (array_key_exists($field, $data) && $data[$field] === '') {
                $data[$field] = null;
            }
        }

        return $data;
    }

    /**
     * Clean estimated value to only contain numbers and decimal points
     */
    private function cleanEstimatedValue($value)
    {
        // Remove all non-numeric characters except decimal point
        $cleaned = preg_replace('/[^0-9.]/', '', $value);
        
        // Remove multiple decimal points (keep only first one)
        $parts = explode('.', $cleaned);
        if (count($parts) > 2) {
            $cleaned = $parts[0] . '.' . implode('', array_slice($parts, 1));
        }
        
        // If value starts with decimal point, add leading zero
        if (strlen($cleaned) > 0 && $cleaned[0] === '.') {
            $cleaned = '0' . $cleaned;
        }
        
        // Remove leading zeros (except when it's "0.")
        if (strlen($cleaned) > 1 && $cleaned[0] === '0' && $cleaned[1] !== '.') {
            $cleaned = ltrim($cleaned, '0');
            if ($cleaned === '' || $cleaned === '.') {
                $cleaned = '0';
            }
        }
        
        return $cleaned;
    }
}