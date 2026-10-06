<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\PreAuthorization;
use App\Services\Billing\DocumentNumbers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
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

        // The same request must never be filed twice. The lock stops a
        // double-click (two requests in flight at once); the lookup stops a
        // repeat submit after the first has landed - one client can only
        // have one request awaiting the payer's answer per payer and service.
        $lock = Cache::lock("preauth-request-{$request->patient_id}-".md5($request->payer.'|'.$request->service), 10);
        if (! $lock->get()) {
            return response()->json(['message' => 'This request is already being submitted.'], 429);
        }

        try {
            $pending = PreAuthorization::where('patient_id', $request->patient_id)
                ->where('payer', $request->payer)
                ->where('service', $request->service)
                ->where('status', 'requested')
                ->first();
            if ($pending) {
                return response()->json(['message' => "{$pending->reference} is already awaiting {$pending->payer}'s answer for this client and service. Record that decision before requesting again."], 422);
            }

            $pa = DB::transaction(fn () => PreAuthorization::create($validator->validated() + [
                'reference' => DocumentNumbers::nextPreAuth(),
                'status' => 'requested',
                'submitted_on' => now()->toDateString(),
                'created_by' => auth()->id(),
            ]));
        } finally {
            $lock->release();
        }

        return response()->json(['message' => "Pre-authorization {$pa->reference} requested.", 'preauth' => BillingController::preAuthRow($pa->load('patient.lead'))], 201);
    }

    /**
     * Record the payer's decision. The payer never touches the CRM - the
     * answer comes back by email, phone or portal and whoever receives it
     * logs it here. An approval opens the matching authorization on the
     * client's file, which is what scheduling and billing actually read; a
     * decision is final once recorded (a denied request is resubmitted as a
     * new one, never flipped).
     */
    public function update(Request $request, PreAuthorization $preAuthorization)
    {
        $this->assertCanInvoice();

        $validator = Validator::make($request->all(), [
            'status' => ['required', 'in:approved,denied'],
            'decided_on' => ['required', 'date', 'before_or_equal:today'],
            'decision_channel' => ['required', 'in:'.implode(',', PreAuthorization::CHANNELS)],
            'payer_reference' => ['required_if:status,approved', 'nullable', 'string', 'max:100'],
            'approved_hours' => ['required_if:status,approved', 'nullable', 'integer', 'min:1', 'max:2000'],
            'coverage_percent' => ['required_if:status,approved', 'nullable', 'integer', 'min:1', 'max:100'],
            'valid_to' => ['nullable', 'date'],
            'denial_reason' => ['required_if:status,denied', 'nullable', 'string', 'max:500'],
        ], [
            'payer_reference.required_if' => 'Enter the approval reference the payer gave.',
            'approved_hours.required_if' => 'Enter the hours the payer approved.',
            'coverage_percent.required_if' => 'Enter the coverage percentage the payer approved.',
            'denial_reason.required_if' => 'Enter the reason the payer gave for the denial.',
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first(), 'errors' => $validator->errors()], 422);
        }
        if ($preAuthorization->status !== 'requested') {
            return response()->json(['message' => "{$preAuthorization->reference} was already marked {$preAuthorization->statusLabel()}."], 422);
        }

        $approved = $request->status === 'approved';

        DB::transaction(function () use ($request, $preAuthorization, $approved) {
            $preAuthorization->fill([
                'status' => $request->status,
                'decided_on' => $request->decided_on,
                'decided_by' => auth()->id(),
                'decision_channel' => $request->decision_channel,
                'denial_reason' => $approved ? null : $request->denial_reason,
            ]);

            if ($approved) {
                $preAuthorization->fill([
                    'payer_reference' => $request->payer_reference,
                    'approved_hours' => (int) $request->approved_hours,
                    'coverage_percent' => (int) $request->coverage_percent,
                    'valid_to' => $request->valid_to ?: $preAuthorization->valid_to,
                ]);

                $patient = $preAuthorization->patient;
                $existing = $patient->authorizations()->where('payer_name', $preAuthorization->payer)->latest('id')->first();
                $authorization = $patient->authorizations()->create([
                    'payer_name' => $preAuthorization->payer,
                    'coverage_percent' => $preAuthorization->coverage_percent,
                    'covers_services' => [$preAuthorization->activityType()],
                    'policy_number' => $existing?->policy_number,
                    'approval_reference' => $preAuthorization->payer_reference,
                    'authorized_hours_total' => $preAuthorization->approved_hours,
                    'renews_at' => $preAuthorization->valid_to,
                    'sort_order' => ($patient->authorizations()->max('sort_order') ?? -1) + 1,
                ]);
                $preAuthorization->patient_authorization_id = $authorization->id;
            }

            $preAuthorization->save();
        });

        $child = $preAuthorization->patient?->lead?->child_name ?? 'client';
        Activity::log(
            'preauth_decision',
            $approved
                ? "Pre-authorization {$preAuthorization->reference} approved by {$preAuthorization->payer} — {$preAuthorization->approved_hours} h at {$preAuthorization->coverage_percent}% for {$child} (via {$preAuthorization->decision_channel})"
                : "Pre-authorization {$preAuthorization->reference} denied by {$preAuthorization->payer} for {$child} (via {$preAuthorization->decision_channel})",
            route('billing.index')
        );

        return response()->json([
            'message' => $approved
                ? "{$preAuthorization->reference} approved — {$preAuthorization->approved_hours} h at {$preAuthorization->coverage_percent}% added to {$child}'s file."
                : "{$preAuthorization->reference} marked Denied.",
            'preauth' => BillingController::preAuthRow($preAuthorization->fresh(['patient.lead', 'decider'])),
        ]);
    }

    private function assertCanInvoice(): void
    {
        abort_unless(auth()->user()->canDo('create_invoice'), 403, 'Pre-authorizations are requested by Finance.');
    }
}
