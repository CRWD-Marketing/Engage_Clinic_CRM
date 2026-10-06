<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\CalendarSession;
use App\Models\Invoice;
use App\Models\InvoiceDispatch;
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
        // A session becomes billable the moment its end time passes, not when
        // the day ends - catch up here as the calendar does, so this
        // morning's session can be invoiced this afternoon.
        CalendarSession::pastDueScheduled()->update(['status' => 'completed']);

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
        // Same insurance date rule the builder applies on save.
        $issue = $composed['insurer_share'] > 0 ? \Carbon\Carbon::parse($composed['period_to']) : now();

        return response()->json($composed + [
            'invoice_number' => DocumentNumbers::peekInvoice(),
            'issue_date' => $issue->format('d M Y'),
            'due_date' => $issue->copy()->addDays((int) config('billing.due_days', 30))->format('d M Y'),
            'settled_count' => $sessions->whereNotNull('invoice_id')->count(),
            'quotation_notice' => $this->quotationNotice($patient, $composed),
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
        $attendanceEdits = [];
        foreach ((array) $request->input('attendance', []) as $id => $att) {
            $s = $sessions->firstWhere('id', (int) $id);
            if ($s && ! empty($att['state'])) {
                $before = $this->ledger->billing($s->fresh())['state'];
                if ($before !== $att['state']) {
                    $attendanceEdits[] = $s->session_date->format('d M Y')." {$before} → {$att['state']}";
                }
                SessionLedger::applyAttendance($s, $att['state'], isset($att['notice_hours']) && $att['notice_hours'] !== '' ? (float) $att['notice_hours'] : null);
            }
        }
        $sessions = CalendarSession::with(['therapist', 'supervisor', 'invoice'])->whereIn('id', $sessions->pluck('id'))->get();

        $composed = $this->compose($patient, $sessions);
        if (! $composed['lines']) {
            return response()->json(['message' => 'Nothing billable in the selection — every chosen session is unchargeable under the cancellation policy.'], 422);
        }

        $invoice = $this->builder->issue($patient, $composed, null, auth()->id());
        if ($attendanceEdits) {
            $invoice->recordEvent('attendance_edited', 'Attendance changed while invoicing: '.implode('; ', $attendanceEdits));
        }
        if ($settled->isNotEmpty()) {
            $invoice->recordEvent('rebilled_sessions', $settled->count().' session(s) were already on another invoice and were billed again by acknowledgement');
        }

        return response()->json([
            'message' => "{$invoice->invoice_number} saved as a draft — {$composed['session_count']} session(s), {$composed['bill_hours']} billable hour(s). Submit it to Finance for verification.",
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
            $invoice->recordEvent('payment', "Receipt {$p->receipt_number} — AED ".number_format((float) $p->amount, 2)." · {$p->method}".($p->reference ? " · ref {$p->reference}" : ''));

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
        $invoice->recordEvent('credit_note', 'AED '.number_format((float) $request->amount, 2).' — '.$request->reason);

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

            $clone = $invoice->replicate(['invoice_number', 'credit_amount', 'credit_reason', 'voided_at', 'void_reason', 'replaces_invoice_id', 'replaced_by_invoice_id', 'sent_to', 'sent_at', 'reminder_sent_at', 'reminders_count', 'amount_paid', 'payment_method', 'claim_reference', 'batch_reference', 'workflow_status', 'submitted_at', 'submitted_by', 'approved_at', 'approved_by', 'correction_note', 'finalized_at', 'finalized_by']);
            // The corrected document is a new draft - it goes back through
            // Finance verification like any other, and an insurance one keeps
            // the last-session-date rule.
            $issue = $invoice->isInsuranceRoute() && $invoice->period_to ? $invoice->period_to->copy() : now();
            $clone->invoice_number = DocumentNumbers::nextInvoice();
            $clone->issue_date = $issue->toDateString();
            $clone->due_date = $issue->copy()->addDays((int) config('billing.due_days', 30))->toDateString();
            $clone->status = $invoice->isInsuranceRoute() ? 'draft' : 'issued';
            $clone->workflow_status = 'draft';
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
                    'service_date' => $clone->issue_date,
                    'status' => 'draft',
                ]);
                if (! $clone->claim_reference) {
                    $clone->forceFill(['claim_reference' => $claim->reference])->save();
                }
            }

            $clone->recordEvent('created', "Draft reissued to replace voided {$invoice->invoice_number}");

            return $clone;
        });

        $invoice->syncPaymentColumns();
        $invoice->recordEvent('voided', $request->reason.($reissued ? " — reissued as {$reissued->invoice_number}" : ' — sessions released for re-invoicing'));

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

        if ($invoice->isVoided() || ! $invoice->isFinal()) {
            return response()->json(['message' => 'Only a Finance-approved, finalized invoice can be sent to a customer.'], 422);
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

            $kind = $request->kind ?? 'invoice';
            $this->logDispatch($invoice, 'email', $kind, $request->to);
            $message = $kind === 'reminder'
                ? "Reminder for {$invoice->invoice_number} sent to {$request->to}."
                : "{$invoice->invoice_number} emailed to {$request->to}.";

            return response()->json(['message' => $message, 'invoice' => InvoicePresenter::row($invoice->fresh())]);
        } finally {
            $lock->release();
        }
    }

    /**
     * Operations hands a draft (or a corrected one) to Finance.
     */
    public function submit(Invoice $invoice)
    {
        $this->assertCanInvoice();
        if ($blocked = $this->blockedTransition($invoice, ['draft', 'correction_required'], 'submitted for verification')) {
            return $blocked;
        }

        $invoice->forceFill(['workflow_status' => 'pending_verification', 'submitted_at' => now(), 'submitted_by' => auth()->id()])->save();
        $invoice->recordEvent('submitted', 'Submitted to Finance for verification');

        return response()->json(['message' => "{$invoice->invoice_number} submitted to Finance for verification.", 'invoice' => InvoicePresenter::row($invoice->fresh())]);
    }

    public function approve(Invoice $invoice)
    {
        $this->assertCanApprove();
        if ($blocked = $this->blockedTransition($invoice, ['pending_verification'], 'approved')) {
            return $blocked;
        }

        $invoice->forceFill(['workflow_status' => 'approved', 'approved_at' => now(), 'approved_by' => auth()->id(), 'correction_note' => null])->save();
        $invoice->recordEvent('approved', 'Verified and approved by Finance');

        return response()->json(['message' => "{$invoice->invoice_number} approved.", 'invoice' => InvoicePresenter::row($invoice->fresh())]);
    }

    /**
     * Finance found an error. The invoice itself is never edited - whoever
     * corrects it either voids and reissues, or resubmits once the underlying
     * record (a missing receipt, say) is fixed. The reason stays in the
     * invoice's history either way.
     */
    public function returnForCorrection(Request $request, Invoice $invoice)
    {
        $this->assertCanApprove();
        $validator = Validator::make($request->all(), ['reason' => ['required', 'string', 'max:500']]);
        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first(), 'errors' => $validator->errors()], 422);
        }
        if ($blocked = $this->blockedTransition($invoice, ['pending_verification'], 'returned for correction')) {
            return $blocked;
        }

        $invoice->forceFill(['workflow_status' => 'correction_required', 'correction_note' => $request->reason, 'approved_at' => null, 'approved_by' => null])->save();
        $invoice->recordEvent('correction_required', $request->reason);

        return response()->json(['message' => "{$invoice->invoice_number} returned for correction.", 'invoice' => InvoicePresenter::row($invoice->fresh())]);
    }

    /**
     * The approved invoice becomes the final PDF and takes its stamp: Company
     * Stamp always, PAID too for a settled family invoice, never PAID on one
     * going to an insurance portal.
     */
    public function finalize(Invoice $invoice)
    {
        $this->assertCanInvoice();
        if ($blocked = $this->blockedTransition($invoice, ['approved'], 'finalized')) {
            return $blocked;
        }
        if (! $invoice->meetsInsuranceDateRule()) {
            return response()->json(['message' => 'Insurance invoice date rule: the invoice date ('.$invoice->issue_date->format('d M Y').') must match the last session date ('.$invoice->period_to->format('d M Y').'). Void and reissue to correct it.'], 422);
        }

        $invoice->forceFill(['workflow_status' => 'finalized', 'finalized_at' => now(), 'finalized_by' => auth()->id()])->save();
        $stamps = implode(' + ', array_map(fn ($s) => $s === 'paid' ? 'PAID stamp' : 'Company Stamp', $invoice->stamps()));
        $invoice->recordEvent('finalized', "Final PDF issued — {$stamps} applied");

        return response()->json(['message' => "{$invoice->invoice_number} finalized — {$stamps} applied.", 'invoice' => InvoicePresenter::row($invoice->fresh())]);
    }

    /**
     * Log an invoice sent outside the CRM - WhatsApp is sent by hand, so the
     * record of it is made here.
     */
    public function storeDispatch(Request $request, Invoice $invoice)
    {
        $this->assertCanInvoice();
        $validator = Validator::make($request->all(), [
            'channel' => ['required', 'in:'.implode(',', InvoiceDispatch::CHANNELS)],
            'sent_to' => ['required', 'string', 'max:255'],
            'sent_at' => ['nullable', 'date', 'before_or_equal:now'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first(), 'errors' => $validator->errors()], 422);
        }
        if ($invoice->isVoided() || ! $invoice->isFinal()) {
            return response()->json(['message' => 'Only a Finance-approved, finalized invoice can be sent to a customer.'], 422);
        }

        $this->logDispatch($invoice, $request->channel, 'invoice', $request->sent_to, $request->sent_at, $request->note);

        return response()->json(['message' => "{$invoice->invoice_number} dispatch logged.", 'invoice' => InvoicePresenter::row($invoice->fresh())], 201);
    }

    public function confirmReceipt(Invoice $invoice, InvoiceDispatch $dispatch)
    {
        $this->assertCanInvoice();
        abort_unless($dispatch->invoice_id === $invoice->id, 404);

        if (! $dispatch->receipt_confirmed_at) {
            $dispatch->forceFill(['receipt_confirmed_at' => now(), 'receipt_confirmed_by' => auth()->id()])->save();
            $invoice->recordEvent('receipt_confirmed', "Customer confirmed receipt ({$dispatch->channelLabel()} to {$dispatch->sent_to})");
        }

        return response()->json(['message' => 'Receipt confirmation recorded.', 'invoice' => InvoicePresenter::row($invoice->fresh())]);
    }

    /**
     * The invoice's own audit trail, oldest first.
     */
    public function history(Invoice $invoice)
    {
        return response()->json([
            'number' => $invoice->invoice_number,
            'events' => $invoice->events()->with('user')->get()->map(fn ($e) => [
                'event' => $e->event,
                'label' => ucfirst(str_replace('_', ' ', $e->event)),
                'note' => $e->note,
                'by' => $e->user ? trim($e->user->first_name.' '.$e->user->last_name) : 'System',
                'at' => $e->created_at->format('d M Y H:i'),
            ])->values(),
        ]);
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
     * receipt in date order, plus the sessions those charges came from.
     * Derived on demand, never stored.
     */
    public function statement(Patient $patient)
    {
        return view('billing.statement', $this->statementData($patient));
    }

    /**
     * The same statement as a real PDF. The browser's own "Save as PDF" adds
     * page margins and drops the full-bleed banner; dompdf with
     * `@page { margin: 0 }` keeps the design edge to edge (billing.statement_pdf
     * is table-based for the same reason billing.print_pdf is).
     */
    public function statementPdf(Patient $patient)
    {
        $data = $this->statementData($patient);
        $name = str($data['profile']['child'] ?: 'patient')->slug();

        return Pdf::loadView('billing.statement_pdf', $data)
            ->setPaper('a4')
            ->download("statement-{$name}-".now()->format('Y-m-d').'.pdf');
    }

    /**
     * @return array<string, mixed>
     */
    protected function statementData(Patient $patient): array
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

        // The sessions behind those charges - the same delivered, priced
        // session ledger the invoice picker reads, oldest first so it runs in
        // the same direction as the balance above it.
        $sessions = $this->ledger->forPatient($patient)->sortBy('session_date')->values();

        return [
            'patient' => $patient,
            'profile' => $profile,
            'rows' => $rows,
            'charged' => $rows->sum('charge'),
            'credited' => $rows->sum('credit'),
            'balance' => $balance,
            'oldest' => $oldest,
            'sessions' => $sessions,
            'sessionHours' => $sessions->sum('bill_hours'),
            'sessionCharged' => $sessions->sum('gross'),
            'clinic' => config('clinic'),
            'asOf' => now(),
        ];
    }

    // ------------------------------------------------------------------

    protected function assertCanInvoice(): void
    {
        abort_unless(auth()->user()->canDo('create_invoice'), 403, 'Invoices are raised by Finance.');
    }

    protected function assertCanApprove(): void
    {
        abort_unless(auth()->user()->canDo('approve_invoice'), 403, 'Invoices are verified and approved by Finance.');
    }

    /**
     * A 422 if the invoice isn't in one of the states this step starts from.
     */
    protected function blockedTransition(Invoice $invoice, array $from, string $verb): ?\Illuminate\Http\JsonResponse
    {
        if ($invoice->isVoided()) {
            return response()->json(['message' => 'A voided invoice can’t be '.$verb.'.'], 422);
        }
        if (! in_array($invoice->workflow_status, $from, true)) {
            return response()->json(['message' => "{$invoice->invoice_number} is {$invoice->workflowLabel()} — it can’t be {$verb} from there."], 422);
        }

        return null;
    }

    /**
     * One dispatch record per send, plus the legacy sent_/reminder_ columns
     * older screens still read.
     */
    protected function logDispatch(Invoice $invoice, string $channel, string $kind, string $to, ?string $at = null, ?string $note = null): InvoiceDispatch
    {
        $sentAt = $at ? \Carbon\Carbon::parse($at) : now();
        $dispatch = $invoice->dispatches()->create([
            'channel' => $channel,
            'kind' => $kind,
            'sent_to' => $to,
            'sent_at' => $sentAt,
            'sent_by' => auth()->id(),
            'note' => $note,
        ]);

        if ($kind === 'reminder') {
            $invoice->forceFill(['reminder_sent_at' => $sentAt, 'reminders_count' => (int) $invoice->reminders_count + 1])->save();
        } else {
            $invoice->forceFill(['sent_to' => $to, 'sent_at' => $sentAt, 'workflow_status' => 'dispatched'])->save();
        }
        $invoice->recordEvent($kind === 'reminder' ? 'reminder_sent' : 'dispatched', "{$dispatch->channelLabel()} to {$to}".($note ? " — {$note}" : ''));

        return $dispatch;
    }

    /**
     * @return array{0: ?Patient, 1: \Illuminate\Support\Collection, 2: ?array}
     */
    protected function resolveSelection(Request $request): array
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

    protected function compose(Patient $patient, $sessions): array
    {
        $profile = $this->ledger->profile($patient);
        $rows = $this->ledger->rows($patient, $sessions, $profile);

        return $this->builder->compose($patient, $rows, $profile) + ['rows' => $rows->values()];
    }

    /**
     * Cross-check against the agreed quotation: flagged for review, never
     * silently adjusted. Null when the selection sits inside what was quoted.
     */
    protected function quotationNotice(Patient $patient, array $composed): ?string
    {
        $cleared = $patient->quotations()->where('status', 'cleared')->get();
        if ($cleared->isEmpty()) {
            return 'No payment-confirmed quotation is on file for this client — sessions are being billed without agreed terms to check them against.';
        }

        $quoted = (float) $cleared->where('billing_unit', 'hour')->sum('quantity');
        if ($quoted <= 0) {
            return null;
        }
        $already = (float) \App\Models\InvoiceLineItem::whereHas('invoice', fn ($q) => $q->where('patient_id', $patient->id)->whereNull('voided_at'))->sum('qty');
        $after = $already + (float) $composed['bill_hours'];

        return $after > $quoted
            ? 'This invoice takes the client to '.rtrim(rtrim(number_format($after, 1), '0'), '.').' billed hours against '.rtrim(rtrim(number_format($quoted, 1), '0'), '.').' quoted — '.rtrim(rtrim(number_format($after - $quoted, 1), '0'), '.').' h beyond the agreed quotation.'
            : null;
    }
}
