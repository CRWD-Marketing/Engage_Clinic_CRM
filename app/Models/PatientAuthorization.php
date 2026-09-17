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
        'approval_reference',
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
     * Which calendar activity_type codes this authorization's covers_services
     * actually matches. covers_services is often a full service label picked
     * at intake (e.g. "ABA therapy", from the Service catalogue) rather than
     * the short activity_type code CalendarSession stores ("ABA") - an exact
     * whereIn() would silently match nothing for any label that isn't a
     * verbatim code, the same substring rule SessionLedger::coversType() uses
     * for billing, so a session it happily bills against still counts here.
     */
    private function matchingActivityTypes(): array
    {
        $covers = $this->covers_services ?? [];
        if (empty($covers)) {
            return [];
        }

        return array_values(array_filter(
            CalendarSession::THERAPY_TYPES,
            fn ($type) => collect($covers)->contains(
                fn ($cover) => stripos($type, $cover) !== false || stripos($cover, $type) !== false
            )
        ));
    }

    /**
     * Hours used against this specific payer, derived from the patient's
     * actual calendar sessions whose activity_type is one this payer covers -
     * never manually typed in, so it can't drift from reality (mirrors the
     * reasoning behind the pre-existing single-authorization Patient::hoursUsed()).
     */
    /**
     * Every non-cancelled session booked against this payer's covered
     * services - past *and* future - so a new recurring booking can be sized
     * to what's genuinely still available, not just what's been delivered.
     */
    public function minutesCommitted(): int
    {
        $query = $this->patient->calendarSessions()->notCancelled();
        if (! empty($this->covers_services)) {
            // whereIn([]) correctly yields zero rows when covers_services is
            // set but matches no real activity_type - only a genuinely empty
            // covers_services (no restriction at all) skips the filter.
            $query->whereIn('activity_type', $this->matchingActivityTypes());
        }

        return (int) $query->sum('duration_minutes');
    }

    public function hoursLeft(): float
    {
        if (! $this->authorized_hours_total) {
            return 0;
        }

        return max(0, round($this->authorized_hours_total - $this->minutesCommitted() / 60, 1));
    }

    public function hoursUsed(): int
    {
        if (empty($this->covers_services)) {
            return 0;
        }

        $minutes = $this->patient->calendarSessions()
            ->where('session_date', '<=', now()->toDateString())
            ->whereIn('activity_type', $this->matchingActivityTypes())
            ->sum('duration_minutes');

        return (int) round($minutes / 60);
    }

    /**
     * Whether this authorization is still in force on the given date - a
     * null renews_at never expires. Used to stop billing/routing a session
     * against an authorization that lapsed before the session happened.
     */
    public function isActiveOn(string $date): bool
    {
        return ! $this->renews_at || $this->renews_at->toDateString() >= $date;
    }

    /**
     * Minutes already consumed against this authorization by every matching
     * session strictly before the given one, in ledger chronological order.
     * This is the baseline a session's own hours are measured against, so a
     * session that straddles the remaining balance can be split hour by
     * hour between "covered" and "exceeds authorization".
     */
    public function minutesUsedBefore(CalendarSession $session): int
    {
        if (empty($this->covers_services)) {
            return 0;
        }

        $sessionDate = $session->session_date->toDateString();

        return (int) $this->patient->calendarSessions()
            ->whereIn('status', ['completed', 'no_show'])
            ->whereIn('activity_type', $this->matchingActivityTypes())
            ->where(function ($q) use ($sessionDate, $session) {
                $q->whereDate('session_date', '<', $sessionDate)
                    ->orWhere(function ($q2) use ($sessionDate, $session) {
                        $q2->whereDate('session_date', $sessionDate)
                            ->where('start_time', '<', $session->start_time);
                    });
            })
            ->sum('duration_minutes');
    }

    /**
     * Minutes still available under authorized_hours_total, measured as of
     * (i.e. not counting) the given session - never negative.
     */
    public function minutesRemainingBefore(CalendarSession $session): int
    {
        if (! $this->authorized_hours_total) {
            return 0;
        }

        return max(0, $this->authorized_hours_total * 60 - $this->minutesUsedBefore($session));
    }

    /**
     * Human-readable "Covers X · Y" line for the authorization card.
     */
    public function coversLabel(): string
    {
        return collect($this->covers_services ?? [])->implode(' · ') ?: '—';
    }
}
