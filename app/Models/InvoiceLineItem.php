<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceLineItem extends Model
{
    protected $fillable = [
        'invoice_id',
        'line_no',
        'calendar_session_id',
        'service_id',
        'patient_authorization_id',
        'therapist_id',
        'description',
        'session_from',
        'session_to',
        'setting',
        'note',
        'payer',
        'coverage_percent',
        'exceeds_authorization',
        'qty',
        'cpt_code',
        'sessions',
        'rate',
        'amount',
        'vat_amount',
        'total',
        'insurer_amount',
        'family_amount',
    ];

    protected function casts(): array
    {
        return [
            'line_no' => 'integer',
            'sessions' => 'integer',
            'coverage_percent' => 'integer',
            'exceeds_authorization' => 'boolean',
            'session_from' => 'datetime',
            'session_to' => 'datetime',
            'qty' => 'decimal:2',
            'rate' => 'decimal:2',
            'amount' => 'decimal:2',
            'vat_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'insurer_amount' => 'decimal:2',
            'family_amount' => 'decimal:2',
        ];
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function authorization()
    {
        return $this->belongsTo(PatientAuthorization::class, 'patient_authorization_id');
    }

    public function therapist()
    {
        return $this->belongsTo(User::class, 'therapist_id');
    }

    public function session()
    {
        return $this->belongsTo(CalendarSession::class, 'calendar_session_id');
    }
}
