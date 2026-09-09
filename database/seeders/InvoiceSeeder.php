<?php

namespace Database\Seeders;

use App\Models\Invoice;
use App\Models\InvoiceLineItem;
use App\Models\Patient;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Seeder;

class InvoiceSeeder extends Seeder
{
    /**
     * Books the last 3 monthly billing periods for every enrolled patient,
     * each with 1-2 line items drawn from the real service catalog.
     */
    public function run(): void
    {
        $services = Service::all();
        $therapists = User::where('role', 'THERAPIST')->pluck('id')->all();
        $biller = User::where('email', 'kavitha@engagebehavior.com')->first() ?? User::whereIn('role', ['FINANCE_STAFF', 'FULL_ADMIN'])->first();

        if ($services->isEmpty()) {
            return;
        }

        $sequence = 1;

        Patient::with(['lead', 'authorizations'])->get()->each(function (Patient $patient) use ($services, $therapists, $biller, &$sequence) {
            $payer = $patient->primaryAuthorization()->payer_name ?? 'Self-pay';
            $coveragePercent = $payer === 'Self-pay' ? 0 : [80, 85, 90][array_rand([80, 85, 90])];

            for ($monthsAgo = 2; $monthsAgo >= 0; $monthsAgo--) {
                $period = now()->subMonths($monthsAgo)->startOfMonth();
                $issueDate = $period->copy()->addDays(random_int(1, 5));

                $status = match (true) {
                    $monthsAgo === 2 => 'paid',
                    $monthsAgo === 1 => 'submitted',
                    default => ['draft', 'pending_info'][array_rand(['draft', 'pending_info'])],
                };

                $lineItemSpecs = collect($services)->shuffle()->take(random_int(1, 2));
                $subtotal = 0;

                $invoice = Invoice::create([
                    'invoice_number' => sprintf('INV-2026-%04d', $sequence++),
                    'patient_id' => $patient->id,
                    'bill_to' => $patient->lead->parent_guardian_name,
                    'payer' => $payer,
                    'coverage_percent' => $coveragePercent,
                    'period' => $period,
                    'issue_date' => $issueDate,
                    'due_date' => $issueDate->copy()->addDays(30),
                    'claim_reference' => $payer !== 'Self-pay' ? 'CLM-'.random_int(1000, 9999) : null,
                    'status' => $status,
                    'subtotal' => 0,
                    'insurance_coverage_amount' => 0,
                    'patient_responsibility' => 0,
                    'created_by' => $biller?->id,
                ]);

                foreach ($lineItemSpecs as $service) {
                    $sessions = random_int(4, 12);
                    $rate = $service->default_rate;
                    $amount = $rate * $sessions;
                    $subtotal += $amount;

                    InvoiceLineItem::create([
                        'invoice_id' => $invoice->id,
                        'service_id' => $service->id,
                        'therapist_id' => $therapists ? $therapists[array_rand($therapists)] : null,
                        'description' => $service->name,
                        'cpt_code' => $service->cpt_code,
                        'sessions' => $sessions,
                        'rate' => $rate,
                        'amount' => $amount,
                    ]);
                }

                $coverageAmount = round($subtotal * $coveragePercent / 100, 2);

                // Payment collection is independent of the claim's own status:
                // fully settled invoices are always paid in full; a submitted
                // claim is sometimes already partly collected from the family
                // while the insurance portion is still pending.
                [$amountPaid, $paymentMethod] = match (true) {
                    $status === 'paid' => [$subtotal, $payer !== 'Self-pay' ? 'Insurance remittance' : 'Card'],
                    $status === 'submitted' && random_int(0, 1) === 1 => [round($subtotal * 0.4, 2), 'Bank transfer'],
                    default => [0, null],
                };

                $invoice->update([
                    'subtotal' => $subtotal,
                    'insurance_coverage_amount' => $coverageAmount,
                    'patient_responsibility' => $subtotal - $coverageAmount,
                    'amount_paid' => $amountPaid,
                    'payment_method' => $paymentMethod,
                ]);
            }
        });
    }
}
