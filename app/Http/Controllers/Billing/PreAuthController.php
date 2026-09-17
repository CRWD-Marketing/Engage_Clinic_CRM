<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\PreAuthorization;
use App\Services\Billing\DocumentNumbers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PreAuthController extends Controller
{
    public function store(Request $request)
    {
        $this->assertCanInvoice();

        $validator = Validator::make($request->all(), [
            'patient_id' => ['required', 'exists:patients,id'],
            'payer' => ['required', 'string', 'max:100'],
            'service' => ['required', 'string', 'max:100'],
            'hours' => ['required', 'integer', 'min:1', 'max:2000'],
            'valid_from' => ['required', 'date'],
            'valid_to' => ['required', 'date', 'after:valid_from'],
            'justification' => ['nullable', 'string', 'max:1000'],
            'resubmitted_from_id' => ['nullable', 'exists:pre_authorizations,id'],
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first(), 'errors' => $validator->errors()], 422);
        }

        $pa = DB::transaction(fn () => PreAuthorization::create($validator->validated() + [
            'reference' => DocumentNumbers::nextPreAuth(),
            'status' => 'requested',
            'submitted_on' => now()->toDateString(),
            'created_by' => auth()->id(),
        ]));

        return response()->json(['message' => "Pre-authorization {$pa->reference} requested.", 'preauth' => BillingController::preAuthRow($pa->load('patient.lead'))], 201);
    }

    /**
     * Record the payer's decision.
     */
    public function update(Request $request, PreAuthorization $preAuthorization)
    {
        $this->assertCanInvoice();

        $validator = Validator::make($request->all(), [
            'status' => ['required', 'in:'.implode(',', PreAuthorization::STATUSES)],
            'payer_reference' => ['nullable', 'string', 'max:100'],
            'denial_reason' => ['nullable', 'string', 'max:500'],
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $preAuthorization->fill([
            'status' => $request->status,
            'payer_reference' => $request->status === 'approved' ? ($request->payer_reference ?: $preAuthorization->payer_reference) : $preAuthorization->payer_reference,
            'denial_reason' => $request->status === 'denied' ? $request->denial_reason : null,
        ])->save();

        return response()->json(['message' => "{$preAuthorization->reference} marked {$preAuthorization->statusLabel()}.", 'preauth' => BillingController::preAuthRow($preAuthorization->fresh('patient.lead'))]);
    }

    private function assertCanInvoice(): void
    {
        abort_unless(auth()->user()->canDo('create_invoice'), 403, 'Pre-authorizations are requested by Finance.');
    }
}
