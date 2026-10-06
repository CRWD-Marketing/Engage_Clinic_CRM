<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InsuranceClaim extends Model
{
    /**
     * "draft" is a claim that's required but not yet in the insurer's
     * portal; "documents_ready" means the finalized invoice and medical
     * report are in hand. Submission is a manual portal step - nothing here
     * assumes it happened until someone marks it so.
     */
    const STATUSES = ['draft', 'documents_ready', 'submitted', 'pending_info', 'rejected', 'settled'];

    const LABELS = [
        'draft' => 'To submit',
        'documents_ready' => 'Documents ready',
        'submitted' => 'Submitted',
        'pending_info' => 'Pending info',
        'rejected' => 'Rejected',
        'settled' => 'Settled',
    ];

    protected $fillable = ['reference', 'payer_reference', 'invoice_id', 'patient_id', 'insurer', 'portal', 'amount', 'period_label', 'service_date', 'status', 'submitted_on', 'settled_on', 'notes', 'medical_report_document_id', 'assessment_report_document_id'];

    protected $casts = [
        'amount' => 'decimal:2',
        'service_date' => 'date',
        'submitted_on' => 'date',
        'settled_on' => 'date',
    ];

    public function medicalReport()
    {
        return $this->belongsTo(PatientDocument::class, 'medical_report_document_id');
    }

    public function assessmentReport()
    {
        return $this->belongsTo(PatientDocument::class, 'assessment_report_document_id');
    }

    /**
     * Actually lodged with the insurer (as opposed to still being prepared).
     */
    public function isSubmitted(): bool
    {
        return $this->submitted_on !== null && ! in_array($this->status, ['draft', 'documents_ready'], true);
    }

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

        // Not yet submitted: age from when the claim became due instead.
        $start = $this->submitted_on ?? $this->created_at ?? now();

        return max(0, (int) $start->copy()->startOfDay()->diffInDays($end));
    }

    public function statusLabel(): string
    {
        return self::LABELS[$this->status] ?? ucfirst($this->status);
    }
}
