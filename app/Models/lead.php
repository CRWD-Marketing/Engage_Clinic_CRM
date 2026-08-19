<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lead extends Model
{
    use HasFactory;

    /**
     * Status constants.
     */
    const STATUS_NEW = 'new';
    const STATUS_CONTACTED = 'contacted';
    const STATUS_ASSESSMENT_BOOKED = 'assessment_booked';
    const STATUS_ASSESSMENT_DONE = 'assessment_done';
    const STATUS_ENROLLED = 'enrolled';
    const STATUS_TERMINATED = 'terminated';

    /**
     * The linear forward pipeline, used for advance()/moveBack()/progress calculations.
     * Deliberately excludes STATUS_TERMINATED - termination is a side branch a lead can
     * be moved to from any stage, not a step in the normal forward progression.
     */
    const PIPELINE_STATUSES = [
        self::STATUS_NEW,
        self::STATUS_CONTACTED,
        self::STATUS_ASSESSMENT_BOOKED,
        self::STATUS_ASSESSMENT_DONE,
        self::STATUS_ENROLLED,
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'child_name',
        'child_age',
        'parent_guardian_name',
        'phone',
        'source',
        'interested_in',
        'insurance',
        'estimated_value',
        'notes',
        'status',
        'assigned_to',
        'follow_up_due_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'estimated_value' => 'decimal:2',
        'follow_up_due_at' => 'datetime',
    ];

    /**
     * Always include the assignee's display name in JSON output, so the
     * Kanban board's JS can refresh a card's owner line without an extra request.
     *
     * @var array<int, string>
     */
    protected $appends = ['assigned_to_name'];

    /**
     * The calendar sessions booked for this child.
     */
    public function calendarSessions()
    {
        return $this->hasMany(CalendarSession::class, 'patient_id');
    }

    /**
     * The clinical/enrollment record this lead was converted into, if any.
     */
    public function patient()
    {
        return $this->hasOne(Patient::class);
    }

    /**
     * Whether this lead is eligible to be converted to a patient - must be
     * enrolled, and not already converted.
     */
    public function canConvertToPatient(): bool
    {
        return $this->status === self::STATUS_ENROLLED && ! $this->patient()->exists();
    }

    /**
     * The staff member this lead is assigned to, if any.
     *
     * Deliberately NOT named assignedTo() - Eloquent snake-cases relation names
     * for array/JSON output, and "assignedTo" -> "assigned_to" would collide
     * with (and silently overwrite) the raw assigned_to FK column in the
     * serialized response, turning it from a plain ID into a full user object.
     */
    public function owner()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Display name of the assigned staff member, or null if unassigned.
     */
    public function getAssignedToNameAttribute(): ?string
    {
        if (! $this->relationLoaded('owner')) {
            $this->load('owner');
        }

        return $this->owner ? trim($this->owner->first_name.' '.$this->owner->last_name) : null;
    }

    /**
     * Full activity log (notes + assignment changes), newest first.
     */
    public function activities()
    {
        return $this->hasMany(LeadActivity::class)->latest();
    }

    /**
     * Just the note-type activity entries.
     */
    public function notesLog()
    {
        return $this->activities()->where('type', LeadActivity::TYPE_NOTE);
    }

    /**
     * Just the assignment-change activity entries.
     */
    public function assignmentLog()
    {
        return $this->activities()->where('type', LeadActivity::TYPE_ASSIGNMENT);
    }

    /**
     * Set the estimated value attribute - ensures only numbers and decimal points.
     */
    public function setEstimatedValueAttribute($value)
    {
        // Remove all non-numeric characters except decimal point
        $cleaned = preg_replace('/[^0-9.]/', '', $value);
        
        // Remove multiple decimal points (keep only first one)
        $parts = explode('.', $cleaned);
        if (count($parts) > 2) {
            $cleaned = $parts[0] . '.' . implode('', array_slice($parts, 1));
        }
        
        // If value starts with decimal point, add leading zero
        if (strlen($cleaned) > 0 && $cleaned[0] === '.') {
            $cleaned = '0' . $cleaned;
        }
        
        // Remove leading zeros (except when it's "0.")
        if (strlen($cleaned) > 1 && $cleaned[0] === '0' && $cleaned[1] !== '.') {
            $cleaned = ltrim($cleaned, '0');
            if ($cleaned === '' || $cleaned === '.') {
                $cleaned = '0';
            }
        }
        
        $this->attributes['estimated_value'] = $cleaned;
    }

    /**
     * Get the numeric estimated value.
     */
    public function getEstimatedValueNumericAttribute(): float
    {
        // Remove commas and any non-numeric characters except decimal point
        $value = preg_replace('/[^0-9.]/', '', $this->estimated_value);
        return (float) $value;
    }

    /**
     * Get the formatted estimated value with commas.
     */
    public function getEstimatedValueFormattedAttribute(): string
    {
        return number_format($this->estimated_value_numeric, 0);
    }

    /**
     * Get all available statuses.
     */
    public static function getStatuses(): array
    {
        return [
            self::STATUS_NEW => 'New',
            self::STATUS_CONTACTED => 'Contacted',
            self::STATUS_ASSESSMENT_BOOKED => 'Assessment Booked',
            self::STATUS_ASSESSMENT_DONE => 'Assessment Done',
            self::STATUS_ENROLLED => 'Enrolled',
            self::STATUS_TERMINATED => 'Terminated',
        ];
    }

    /**
     * Get the status badge color.
     */
    public function getStatusBadgeColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_NEW => 'blue',
            self::STATUS_CONTACTED => 'yellow',
            self::STATUS_ASSESSMENT_BOOKED => 'orange',
            self::STATUS_ASSESSMENT_DONE => 'purple',
            self::STATUS_ENROLLED => 'green',
            self::STATUS_TERMINATED => 'red',
            default => 'gray',
        };
    }

    /**
     * Whether this lead has been marked terminated (lost/declined).
     */
    public function isTerminated(): bool
    {
        return $this->status === self::STATUS_TERMINATED;
    }

    /**
     * Get the status label.
     */
    public function getStatusLabelAttribute(): string
    {
        return self::getStatuses()[$this->status] ?? ucfirst($this->status);
    }

    /**
     * Get the next status in the pipeline.
     */
    public function getNextStatus(): ?string
    {
        $currentIndex = array_search($this->status, self::PIPELINE_STATUSES);

        if ($currentIndex !== false && isset(self::PIPELINE_STATUSES[$currentIndex + 1])) {
            return self::PIPELINE_STATUSES[$currentIndex + 1];
        }

        return null;
    }

    /**
     * Get the previous status in the pipeline.
     */
    public function getPreviousStatus(): ?string
    {
        $currentIndex = array_search($this->status, self::PIPELINE_STATUSES);

        if ($currentIndex !== false && isset(self::PIPELINE_STATUSES[$currentIndex - 1]) && $currentIndex > 0) {
            return self::PIPELINE_STATUSES[$currentIndex - 1];
        }

        return null;
    }

    /**
     * Check if lead is at the final stage.
     */
    public function isAtFinalStage(): bool
    {
        return $this->status === self::STATUS_ENROLLED;
    }

    /**
     * Check if lead is at the first stage.
     */
    public function isAtFirstStage(): bool
    {
        return $this->status === self::STATUS_NEW;
    }

    /**
     * Get the stage index (0-based) within the pipeline. Terminated leads (not part of
     * the linear pipeline) report index 0.
     */
    public function getStageIndex(): int
    {
        $index = array_search($this->status, self::PIPELINE_STATUSES);

        return $index !== false ? $index : 0;
    }

    /**
     * Get the total number of pipeline stages.
     */
    public static function getTotalStages(): int
    {
        return count(self::PIPELINE_STATUSES);
    }

    /**
     * Get the progress percentage through the pipeline.
     */
    public function getProgressPercentageAttribute(): int
    {
        $total = self::getTotalStages() - 1;
        $current = $this->getStageIndex();
        
        if ($total === 0) {
            return 100;
        }
        
        return round(($current / $total) * 100);
    }

    /**
     * Check if the lead can be advanced.
     */
    public function canAdvance(): bool
    {
        return !$this->isAtFinalStage() && !$this->isTerminated();
    }

    /**
     * Check if the lead can be moved back.
     */
    public function canMoveBack(): bool
    {
        return !$this->isAtFirstStage();
    }

    /**
     * Advance the lead to the next stage.
     */
    public function advance(): bool
    {
        $nextStatus = $this->getNextStatus();
        
        if ($nextStatus) {
            $this->update(['status' => $nextStatus]);
            return true;
        }
        
        return false;
    }

    /**
     * Move the lead back to the previous stage.
     */
    public function moveBack(): bool
    {
        $previousStatus = $this->getPreviousStatus();
        
        if ($previousStatus) {
            $this->update(['status' => $previousStatus]);
            return true;
        }
        
        return false;
    }

    /**
     * Scope a query to only include new leads.
     */
    public function scopeNew($query)
    {
        return $query->where('status', self::STATUS_NEW);
    }

    /**
     * Scope a query to only include contacted leads.
     */
    public function scopeContacted($query)
    {
        return $query->where('status', self::STATUS_CONTACTED);
    }

    /**
     * Scope a query to only include leads with assessment booked.
     */
    public function scopeAssessmentBooked($query)
    {
        return $query->where('status', self::STATUS_ASSESSMENT_BOOKED);
    }

    /**
     * Scope a query to only include leads with assessment done.
     */
    public function scopeAssessmentDone($query)
    {
        return $query->where('status', self::STATUS_ASSESSMENT_DONE);
    }

    /**
     * Scope a query to only include enrolled leads.
     */
    public function scopeEnrolled($query)
    {
        return $query->where('status', self::STATUS_ENROLLED);
    }

    /**
     * Scope a query to only include leads still open (not enrolled or terminated).
     */
    public function scopeActive($query)
    {
        return $query->whereNotIn('status', [self::STATUS_ENROLLED, self::STATUS_TERMINATED]);
    }

    /**
     * Scope a query to only include leads that are in progress (not new, enrolled, or terminated).
     */
    public function scopeInProgress($query)
    {
        return $query->whereNotIn('status', [self::STATUS_NEW, self::STATUS_ENROLLED, self::STATUS_TERMINATED]);
    }

    /**
     * Scope a query to only include terminated (lost/declined) leads.
     */
    public function scopeTerminated($query)
    {
        return $query->where('status', self::STATUS_TERMINATED);
    }
}