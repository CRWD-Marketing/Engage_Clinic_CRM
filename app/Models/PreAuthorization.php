<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PreAuthorization extends Model
{
    const STATUSES = ['requested', 'approved', 'denied'];

    protected $fillable = [
        'reference', 'patient_id', 'payer', 'service', 'hours', 'valid_from', 'valid_to', 'status',
        'payer_reference', 'justification', 'denial_reason', 'submitted_on', 'resubmitted_from_id', 'created_by',
    ];

    protected $casts = [
        'hours' => 'integer',
        'valid_from' => 'date',
        'valid_to' => 'date',
        'submitted_on' => 'date',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function resubmittedFrom()
    {
        return $this->belongsTo(self::class, 'resubmitted_from_id');
    }

    public function statusLabel(): string
    {
        return ucfirst($this->status);
    }
}
