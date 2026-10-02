<?php

namespace Tests\Feature\Api;

use App\Models\CalendarSession;
use App\Models\Invoice;
use App\Models\Patient;

class BillingNewInvoiceTest extends ApiTestCase
{
    private const LEDGER_ROW = [
        'id', 'session_date', 'date_label', 'start_time', 'end_time', 'duration_minutes', 'hours', 'bill_hours', 'attendance', 'attendance_label',
        'charge_rule', 'factor', 'notice_hours', 'activity_type', 'service_label', 'therapist_id', 'therapist_name', 'trainee_note', 'setting', 'rate',
        'net', 'vat', 'gross', 'payer', 'coverage_pct', 'authorization_id', 'covered_bill_hours', 'excess_bill_hours', 'insufficient_authorization',
        'insufficient_message', 'insurer_amount', 'family_amount', 'invoiced', 'invoice_number', 'billing_status',
    ];

    /**
     * A client with two delivered sessions waiting to be billed. The seed
     * invoices every completed session, so two are put back to unbilled the
     * way voiding an invoice without reissue does.
     */
    private function patientWithUnbilledSessions(): array
    {
        foreach (Patient::all() as $patient) {
            $ids = $patient->calendarSessions()->where('status', 'completed')
                ->whereNotIn('activity_type', CalendarSession::NON_THERAPY_TYPES)->limit(2)->pluck('id')->all();
            if (count($ids) === 2) {
                CalendarSession::whereIn('id', $ids)->update(['invoice_id' => null]);

                return [$patient, $ids];
            }
        }
        $this->fail('No client with completed sessions in the seed.');
    }

    public function test_the_ledger_lists_a_clients_delivered_sessions_priced(): void
    {
        $this->actingAsEmail('kavitha@engagebehavior.com');
        [$patient] = $this->patientWithUnbilledSessions();

        $response = $this->getJson($this->api("billing/patients/{$patient->id}/ledger"))->assertOk()
            ->assertJsonStructure([
                'patient' => ['id', 'name', 'parent', 'email', 'phone', 'payer', 'rate', 'vat_rate', 'setting', 'prepaid', 'package', 'insurers'],
                'rows' => [self::LEDGER_ROW],
                'prepaid_left', 'prepaid_used',
            ]);
        $this->assertEqualsCanonicalizing(self::LEDGER_ROW, array_keys(array_diff_key($response->json('rows.0'), ['prepaid_left' => 1])));
    }

    public function test_preview_then_issue_and_the_double_billing_guard(): void
    {
        $this->actingAsEmail('kavitha@engagebehavior.com');
        [$patient, $ids] = $this->patientWithUnbilledSessions();
        $body = ['patient_id' => $patient->id, 'session_ids' => $ids];

        $this->postJson($this->api('billing/invoices/preview'), ['patient_id' => $patient->id])->assertStatus(422);
        $before = Invoice::count();
        $preview = $this->postJson($this->api('billing/invoices/preview'), $body)->assertOk()
            ->assertJsonStructure([
                'lines' => [['line_no', 'calendar_session_id', 'description', 'note', 'from_label', 'to_label', 'payer', 'coverage_percent', 'amount', 'vat_amount', 'total', 'insurer_amount', 'family_amount']],
                'warnings', 'session_count', 'bill_hours', 'net', 'vat', 'total', 'insurer_share', 'family_share', 'amount_due', 'splits',
                'payer', 'period_label', 'bill_to', 'child', 'invoice_number', 'issue_date', 'due_date', 'settled_count',
            ])
            ->assertJsonPath('session_count', 2)
            ->assertJsonPath('settled_count', 0);
        $this->assertSame($before, Invoice::count(), 'A preview must not save anything.');

        $issued = $this->postJson($this->api('billing/invoices'), $body)->assertCreated()
            ->assertJsonPath('invoice.number', $preview->json('invoice_number'))
            ->assertJsonPath('invoice.total', $preview->json('total'));
        $this->assertSame($issued->json('invoice.id'), CalendarSession::find($ids[0])->invoice_id);

        // The same sessions again: refused until acknowledged.
        $this->postJson($this->api('billing/invoices'), $body)->assertStatus(409)->assertJsonPath('settled_count', 2);
        $this->postJson($this->api('billing/invoices'), $body + ['acknowledge_settled' => true])->assertCreated();
    }

    public function test_an_attendance_correction_changes_the_price_and_is_saved_on_issue(): void
    {
        $this->actingAsEmail('kavitha@engagebehavior.com');
        [$patient, $ids] = $this->patientWithUnbilledSessions();
        $full = $this->postJson($this->api('billing/invoices/preview'), ['patient_id' => $patient->id, 'session_ids' => [$ids[0]]])->json('total');

        $body = ['patient_id' => $patient->id, 'session_ids' => [$ids[0]], 'attendance' => [$ids[0] => ['state' => 'cancelled_late', 'notice_hours' => 3]]];
        $late = $this->postJson($this->api('billing/invoices/preview'), $body)->assertOk()->json('total');
        $this->assertEqualsWithDelta($full / 2, $late, 0.01);
        $this->assertSame('completed', CalendarSession::find($ids[0])->status, 'A preview must not change the calendar.');

        $this->postJson($this->api('billing/invoices'), $body)->assertCreated();
        $session = CalendarSession::find($ids[0]);
        $this->assertSame(['cancelled', 'family'], [$session->status, $session->cancel_reason]);

        // A clinic cancellation is free, so there is nothing to bill.
        $free = ['patient_id' => $patient->id, 'session_ids' => [$ids[1]], 'attendance' => [$ids[1] => ['state' => 'cancelled_clinic']]];
        $this->postJson($this->api('billing/invoices'), $free)->assertStatus(422);
    }

    public function test_prepaid_top_up_and_the_family_statement(): void
    {
        $this->actingAsEmail('kavitha@engagebehavior.com');
        $patient = Patient::whereHas('invoices')->firstOrFail();

        $this->postJson($this->api("billing/patients/{$patient->id}/top-up"), ['hours' => 0])->assertStatus(422);
        $top = $this->postJson($this->api("billing/patients/{$patient->id}/top-up"), ['hours' => 10])->assertOk()
            ->assertJsonPath('message', '10 prepaid hour(s) added.');
        $this->assertGreaterThanOrEqual(10, $top->json('prepaid_total'));

        $statement = $this->getJson($this->api("billing/patients/{$patient->id}/statement"))->assertOk()
            ->assertJsonStructure([
                'patient_id', 'child', 'bill_to', 'payer', 'as_of', 'charged', 'credited', 'balance', 'oldest_open',
                'rows' => [['date_label', 'ref', 'desc', 'charge', 'credit', 'balance']],
                'sessions', 'session_hours', 'session_charged',
            ]);
        $rows = $statement->json('rows');
        $this->assertEqualsWithDelta($statement->json('balance'), end($rows)['balance'], 0.01);
        $this->assertEqualsWithDelta($statement->json('charged') - $statement->json('credited'), $statement->json('balance'), 0.01);
    }

    public function test_a_role_without_the_invoice_action_cannot_raise_an_invoice(): void
    {
        [$patient, $ids] = $this->patientWithUnbilledSessions();
        $this->actingAsEmail('indira@engagebehavior.com');
        $this->postJson($this->api('billing/invoices'), ['patient_id' => $patient->id, 'session_ids' => $ids])->assertForbidden();
    }
}
