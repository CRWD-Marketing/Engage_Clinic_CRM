<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\PresentsNotes;
use App\Http\Controllers\Patient\PatientController as WebPatientController;
use App\Models\CalendarSession;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\PatientAuthorization;
use App\Models\PatientDocument;
use App\Models\PatientNote;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Patients for the mobile app. The list and the patient page reuse the web
 * controller's own queries (its view data) and return them as JSON; notes,
 * today's goals, "Edit details" and documents are the web actions as they
 * are, since those already answer JSON.
 */
class PatientController extends WebPatientController
{
    use PresentsNotes;

    /** GET /patients?search= */
    public function index(Request $request)
    {
        $patients = parent::index($request)->getData()['patients'];

        return response()->json($patients->map(fn (Patient $patient) => $this->patientRow($patient) + [
            'authorizations' => $this->authorizations($patient),
            'attendance_rate' => $patient->attendanceRate(),
            'is_profile_incomplete' => $patient->isProfileIncomplete(),
        ])->values());
    }

    /** GET /patients/{patient} — every tab of patient/show.blade.php. */
    public function show(Patient $patient)
    {
        $data = parent::show($patient)->getData();
        $patient = $data['patient'];
        $todaysSession = $data['todaysSession'];

        return response()->json([
            'patient' => $this->patientRow($patient),
            'authorizations' => $this->authorizations($patient),
            'notes' => $patient->notes->map(fn (PatientNote $note) => $this->presentNote($note))->values(),
            'recommended_goals' => $data['recommendedGoals']->map(fn (array $row) => [
                'goal' => $row['goal']->attributesToArray(),
                'used' => $row['used'],
                'last_used_at' => $row['last_used_at']?->toDateString(),
            ])->values(),
            'todays_session' => $todaysSession ? $this->sessions(collect([$todaysSession]))->first() : null,
            'todays_goal_ids' => $data['todaysGoalIds'],
            'care_team' => $patient->careTeam(),
            'upcoming_sessions' => $this->sessions($data['upcomingSessions']),
            'past_sessions' => $this->sessions($data['pastSessions']),
            'attendance_rate' => $patient->attendanceRate(),
            'is_profile_incomplete' => $patient->isProfileIncomplete(),
            'missing_fields_label' => $patient->missingFieldsLabel(),
            'payments' => [
                'billed_total' => (float) $data['billedTotal'],
                'collected_total' => (float) $data['collectedTotal'],
                'outstanding_total' => (float) $data['outstandingTotal'],
                'invoices' => $patient->invoices->map(fn (Invoice $invoice) => [
                    'id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'issue_date' => $invoice->issue_date,
                    'period' => $invoice->period,
                    'subtotal' => $invoice->subtotal,
                    'amount_paid' => $invoice->amount_paid,
                    'payment_method' => $invoice->payment_method,
                    'payment_status_label' => $invoice->paymentStatusLabel(),
                ])->values(),
            ],
            'documents' => $patient->documents->map(fn (PatientDocument $document) => $this->documentRow($document))->values(),
            'profile' => $this->profile($patient),
            'edit_options' => [
                'packages' => $data['packages']->map(fn ($package) => [
                    'name' => $package->name,
                    'label' => $package->name.' — '.$this->trim($package->hours_per_week).'h/wk @ AED '.number_format($package->rate, 0).'/hr',
                ])->values(),
                'insurances' => $data['insurances']->pluck('name')->values(),
                'document_types' => PatientDocument::TYPES,
            ],
        ]);
    }

    /** GET /patients/create-options — the pickers of the web's "Add patient" form. */
    public function createOptions(Request $request)
    {
        $data = parent::index($request)->getData();

        return response()->json([
            'packages' => $data['packages']->map(fn ($package) => [
                'name' => $package->name,
                'label' => $package->name.' — '.$this->trim($package->hours_per_week).'h/wk @ AED '.number_format($package->rate, 0).'/hr',
            ])->values(),
            'insurances' => $data['insurances']->pluck('name')->values(),
        ]);
    }

    /** POST /patients — the web action; it answers with a redirect URL, the app needs the new patient's id. */
    public function store(Request $request)
    {
        $response = parent::store($request);
        if ($response->getStatusCode() !== 201) {
            return $response;
        }

        return response()->json([
            'success' => true,
            'message' => 'Patient added.',
            'patient_id' => Patient::latest('id')->value('id'),
        ], 201);
    }

    /** PUT /patients/{patient} — the web action, with errors in Laravel's usual `{message, errors}` shape. */
    public function update(Request $request, Patient $patient)
    {
        $response = parent::update($request, $patient);

        return $response->getStatusCode() === 200
            ? response()->json(['success' => true, 'message' => 'Patient details updated.'])
            : $response;
    }

    /** POST /patients/{patient}/documents — the web action, returning the row in the app's shape. */
    public function storeDocument(Request $request, Patient $patient)
    {
        $this->assertAssignedTherapist($patient);

        $response = app(\App\Http\Controllers\Patient\PatientDocumentController::class)->store($request, $patient);
        if ($response->getStatusCode() !== 201) {
            return $response;
        }

        $document = PatientDocument::with('uploader')->findOrFail($response->getData(true)['document']['id']);

        return response()->json(['success' => true, 'document' => $this->documentRow($document)], 201);
    }

    /** DELETE /patients/{patient}/documents/{document} */
    public function destroyDocument(Patient $patient, PatientDocument $document)
    {
        $this->assertAssignedTherapist($patient);

        return app(\App\Http\Controllers\Patient\PatientDocumentController::class)->destroy($patient, $document);
    }

    // ---- Presenters ----------------------------------------------------

    /** The patient's own columns plus its lead, without the other loaded relations. */
    private function patientRow(Patient $patient): array
    {
        return $patient->attributesToArray() + ['lead' => $patient->lead];
    }

    /** Authorizations in card order, with the values the web computes for each card. */
    private function authorizations(Patient $patient): Collection
    {
        return $patient->authorizations->sortBy('sort_order')->map(fn (PatientAuthorization $auth) => $auth->attributesToArray() + [
            'hours_used' => $auth->hoursUsed(),
            'covers_label' => $auth->coversLabel(),
        ])->values();
    }

    private function sessions(Collection $sessions): Collection
    {
        $calendar = app(CalendarController::class);

        return $sessions->map(function (CalendarSession $session) use ($calendar) {
            $session->loadMissing(['therapist', 'child', 'coverFor', 'supervisor']);

            return $calendar->payloadFor($session);
        })->values();
    }

    private function documentRow(PatientDocument $document): array
    {
        $expiry = $document->expiryStatus();

        return [
            'id' => $document->id,
            'name' => $document->name,
            'type' => $document->type,
            'uploader_label' => $document->uploaderLabel(),
            'created_at' => $document->created_at,
            'expires_at' => $document->expires_at,
            'expiry_label' => $expiry['label'],
            'expiry_variant' => $expiry['variant'],
            'has_file' => (bool) $document->file_path,
        ];
    }

    /** The values patient/show.blade.php computes for its "Profile & intake" tab. */
    private function profile(Patient $patient): array
    {
        $lead = $patient->lead;
        $packages = $lead ? $lead->packages()->get() : collect();
        $rates = $packages->pluck('rate')->filter()->unique();
        $settings = $packages->pluck('delivery_mode')->filter()->unique();
        $rows = collect($lead->funding_services_needed ?? []);
        $payers = $rows->pluck('payer')->filter()->unique();

        // Approved hours on each insurance-paid service: the intake form's
        // `approved_hours`, or the number in an older row's free-text `cover`
        // ("96 h approved"), falling back to its weekly hours.
        $insuranceHours = $rows->filter(fn ($row) => ($row['payer'] ?? null) === 'Insurance')
            ->sum(fn ($row) => (int) ($row['approved_hours']
                ?? (preg_match('/(\d+)/', $row['cover'] ?? '', $m) ? $m[1] : ($row['hours_per_week'] ?? 0))));

        $name = fn ($user) => $user ? trim($user->first_name.' '.$user->last_name) : null;

        return [
            'package_names' => $packages->pluck('name')->values(),
            'package_hours_per_week' => (float) $packages->sum(fn ($p) => (float) $p->hours_per_week),
            'package_rate_per_hour' => $rates->count() === 1 ? (float) $rates->first() : ($rates->count() > 1 ? 'Mixed' : null),
            'package_value_excl_vat' => (float) $packages->sum(fn ($p) => (float) $p->total_excl_vat),
            'package_location' => optional(optional($lead)->packageLocation)->name,
            'package_setting' => $settings->count() === 1 ? $settings->first() : ($settings->count() > 1 ? 'Mixed' : null),
            'funding_summary' => $payers->count() > 1 ? 'Mixed — insurance + self pay' : ($payers->first() ?? optional($lead)->funding_type),
            'funding_hours_needed' => $insuranceHours > 0 ? $insuranceHours : null,
            'assessment_clinician_name' => $name(optional($lead)->assessmentClinician),
            'lead_owner_name' => optional($lead)->assigned_to_name,
        ];
    }

    /** "30" / "12.5" — a number without a trailing ".0". */
    private function trim($value): string
    {
        return rtrim(rtrim(number_format((float) $value, 1), '0'), '.');
    }
}
