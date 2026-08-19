<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    public const STATUSES = ['draft', 'submitted', 'pending_info', 'paid', 'rejected'];

    protected $fillable = [
        'invoice_number',
        'patient_id',
        'bill_to',
        'payer',
        'coverage_percent',
        'period',
        'issue_date',
        'due_date',
        'claim_reference',
        'status',
        'subtotal',
        'insurance_coverage_amount',
        'patient_responsibility',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'period' => 'date',
            'issue_date' => 'date',
            'due_date' => 'date',
            'coverage_percent' => 'integer',
            'subtotal' => 'decimal:2',
            'insurance_coverage_amount' => 'decimal:2',
            'patient_responsibility' => 'decimal:2',
        ];
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function lineItems()
    {
        return $this->hasMany(InvoiceLineItem::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isSelfPay(): bool
    {
        return $this->payer === 'Self-pay';
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'draft' => 'Draft',
            'submitted' => 'Submitted',
            'pending_info' => 'Pending info',
            'paid' => 'Paid',
            'rejected' => 'Rejected',
            default => ucfirst($this->status),
        };
    }
}
