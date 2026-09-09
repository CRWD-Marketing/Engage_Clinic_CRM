<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class CalendarSession extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'therapist_id',
        'patient_id',
        'patient_name',
        'activity_type',
        'session_date',
        'start_time',
        'end_time',
        'duration_minutes',
        'room',
        'status',
        'follow_up_completed_at',
        'notes',
        'created_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'session_date' => 'date',
        'duration_minutes' => 'integer',
        'follow_up_completed_at' => 'datetime',
    ];

    /**
     * Model event hooks.
     */
    protected static function booted(): void
    {
        // Keep patient_name (denormalized) in sync with the linked Lead's
        // child_name whenever the session is attached to a different lead.
        static::saving(function (CalendarSession $session) {
            if ($session->isDirty('patient_id')) {
                $session->patient_name = $session->patient_id
                    ? Lead::whereKey($session->patient_id)->value('child_name')
                    : null;
            }

            // Keep end_time in sync with start_time + duration_minutes so
            // it never has to be set manually by the controller.
            if (($session->isDirty('start_time') || $session->isDirty('duration_minutes'))
                && $session->start_time
                && $session->duration_minutes) {
                $session->end_time = \Carbon\Carbon::parse($session->start_time)
                    ->addMinutes((int) $session->duration_minutes)
                    ->format('H:i:s');
            }
        });
    }

    /**
     * The staff member assigned to this session.
     */
    public function therapist()
    {
        return $this->belongsTo(User::class, 'therapist_id');
    }

    /**
     * The child (Lead) this session is booked for.
     *
     * Renamed from patient() for clarity — the underlying FK column is
     * still `patient_id`, referencing leads.id.
     */
    public function child()
    {
        return $this->belongsTo(Lead::class, 'patient_id');
    }

    /**
     * The staff member who created this session.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Patient goals marked as worked on during this session.
     */
    public function goals()
    {
        return $this->belongsToMany(PatientGoal::class, 'session_goals');
    }

    /**
     * Scope a query to only include sessions for a given therapist.
     */
    public function scopeForTherapist(Builder $query, $therapistId): Builder
    {
        return $query->where('therapist_id', $therapistId);
    }

    /**
     * Scope a query to only include sessions on a given date.
     */
    public function scopeOnDate(Builder $query, string $date): Builder
    {
        return $query->whereDate('session_date', $date);
    }

    /**
     * Scope a query to exclude cancelled sessions.
     */
    public function scopeNotCancelled(Builder $query): Builder
    {
        return $query->where('status', '!=', 'cancelled');
    }

    /**
     * Scope a query to only include no-shows still awaiting a coordinator follow-up.
     */
    public function scopeNoShowNeedsFollowUp(Builder $query): Builder
    {
        return $query->where('status', 'no_show')->whereNull('follow_up_completed_at');
    }
}