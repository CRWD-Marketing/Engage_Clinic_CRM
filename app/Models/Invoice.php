<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    /**
     * Claim-side status (kept for the payer workflow and older dashboards).
     * The invoice's own money status is never stored - see billingStatus().
     */
    public const STATUSES = ['draft', 'issued', 'submitted', 'pending_info', 'paid', 'rejected'];

    protected $fillable = [
        'invoice_number',
        'patient_id',
        'bill_to',
        'payer',
        'coverage_percent',
        'period',
        'period_from',
        'period_to',
        'issue_date',
        'due_date',
        'claim_reference',
        'status',
        'subtotal',
        'vat_amount',
        'total',
        'hourly_rate',
        'insurance_coverage_amount',
        'patient_responsibility',
        'payer_splits',
        'amount_paid',
        'payment_method',
        'credit_amount',
        'credit_reason',
        'voided_at',
        'void_reason',
        'replaces_invoice_id',
        'replaced_by_invoice_id',
        'sent_to',
        'sent_at',
        'reminder_sent_at',
        'reminders_count',
        'batch_reference',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'period' => 'date',
            'period_from' => 'date',
            'period_to' => 'date',
            'issue_date' => 'date',
            'due_date' => 'date',
            'coverage_percent' => 'integer',
            'subtotal' => 'decimal:2',
            'vat_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'hourly_rate' => 'decimal:2',
            'insurance_coverage_amount' => 'decimal:2',
            'patient_responsibility' => 'decimal:2',
            'payer_splits' => 'array',
            'amount_paid' => 'decimal:2',
            'credit_amount' => 'decimal:2',
            'voided_at' => 'datetime',
            'sent_at' => 'datetime',
            'reminder_sent_at' => 'datetime',
            'reminders_count' => 'integer',
        ];
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function lineItems()
    {
        return $this->hasMany(InvoiceLineItem::class)->orderBy('line_no');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class)->orderBy('received_on')->orderBy('id');
    }

    public function claims()
    {
        return $this->hasMany(InsuranceClaim::class);
    }

    public function sessions()
    {
        return $this->hasMany(CalendarSession::class);
    }

    public function replaces()
    {
        return $this->belongsTo(self::class, 'replaces_invoice_id');
    }

    public function replacedBy()
    {
        return $this->belongsTo(self::class, 'replaced_by_invoice_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isSelfPay(): bool
    {
        return strcasecmp((string) $this->payer, 'Self-pay') === 0 || (float) $this->insurance_coverage_amount <= 0;
    }

    public function isVoided(): bool
    {
        return $this->voided_at !== null;
    }

    public function paidAmount(): float
    {
        return $this->relationLoaded('payments')
            ? (float) $this->payments->sum('amount')
            : (float) $this->payments()->sum('amount');
    }

    /**
     * total − credit − paid. Computed on every read, never stored, so it can't
     * drift from the receipts.
     */
    public function balance(): float
    {
        return round((float) $this->total - (float) $this->credit_amount - $this->paidAmount(), 2);
    }

    /**
     * voided | paid | partly_paid | outstanding - derived from receipts and credit.
     */
    public function billingStatus(): string
    {
        if ($this->isVoided()) {
            return 'voided';
        }
        if ($this->balance() <= 0.01) {
            return 'paid';
        }
        if ($this->paidAmount() > 0) {
            return 'partly_paid';
        }

        return 'outstanding';
    }

    public function billingStatusLabel(): string
    {
        return match ($this->billingStatus()) {
            'voided' => 'Voided',
            'paid' => (float) $this->credit_amount > 0 ? 'Paid · credited' : 'Paid',
            'partly_paid' => 'Partly paid',
            default => 'Outstanding',
        };
    }

    /**
     * Older label used by the patient Payments tab and dashboards.
     */
    public function paymentStatusLabel(): string
    {
        return match ($this->billingStatus()) {
            'voided' => 'Voided',
            'paid' => 'Paid',
            'partly_paid' => 'Partly paid',
            default => 'Unpaid',
        };
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'pending_info' => 'Pending info',
            default => ucfirst((string) $this->status),
        };
    }

    public function daysPastDue(): int
    {
        if (! $this->due_date) {
            return 0;
        }

        return (int) now()->startOfDay()->diffInDays($this->due_date->startOfDay(), false) * -1;
    }

    public function periodLabel(): string
    {
        if ($this->period_from && $this->period_to) {
            return $this->period_from->format('M') === $this->period_to->format('M') && $this->period_from->format('Y') === $this->period_to->format('Y')
                ? $this->period_from->format('M Y')
                : $this->period_from->format('M').' – '.$this->period_to->format('M Y');
        }

        return $this->period ? $this->period->format('M Y') : '';
    }

    /**
     * Keep the two legacy columns older screens read in step with the
     * receipts table.
     */
    public function syncPaymentColumns(): void
    {
        $paid = $this->paidAmount();
        $last = $this->payments()->latest('received_on')->latest('id')->first();

        $this->forceFill([
            'amount_paid' => $paid,
            'payment_method' => $last?->method,
            'status' => $this->isVoided() ? $this->status : ($this->balance() <= 0.01 && ! in_array($this->status, ['rejected'], true) ? 'paid' : ($this->status === 'paid' ? 'issued' : $this->status)),
        ])->save();
    }
}
