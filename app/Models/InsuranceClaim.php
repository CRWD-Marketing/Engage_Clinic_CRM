<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InsuranceClaim extends Model
{
    const STATUSES = ['draft', 'submitted', 'pending_info', 'rejected', 'settled'];

    const LABELS = [
        'draft' => 'Draft',
        'submitted' => 'Submitted',
        'pending_info' => 'Pending info',
        'rejected' => 'Rejected',
        'settled' => 'Settled',
    ];

    protected $fillable = ['reference', 'invoice_id', 'patient_id', 'insurer', 'amount', 'period_label', 'status', 'submitted_on', 'settled_on', 'notes'];

    protected $casts = [
        'amount' => 'decimal:2',
        'submitted_on' => 'date',
        'settled_on' => 'date',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function isOpen(): bool
    {
        return $this->status !== 'settled';
    }

    public function ageDays(): int
    {
        $end = $this->status === 'settled' && $this->settled_on ? $this->settled_on->startOfDay() : now()->startOfDay();

        return max(0, (int) $this->submitted_on->startOfDay()->diffInDays($end));
    }

    public function statusLabel(): string
    {
        return self::LABELS[$this->status] ?? ucfirst($this->status);
    }
}
