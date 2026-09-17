<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\CalendarSession;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\PatientAuthorization;
use App\Models\Payment;
use App\Services\Billing\DocumentNumbers;
use App\Services\Billing\InvoiceBuilder;
use App\Services\Billing\InvoicePresenter;
use App\Services\Billing\SessionLedger;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class InvoiceController extends Controller
{
    public function __construct(private SessionLedger $ledger, private InvoiceBuilder $builder)
    {
    }

    /**
     * The client's delivered sessions, priced and routed - what the New
     * invoice picker lists.
     */
    public function ledger(Patient $patient)
    {
        $patient->load(['lead', 'authorizations']);
        $rows = $this->ledger->forPatient($patient);
        $profile = $this->ledger->profile($patient);

        return response()->json([
            'patient' => InvoicePresenter::patient($patient, $this->ledger),
            'rows' => $rows->values(),
            'prepaid_left' => $profile['prepaid'] ? ($rows->first()['prepaid_left'] ?? $profile['prepaid']['total']) : null,
            'prepaid_used' => $profile['prepaid'] ? $profile['prepaid']['total'] - ($rows->first()['prepaid_left'] ?? $profile['prepaid']['total']) : null,
        ]);
    }

    /**
     * The invoice exactly as it would be issued for the selected sessions -
     * nothing is saved. Attendance corrections are applied in memory here and
     * persisted only on save.
     */
    public function preview(Request $request)
    {
        $this->assertCanInvoice();
        [$patient, $sessions, $errors] = $this->resolveSelection($request);
        if ($errors) {
            return response()->json($errors, 422);
        }

        $composed = $this->compose($patient, $sessions);

        return response()->json($composed + [
            'invoice_number' => DocumentNumbers::peekInvoice(),
            'issue_date' => now()->format('d M Y'),
            'due_date' => now()->addDays((int) config('billing.due_days', 30))->format('d M Y'),
            'settled_count' => $sessions->whereNotNull('invoice_id')->count(),
            'clinic' => config('clinic'),
        ]);
    }

    public function store(Request $request)
    {
        $this->assertCanInvoice();
        [$patient, $sessions, $errors] = $this->resolveSelection($request);
        if ($errors) {
            return response()->json($errors, 422);
        }

        $settled = $sessions->whereNotNull('invoice_id');
        if ($settled->isNotEmpty() && ! $request->boolean('acknowledge_settled')) {
            return response()->json([
                'message' => $settled->count().' selected session(s) are already on an invoice.',
                'settled_count' => $settled->count(),
                'settled_ids' => $settled->pluck('id')->values(),
            ], 409);
        }

        // Attendance corrections made in the picker are written back to the
        // calendar - the invoice is a view over that evidence, not a fork of it.
        foreach ((array) $request->input('attendance', []) as $id => $att) {
            $s = $sessions->firstWhere('id', (int) $id);
            if ($s && ! empty($att['state'])) {
                SessionLedger::applyAttendance($s, $att['state'], isset($att['notice_hours']) && $att['notice_hours'] !== '' ? (float) $att['notice_hours'] : null);
            }
        }
        $sessions = CalendarSession::with(['therapist', 'supervisor', 'invoice'])->whereIn('id', $sessions->pluck('id'))->get();

        $composed = $this->compose($patient, $sessions);
        if (! $composed['lines']) {
            return response()->json(['message' => 'Nothing billable in the selection — every chosen session is unchargeable under the cancellation policy.'], 422);
        }

        $invoice = $this->builder->issue($patient, $composed, null, auth()->id());

        return response()->json([
            'message' => "{$invoice->invoice_number} raised — {$composed['session_count']} session(s), {$composed['bill_hours']} billable hour(s).",
            'invoice' => InvoicePresenter::row($invoice),
        ], 201);
    }

    public function show(Invoice $invoice)
    {
        $invoice->load(['patient.lead', 'lineItems.therapist', 'payments', 'claims', 'replaces', 'replacedBy']);

        return view('billing.print', ['invoice' => $invoice, 'clinic' => config('clinic'), 'row' => InvoicePresenter::row($invoice)]);
    }

    public function data(Invoice $invoice)
    {
        return response()->json(InvoicePresenter::row($invoice));
    }

    public function storePayment(Request $request, Invoice $invoice)
    {
        $this->assertCanInvoice();
        if ($invoice->isVoided()) {
            return response()->json(['message' => 'A voided invoice can’t take payments.'], 422);
        }

        $validator = Validator::make($request->all(), [
            'amount' => ['required', 'numeric', 'min:0.01'],
            'method' => ['required', 'in:'.implode(',', Payment::METHODS)],
            'received_on' => ['required', 'date'],
            'reference' => ['nullable', 'string', 'max:100'],
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first(), 'errors' => $validator->errors()], 422);
        }

        $payment = DB::transaction(function () use ($request, $invoice) {
            $p = $invoice->payments()->create([
                'receipt_number' => DocumentNumbers::nextReceipt(),
                'amount' => round((float) $request->amount, 2),
                'method' => $request->method,
                'received_on' => $request->received_on,
                'reference' => $request->reference,
                'recorded_by' => auth()->id(),
            ]);
            $invoice->unsetRelation('payments')->syncPaymentColumns();

            return $p;
        });

        return response()->json([
            'message' => "Receipt {$payment->receipt_number} recorded — AED ".number_format($payment->amount, 2).'.',
            'invoice' => InvoicePresenter::row($invoice->fresh()),
        ], 201);
    }

    public function storeCredit(Request $request, Invoice $invoice)
    {
        $this->assertCanInvoice();
        if ($invoice->isVoided()) {
            return response()->json(['message' => 'A voided invoice can’t be credited further.'], 422);
        }

        $validator = Validator::make($request->all(), [
            'amount' => ['required', 'numeric', 'min:0.01', 'max:'.max(0.01, (float) $invoice->total - (float) $invoice->credit_amount)],
            'reason' => ['required', 'string', 'max:255'],
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first(), 'errors' => $validator->errors()], 422);
        }

        $invoice->forceFill([
            'credit_amount' => round((float) $invoice->credit_amount + (float) $request->amount, 2),
            'credit_reason' => trim(($invoice->credit_reason ? $invoice->credit_reason."\n" : '').$request->reason),
        ])->save();
        $invoice->syncPaymentColumns();

        Activity::log(
            'credit_note',
            'Credit note AED '.number_format((float) $request->amount, 2)." issued against {$invoice->invoice_number}",
            route('billing.invoices.show', $invoice)
        );

        return response()->json([
            'message' => 'Credit note of AED '.number_format((float) $request->amount, 2).' issued.',
            'invoice' => InvoicePresenter::row($invoice->fresh()),
        ]);
    }

    /**
     * An issued tax invoice is never edited or deleted: void raises a credit
     * note for the open balance and, usually, reissues a corrected document
     * under a fresh number linked back to this one.
     */
    public function void(Request $request, Invoice $invoice)
    {
        $this->assertCanInvoice();
        if ($invoice->isVoided()) {
            return response()->json(['message' => 'Already voided.'], 422);
        }

        $validator = Validator::make($request->all(), ['reason' => ['required', 'string', 'max:255'], 'reissue' => ['sometimes', 'boolean']]);
        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first(), 'errors' => $validator->errors()], 422);
        }

        $reissued = DB::transaction(function () use ($request, $invoice) {
            $open = max(0, $invoice->balance());
            $invoice->forceFill([
                'credit_amount' => round((float) $invoice->credit_amount + $open, 2),
                'credit_reason' => trim(($invoice->credit_reason ? $invoice->credit_reason."\n" : '').'Voided — '.$request->reason),
                'voided_at' => now(),
                'void_reason' => $request->reason,
            ])->save();
            $invoice->claims()->where('status', '!=', 'settled')->update(['status' => 'draft', 'notes' => 'Invoice voided — '.$request->reason]);

            if (! $request->boolean('reissue', true)) {
                // Sessions go back to unbilled so they can be picked again.
                $invoice->sessions()->update(['invoice_id' => null]);

                return null;
            }

            $clone = $invoice->replicate(['invoice_number', 'credit_amount', 'credit_reason', 'voided_at', 'void_reason', 'replaces_invoice_id', 'replaced_by_invoice_id', 'sent_to', 'sent_at', 'reminder_sent_at', 'reminders_count', 'amount_paid', 'payment_method', 'claim_reference', 'batch_reference']);
            $clone->invoice_number = DocumentNumbers::nextInvoice();
            $clone->issue_date = now()->toDateString();
            $clone->due_date = now()->addDays((int) config('billing.due_days', 30))->toDateString();
            $clone->status = (float) $invoice->insurance_coverage_amount > 0 ? 'submitted' : 'issued';
            $clone->amount_paid = 0;
            $clone->replaces_invoice_id = $invoice->id;
            $clone->created_by = auth()->id();
            $clone->save();

            foreach ($invoice->lineItems as $line) {
                $copy = $line->replicate();
                $copy->invoice_id = $clone->id;
                $copy->save();
            }
            $invoice->sessions()->update(['invoice_id' => $clone->id]);
            $invoice->forceFill(['replaced_by_invoice_id' => $clone->id])->save();

            foreach ($invoice->payer_splits ?? [] as $split) {
                $claim = $clone->claims()->create([
                    'reference' => DocumentNumbers::nextClaim(),
                    'patient_id' => $clone->patient_id,
                    'insurer' => $split['payer'],
                    'amount' => $split['amount'],
                    'period_label' => $clone->periodLabel(),
                    'status' => 'submitted',
                    'submitted_on' => now()->toDateString(),
                ]);
                if (! $clone->claim_reference) {
                    $clone->forceFill(['claim_reference' => $claim->reference])->save();
                }
            }

            return $clone;
        });

        $invoice->syncPaymentColumns();

        Activity::log(
            'invoice_voided',
            "Invoice {$invoice->invoice_number} voided — {$request->reason}".($reissued ? " — reissued as {$reissued->invoice_number}" : ''),
            route('billing.invoices.show', $reissued ?: $invoice)
        );

        return response()->json([
            'message' => "{$invoice->invoice_number} voided".($reissued ? " and reissued as {$reissued->invoice_number}." : '.'),
            'invoice' => InvoicePresenter::row($invoice->fresh()),
            'reissued' => $reissued ? InvoicePresenter::row($reissued) : null,
        ]);
    }

    /**
     * Email the invoice (or a payment reminder) with the printable document
     * attached. The send result - not the click - is what stamps the invoice.
     */
    public function send(Request $request, Invoice $invoice)
    {
        $this->assertCanInvoice();

        $validator = Validator::make($request->all(), [
            'to' => ['required', 'email'],
            'cc' => ['nullable', 'email'],
            'subject' => ['required', 'string', 'max:200'],
            'message' => ['required', 'string', 'max:5000'],
            'kind' => ['nullable', 'in:invoice,reminder'],
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first(), 'errors' => $validator->errors()], 422);
        }

        // A double-click (or any near-simultaneous repeat request) on "Send
        // email" must never result in two emails - the client-side button
        // disables itself, but that's only a courtesy; this lock is what
        // actually guarantees it server-side. A request that loses the race
        // isn't an error - it's told the email is already on its way, using
        // the same success shape the first request gets, per the invoice's
        // state once the first request finishes.
        $lock = Cache::lock("invoice-send-{$invoice->id}", 30);
        if (! $lock->get()) {
            return response()->json([
                'message' => "{$invoice->invoice_number} is already being sent.",
                'invoice' => InvoicePresenter::row($invoice->fresh(['patient.lead', 'payments', 'claims'])),
            ]);
        }

        try {
            $invoice->load(['patient.lead', 'lineItems.therapist', 'payments', 'claims', 'replaces', 'replacedBy']);
            // A dedicated template, not billing.print - dompdf doesn't reliably
            // render the flex/grid layout that view uses for the browser's own
            // "Print / Save as PDF" output, so print_pdf rebuilds the same design
            // with table-based layout dompdf renders correctly.
            $attachment = Pdf::loadView('billing.print_pdf', ['invoice' => $invoice, 'clinic' => config('clinic'), 'row' => InvoicePresenter::row($invoice)])
                ->setPaper('a4')
                ->output();
            $body = nl2br(e($request->message));

            try {
                Mail::html('<div style="font: 14px/1.5 Arial, sans-serif; color: #2B3A4C;">'.$body.'</div>', function ($m) use ($request, $invoice, $attachment) {
                    $m->to($request->to)->subject($request->subject);
                    if ($request->filled('cc')) {
                        $m->cc($request->cc);
                    }
                    $m->attachData($attachment, $invoice->invoice_number.'.pdf', ['mime' => 'application/pdf']);
                });
            } catch (\Throwable $e) {
                report($e);

                return response()->json(['message' => 'The email could not be sent: '.$e->getMessage()], 500);
            }

            if (($request->kind ?? 'invoice') === 'reminder') {
                $invoice->forceFill(['reminder_sent_at' => now(), 'reminders_count' => (int) $invoice->reminders_count + 1])->save();
                $message = "Reminder for {$invoice->invoice_number} sent to {$request->to}.";
            } else {
                $invoice->forceFill(['sent_to' => $request->to, 'sent_at' => now()])->save();
                $message = "{$invoice->invoice_number} emailed to {$request->to}.";
            }

            return response()->json(['message' => $message, 'invoice' => InvoicePresenter::row($invoice->fresh())]);
        } finally {
            $lock->release();
        }
    }

    /**
     * Prepaid top-up: hours are the unit the family buys, so this adds to the
     * self-pay authorization's hour envelope.
     */
    public function topUp(Request $request, Patient $patient)
    {
        $this->assertCanInvoice();

        $validator = Validator::make($request->all(), ['hours' => ['required', 'integer', 'min:1', 'max:500']]);
        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $auth = $patient->authorizations()->get()->first(fn (PatientAuthorization $a) => preg_match('/self/i', (string) $a->payer_name));
        if (! $auth) {
            $auth = $patient->authorizations()->create([
                'payer_name' => 'Self-pay',
                'coverage_percent' => 0,
                'covers_services' => ['ABA', 'Speech', 'OT'],
                'authorized_hours_total' => 0,
                'sort_order' => ($patient->authorizations()->max('sort_order') ?? -1) + 1,
            ]);
        }
        $auth->increment('authorized_hours_total', (int) $request->hours);

        return response()->json(['message' => "{$request->hours} prepaid hour(s) added.", 'prepaid_total' => (int) $auth->fresh()->authorized_hours_total]);
    }

    /**
     * Per-family running-balance statement: every invoice, credit note and
     * receipt in date order. Derived on demand, never stored.
     */
    public function statement(Patient $patient)
    {
        $patient->load(['lead', 'authorizations', 'invoices.payments']);
        $entries = collect();

        foreach ($patient->invoices as $inv) {
            $entries->push(['date' => $inv->issue_date, 'ref' => $inv->invoice_number, 'desc' => 'Tax invoice · '.$inv->periodLabel().($inv->isVoided() ? ' (voided — '.$inv->void_reason.')' : ''), 'charge' => $inv->isVoided() ? 0 : (float) $inv->total, 'credit' => 0]);
            if ((float) $inv->credit_amount > 0) {
                $entries->push(['date' => $inv->voided_at ?? $inv->updated_at, 'ref' => $inv->invoice_number, 'desc' => 'Credit note — '.($inv->credit_reason ?: 'credit'), 'charge' => 0, 'credit' => (float) $inv->credit_amount]);
            }
            foreach ($inv->payments as $p) {
                $entries->push(['date' => $p->received_on, 'ref' => $p->receipt_number, 'desc' => "Payment received — {$p->method}".($p->reference ? " · ref {$p->reference}" : '')." · {$inv->invoice_number}", 'charge' => 0, 'credit' => (float) $p->amount]);
            }
        }

        $balance = 0;
        $rows = $entries->sortBy(fn ($e) => $e['date']?->format('Y-m-d').$e['ref'])->values()->map(function ($e) use (&$balance) {
            $balance += $e['charge'] - $e['credit'];
            $e['balance'] = $balance;
            $e['date_label'] = $e['date']?->format('d M Y');

            return $e;
        });

        $oldest = $patient->invoices->filter(fn ($i) => ! $i->isVoided() && $i->balance() > 0.01)->sortBy('due_date')->first();
        $profile = $this->ledger->profile($patient);

        return view('billing.statement', [
            'patient' => $patient,
            'profile' => $profile,
            'rows' => $rows,
            'charged' => $rows->sum('charge'),
            'credited' => $rows->sum('credit'),
            'balance' => $balance,
            'oldest' => $oldest,
            'clinic' => config('clinic'),
            'asOf' => now(),
        ]);
    }

    // ------------------------------------------------------------------

    private function assertCanInvoice(): void
    {
        abort_unless(auth()->user()->canDo('create_invoice'), 403, 'Invoices are raised by Finance.');
    }

    /**
     * @return array{0: ?Patient, 1: \Illuminate\Support\Collection, 2: ?array}
     */
    private function resolveSelection(Request $request): array
    {
        $validator = Validator::make($request->all(), [
            'patient_id' => ['required', 'exists:patients,id'],
            'session_ids' => ['required', 'array', 'min:1'],
            'session_ids.*' => ['integer'],
            'attendance' => ['nullable', 'array'],
        ]);
        if ($validator->fails()) {
            return [null, collect(), ['message' => $validator->errors()->first(), 'errors' => $validator->errors()]];
        }

        $patient = Patient::with(['lead', 'authorizations'])->findOrFail($request->patient_id);
        $sessions = $patient->calendarSessions()
            ->with(['therapist', 'supervisor', 'invoice'])
            ->whereIn('id', $request->session_ids)
            ->get();

        if ($sessions->isEmpty()) {
            return [$patient, collect(), ['message' => 'None of the selected sessions belong to this client.']];
        }

        // Preview-time attendance overrides, applied in memory only.
        foreach ((array) $request->input('attendance', []) as $id => $att) {
            $s = $sessions->firstWhere('id', (int) $id);
            if ($s && ! empty($att['state']) && ! (in_array($att['state'], ['completed', 'no_show'], true) && ! $s->isPast())) {
                $probe = $s->replicate();
                $probe->id = $s->id;
                $probe->setRelations($s->getRelations());
                $probe->invoice_id = $s->invoice_id;
                $stateChanges = match ($att['state']) {
                    'completed' => ['status' => 'completed', 'cancel_reason' => null, 'cancel_notice_hours' => null],
                    'no_show' => ['status' => 'no_show', 'cancel_reason' => null, 'cancel_notice_hours' => null],
                    'cancelled_clinic' => ['status' => 'cancelled', 'cancel_reason' => 'clinic', 'cancel_notice_hours' => null],
                    'cancelled_late' => ['status' => 'cancelled', 'cancel_reason' => 'family', 'cancel_notice_hours' => isset($att['notice_hours']) && $att['notice_hours'] !== '' ? (float) $att['notice_hours'] : max(0, (int) config('billing.cancel_policy.notice_hours') - 1)],
                    'cancelled_notice' => ['status' => 'cancelled', 'cancel_reason' => 'family', 'cancel_notice_hours' => isset($att['notice_hours']) && $att['notice_hours'] !== '' ? (float) $att['notice_hours'] : (int) config('billing.cancel_policy.notice_hours')],
                    default => [],
                };
                foreach ($stateChanges as $k => $v) {
                    $s->{$k} = $v;
                }
            }
        }

        return [$patient, $sessions, null];
    }

    private function compose(Patient $patient, $sessions): array
    {
        $profile = $this->ledger->profile($patient);
        $rows = $this->ledger->rows($patient, $sessions, $profile);

        return $this->builder->compose($patient, $rows, $profile) + ['rows' => $rows->values()];
    }
}
