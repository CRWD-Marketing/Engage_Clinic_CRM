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
    ];

    /**
     * The calendar sessions booked for this child.
     */
    public function calendarSessions()
    {
        return $this->hasMany(CalendarSession::class, 'patient_id');
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
            default => 'gray',
        };
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
        $statuses = array_keys(self::getStatuses());
        $currentIndex = array_search($this->status, $statuses);
        
        if ($currentIndex !== false && isset($statuses[$currentIndex + 1])) {
            return $statuses[$currentIndex + 1];
        }
        
        return null;
    }

    /**
     * Get the previous status in the pipeline.
     */
    public function getPreviousStatus(): ?string
    {
        $statuses = array_keys(self::getStatuses());
        $currentIndex = array_search($this->status, $statuses);
        
        if ($currentIndex !== false && isset($statuses[$currentIndex - 1])) {
            return $statuses[$currentIndex - 1];
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
     * Get the stage index (0-based).
     */
    public function getStageIndex(): int
    {
        $statuses = array_keys(self::getStatuses());
        $index = array_search($this->status, $statuses);
        
        return $index !== false ? $index : 0;
    }

    /**
     * Get the total number of stages.
     */
    public static function getTotalStages(): int
    {
        return count(self::getStatuses());
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
        return !$this->isAtFinalStage();
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
     * Scope a query to only include active leads (not enrolled).
     */
    public function scopeActive($query)
    {
        return $query->where('status', '!=', self::STATUS_ENROLLED);
    }

    /**
     * Scope a query to only include leads that are in progress (not new or enrolled).
     */
    public function scopeInProgress($query)
    {
        return $query->whereNotIn('status', [self::STATUS_NEW, self::STATUS_ENROLLED]);
    }
}