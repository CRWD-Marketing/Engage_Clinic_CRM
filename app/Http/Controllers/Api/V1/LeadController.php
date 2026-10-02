<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Lead\LeadController as WebLeadController;
use App\Models\Lead;
use App\Models\Package;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Leads pipeline for the mobile app. Every write is the web controller's own
 * action (they already answer JSON); this adds the board as JSON and tags
 * each returned lead with whether it has already become a patient.
 */
class LeadController extends WebLeadController
{
    /** GET /leads — LeadController::index() view data: every lead, newest first, plus the page's option lists. */
    public function board(Request $request)
    {
        $data = parent::index($request)->getData();
        $name = fn ($user) => ['id' => $user->id, 'name' => trim($user->first_name.' '.$user->last_name)];
        $insurers = $data['insurances']->pluck('name')->values();

        return response()->json([
            'leads' => $data['leads']->map(fn (Lead $lead) => $this->leadRow($lead))->values(),
            'assignable_users' => $data['assignableUsers']->map($name)->values(),
            'insurance_options' => ['Not sure yet', ...$insurers, 'Self-pay'],
            'intake_options' => [
                'clinicians' => $data['clinicians']->map($name)->values(),
                'insurers' => $insurers,
                'services' => $data['services']->pluck('name')->values(),
                'locations' => $data['intakeLocations']->map(fn ($l) => ['id' => $l->id, 'name' => $l->name])->values(),
                'packages' => $data['packages']->map(fn (Package $p) => $this->packageRow($p))->values(),
            ],
        ]);
    }

    /** GET /leads/{lead} */
    public function show(Request $request, Lead $lead)
    {
        $data = parent::show($request, $lead)->getData(true);
        $clinician = $lead->assessmentClinician;

        return response()->json([
            'success' => true,
            'lead' => $this->leadRow($lead),
            'notes_log' => $data['notes_log'],
            'assignment_log' => $data['assignment_log'],
            'agreed_packages' => $lead->packages()->with('service')->get()->map(fn (Package $p) => $this->packageRow($p))->values(),
            'assessment_clinician_name' => $clinician ? trim($clinician->first_name.' '.$clinician->last_name) : null,
        ]);
    }

    public function store(Request $request)
    {
        return $this->withLeadRow(parent::store($request));
    }

    public function update(Request $request, Lead $lead)
    {
        return $this->withLeadRow(parent::update($request, $lead));
    }

    public function updateStatus(Request $request, Lead $lead)
    {
        return $this->withLeadRow(parent::updateStatus($request, $lead));
    }

    public function restore(Lead $lead)
    {
        return $this->withLeadRow(parent::restore($lead));
    }

    /** POST /leads/{lead}/convert-to-patient — the web answers with a redirect URL; the app needs the new patient's id. */
    public function convertToPatient(Lead $lead)
    {
        $response = parent::convertToPatient($lead);
        if ($response->getStatusCode() !== 200) {
            return $response;
        }

        return response()->json([
            'success' => true,
            'message' => 'Converted to patient.',
            'patient_id' => $lead->patient()->value('id'),
        ]);
    }

    /** Replace the `lead` in a successful web response with the board row (fresh values + has_patient). */
    private function withLeadRow(JsonResponse $response): JsonResponse
    {
        $data = $response->getData(true);

        if (($data['success'] ?? false) && isset($data['lead']['id'])) {
            $data['lead'] = $this->leadRow(Lead::findOrFail($data['lead']['id']));
            $response->setData($data);
        }

        return $response;
    }

    private function leadRow(Lead $lead): array
    {
        return $lead->withoutRelations()->toArray() + ['has_patient' => $lead->patient()->exists()];
    }

    private function packageRow(Package $package): array
    {
        return [
            'id' => $package->id,
            'name' => $package->name,
            'location_id' => $package->location_id,
            'summary' => $package->summaryLabel(),
        ];
    }
}
