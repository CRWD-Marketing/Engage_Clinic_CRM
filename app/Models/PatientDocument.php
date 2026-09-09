<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PatientDocument extends Model
{
    protected $fillable = [
        'patient_id',
        'uploaded_by',
        'name',
        'type',
        'file_path',
        'original_filename',
        'mime_type',
        'file_size',
        'expires_at',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'expires_at' => 'date',
    ];

    const TYPES = [
        'Assessment report',
        'Progress review',
        'Authorization',
        'Consent',
        'Identity',
        'Invoice / receipt',
        'Correspondence',
    ];

    /**
     * Same renewal window used for the patient header's authorization-renewal
     * banner (Patient::authorizations soonest renews_at), so "within the
     * renewal window" means one consistent thing across the app.
     */
    const RENEWAL_WINDOW_DAYS = 45;

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * "You" when the viewer filed it themselves, otherwise the filer's name.
     */
    public function uploaderLabel(): string
    {
        if (auth()->id() && $this->uploaded_by === auth()->id()) {
            return 'You';
        }

        return $this->uploader ? trim($this->uploader->first_name.' '.$this->uploader->last_name) : 'System';
    }

    /**
     * Human-readable file size, e.g. "1.2 MB".
     */
    public function humanFileSize(): string
    {
        $bytes = $this->file_size ?? 0;

        if ($bytes < 1024) {
            return $bytes.' B';
        }

        if ($bytes < 1024 * 1024) {
            return round($bytes / 1024, 1).' KB';
        }

        return round($bytes / (1024 * 1024), 1).' MB';
    }

    /**
     * Expiry badge: no date on file, comfortably valid, or due for renewal
     * within the renewal window (or already past).
     */
    public function expiryStatus(): array
    {
        if (!$this->expires_at) {
            return ['label' => 'No expiry', 'variant' => 'neutral'];
        }

        $daysUntil = now()->startOfDay()->diffInDays($this->expires_at, false);

        if ($daysUntil <= self::RENEWAL_WINDOW_DAYS) {
            return ['label' => 'Expires '.$this->expires_at->format('d M Y').' — renew', 'variant' => 'warn'];
        }

        return ['label' => 'Valid to '.$this->expires_at->format('d M Y'), 'variant' => 'ok'];
    }

    /**
     * Whether this document is within (or past) the renewal window - used to
     * decide if an Authorization-type document should raise the patient
     * header's attention banner.
     */
    public function isDueForRenewal(): bool
    {
        return $this->expires_at
            && now()->startOfDay()->diffInDays($this->expires_at, false) <= self::RENEWAL_WINDOW_DAYS;
    }
}
