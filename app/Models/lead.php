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
     * Fixed reason list for the "Terminate lead" modal - kept short and
     * closed-ended so the termination history stays reportable/filterable
     * rather than accumulating one-off free-text reasons.
     */
    const TERMINATION_REASONS = [
        'Fees / budget',
        'No insurance coverage',
        'Chose another provider',
        'Unreachable — no response',
        'Distance / relocated',
        'Not a fit for our services',
        'Duplicate enquiry',
        'Other',
    ];

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
        'email',
        'source',
        'campaign',
        'ad_name',
        'lead_form_name',
        'city',
        'child_age_band',
        'interested_in',
        'insurance',
        'estimated_value',
        'notes',
        'status',
        'assigned_to',
        'follow_up_due_at',
        'termination_reason',
        'termination_note',
        'terminated_at',
        'status_before_termination',

        // Step 1: Parent contact verified
        'parent_contact_completed_at',
        'parent_relationship',
        'parent_alternate_phone',
        'preferred_language',

        // Step 2: Child details complete
        'child_details_completed_at',
        'child_date_of_birth',
        'child_gender',
        'child_emirates_id',
        'child_emirates_id_expiry',
        'diagnosis_suspected',
        'nursery_school',
        'main_concern',

        // Step 3: Intake form received
        'intake_form_completed_at',
        'intake_form_received_on',
        'intake_form_received_via',
        'allergies',
        'medical_history',

        // Step 4: Consultation / assessment done
        'assessment_completed_at',
        'assessment_date',
        'assessment_clinician_id',
        'assessment_tool',
        'assessment_report_reference',
        'assessment_report_summary',

        // Step 5: Funding confirmed
        'funding_completed_at',
        'funding_type',
        'funding_insurer',
        'funding_policy_number',
        'funding_approval_valid_until',
        'funding_services_needed',
        'funding_notes',

        // Step 6: Package agreed
        'package_completed_at',
        'package_location_id',
        'package_ids',
        'package_start_date',
        'package_sessions_per_week',
        'package_agreed_by',
        'package_scheduling_notes',

        // Step 7: Consent & terms signed
        'consent_completed_at',
        'consent_signed_date',
        'consent_signed_by',
        'consent_data_photo',
        'consent_signature_method',
        'consent_notes',
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
        'terminated_at' => 'datetime',

        'parent_contact_completed_at' => 'datetime',
        'child_details_completed_at' => 'datetime',
        'child_date_of_birth' => 'date',
        'child_emirates_id_expiry' => 'date',
        'intake_form_completed_at' => 'datetime',
        'intake_form_received_on' => 'date',
        'assessment_completed_at' => 'datetime',
        'assessment_date' => 'date',
        'funding_completed_at' => 'datetime',
        'funding_approval_valid_until' => 'date',
        'funding_services_needed' => 'array',
        'package_completed_at' => 'datetime',
        'package_ids' => 'array',
        'package_start_date' => 'date',
        'consent_completed_at' => 'datetime',
        'consent_signed_date' => 'date',
    ];

    /**
     * The 7 intake-checklist steps, in display order, mapped to the request
     * flag ("intake_step") that marks each one complete and the model column
     * that records when. Drives both the "N of 7 complete" progress count and
     * routes each step's modal save through the same update() endpoint.
     */
    const INTAKE_STEPS = [
        'parent_contact' => 'parent_contact_completed_at',
        'child_details' => 'child_details_completed_at',
        'intake_form' => 'intake_form_completed_at',
        'assessment' => 'assessment_completed_at',
        'funding' => 'funding_completed_at',
        'package' => 'package_completed_at',
        'consent' => 'consent_completed_at',
    ];

    /**
     * How many of the 7 intake steps are marked complete.
     */
    public function getIntakeStepsCompleteAttribute(): int
    {
        return collect(self::INTAKE_STEPS)->filter(fn ($column) => $this->{$column} !== null)->count();
    }

    /**
     * Always include the assignee's display name in JSON output, so the
     * Kanban board's JS can refresh a card's owner line without an extra request.
     *
     * @var array<int, string>
     */
    protected $appends = ['assigned_to_name', 'intake_steps_complete'];

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
     * enrolled, have every intake-checklist step actually complete, and not
     * already converted.
     */
    public function canConvertToPatient(): bool
    {
        return $this->status === self::STATUS_ENROLLED
            && $this->intake_steps_complete === count(self::INTAKE_STEPS)
            && ! $this->patient()->exists();
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

    public function assessmentClinician()
    {
        return $this->belongsTo(User::class, 'assessment_clinician_id');
    }

    public function packageLocation()
    {
        return $this->belongsTo(Location::class, 'package_location_id');
    }

    /**
     * The Package rows referenced by package_ids (step 6, "pick one or more").
     */
    public function packages()
    {
        return Package::whereIn('id', $this->package_ids ?? []);
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
     * Bring a terminated lead back into the active pipeline, at the stage it
     * was lost from (status_before_termination), or STATUS_NEW if that
     * wasn't recorded (e.g. a lead terminated before this column existed).
     */
    public function restore(): bool
    {
        if (! $this->isTerminated()) {
            return false;
        }

        $this->update([
            'status' => $this->status_before_termination ?: self::STATUS_NEW,
            'termination_reason' => null,
            'termination_note' => null,
            'terminated_at' => null,
            'status_before_termination' => null,
        ]);

        return true;
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