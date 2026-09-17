<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CalendarSession extends Model
{
    use HasFactory;

    /**
     * Client-facing therapy types - the ones that count as "direct therapy
     * hours" in the utilisation report and against a payer's authorized hours.
     */
    const THERAPY_TYPES = ['ABA', 'Speech', 'OT', 'Assessment', 'Parent training'];

    /**
     * Staff-only blocks: never therapy hours, coloured separately on the grid.
     */
    const NON_THERAPY_TYPES = ['Supervision', 'Observation', 'Admin time', 'Training', 'Meeting'];

    const DEFAULT_TYPES = ['ABA', 'Speech', 'OT', 'Assessment', 'Supervision', 'Parent training', 'Observation', 'Admin time', 'Training'];

    /**
     * "closed" is a slot discontinued from Therapists & schedules - it's gone
     * from the calendar, frees the therapist's time and the patient's
     * authorized hours, and can be reopened. "cancelled" is a single
     * occurrence that didn't happen and stays visible struck-through.
     */
    const STATUSES = ['scheduled', 'completed', 'cancelled', 'no_show', 'closed'];

    /**
     * Statuses staff can set by hand. "completed" is deliberately absent -
     * attendance is never logged manually; a session flips to completed on its
     * own once its end time passes untouched (see scopePastDueScheduled()).
     */
    const MANUAL_STATUSES = ['scheduled', 'cancelled', 'no_show', 'closed'];

    /**
     * Statuses that take a session off the books - not shown on the calendar
     * grid as active, not blocking a slot, not counted as hours.
     */
    const INACTIVE_STATUSES = ['cancelled', 'closed'];

    const CANCEL_REASONS = ['family', 'clinic'];

    protected $fillable = [
        'therapist_id',
        'cover_for_user_id',
        'patient_id',
        'patient_name',
        'patient_ids',
        'activity_label',
        'activity_type',
        'activity_types',
        'session_date',
        'start_time',
        'end_time',
        'duration_minutes',
        'room',
        'status',
        'cancel_reason',
        'cancel_notice_hours',
        'cancelled_at',
        'invoice_id',
        'follow_up_completed_at',
        'notes',
        'recurrence_group',
        'supervised_by',
        'supervised_at',
        'supervision_notes',
        'therapist_note',
        'created_by',
    ];

    protected $casts = [
        'session_date' => 'date',
        'duration_minutes' => 'integer',
        'patient_ids' => 'array',
        'activity_types' => 'array',
        'follow_up_completed_at' => 'datetime',
        'supervised_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'cancel_notice_hours' => 'float',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    protected static function booted(): void
    {
        static::saving(function (CalendarSession $session) {
            // Keep patient_name (denormalized) in sync with the linked Lead's
            // child_name. Group / custom bookings have no single patient_id and
            // carry their own label, so those are left alone.
            if ($session->isDirty('patient_id') && $session->patient_id) {
                $session->patient_name = Lead::whereKey($session->patient_id)->value('child_name');
            }

            if (($session->isDirty('start_time') || $session->isDirty('duration_minutes'))
                && $session->start_time
                && $session->duration_minutes) {
                $session->end_time = Carbon::parse($session->start_time)
                    ->addMinutes((int) $session->duration_minutes)
                    ->format('H:i:s');
            }

            if ($session->status !== 'cancelled') {
                $session->cancel_reason = null;
                $session->cancel_notice_hours = null;
                $session->cancelled_at = null;
            } elseif ($session->isDirty('status') && ! $session->cancelled_at) {
                $session->cancelled_at = now();
            }
        });
    }

    public function therapist()
    {
        return $this->belongsTo(User::class, 'therapist_id');
    }

    /**
     * The therapist this session was originally booked for, when it has been
     * reassigned because they're on leave ("covered shift").
     */
    public function coverFor()
    {
        return $this->belongsTo(User::class, 'cover_for_user_id');
    }

    public function supervisor()
    {
        return $this->belongsTo(User::class, 'supervised_by');
    }

    /**
     * The child (Lead) this session is booked for. The FK column is still
     * `patient_id`, referencing leads.id.
     */
    public function child()
    {
        return $this->belongsTo(Lead::class, 'patient_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function goals()
    {
        return $this->belongsToMany(PatientGoal::class, 'session_goals');
    }

    public function scopeForTherapist(Builder $query, $therapistId): Builder
    {
        return $query->where('therapist_id', $therapistId);
    }

    public function scopeOnDate(Builder $query, string $date): Builder
    {
        return $query->whereDate('session_date', $date);
    }

    public function scopeNotCancelled(Builder $query): Builder
    {
        return $query->whereNotIn('status', self::INACTIVE_STATUSES);
    }

    public function scopeNotClosed(Builder $query): Builder
    {
        return $query->where('status', '!=', 'closed');
    }

    public function scopeNoShowNeedsFollowUp(Builder $query): Builder
    {
        return $query->where('status', 'no_show')->whereNull('follow_up_completed_at');
    }

    /**
     * Client-facing therapy only - excludes supervision/admin/training blocks
     * and anything not booked against a patient.
     */
    public function scopeDirectTherapy(Builder $query): Builder
    {
        return $query->whereNotIn('activity_type', self::NON_THERAPY_TYPES)
            ->where(fn (Builder $q) => $q->whereNotNull('patient_id')->orWhereNotNull('patient_ids'));
    }

    /**
     * Sessions still "scheduled" whose end time has already passed. Attendance
     * isn't logged manually - the Clinical Supervisor only ever touches a
     * session *before* it happens; anything left untouched once its time
     * passes is, by the clinic's own process, an attended session.
     */
    public function scopePastDueScheduled(Builder $query): Builder
    {
        return $query->where('status', 'scheduled')
            ->whereRaw('TIMESTAMP(session_date, end_time) < ?', [now()]);
    }

    public function endsAt(): Carbon
    {
        return Carbon::parse($this->session_date->format('Y-m-d').' '.$this->end_time);
    }

    public function isPast(): bool
    {
        return $this->endsAt()->lt(now());
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }

    public function isSupervised(): bool
    {
        return $this->supervised_at !== null;
    }

    public function displayName(): string
    {
        return $this->patient_name ?: ($this->activity_label ?: 'Unassigned');
    }

    /**
     * Which legend category the session falls under on the roster grid.
     */
    public function category(): string
    {
        if ($this->isCancelled()) {
            return 'cancelled';
        }
        if ($this->cover_for_user_id) {
            return 'covered';
        }

        return match ($this->activity_type) {
            'Supervision' => 'supervision',
            'Observation' => 'observation',
            'Admin time', 'Training', 'Meeting' => 'admin',
            default => 'therapy',
        };
    }

    public function statusLabel(): string
    {
        if ($this->isCancelled()) {
            return $this->cancel_reason ? 'Cancelled — '.$this->cancel_reason : 'Cancelled';
        }

        return match ($this->status) {
            'no_show' => 'No-show',
            'closed' => 'Closed',
            default => ucfirst($this->status),
        };
    }
}
