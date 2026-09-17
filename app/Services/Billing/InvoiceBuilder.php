<?php

namespace App\Services\Billing;

use App\Models\CalendarSession;
use App\Models\InsuranceClaim;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Turns ledger rows into a tax invoice: one numbered line per billed hour,
 * VAT and payer split per line, totals summed from unrounded line values.
 */
class InvoiceBuilder
{
    public function __construct(private SessionLedger $ledger)
    {
    }

    /**
     * Expand ledger rows into per-hour lines and totals. Rows with no billable
     * hours are dropped (they're kept on the ledger for utilisation only).
     */
    public function compose(Patient $patient, Collection $rows, ?array $profile = null): array
    {
        $profile ??= $this->ledger->profile($patient);
        $vatRate = $profile['vat_rate'];
        $lines = [];
        $n = 0;

        $warnings = [];

        foreach ($rows->filter(fn ($r) => $r['bill_hours'] > 0)->sortBy(fn ($r) => $r['session_date'].' '.$r['start_time']) as $r) {
            $start = Carbon::parse($r['session_date'].' '.$r['start_time']);
            $coveredHours = $r['covered_bill_hours'] ?? $r['bill_hours'];

            if (! empty($r['insufficient_message'])) {
                $warnings[] = $r['date_label'].' — '.$r['insufficient_message'];
            }

            for ($i = 0; $i < $r['bill_hours']; $i++) {
                $from = $start->copy()->addHours($i);
                $to = $from->copy()->addHour();
                $amount = (float) $r['rate'] * ($r['factor'] ?? 1.0);
                $vat = $amount * $vatRate;
                $total = $amount + $vat;
                $n++;

                // Only the hours within the authorization's remaining balance
                // bill to the insurer - once that runs out (mid-session or
                // not), every hour after it bills to the family instead,
                // never silently to insurance under the same authorization.
                $isExcess = $i >= $coveredHours;
                $linePayer = $isExcess ? 'Self-pay' : $r['payer'];
                $linePct = $isExcess ? 0 : $r['coverage_pct'];
                $lineAuthId = $isExcess ? null : ($r['authorization_id'] ?? null);
                $pct = $linePct / 100;

                $lines[] = [
                    'line_no' => $n,
                    'calendar_session_id' => $r['id'],
                    'therapist_id' => $r['therapist_id'],
                    'patient_authorization_id' => $lineAuthId,
                    'exceeds_authorization' => $isExcess && ! empty($r['insufficient_authorization']),
                    'description' => $r['service_label'],
                    'note' => trim(($r['setting'] === 'Clinic' ? 'Clinic' : 'Client home').' by '.$r['therapist_name'].($r['trainee_note'] ? ' (+ '.$r['trainee_note'].', in training)' : '')),
                    'setting' => $r['setting'],
                    'session_from' => $from->format('Y-m-d H:i:s'),
                    'session_to' => $to->format('Y-m-d H:i:s'),
                    'from_label' => $from->format('d M Y · g:i A'),
                    'to_label' => $to->format('d M Y · g:i A'),
                    'payer' => $linePayer,
                    'coverage_percent' => $linePct,
                    'qty' => 1,
                    'rate' => $amount,
                    'amount' => $amount,
                    'vat_amount' => $vat,
                    'total' => $total,
                    'insurer_amount' => $total * $pct,
                    'family_amount' => $total * (1 - $pct),
                ];
            }
        }

        $net = array_sum(array_column($lines, 'amount'));
        $vat = array_sum(array_column($lines, 'vat_amount'));
        $gross = array_sum(array_column($lines, 'total'));
        $insurer = array_sum(array_column($lines, 'insurer_amount'));
        $family = array_sum(array_column($lines, 'family_amount'));

        $splits = collect($lines)
            ->filter(fn ($l) => $l['payer'] !== 'Self-pay' && $l['insurer_amount'] > 0)
            ->groupBy('payer')
            ->map(fn ($g, $payer) => ['payer' => $payer, 'pct' => (int) $g->first()['coverage_percent'], 'amount' => round($g->sum('insurer_amount'), 2)])
            ->values()
            ->all();

        $billed = $rows->filter(fn ($r) => $r['bill_hours'] > 0);
        $dates = $billed->pluck('session_date')->sort()->values();
        $from = $dates->first() ? Carbon::parse($dates->first()) : now();
        $to = $dates->last() ? Carbon::parse($dates->last()) : now();

        return [
            'lines' => $lines,
            'warnings' => array_values(array_unique($warnings)),
            'session_count' => $billed->count(),
            'bill_hours' => (int) $billed->sum('bill_hours'),
            'net' => round($net, 2),
            'vat' => round($vat, 2),
            'total' => round($gross, 2),
            'insurer_share' => round($insurer, 2),
            'family_share' => round($family, 2),
            'amount_due' => round($gross - $insurer, 2),
            'splits' => $splits,
            'payer' => $splits[0]['payer'] ?? 'Self-pay',
            'coverage_percent' => $splits[0]['pct'] ?? 0,
            'period_from' => $from->toDateString(),
            'period_to' => $to->toDateString(),
            'period_label' => $from->format('M Y') === $to->format('M Y') ? $from->format('M Y') : $from->format('M').' – '.$to->format('M Y'),
            'rate' => $profile['rate'],
            'bill_to' => $profile['bill_to'],
            'parent_email' => $profile['parent_email'],
            'child' => $profile['child'],
        ];
    }

    /**
     * Persist a composed invoice: number, frozen lines, sessions marked as
     * invoiced, a claim per payer. One transaction so a failure leaves no
     * half-issued document or gap in the sequence.
     */
    public function issue(Patient $patient, array $composed, ?string $batchReference = null, ?int $userId = null): Invoice
    {
        return DB::transaction(function () use ($patient, $composed, $batchReference, $userId) {
            $issue = now();
            $serviceIds = Service::pluck('id', 'name');

            $invoice = Invoice::create([
                'invoice_number' => DocumentNumbers::nextInvoice(),
                'patient_id' => $patient->id,
                'bill_to' => $composed['bill_to'],
                'payer' => $composed['payer'],
                'coverage_percent' => $composed['coverage_percent'],
                'period' => Carbon::parse($composed['period_from'])->startOfMonth()->toDateString(),
                'period_from' => $composed['period_from'],
                'period_to' => $composed['period_to'],
                'issue_date' => $issue->toDateString(),
                'due_date' => $issue->copy()->addDays((int) config('billing.due_days', 30))->toDateString(),
                'status' => $composed['insurer_share'] > 0 ? 'submitted' : 'issued',
                'subtotal' => $composed['net'],
                'vat_amount' => $composed['vat'],
                'total' => $composed['total'],
                'hourly_rate' => $composed['rate'],
                'insurance_coverage_amount' => $composed['insurer_share'],
                'patient_responsibility' => $composed['family_share'],
                'payer_splits' => $composed['splits'],
                'batch_reference' => $batchReference,
                'created_by' => $userId,
            ]);

            foreach ($composed['lines'] as $line) {
                $invoice->lineItems()->create([
                    'line_no' => $line['line_no'],
                    'calendar_session_id' => $line['calendar_session_id'],
                    'service_id' => $serviceIds[$line['description']] ?? null,
                    'patient_authorization_id' => $line['patient_authorization_id'],
                    'therapist_id' => $line['therapist_id'],
                    'description' => $line['description'],
                    'session_from' => $line['session_from'],
                    'session_to' => $line['session_to'],
                    'setting' => $line['setting'],
                    'note' => $line['note'],
                    'payer' => $line['payer'],
                    'coverage_percent' => $line['coverage_percent'],
                    'exceeds_authorization' => $line['exceeds_authorization'],
                    'qty' => 1,
                    'sessions' => 1,
                    'rate' => $line['rate'],
                    'amount' => round($line['amount'], 2),
                    'vat_amount' => round($line['vat_amount'], 2),
                    'total' => round($line['total'], 2),
                    'insurer_amount' => round($line['insurer_amount'], 2),
                    'family_amount' => round($line['family_amount'], 2),
                ]);
            }

            $sessionIds = collect($composed['lines'])->pluck('calendar_session_id')->unique()->all();
            CalendarSession::whereIn('id', $sessionIds)->update(['invoice_id' => $invoice->id]);

            foreach ($composed['splits'] as $split) {
                $claim = InsuranceClaim::create([
                    'reference' => DocumentNumbers::nextClaim(),
                    'invoice_id' => $invoice->id,
                    'patient_id' => $patient->id,
                    'insurer' => $split['payer'],
                    'amount' => $split['amount'],
                    'period_label' => $composed['period_label'],
                    'status' => 'submitted',
                    'submitted_on' => $issue->toDateString(),
                ]);
                if (! $invoice->claim_reference) {
                    $invoice->forceFill(['claim_reference' => $claim->reference])->save();
                }
            }

            return $invoice->fresh(['lineItems', 'payments', 'claims', 'patient.lead']);
        });
    }
}
