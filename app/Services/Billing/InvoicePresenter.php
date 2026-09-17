<?php

namespace App\Services\Billing;

use App\Models\Invoice;
use App\Models\Patient;

/**
 * The JSON shape the billing screen renders an invoice row from. One place,
 * so a freshly-saved invoice and a listed one always look the same.
 */
class InvoicePresenter
{
    public static function row(Invoice $i): array
    {
        $i->loadMissing(['patient.lead', 'payments', 'claims', 'replaces', 'replacedBy']);
        $status = $i->billingStatus();
        $days = $i->daysPastDue();

        return [
            'id' => $i->id,
            'number' => $i->invoice_number,
            'patient_id' => $i->patient_id,
            'patient' => $i->patient?->lead?->child_name ?? '—',
            'parent' => $i->bill_to ?: ($i->patient?->lead?->parent_guardian_name ?? ''),
            'parent_email' => $i->patient?->lead?->email,
            'payer' => $i->payer ?: 'Self-pay',
            'period' => $i->periodLabel(),
            'issued' => $i->issue_date?->format('Y-m-d'),
            'issued_label' => $i->issue_date?->format('d M Y'),
            'due' => $i->due_date?->format('Y-m-d'),
            'due_label' => $i->due_date?->format('d M Y'),
            'net' => (float) $i->subtotal,
            'vat' => (float) $i->vat_amount,
            'total' => (float) $i->total,
            'insurer_share' => (float) $i->insurance_coverage_amount,
            'family_share' => (float) $i->patient_responsibility,
            'paid' => $i->paidAmount(),
            'credit' => (float) $i->credit_amount,
            'credit_reason' => $i->credit_reason,
            'balance' => max(0, $i->balance()),
            'status' => $status,
            'status_label' => $i->billingStatusLabel(),
            'claim_status' => $i->status,
            'claim_reference' => $i->claim_reference,
            'voided' => $i->isVoided(),
            'voided_on' => $i->voided_at?->format('d M Y'),
            'void_reason' => $i->void_reason,
            'replaces' => $i->replaces?->invoice_number,
            'replaced_by' => $i->replacedBy?->invoice_number,
            'sent_to' => $i->sent_to,
            'sent_at' => $i->sent_at?->format('d M Y H:i'),
            'reminder_sent_at' => $i->reminder_sent_at?->format('d M Y'),
            'reminders_count' => (int) $i->reminders_count,
            'batch' => $i->batch_reference,
            'days_past_due' => $days,
            'age_label' => $days > 0 ? "{$days} d overdue" : (abs($days).' d to due'),
            'receipts' => $i->payments->map(fn ($p) => [
                'id' => $p->id,
                'number' => $p->receipt_number,
                'amount' => (float) $p->amount,
                'method' => $p->method,
                'date' => $p->received_on->format('d M Y'),
                'reference' => $p->reference,
            ])->values()->all(),
            'print_url' => route('billing.invoices.show', $i),
        ];
    }

    public static function patient(Patient $p, SessionLedger $ledger): array
    {
        $profile = $ledger->profile($p);

        return [
            'id' => $p->id,
            'name' => $profile['child'] ?? ('Patient #'.$p->id),
            'parent' => $profile['bill_to'],
            'email' => $profile['parent_email'],
            'phone' => $profile['phone'],
            'payer' => $profile['primary_payer'],
            'rate' => $profile['rate'],
            'setting' => $profile['setting'],
            'prepaid' => $profile['prepaid'],
            'package' => $profile['package_label'],
            'insurers' => $profile['insurers']->map(fn ($a) => ['payer' => $a->payer_name, 'pct' => (int) $a->coverage_percent, 'covers' => $a->covers_services ?? []])->values()->all(),
        ];
    }
}
