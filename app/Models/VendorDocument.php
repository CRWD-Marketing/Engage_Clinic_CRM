<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VendorDocument extends Model
{
    /**
     * Document slots on the registration form, keyed by the type stored here.
     * `expires` = an expiry date is expected once the file is uploaded.
     */
    const TYPES = [
        'trade_licence' => ['label' => 'Trade Licence', 'hint' => null, 'expires' => true],
        'vat_certificate' => ['label' => 'VAT Certificate', 'hint' => 'Required if VAT registered', 'expires' => true],
        'insurance' => ['label' => 'Insurance Certificate', 'hint' => 'Liability or professional indemnity', 'expires' => true],
        'professional_licence' => ['label' => 'Professional / Regulatory Licence', 'hint' => 'e.g. DoH, Civil Defence, ADWEA approvals', 'expires' => true],
        'agreement' => ['label' => 'Signed Contract / Service Agreement', 'hint' => 'If already in place', 'expires' => false],
        'other' => ['label' => 'Other Supporting Documents', 'hint' => 'Company profile, certifications', 'expires' => false],
    ];

    const TYPE_BANK_LETTER = 'bank_letter';

    /** Days before expiry a document starts being flagged for renewal. */
    const EXPIRY_WARNING_DAYS = 30;

    protected $fillable = [
        'vendor_id', 'type', 'document_number', 'issue_date', 'expiry_date',
        'file_path', 'file_original_name', 'file_mime', 'file_size',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'expiry_date' => 'date',
    ];

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function getLabelAttribute(): string
    {
        if ($this->type === self::TYPE_BANK_LETTER) {
            return 'Bank Letter / Cancelled Cheque';
        }

        return self::TYPES[$this->type]['label'] ?? ucwords(str_replace('_', ' ', $this->type));
    }

    /**
     * valid / expiring (within 30 days) / expired / pending (no expiry date
     * on a document that should have one) / on_file (undated documents).
     */
    public function getStateAttribute(): string
    {
        if (! $this->expiry_date) {
            return (self::TYPES[$this->type]['expires'] ?? false) ? 'pending' : 'on_file';
        }

        $days = $this->daysToExpiry();

        if ($days < 0) {
            return 'expired';
        }

        return $days <= self::EXPIRY_WARNING_DAYS ? 'expiring' : 'valid';
    }

    public function daysToExpiry(): ?int
    {
        return $this->expiry_date ? (int) now()->startOfDay()->diffInDays($this->expiry_date, false) : null;
    }
}
