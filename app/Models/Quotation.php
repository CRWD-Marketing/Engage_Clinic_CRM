<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * The agreed commercial terms for a client before any session is scheduled:
 * what is being delivered, at what rate, how it's paid, and whether the
 * customer has confirmed and paid. A quotation that reaches "cleared" is what
 * opens the calendar for that client.
 */
class Quotation extends Model
{
    public const STATUSES = [
        'draft' => 'Draft',
        'sent' => 'Sent',
        'customer_confirmed' => 'Customer confirmed · payment pending',
        'cleared' => 'Payment confirmed',
        'cancelled' => 'Cancelled',
    ];

    public const PAYMENT_MODES = ['Self pay', 'Insurance'];

    public const PRICING_BASES = ['Per hour', 'Per session', 'Package', 'Insurance approved rate'];

    public const SEND_CHANNELS = ['WhatsApp', 'Email'];

    public const CONFIRMATION_METHODS = ['Written', 'Verbal'];

    protected $fillable = [
        'quote_number', 'patient_id', 'customer_name', 'location', 'payment_mode', 'payer', 'service',
        'pricing_basis', 'billing_unit', 'quantity', 'rate', 'subtotal', 'vat_amount', 'total',
        'payment_terms', 'valid_until', 'status', 'sent_at', 'sent_via', 'confirmed_at', 'confirmation_method',
        'prepayment_policy_received', 'pos_agreement_received', 'noc_received', 'liability_acknowledged',
        'payment_method', 'amount_received', 'pos_fee_amount', 'payment_reference', 'payment_received_on',
        'payment_confirmed_at', 'payment_confirmed_by', 'cancel_reason', 'notes', 'created_by',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'rate' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'amount_received' => 'decimal:2',
        'pos_fee_amount' => 'decimal:2',
        'valid_until' => 'date',
        'payment_received_on' => 'date',
        'sent_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'payment_confirmed_at' => 'datetime',
        'prepayment_policy_received' => 'boolean',
        'pos_agreement_received' => 'boolean',
        'noc_received' => 'boolean',
        'liability_acknowledged' => 'boolean',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function events()
    {
        return $this->hasMany(QuotationEvent::class)->orderBy('created_at')->orderBy('id');
    }

    /**
     * Receipts on invoices that were paid out of this quotation's prepayment.
     */
    public function appliedPayments()
    {
        return $this->hasMany(Payment::class);
    }

    public function isInsurance(): bool
    {
        return $this->payment_mode === 'Insurance';
    }

    /**
     * Payment confirmed (or, for direct insurance billing, NOC and liability
     * acknowledgement on file) - the scheduling gate.
     */
    public function isCleared(): bool
    {
        return $this->status === 'cleared';
    }

    public function isExpired(): bool
    {
        return ! in_array($this->status, ['cleared', 'cancelled'], true) && $this->valid_until->endOfDay()->isPast();
    }

    public function statusLabel(): string
    {
        if ($this->isCleared() && $this->isInsurance()) {
            return 'Insurance billing activated';
        }

        return $this->isExpired() ? 'Expired' : (self::STATUSES[$this->status] ?? ucfirst($this->status));
    }

    /**
     * Prepayment received and not yet drawn down by an invoice.
     */
    public function prepaidBalance(): float
    {
        return round(max(0, (float) $this->amount_received - (float) $this->appliedPayments()->sum('amount')), 2);
    }

    /**
     * Card POS fee per the signed agreement: a percentage of the service
     * value plus VAT on that fee. Informational - shown beside the total,
     * not folded into it.
     */
    public static function posFee(float $serviceValue): float
    {
        $fee = $serviceValue * (float) config('billing.pos_fee_pct', 2) / 100;

        return round($fee * (1 + (float) config('billing.vat_rate', 5) / 100), 2);
    }

    public function recordEvent(string $event, ?string $note = null): QuotationEvent
    {
        return $this->events()->create(['user_id' => auth()->id(), 'event' => $event, 'note' => $note]);
    }
}
