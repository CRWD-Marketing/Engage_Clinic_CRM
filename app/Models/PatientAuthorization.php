<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PatientAuthorization extends Model
{
    protected $fillable = [
        'patient_id',
        'payer_name',
        'coverage_percent',
        'covers_services',
        'policy_number',
        'authorized_hours_total',
        'renews_at',
        'sort_order',
    ];

    protected $casts = [
        'covers_services' => 'array',
        'coverage_percent' => 'integer',
        'authorized_hours_total' => 'integer',
        'renews_at' => 'date',
        'sort_order' => 'integer',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Hours used against this specific payer, derived from the patient's
     * actual calendar sessions whose activity_type is one this payer covers -
     * never manually typed in, so it can't drift from reality (mirrors the
     * reasoning behind the pre-existing single-authorization Patient::hoursUsed()).
     */
    public function hoursUsed(): int
    {
        $covers = $this->covers_services ?? [];

        if (empty($covers)) {
            return 0;
        }

        $minutes = $this->patient->calendarSessions()
            ->where('session_date', '<=', now()->toDateString())
            ->whereIn('activity_type', $covers)
            ->sum('duration_minutes');

        return (int) round($minutes / 60);
    }

    /**
     * Human-readable "Covers X · Y" line for the authorization card.
     */
    public function coversLabel(): string
    {
        return collect($this->covers_services ?? [])->implode(' · ') ?: '—';
    }
}
