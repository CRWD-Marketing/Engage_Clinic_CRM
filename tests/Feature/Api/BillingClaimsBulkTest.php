<?php

namespace Tests\Feature\Api;

use App\Models\CalendarSession;
use App\Models\InsuranceClaim;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\PreAuthorization;

class BillingClaimsBulkTest extends ApiTestCase
{
    public function test_the_overview_carries_claims_pre_authorizations_and_pickers(): void
    {
        $this->actingAsEmail('kavitha@engagebehavior.com');

        $this->getJson($this->api('billing'))->assertOk()->assertJsonStructure([
            'claims' => [['id', 'reference', 'patient', 'insurer', 'amount', 'period', 'status', 'status_label', 'age', 'open', 'notes', 'invoice']],
            'claim_aging' => [['label', 'min', 'max', 'count', 'amount']],
            'claim_statuses' => ['draft', 'submitted', 'pending_info', 'rejected', 'settled'],
            'rejected_alert',
            'pre_auths' => [[
                'id', 'reference', 'patient_id', 'patient', 'payer', 'service', 'hours', 'from', 'to', 'from_iso', 'to_iso', 'submitted',
                'status', 'status_label', 'payer_reference', 'denial_reason', 'justification', 'resubmitted_from',
            ]],
            'payers', 'services',
            'bulk_defaults' => ['from', 'to'],
        ]);
    }

    public function test_settling_a_claim_records_the_remittance_and_unsettling_reverses_it(): void
    {
        $this->actingAsEmail('kavitha@engagebehavior.com');
        $claim = InsuranceClaim::where('status', 'submitted')->whereNotNull('invoice_id')->firstOrFail();
        $paid = (float) $claim->invoice->payments()->sum('amount');

        $this->patchJson($this->api("billing/claims/{$claim->id}"), ['status' => 'lost'])->assertStatus(422);

        $this->patchJson($this->api("billing/claims/{$claim->id}"), ['status' => 'settled'])->assertOk()
            ->assertJsonPath('message', "{$claim->reference} marked Settled.")
            ->assertJsonPath('claim.status', 'settled')
            ->assertJsonPath('claim.open', false)
            ->assertJsonStructure(['invoice' => ['id', 'number', 'paid', 'balance', 'receipts']]);
        $this->assertEqualsWithDelta($paid + (float) $claim->amount, (float) $claim->invoice->payments()->sum('amount'), 0.01);

        $this->patchJson($this->api("billing/claims/{$claim->id}"), ['status' => 'rejected', 'notes' => 'Missing treatment plan'])->assertOk()
            ->assertJsonPath('claim.status', 'rejected')
            ->assertJsonPath('claim.notes', 'Missing treatment plan')
            ->assertJsonPath('invoice.claim_status', 'rejected');
        $this->assertEqualsWithDelta($paid, (float) $claim->invoice->payments()->sum('amount'), 0.01);
    }

    public function test_requesting_and_deciding_a_pre_authorization(): void
    {
        $this->actingAsEmail('kavitha@engagebehavior.com');
        $patient = Patient::firstOrFail();
        $body = ['patient_id' => $patient->id, 'payer' => 'Daman', 'service' => 'ABA therapy session', 'hours' => 40, 'valid_from' => '2026-10-01', 'valid_to' => '2027-03-31', 'justification' => 'Progress review attached'];

        $this->postJson($this->api('billing/pre-authorizations'), ['valid_to' => '2026-09-01'] + $body)->assertStatus(422)->assertJsonValidationErrors('valid_to');

        $id = $this->postJson($this->api('billing/pre-authorizations'), $body)->assertCreated()
            ->assertJsonPath('preauth.status', 'requested')
            ->assertJsonPath('preauth.patient_id', $patient->id)
            ->assertJsonPath('preauth.from_iso', '2026-10-01')
            ->json('preauth.id');
        $reference = PreAuthorization::findOrFail($id)->reference;
        $this->assertStringStartsWith('PA-', $reference);

        $this->patchJson($this->api("billing/pre-authorizations/{$id}"), ['status' => 'denied', 'denial_reason' => 'Plan out of date'])->assertOk()
            ->assertJsonPath('message', "{$reference} marked Denied.")
            ->assertJsonPath('preauth.denial_reason', 'Plan out of date');

        // A resubmission is a new request that points back at the denied one.
        $this->postJson($this->api('billing/pre-authorizations'), $body + ['resubmitted_from_id' => $id])->assertCreated()
            ->assertJsonPath('preauth.resubmitted_from', $id);
    }

    public function test_the_bulk_run_previews_unbilled_sessions_and_issues_one_invoice_per_family(): void
    {
        $this->actingAsEmail('kavitha@engagebehavior.com');
        // Put two families' completed sessions back to unbilled, the way voiding without reissue does.
        $patients = Patient::all()->filter(fn ($p) => $p->calendarSessions()->where('status', 'completed')->whereNotIn('activity_type', CalendarSession::NON_THERAPY_TYPES)->exists())->take(2)->values();
        foreach ($patients as $p) {
            $p->calendarSessions()->where('status', 'completed')->update(['invoice_id' => null]);
        }
        $period = ['from' => CalendarSession::min('session_date'), 'to' => now()->toDateString()];

        $groups = $this->getJson($this->api('billing/bulk-run').'?'.http_build_query($period))->assertOk()
            ->assertJsonStructure(['groups' => [['patient_id', 'patient', 'parent', 'payer', 'sessions', 'hours', 'net', 'vat', 'total', 'insurer_share', 'family_share', 'adjusted', 'rows']]])
            ->json('groups');
        $this->assertContains($patients[0]->id, array_column($groups, 'patient_id'));
        $group = collect($groups)->firstWhere('patient_id', $patients[0]->id);

        $this->postJson($this->api('billing/bulk-run'), $period)->assertStatus(422);
        $before = Invoice::count();
        $run = $this->postJson($this->api('billing/bulk-run'), $period + ['patient_ids' => [$patients[0]->id, $patients[1]->id]])->assertCreated()
            ->assertJsonCount(2, 'invoices')
            ->assertJsonStructure(['message', 'run', 'invoices' => [['id', 'number', 'total']]]);
        $this->assertSame($before + 2, Invoice::count());
        $this->assertStringStartsWith('RUN-', $run->json('run'));
        $this->assertEqualsWithDelta($group['total'], collect($run->json('invoices'))->firstWhere('patient_id', $patients[0]->id)['total'], 0.01);

        // Those sessions are billed now, so the same families have nothing left in the period.
        $again = array_column($this->getJson($this->api('billing/bulk-run').'?'.http_build_query($period))->json('groups'), 'patient_id');
        $this->assertNotContains($patients[0]->id, $again);
    }

    public function test_the_invoice_and_statement_download_as_pdf(): void
    {
        $this->actingAsEmail('kavitha@engagebehavior.com');
        $invoice = Invoice::firstOrFail();

        $pdf = $this->get($this->api("billing/invoices/{$invoice->id}/pdf"))->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $pdf->getContent());
        $this->assertStringContainsString($invoice->invoice_number.'.pdf', $pdf->headers->get('content-disposition'));

        $statement = $this->get($this->api("billing/patients/{$invoice->patient_id}/statement/pdf"))->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $statement->getContent());
    }

    public function test_claims_pre_authorizations_and_the_bulk_run_need_the_invoice_action(): void
    {
        $claim = InsuranceClaim::firstOrFail();
        $this->actingAsEmail('indira@engagebehavior.com');

        $this->patchJson($this->api("billing/claims/{$claim->id}"), ['status' => 'settled'])->assertForbidden();
        $this->postJson($this->api('billing/pre-authorizations'), [])->assertForbidden();
        $this->postJson($this->api('billing/bulk-run'), [])->assertForbidden();
        $this->get($this->api('billing/invoices/'.Invoice::firstOrFail()->id.'/pdf'))->assertForbidden();
    }
}
