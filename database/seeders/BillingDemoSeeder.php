<?php

namespace Database\Seeders;

use App\Models\Patient;
use App\Models\PreAuthorization;
use App\Models\User;
use App\Services\Billing\DocumentNumbers;
use App\Services\Billing\InvoiceBuilder;
use App\Services\Billing\SessionLedger;
use Illuminate\Database\Seeder;

class BillingDemoSeeder extends Seeder
{
    /**
     * Issues real invoices - via the same SessionLedger + InvoiceBuilder the
     * "+ New invoice" button uses, not a hand-rolled shape - for every
     * patient's uninvoiced completed sessions, then records a payment on a
     * realistic subset and seeds a couple of pre-authorization requests.
     * Only ever touches sessions with no invoice_id yet, so it's safe to
     * re-run and never duplicates or re-bills anything already on an
     * invoice (including ones raised by hand through the real UI).
     */
    public function run(): void
    {
        $ledger = app(SessionLedger::class);
        $builder = app(InvoiceBuilder::class);
        $biller = User::where('email', 'kavitha@engagebehavior.com')->first()
            ?? User::whereIn('role', ['FINANCE_STAFF', 'FULL_ADMIN'])->first();

        $invoiceIndex = 0;

        // A plain closure, not fn() => ... - an arrow function captures
        // $invoiceIndex by value at definition time, so the by-reference
        // parameter below would bind to a copy that resets on every call
        // instead of actually incrementing across patients.
        Patient::with(['lead', 'authorizations'])->get()->each(
            function (Patient $patient) use ($ledger, $builder, $biller, &$invoiceIndex) {
                $this->invoicePatient($patient, $ledger, $builder, $biller, $invoiceIndex);
            }
        );

        $this->seedPreAuthorizations($biller);
    }

    private function invoicePatient(Patient $patient, SessionLedger $ledger, InvoiceBuilder $builder, ?User $biller, int &$invoiceIndex): void
    {
        $sessions = $patient->calendarSessions()
            ->with(['therapist', 'supervisor', 'invoice'])
            ->whereNull('invoice_id')
            ->where('status', 'completed')
            ->orderBy('session_date')
            ->get();

        if ($sessions->isEmpty()) {
            return;
        }

        // One invoice per calendar month of backlog, same as a biller
        // working through unbilled sessions a month at a time.
        $sessions->groupBy(fn ($s) => $s->session_date->format('Y-m'))->each(
            function ($monthSessions) use ($patient, $ledger, $builder, $biller, &$invoiceIndex) {
                $rows = $ledger->rows($patient, $monthSessions);
                $composed = $builder->compose($patient, $rows);

                if (! $composed['lines']) {
                    return;
                }

                $invoice = $builder->issue($patient, $composed, null, $biller?->id);

                // issue()/nextInvoice() always stamps "today" - the seeded
                // calendar backlog only spans a few weeks, so deriving the
                // issue date from the billed period can never actually reach
                // a genuinely overdue due date. Cycle through a fixed spread
                // of aging positions instead (same deliberate-spread approach
                // LeadSeeder uses for lead created_at), so Aging & statements
                // gets real variety across every bucket instead of everything
                // landing in "current."
                $issueDate = $this->agingIssueDate($invoiceIndex++);
                $invoice->forceFill(['issue_date' => $issueDate, 'due_date' => $issueDate->copy()->addDays(30)])->save();
                $invoice->claims()->update(['submitted_on' => $issueDate]);

                // Roughly half get a payment on file, so Aging & statements
                // shows a mixed picture instead of everything sitting unpaid.
                if (random_int(0, 1) === 0) {
                    return;
                }

                $familyOwed = round((float) $invoice->patient_responsibility, 2);
                if ($familyOwed <= 0) {
                    return;
                }

                // 2 in 3 paid in full, 1 in 3 partial - matches the aging
                // buckets actually having a spread instead of clustering at
                // fully-open or fully-settled.
                $amount = random_int(0, 2) > 0 ? $familyOwed : round($familyOwed * (random_int(30, 70) / 100), 2);
                $method = $invoice->payer === 'Self-pay'
                    ? ['Card', 'Bank transfer', 'Cash'][array_rand(['Card', 'Bank transfer', 'Cash'])]
                    : 'Bank transfer';

                $invoice->payments()->create([
                    'receipt_number' => DocumentNumbers::nextReceipt(),
                    'amount' => $amount,
                    'method' => $method,
                    'received_on' => $invoice->issue_date->copy()->addDays(random_int(1, 20))->min(now()),
                    'reference' => null,
                    'recorded_by' => $biller?->id,
                ]);
                $invoice->unsetRelation('payments')->syncPaymentColumns();
            }
        );
    }

    /**
     * Issue date for the Nth invoice, cycling through one target per aging
     * bucket (config('billing.aging_buckets')): not-yet-due, 1-30, 31-60,
     * 61-90, 90+ days overdue. Picked so issue_date + the 30-day due term
     * lands the invoice at that exact overdue age today.
     */
    private function agingIssueDate(int $index): \Illuminate\Support\Carbon
    {
        $targetOverdueDays = [-15, 15, 45, 75, 120];

        return now()->subDays(30 + $targetOverdueDays[$index % count($targetOverdueDays)]);
    }

    /**
     * A handful of pre-authorization requests against patients who actually
     * hold an insurance authorization (pre-auth is meaningless for a
     * self-pay family) - one of each outcome so the register isn't just
     * "everything pending."
     */
    private function seedPreAuthorizations(?User $biller): void
    {
        $patients = Patient::with(['lead', 'authorizations'])
            ->whereHas('authorizations', fn ($q) => $q->where('payer_name', 'not like', '%self%'))
            ->take(3)
            ->get();

        $statuses = ['approved', 'requested', 'denied'];

        foreach ($patients as $i => $patient) {
            $auth = $patient->authorizations->first(fn ($a) => stripos((string) $a->payer_name, 'self') === false);
            if (! $auth) {
                continue;
            }

            $status = $statuses[$i % count($statuses)];

            PreAuthorization::create([
                'reference' => DocumentNumbers::nextPreAuth(),
                'patient_id' => $patient->id,
                'payer' => $auth->payer_name,
                'service' => 'ABA therapy',
                'hours' => [40, 60, 80][$i % 3],
                'valid_from' => now()->startOfMonth(),
                'valid_to' => now()->addMonths(6)->endOfMonth(),
                'status' => $status,
                'payer_reference' => $status !== 'requested' ? strtoupper(substr($auth->payer_name, 0, 2)).'-'.random_int(100000, 999999) : null,
                'justification' => 'Continuation of ABA programme per updated treatment plan.',
                'denial_reason' => $status === 'denied' ? 'Annual hours cap reached for this policy period.' : null,
                'submitted_on' => now()->subDays(random_int(3, 15)),
                'created_by' => $biller?->id,
            ]);
        }
    }
}
