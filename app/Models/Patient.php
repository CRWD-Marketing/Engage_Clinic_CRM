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
        'enrolled_at',
    ];

    protected $casts = [
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
     * A patient can have more than one concurrent insurance authorization
     * (e.g. one payer covering ABA/Speech, another covering OT/Assessments).
     * Ordered by sort_order - the first one is the "primary" shown on the
     * patient list page.
     */
    public function authorizations()
    {
        return $this->hasMany(PatientAuthorization::class)->orderBy('sort_order');
    }

    /**
     * The primary (first) authorization, for compact display like the
     * patient list's Insurance/Authorization columns.
     */
    public function primaryAuthorization(): ?PatientAuthorization
    {
        return $this->relationLoaded('authorizations')
            ? $this->authorizations->first()
            : $this->authorizations()->first();
    }

    public function documents()
    {
        return $this->hasMany(PatientDocument::class)->latest();
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

        return User::whereIn('id', $therapistIds)->orderBy('first_name')->get(['id', 'first_name', 'last_name', 'job_title']);
    }

    /**
     * All-time attendance rate: completed / (completed + no_show + family
     * cancellations), across every session this patient has ever had. A
     * clinic-initiated cancellation isn't the family's fault and stays
     * excluded entirely; a family cancellation counts against them the same
     * way it does in the billing cancellation policy. Null when there's
     * nothing to measure yet.
     */
    public function attendanceRate(): ?int
    {
        $sessions = $this->calendarSessions()
            ->where(fn ($q) => $q->whereIn('status', ['completed', 'no_show'])
                ->orWhere(fn ($q2) => $q2->where('status', 'cancelled')->where('cancel_reason', 'family')))
            ->get();

        if ($sessions->isEmpty()) {
            return null;
        }

        return (int) round($sessions->where('status', 'completed')->count() / $sessions->count() * 100);
    }

    /**
     * Sessions booked for this patient. CalendarSession.patient_id references
     * the lead (not this table) since leads remain the scheduling identity -
     * there's no FK on leads pointing back here, so this can't be a real
     * hasManyThrough; it's a plain query keyed off lead_id instead.
     */
    public function calendarSessions()
    {
        $leadId = $this->lead_id;

        // Group bookings carry every child in patient_ids rather than a single
        // patient_id, so a child's sessions are those matching either.
        return CalendarSession::where(fn ($q) => $q
            ->where('patient_id', $leadId)
            ->orWhereJsonContains('patient_ids', $leadId));
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
            || ! $this->authorizations()->exists()
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
        if (! $this->authorizations()->exists()) $missing[] = 'insurance authorization';

        return implode(', ', $missing);
    }
}
