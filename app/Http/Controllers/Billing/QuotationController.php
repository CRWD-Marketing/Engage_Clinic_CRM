<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\Quotation;
use App\Services\Billing\DocumentNumbers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Inquiry → quotation → customer confirmation → payment confirmation. The
 * last step is the trigger for session scheduling.
 */
class QuotationController extends Controller
{
    public function store(Request $request)
    {
        $this->assertCanInvoice();

        $validator = Validator::make($request->all(), [
            'patient_id' => ['required', 'exists:patients,id'],
            'location' => ['required', 'string', 'max:100'],
            'payment_mode' => ['required', 'in:'.implode(',', Quotation::PAYMENT_MODES)],
            'payer' => ['required_if:payment_mode,Insurance', 'nullable', 'string', 'max:100'],
            'service' => ['required', 'string', 'max:100'],
            'pricing_basis' => ['required', 'in:'.implode(',', Quotation::PRICING_BASES)],
            'billing_unit' => ['required', 'in:hour,session'],
            'quantity' => ['required', 'numeric', 'min:0.5', 'max:5000'],
            'rate' => ['required', 'numeric', 'min:0.01', 'max:100000'],
            'payment_terms' => ['required', 'string', 'max:255'],
            'valid_until' => ['required', 'date', 'after_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'payer.required_if' => 'Choose the insurer for an insurance quotation.',
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first(), 'errors' => $validator->errors()], 422);
        }

        // Same double-submit guard as the pre-authorization form.
        $lock = Cache::lock("quotation-{$request->patient_id}-".md5($request->service.'|'.$request->payment_mode), 10);
        if (! $lock->get()) {
            return response()->json(['message' => 'This quotation is already being saved.'], 429);
        }

        try {
            $patient = Patient::with('lead')->findOrFail($request->patient_id);
            $subtotal = round((float) $request->quantity * (float) $request->rate, 2);
            $vat = round($subtotal * (float) config('billing.vat_rate', 5) / 100, 2);

            $quotation = DB::transaction(function () use ($validator, $patient, $subtotal, $vat) {
                $q = Quotation::create($validator->validated() + [
                    'quote_number' => DocumentNumbers::nextQuotation(),
                    'customer_name' => $patient->lead?->parent_guardian_name,
                    'subtotal' => $subtotal,
                    'vat_amount' => $vat,
                    'total' => $subtotal + $vat,
                    'status' => 'draft',
                    'created_by' => auth()->id(),
                ]);
                $q->recordEvent('created', "{$q->service} — {$q->quantity} {$q->billing_unit}(s) at AED ".number_format((float) $q->rate, 2));

                return $q;
            });
        } finally {
            $lock->release();
        }

        return response()->json(['message' => "Quotation {$quotation->quote_number} drafted.", 'quotation' => self::row($quotation)], 201);
    }

    public function show(Quotation $quotation)
    {
        $quotation->load('patient.lead');

        return view('billing.quotation', ['quotation' => $quotation, 'clinic' => config('clinic')]);
    }

    /**
     * The quotation was shared with the customer (it's sent from WhatsApp or
     * email by hand; this records that it went).
     */
    public function send(Request $request, Quotation $quotation)
    {
        $this->assertCanInvoice();
        $validator = Validator::make($request->all(), ['sent_via' => ['required', 'in:'.implode(',', Quotation::SEND_CHANNELS)]]);
        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }
        if ($blocked = $this->blocked($quotation, ['draft', 'sent'], 'sent')) {
            return $blocked;
        }

        $quotation->forceFill(['status' => 'sent', 'sent_at' => now(), 'sent_via' => $request->sent_via])->save();
        $quotation->recordEvent('sent', "Shared with the customer by {$request->sent_via}");

        return response()->json(['message' => "{$quotation->quote_number} marked as sent by {$request->sent_via}.", 'quotation' => self::row($quotation->fresh())]);
    }

    /**
     * Customer confirmation, written or verbal.
     */
    public function confirm(Request $request, Quotation $quotation)
    {
        $this->assertCanInvoice();
        $validator = Validator::make($request->all(), [
            'confirmation_method' => ['required', 'in:'.implode(',', Quotation::CONFIRMATION_METHODS)],
            'confirmed_on' => ['required', 'date', 'before_or_equal:today'],
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }
        if ($blocked = $this->blocked($quotation, ['sent'], 'confirmed')) {
            return $blocked;
        }

        $quotation->forceFill(['status' => 'customer_confirmed', 'confirmed_at' => $request->confirmed_on, 'confirmation_method' => $request->confirmation_method])->save();
        $quotation->recordEvent('customer_confirmed', "{$request->confirmation_method} confirmation from the customer");

        return response()->json(['message' => "{$quotation->quote_number} confirmed by the customer — payment pending.", 'quotation' => self::row($quotation->fresh())]);
    }

    /**
     * Payment confirmation - the scheduling trigger. Self-pay needs the
     * signed prepayment policy and the money (plus the POS fee agreement for
     * a card); direct insurance billing needs the NOC and the customer's
     * acknowledgement of liability for anything the insurer rejects.
     */
    public function clear(Request $request, Quotation $quotation)
    {
        $this->assertCanInvoice();
        if ($blocked = $this->blocked($quotation, ['customer_confirmed'], 'cleared for scheduling')) {
            return $blocked;
        }

        if ($quotation->isInsurance()) {
            $validator = Validator::make($request->all(), [
                'noc_received' => ['accepted'],
                'liability_acknowledged' => ['accepted'],
            ], [
                'noc_received.accepted' => 'The NOC must be on file before direct insurance billing is activated.',
                'liability_acknowledged.accepted' => 'The customer must acknowledge 100% personal liability for insurance-rejected amounts.',
            ]);
        } else {
            $validator = Validator::make($request->all(), [
                'prepayment_policy_received' => ['accepted'],
                'amount_received' => ['required', 'numeric', 'min:0.01', 'max:'.max(0.01, (float) $quotation->total)],
                'payment_method' => ['required', 'in:'.implode(',', array_diff(Payment::METHODS, ['Insurance remittance']))],
                'payment_received_on' => ['required', 'date', 'before_or_equal:today'],
                'payment_reference' => ['nullable', 'string', 'max:100'],
                'pos_agreement_received' => ['exclude_unless:payment_method,Card', 'accepted'],
            ], [
                'prepayment_policy_received.accepted' => 'The signed prepayment policy must be on file before payment is confirmed.',
                'pos_agreement_received.accepted' => 'A card payment needs the signed POS fee agreement on file.',
                'amount_received.max' => 'The amount received can’t be more than the quotation total (AED '.number_format((float) $quotation->total, 2).').',
            ]);
        }
        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first(), 'errors' => $validator->errors()], 422);
        }

        $card = ! $quotation->isInsurance() && $request->payment_method === 'Card';
        $quotation->forceFill([
            'status' => 'cleared',
            'payment_confirmed_at' => now(),
            'payment_confirmed_by' => auth()->id(),
        ] + ($quotation->isInsurance() ? [
            'noc_received' => true,
            'liability_acknowledged' => true,
        ] : [
            'prepayment_policy_received' => true,
            'pos_agreement_received' => $card,
            'payment_method' => $request->payment_method,
            'amount_received' => round((float) $request->amount_received, 2),
            'payment_received_on' => $request->payment_received_on,
            'payment_reference' => $request->payment_reference,
            'pos_fee_amount' => $card ? Quotation::posFee((float) $quotation->subtotal) : 0,
        ]))->save();

        $child = $quotation->patient?->lead?->child_name ?? 'client';
        $note = $quotation->isInsurance()
            ? "Insurance billing activated with {$quotation->payer} — NOC and liability acknowledgement on file"
            : 'Payment of AED '.number_format((float) $quotation->amount_received, 2)." confirmed · {$quotation->payment_method}".($quotation->payment_reference ? " · ref {$quotation->payment_reference}" : '');
        $quotation->recordEvent('cleared', $note);
        Activity::log('quotation_cleared', "{$quotation->quote_number} for {$child}: {$note} — cleared for scheduling", route('billing.index'));

        return response()->json(['message' => "{$quotation->quote_number} cleared — {$child} can now be scheduled.", 'quotation' => self::row($quotation->fresh())]);
    }

    public function cancel(Request $request, Quotation $quotation)
    {
        $this->assertCanInvoice();
        $validator = Validator::make($request->all(), ['reason' => ['required', 'string', 'max:255']]);
        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }
        if ($blocked = $this->blocked($quotation, ['draft', 'sent', 'customer_confirmed'], 'cancelled')) {
            return $blocked;
        }

        $quotation->forceFill(['status' => 'cancelled', 'cancel_reason' => $request->reason])->save();
        $quotation->recordEvent('cancelled', $request->reason);

        return response()->json(['message' => "{$quotation->quote_number} cancelled.", 'quotation' => self::row($quotation->fresh())]);
    }

    public function history(Quotation $quotation)
    {
        return response()->json([
            'number' => $quotation->quote_number,
            'events' => $quotation->events()->with('user')->get()->map(fn ($e) => [
                'label' => ucfirst(str_replace('_', ' ', $e->event)),
                'note' => $e->note,
                'by' => $e->user ? trim($e->user->first_name.' '.$e->user->last_name) : 'System',
                'at' => $e->created_at->format('d M Y H:i'),
            ])->values(),
        ]);
    }

    public static function row(Quotation $q): array
    {
        $q->loadMissing('patient.lead');
        $retention = (int) config('billing.retention_years.quotations', 2);

        return [
            'id' => $q->id,
            'number' => $q->quote_number,
            'patient_id' => $q->patient_id,
            'patient' => $q->patient?->lead?->child_name ?? '—',
            'customer' => $q->customer_name,
            'location' => $q->location,
            'payment_mode' => $q->payment_mode,
            'payer' => $q->payer,
            'insurance' => $q->isInsurance(),
            'service' => $q->service,
            'pricing_basis' => $q->pricing_basis,
            'unit' => $q->billing_unit,
            'quantity' => (float) $q->quantity,
            'rate' => (float) $q->rate,
            'subtotal' => (float) $q->subtotal,
            'vat' => (float) $q->vat_amount,
            'total' => (float) $q->total,
            'pos_fee_if_card' => Quotation::posFee((float) $q->subtotal),
            'pos_fee' => (float) $q->pos_fee_amount,
            'terms' => $q->payment_terms,
            'valid_until' => $q->valid_until->format('d M Y'),
            'expired' => $q->isExpired(),
            'status' => $q->isExpired() ? 'expired' : $q->status,
            'status_label' => $q->statusLabel(),
            'sent' => $q->sent_at ? $q->sent_at->format('d M Y').' · '.$q->sent_via : null,
            'confirmed' => $q->confirmed_at ? $q->confirmed_at->format('d M Y').' · '.$q->confirmation_method : null,
            'cleared_on' => $q->payment_confirmed_at?->format('d M Y'),
            'amount_received' => (float) $q->amount_received,
            'prepaid_balance' => $q->isCleared() && ! $q->isInsurance() ? $q->prepaidBalance() : 0,
            'payment_method' => $q->payment_method,
            'payment_reference' => $q->payment_reference,
            'retain_until' => $q->created_at->copy()->addYears($retention)->format('d M Y'),
            'print_url' => route('billing.quotations.show', $q),
        ];
    }

    private function blocked(Quotation $quotation, array $from, string $verb): ?\Illuminate\Http\JsonResponse
    {
        if ($quotation->isExpired() && $verb !== 'cancelled') {
            return response()->json(['message' => "{$quotation->quote_number} expired on {$quotation->valid_until->format('d M Y')} — raise a new quotation."], 422);
        }
        if (! in_array($quotation->status, $from, true)) {
            return response()->json(['message' => "{$quotation->quote_number} is {$quotation->statusLabel()} — it can’t be {$verb} from there."], 422);
        }

        return null;
    }

    private function assertCanInvoice(): void
    {
        abort_unless(auth()->user()->canDo('create_invoice'), 403, 'Quotations are managed by Finance.');
    }
}
