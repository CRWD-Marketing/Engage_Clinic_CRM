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
    public function update(Request $request, InsuranceClaim $claim)
    {
        abort_unless(auth()->user()->canDo('create_invoice'), 403, 'Claims are managed by Finance.');

        $validator = Validator::make($request->all(), [
            'status' => ['required', 'in:'.implode(',', InsuranceClaim::STATUSES)],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $invoiceRow = DB::transaction(function () use ($request, $claim) {
            $wasSettled = $claim->status === 'settled';
            $willBeSettled = $request->status === 'settled';

            $claim->fill([
                'status' => $request->status,
                'notes' => $request->filled('notes') ? $request->notes : $claim->notes,
                'settled_on' => $willBeSettled ? ($claim->settled_on ?? now()->toDateString()) : null,
            ])->save();

            if ($claim->wasChanged('status')) {
                Activity::log(
                    'claim_status',
                    "Claim {$claim->reference} marked {$claim->statusLabel()}",
                    $claim->invoice_id ? route('billing.invoices.show', $claim->invoice_id) : null
                );
            }

            $invoice = $claim->invoice;
            if ($invoice && ! $invoice->isVoided()) {
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
