<?php

namespace App\Http\Controllers\Lead;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\Patient;
use App\Models\User;
use App\Notifications\LeadAssigned;
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

        $services = \App\Models\Service::where('is_active', true)->orderBy('name')->get();

        $clinicians = \App\Models\User::where('role', 'THERAPIST')
            ->where('is_active', true)
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name']);

        $intakeLocations = \App\Models\Location::where('is_active', true)->orderBy('name')->get();

        $packages = \App\Models\Package::with('service')->where('is_active', true)->orderBy('name')->get();

        $insurances = \App\Models\Insurance::where('is_active', true)->orderBy('name')->get();

        return view('lead.index', compact(
            'leads',
            'statuses',
            'totalValue',
            'activeLeads',
            'unassignedCount',
            'followUpCount',
            'terminatedCount',
            'assignableUsers',
            'services',
            'clinicians',
            'intakeLocations',
            'packages',
            'insurances'
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
            $lead->load(['owner', 'notesLog.user', 'assignmentLog.user', 'assessmentClinician', 'packageLocation']);

            return response()->json([
                'success' => true,
                'lead' => $lead,
                'notes_log' => $lead->notesLog,
                'assignment_log' => $lead->assignmentLog,
                'agreed_packages' => $lead->packages()->get(),
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
            'email' => 'nullable|email|max:255',
            'source' => 'nullable|string|max:50',
            'interested_in' => 'nullable|string|max:100',
            'insurance' => 'nullable|string|max:50',
            'estimated_value' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
            'status' => 'nullable|string|in:new,contacted,assessment_booked,assessment_done,enrolled,terminated',
            'assigned_to' => 'nullable|exists:users,id',
            'follow_up_due_at' => 'nullable|date',
            'termination_reason' => 'nullable|string|in:'.implode(',', Lead::TERMINATION_REASONS),
            'termination_note' => 'nullable|string',

            // Intake checklist - which step this save belongs to (if any), so
            // its *_completed_at can be stamped. Not itself a Lead column.
            'intake_step' => 'nullable|string|in:'.implode(',', array_keys(Lead::INTAKE_STEPS)),

            // Step 1: Parent contact verified
            'parent_relationship' => 'nullable|string|max:50',
            'parent_alternate_phone' => 'nullable|string|max:20',
            'preferred_language' => 'nullable|string|max:20',

            // Step 2: Child details complete
            'child_date_of_birth' => 'nullable|date',
            'child_gender' => 'nullable|string|max:20',
            'child_emirates_id' => 'nullable|string|max:30',
            'child_emirates_id_expiry' => 'nullable|date',
            'diagnosis_suspected' => 'nullable|string|max:255',
            'nursery_school' => 'nullable|string|max:255',
            'main_concern' => 'nullable|string|max:255',

            // Step 3: Intake form received
            'intake_form_received_on' => 'nullable|date',
            'intake_form_received_via' => 'nullable|string|max:30',
            'allergies' => 'nullable|string',
            'medical_history' => 'nullable|string',

            // Step 4: Consultation / assessment done
            'assessment_date' => 'nullable|date',
            'assessment_clinician_id' => 'nullable|exists:users,id',
            'assessment_tool' => 'nullable|string|max:50',
            'assessment_report_reference' => 'nullable|string|max:255',
            'assessment_report_summary' => 'nullable|string',

            // Step 5: Funding confirmed
            'funding_type' => 'nullable|string|max:50',
            'funding_insurer' => 'nullable|string|max:100',
            'funding_policy_number' => 'nullable|string|max:100',
            'funding_approval_valid_until' => 'nullable|date',
            // Submitted as a JSON string (built from dynamic pill-button rows
            // that aren't real named inputs), not a native array field -
            // decoded and normalized below, before mass update.
            'funding_services_needed' => 'nullable|string',
            'funding_notes' => 'nullable|string',

            // Step 6: Package agreed
            'package_location_id' => 'nullable|exists:locations,id',
            'package_ids' => 'nullable|array',
            'package_ids.*' => 'integer|exists:packages,id',
            'package_start_date' => 'nullable|date',
            'package_sessions_per_week' => 'nullable|integer|min:0|max:255',
            'package_agreed_by' => 'nullable|string|max:255',
            'package_scheduling_notes' => 'nullable|string',

            // Step 7: Consent & terms signed
            'consent_signed_date' => 'nullable|date',
            'consent_signed_by' => 'nullable|string|max:255',
            'consent_data_photo' => 'nullable|string|max:10',
            'consent_signature_method' => 'nullable|string|max:30',
            'consent_notes' => 'nullable|string',
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

        if (array_key_exists('funding_services_needed', $data)) {
            $decoded = json_decode((string) $data['funding_services_needed'], true);
            $data['funding_services_needed'] = is_array($decoded) && count($decoded) > 0 ? $decoded : null;
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

        // Saving a step's modal marks it complete - re-saving just refreshes
        // the timestamp, which is fine, that's "last confirmed/edited at".
        $step = $data['intake_step'] ?? null;
        unset($data['intake_step']);

        // An unchecked checkbox group submits nothing at all, so unchecking
        // every package on this step's form and saving would otherwise leave
        // $data without a package_ids key - and update() only touches keys
        // that are present, so the lead's old package_ids would silently
        // survive. Since this request came from the Package step's own form,
        // its absence here unambiguously means "no packages checked", so
        // force the key onto $data to actually clear it.
        if ($step === 'package' && ! array_key_exists('package_ids', $data)) {
            $data['package_ids'] = null;
        }

        $previousAssignedTo = $lead->assigned_to;

        // Per-action access (Roles & access): owning/re-owning a lead and
        // terminating one are separate grants from editing its details.
        $me = auth()->user();
        if (array_key_exists('assigned_to', $data) && (int) $data['assigned_to'] !== (int) $previousAssignedTo) {
            $needed = $previousAssignedTo ? 'reassign_lead_owner' : 'assign_lead_owner';
            if (! $me->canDo($needed)) {
                $message = $previousAssignedTo ? 'Your access level can’t reassign a lead owner.' : 'Your access level can’t assign a lead owner.';
                return $request->ajax() || $request->wantsJson()
                    ? response()->json(['success' => false, 'message' => $message, 'errors' => ['assigned_to' => [$message]]], 403)
                    : back()->withErrors(['assigned_to' => $message])->withInput();
            }
        }
        $isNewlyTerminated = ($data['status'] ?? null) === Lead::STATUS_TERMINATED && $lead->status !== Lead::STATUS_TERMINATED;

        if ($isNewlyTerminated && ! $me->canDo('terminate_lead')) {
            $message = 'Your access level can’t terminate a lead.';
            return $request->ajax() || $request->wantsJson()
                ? response()->json(['success' => false, 'message' => $message, 'errors' => ['status' => [$message]]], 403)
                : back()->withErrors(['status' => $message])->withInput();
        }

        if ($isNewlyTerminated) {
            if (empty($data['termination_reason'])) {
                $message = 'Select a reason before terminating this lead.';
                return $request->ajax() || $request->wantsJson()
                    ? response()->json(['success' => false, 'message' => $message, 'errors' => ['termination_reason' => [$message]]], 422)
                    : back()->withErrors(['termination_reason' => $message])->withInput();
            }

            // status becomes "terminated" below, so the stage it was lost
            // from - shown as "Lost at" in the history panel, and where
            // Restore sends it back to - has to be captured here first.
            $data['status_before_termination'] = $lead->status;
            $data['terminated_at'] = now();
        }

        $lead->update($data);

        // wasChanged() only reflects the most recent save, so capture the
        // assignment-log check before the second update() below (for the
        // completed_at stamp) overwrites it.
        if ($lead->wasChanged('assigned_to')) {
            $this->logAssignmentChange($lead, $previousAssignedTo, $lead->assigned_to);
        }

        // The package (checkbox group) and funding (hidden pill/JSON inputs)
        // steps have a required field that HTML's native validation can't
        // enforce - an unchecked checkbox group and a hidden input are both
        // skipped during constraint validation - so a step could otherwise
        // get marked complete without ever picking a package or funding
        // type. Still save whatever else was filled in on this step's form
        // (nothing here is discarded) - just stamp *_completed_at only while
        // that field genuinely has a value, and clear it back to null (so
        // the step reverts to "Fill in" and drops out of the 7/7 count) the
        // moment it's unchecked/cleared again, even if it was set before.
        if ($step) {
            $requiredFieldMet = match ($step) {
                'package' => ! empty($lead->package_ids),
                'funding' => ! empty($lead->funding_type),
                default => true,
            };

            $lead->update([Lead::INTAKE_STEPS[$step] => $requiredFieldMet ? now() : null]);
        }

        // Finishing the 7th intake step (from any earlier stage, most commonly
        // Initial assessment) auto-advances the lead straight to Enrolled -
        // a fully-intaked lead shouldn't need a separate manual "Success" click
        // just to unlock the Convert-to-client action.
        if ($lead->intake_steps_complete === 7 && ! in_array($lead->status, [Lead::STATUS_ENROLLED, Lead::STATUS_TERMINATED], true)) {
            $lead->update(['status' => Lead::STATUS_ENROLLED]);
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
        abort_unless(auth()->user()->canDo('add_lead_notes'), 403, 'Your access level can’t add lead notes.');

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
        abort_unless(auth()->user()->canDo('convert_to_client'), 403, 'Your access level can’t convert leads to clients.');

        if (! $lead->canConvertToPatient()) {
            $message = match (true) {
                $lead->status !== Lead::STATUS_ENROLLED => 'Only enrolled leads can be converted to a patient.',
                $lead->intake_steps_complete !== count(Lead::INTAKE_STEPS) => 'Finish the remaining intake checklist steps before converting this lead.',
                default => 'This lead has already been converted to a patient.',
            };

            return response()->json(['success' => false, 'message' => $message], 422);
        }

        $patient = Patient::create([
            'lead_id' => $lead->id,
            'diagnosis' => $lead->diagnosis_suspected,
            'programme' => optional($lead->packages()->first())->name,
            'enrolled_at' => now(),
        ]);

        // Carry the intake's Funding step over as the patient's first
        // authorization - self-pay isn't a payer authorization (that's the
        // separate prepaid-hours top-up flow), so only insurance-funded
        // leads get one.
        if ($lead->funding_insurer && stripos((string) $lead->funding_type, 'insurance') !== false) {
            // funding_services_needed is a list of {service, payer, hours_per_week,
            // approved_hours, approval_reference} rows from the Funding step's
            // pill-button picker, not the plain activity-type strings
            // (ABA/Speech/OT) covers_services expects - pull out just the
            // service name from each row. Only rows actually paid by
            // insurance count toward the authorization's approved hours -
            // a mixed lead's self-pay services aren't part of it.
            $insuranceRows = collect($lead->funding_services_needed ?? [])
                ->filter(fn ($row) => is_array($row) && ($row['payer'] ?? null) === 'Insurance');

            $coversServices = $insuranceRows
                ->map(fn ($row) => $row['service'] ?? null)
                ->filter()
                ->unique()
                ->values()
                ->all();

            $patient->authorizations()->create([
                'payer_name' => $lead->funding_insurer,
                'coverage_percent' => \App\Models\Insurance::where('name', $lead->funding_insurer)->value('default_coverage_percent') ?? 0,
                'covers_services' => $coversServices,
                'policy_number' => $lead->funding_policy_number,
                'approval_reference' => $insuranceRows->map(fn ($row) => $row['approval_reference'] ?? null)->filter()->first(),
                'authorized_hours_total' => (int) $insuranceRows->sum(fn ($row) => (float) ($row['approved_hours'] ?? 0)),
                'renews_at' => $lead->funding_approval_valid_until,
                'sort_order' => 0,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Converted to patient.',
            'redirect' => route('patient.show', $patient),
        ]);
    }

    /**
     * Bring a terminated lead back into the active pipeline, at the stage it
     * was lost from - used by the "Restore" button in the terminated-leads
     * history panel.
     */
    public function restore(Lead $lead)
    {
        abort_unless(auth()->user()->canDo('terminate_lead'), 403, 'Your access level can’t restore a terminated lead.');

        if (! $lead->restore()) {
            return response()->json(['success' => false, 'message' => 'This lead is not terminated.'], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Lead restored.',
            'lead' => $lead,
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

        if ($newUserId && (int) $newUserId !== (int) auth()->id()) {
            User::find($newUserId)?->notify(new LeadAssigned($lead));
        }
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
        // Blank form fields (date inputs left empty, unselected selects, etc.)
        // arrive as '' - every one of these columns is nullable and none
        // treats '' as meaningfully different from "not set", so this is
        // simpler and safer than maintaining a fixed field list (a date-cast
        // column in particular would choke trying to parse '').
        foreach ($data as $field => $value) {
            if ($value === '') {
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