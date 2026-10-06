<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\InsuranceClaim;
use App\Services\Billing\DocumentNumbers;
use App\Services\Billing\InvoicePresenter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ClaimController extends Controller
{
    /**
     * One claim plus the client's documents that can back it - what the
     * submission panel needs to attach the medical and assessment reports.
     */
    public function show(InsuranceClaim $claim)
    {
        return response()->json([
            'claim' => BillingController::claimRow($claim->load(['patient.lead', 'invoice'])),
            'documents' => $claim->patient
                ? $claim->patient->documents()->orderByDesc('created_at')->get(['id', 'name', 'type'])->map(fn ($d) => ['id' => $d->id, 'name' => $d->name, 'type' => $d->type])->values()
                : [],
        ]);
    }

    public function update(Request $request, InsuranceClaim $claim)
    {
        abort_unless(auth()->user()->canDo('create_invoice'), 403, 'Claims are managed by Finance.');

        $validator = Validator::make($request->all(), [
            'status' => ['required', 'in:'.implode(',', InsuranceClaim::STATUSES)],
            'notes' => ['nullable', 'string', 'max:500'],
            'payer_reference' => ['nullable', 'string', 'max:60'],
            'portal' => ['nullable', 'string', 'max:60'],
            'medical_report_document_id' => ['nullable', 'integer'],
            'assessment_report_document_id' => ['nullable', 'integer'],
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        // Submission details ride along with the status when the panel sends
        // them; a bare status change (the inline dropdown) leaves them alone.
        $details = [];
        foreach (['payer_reference', 'portal', 'medical_report_document_id', 'assessment_report_document_id'] as $key) {
            if ($request->has($key)) {
                $details[$key] = $request->input($key) ?: null;
            }
        }
        foreach (['medical_report_document_id', 'assessment_report_document_id'] as $key) {
            if (! empty($details[$key]) && ! $claim->patient?->documents()->whereKey($details[$key])->exists()) {
                return response()->json(['message' => 'That document isn’t on this client’s file.'], 422);
            }
        }
        $claim->fill($details);

        // Moving a claim towards the insurer needs what the portal needs: the
        // finalized invoice PDF and the medical report.
        $advancing = in_array($request->status, ['documents_ready', 'submitted'], true) && $request->status !== $claim->getOriginal('status');
        if ($advancing) {
            if ($claim->invoice && ! $claim->invoice->isVoided() && ! $claim->invoice->isFinal()) {
                return response()->json(['message' => "Invoice {$claim->invoice->invoice_number} is {$claim->invoice->workflowLabel()} — the claim can only go to the insurer once the invoice is approved by Finance and finalized."], 422);
            }
            if (! $claim->medical_report_document_id) {
                return response()->json(['message' => 'Attach the medical report before marking this claim '.InsuranceClaim::LABELS[$request->status].'.'], 422);
            }
        }

        // Settling records the insurer's remittance as a receipt. If the
        // invoice has already been paid some other way, that receipt would
        // double-count the money - stop and let Finance sort the receipts.
        if ($request->status === 'settled' && $claim->getOriginal('status') !== 'settled' && $claim->invoice && ! $claim->invoice->isVoided()) {
            $room = max(0, $claim->invoice->balance());
            if ((float) $claim->amount > $room + 0.01) {
                return response()->json(['message' => "Settling {$claim->reference} would record AED ".number_format((float) $claim->amount, 2)." against {$claim->invoice->invoice_number}, which only has AED ".number_format($room, 2).' outstanding. Check the receipts already on that invoice first.'], 422);
            }
        }

        $invoiceRow = DB::transaction(function () use ($request, $claim) {
            $wasSettled = $claim->status === 'settled';
            $willBeSettled = $request->status === 'settled';

            $claim->fill([
                'status' => $request->status,
                'notes' => $request->filled('notes') ? $request->notes : $claim->notes,
                'settled_on' => $willBeSettled ? ($claim->settled_on ?? now()->toDateString()) : null,
                'submitted_on' => in_array($request->status, ['draft', 'documents_ready'], true) ? $claim->submitted_on : ($claim->submitted_on ?? now()->toDateString()),
            ]);
            $changed = array_keys($claim->getDirty());
            $claim->save();

            if ($claim->wasChanged('status')) {
                Activity::log(
                    'claim_status',
                    "Claim {$claim->reference} marked {$claim->statusLabel()}",
                    $claim->invoice_id ? route('billing.invoices.show', $claim->invoice_id) : null
                );
            }

            $invoice = $claim->invoice;
            if ($invoice && $changed) {
                $parts = [];
                if (in_array('status', $changed, true)) {
                    $parts[] = "marked {$claim->statusLabel()}";
                }
                if (in_array('payer_reference', $changed, true) && $claim->payer_reference) {
                    $parts[] = "insurer reference {$claim->payer_reference} recorded";
                }
                if (in_array('portal', $changed, true) && $claim->portal) {
                    $parts[] = "portal {$claim->portal}";
                }
                if (array_intersect(['medical_report_document_id', 'assessment_report_document_id'], $changed)) {
                    $parts[] = 'supporting documents updated';
                }
                if ($parts) {
                    $invoice->recordEvent('claim_updated', "Claim {$claim->reference} — ".implode(', ', $parts));
                }
            }
            if ($invoice && ! $invoice->isVoided()) {
                // Lodged with the insurer: for this billing route that is the
                // invoice's dispatch.
                if ($request->status === 'submitted' && $invoice->workflow_status === 'finalized') {
                    $invoice->forceFill(['workflow_status' => 'dispatched'])->save();
                }

                // Settling a claim is the insurer's money actually landing -
                // record it as a receipt so the balance genuinely drops, the
                // same way a family payment would. The invoice previously
                // kept showing its full gross total as outstanding forever,
                // because "settled" never touched a single payment record.
                // Moving off "settled" (a correction) reverses that receipt
                // instead of leaving a stale one behind.
                $alreadyRemitted = $invoice->payments()
                    ->where('reference', $claim->reference)
                    ->where('method', 'Insurance remittance')
                    ->exists();

                if ($willBeSettled && ! $wasSettled && ! $alreadyRemitted) {
                    $invoice->payments()->create([
                        'receipt_number' => DocumentNumbers::nextReceipt(),
                        'amount' => $claim->amount,
                        'method' => 'Insurance remittance',
                        'received_on' => $claim->settled_on,
                        'reference' => $claim->reference,
                        'recorded_by' => auth()->id(),
                    ]);
                } elseif (! $willBeSettled && $wasSettled) {
                    $invoice->payments()
                        ->where('reference', $claim->reference)
                        ->where('method', 'Insurance remittance')
                        ->delete();
                }

                // Non-settled claim states still mirror onto the invoice's own
                // status for older screens that read it - "settled" isn't
                // itself a valid invoice status, so the receipt above (via
                // syncPaymentColumns' balance check) decides paid vs issued.
                if (! $willBeSettled) {
                    $invoice->forceFill(['status' => $request->status])->save();
                }
                $invoice->unsetRelation('payments')->syncPaymentColumns();
            }

            return $invoice ? InvoicePresenter::row($invoice->fresh()) : null;
        });

        return response()->json([
            'message' => "{$claim->reference} marked {$claim->statusLabel()}.",
            'claim' => BillingController::claimRow($claim->fresh('patient.lead')),
            'invoice' => $invoiceRow,
        ]);
    }
}
