<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PreAuthorization extends Model
{
    const STATUSES = ['requested', 'approved', 'denied'];

    /**
     * How the payer's answer reached the clinic.
     */
    const CHANNELS = ['Email', 'Phone', 'Insurance portal', 'WhatsApp', 'Letter'];

    protected $fillable = [
        'reference', 'patient_id', 'payer', 'service', 'hours', 'valid_from', 'valid_to', 'status',
        'payer_reference', 'justification', 'denial_reason', 'submitted_on', 'resubmitted_from_id', 'created_by',
        'decided_on', 'decided_by', 'decision_channel', 'approved_hours', 'coverage_percent', 'patient_authorization_id',
    ];

    protected $casts = [
        'hours' => 'integer',
        'valid_from' => 'date',
        'valid_to' => 'date',
        'submitted_on' => 'date',
        'decided_on' => 'date',
        'approved_hours' => 'integer',
        'coverage_percent' => 'integer',
    ];

    public function decider()
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    public function authorization()
    {
        return $this->belongsTo(PatientAuthorization::class, 'patient_authorization_id');
    }

    /**
     * The calendar activity type this request's service bills as ("ABA",
     * "Speech", "OT"...) - what the client-file authorization must list for
     * the session ledger to route hours to it. Falls back to the service
     * label itself when nothing matches.
     */
    public function activityType(): string
    {
        $code = array_search($this->service, config('billing.service_labels', []), true);
        if ($code !== false) {
            return $code;
        }
        foreach (CalendarSession::THERAPY_TYPES as $type) {
            if (stripos($this->service, $type) !== false) {
                return $type;
            }
        }

        return stripos($this->service, 'occupational') !== false ? 'OT' : $this->service;
    }

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
