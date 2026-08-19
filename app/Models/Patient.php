<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
    protected $fillable = [
        'lead_id',
        'diagnosis',
        'programme',
        'treatment_plan_review_due_at',
        'insurance_provider',
        'authorized_sessions_total',
        'authorization_renews_at',
        'enrolled_at',
    ];

    protected $casts = [
        'authorization_renews_at' => 'date',
        'treatment_plan_review_due_at' => 'date',
        'enrolled_at' => 'datetime',
    ];

    /**
     * The lead this patient was converted from - name, age, parent, phone all
     * still live there since it's the stable "child identity" record.
     */
    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    public function notes()
    {
        return $this->hasMany(PatientNote::class)->latest();
    }

    public function goals()
    {
        return $this->hasMany(PatientGoal::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class)->latest('issue_date');
    }

    /**
     * Care team is derived from Calendar, not manually assigned: whichever
     * therapists actually have a session with this patient. Not a real
     * Eloquent relation (must be called as a method, not a property) since
     * it resolves through the lead's calendar sessions rather than a pivot.
     */
    public function careTeam()
    {
        $therapistIds = $this->calendarSessions()->distinct()->pluck('therapist_id');

        return User::whereIn('id', $therapistIds)->orderBy('first_name')->get(['id', 'first_name', 'last_name']);
    }

    /**
     * Sessions booked for this patient. CalendarSession.patient_id references
     * the lead (not this table) since leads remain the scheduling identity -
     * there's no FK on leads pointing back here, so this can't be a real
     * hasManyThrough; it's a plain query keyed off lead_id instead.
     */
    public function calendarSessions()
    {
        return CalendarSession::where('patient_id', $this->lead_id);
    }

    /**
     * Authorized hours "used" is derived from actual past calendar sessions
     * rather than manually typed in, so it can never drift from reality.
     */
    public function hoursUsed(): int
    {
        $minutes = $this->calendarSessions()
            ->where('session_date', '<=', now()->toDateString())
            ->sum('duration_minutes');

        return (int) round($minutes / 60);
    }

    /**
     * A profile is "incomplete" (surfaced as a banner + needs-details badge)
     * until the core clinical/admin fields are filled in.
     */
    public function isProfileIncomplete(): bool
    {
        return ! $this->diagnosis
            || ! $this->programme
            || ! $this->insurance_provider
            || ! $this->authorized_sessions_total
            || ! optional($this->lead)->phone;
    }

    /**
     * Human-readable list of what's missing, for the incomplete-profile banner.
     */
    public function missingFieldsLabel(): string
    {
        $missing = [];

        if (! $this->diagnosis) $missing[] = 'diagnosis';
        if (! $this->programme) $missing[] = 'programme';
        if (! optional($this->lead)->phone) $missing[] = 'contact number';
        if (! $this->insurance_provider) $missing[] = 'insurance';
        if (! $this->authorized_sessions_total) $missing[] = 'authorized hours';

        return implode(', ', $missing);
    }
}
