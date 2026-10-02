<?php

namespace Tests\Feature\Api;

use App\Models\Invoice;
use Illuminate\Support\Facades\Mail;

class BillingTest extends ApiTestCase
{
    private const INVOICE_KEYS = [
        'id', 'number', 'patient_id', 'patient', 'parent', 'parent_email', 'payer', 'period', 'issued', 'issued_label', 'due', 'due_label',
        'net', 'vat', 'total', 'insurer_share', 'family_share', 'paid', 'credit', 'credit_reason', 'balance', 'status', 'status_label',
        'claim_status', 'claim_reference', 'voided', 'voided_on', 'void_reason', 'replaces', 'replaced_by', 'sent_to', 'sent_at',
        'reminder_sent_at', 'reminders_count', 'batch', 'days_past_due', 'age_label', 'receipts', 'print_url',
    ];

    private function openInvoice(): Invoice
    {
        return Invoice::whereNull('voided_at')->get()->first(fn (Invoice $i) => $i->balance() > 100) ?? $this->fail('No open invoice in the seed.');
    }

    public function test_the_overview_has_tiles_invoices_and_pickers(): void
    {
        $this->actingAsEmail('kavitha@engagebehavior.com');

        $response = $this->getJson($this->api('billing'))->assertOk()
            ->assertJsonStructure([
                'can_invoice', 'month_label', 'payer_summary',
                'tiles' => ['invoiced_mtd', 'collected_mtd', 'outstanding_claims', 'avg_claim_cycle'],
                'invoices' => [self::INVOICE_KEYS],
                'revenue_by_payer', 'claims', 'claim_aging', 'claim_statuses', 'pre_auths',
                'aging' => ['total_outstanding', 'open_count', 'past_due', 'past_due_count', 'oldest_days', 'rows', 'buckets', 'by_payer', 'families'],
                'patients', 'payers', 'services', 'methods', 'cancel_policy', 'bulk_defaults', 'clinic_name',
            ])
            ->assertJsonPath('can_invoice', true);
        $this->assertEqualsCanonicalizing(self::INVOICE_KEYS, array_keys($response->json('invoices.0')));

        // No billing module: no access.
        $this->actingAsEmail('coordinator@engagebehavior.com');
        $this->getJson($this->api('billing'))->assertForbidden();
    }

    public function test_recording_a_payment_and_a_credit_note(): void
    {
        $this->actingAsEmail('kavitha@engagebehavior.com');
        $invoice = $this->openInvoice();
        $base = "billing/invoices/{$invoice->id}";
        $balance = $invoice->balance();

        $this->getJson($this->api($base))->assertOk()->assertJsonPath('number', $invoice->invoice_number);

        $this->postJson($this->api("$base/payments"), ['amount' => 0, 'method' => 'Cheque'])->assertStatus(422)->assertJsonStructure(['message', 'errors']);
        $paid = $this->postJson($this->api("$base/payments"), ['amount' => 50, 'method' => 'Card', 'received_on' => now()->toDateString(), 'reference' => 'POS-1'])
            ->assertCreated()
            ->assertJsonPath('invoice.balance', round($balance - 50, 2));
        $this->assertStringContainsString('recorded — AED 50.00.', $paid->json('message'));
        $this->assertSame('Card', collect($paid->json('invoice.receipts'))->last()['method']);

        $this->postJson($this->api("$base/credit"), ['amount' => 20])->assertStatus(422);
        $this->postJson($this->api("$base/credit"), ['amount' => 20, 'reason' => 'Goodwill'])->assertOk()
            ->assertJsonPath('message', 'Credit note of AED 20.00 issued.')
            ->assertJsonPath('invoice.balance', round($balance - 70, 2));
    }

    public function test_void_reissues_under_a_new_number_and_email_stamps_the_invoice(): void
    {
        Mail::fake();
        $this->actingAsEmail('kavitha@engagebehavior.com');
        $invoice = $this->openInvoice();
        $base = "billing/invoices/{$invoice->id}";

        $this->postJson($this->api("$base/send"), ['to' => 'not-an-email', 'subject' => 's', 'message' => 'm'])->assertStatus(422);
        $this->postJson($this->api("$base/send"), ['to' => 'parent@example.com', 'subject' => 'Invoice', 'message' => 'Attached.', 'kind' => 'reminder'])
            ->assertOk()
            ->assertJsonPath('invoice.reminders_count', $invoice->reminders_count + 1);

        $this->postJson($this->api("$base/void"), [])->assertStatus(422);
        $voided = $this->postJson($this->api("$base/void"), ['reason' => 'Wrong period', 'reissue' => true])->assertOk()
            ->assertJsonPath('invoice.voided', true)
            ->assertJsonPath('invoice.status', 'voided')
            ->assertJsonPath('reissued.replaces', $invoice->invoice_number);
        $this->assertNotSame($invoice->invoice_number, $voided->json('reissued.number'));

        $this->postJson($this->api("$base/payments"), ['amount' => 10, 'method' => 'Cash', 'received_on' => now()->toDateString()])
            ->assertStatus(422)->assertJsonPath('message', 'A voided invoice can’t take payments.');
    }

    public function test_only_roles_with_the_invoice_action_can_change_money_documents(): void
    {
        // HR has neither the billing module nor the action.
        $this->actingAsEmail('hr@engagebehavior.com');
        $invoice = $this->openInvoice();
        $this->postJson($this->api("billing/invoices/{$invoice->id}/payments"), ['amount' => 10, 'method' => 'Cash', 'received_on' => now()->toDateString()])
            ->assertForbidden();
    }
}
