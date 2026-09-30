<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    use HasFactory;

    /**
     * Status constants.
     */
    const STATUS_NEW = 'new';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_CONTACTED = 'contacted';
    const STATUS_CONVERTED = 'converted';
    const STATUS_CLOSED = 'closed';

    /**
     * Free-consultation start times (Abu Dhabi time), offered by the website
     * booking, the contact form and staff rescheduling in CRM -> Contacts.
     */
    const CONSULTATION_TIMES = [
        '9:00 AM', '9:30 AM', '10:00 AM', '10:30 AM', '11:00 AM', '11:30 AM',
        '1:00 PM', '1:30 PM', '2:00 PM', '2:30 PM', '3:00 PM', '3:30 PM', '4:00 PM', '4:30 PM',
    ];

    /**
     * Insurance choices, value => label: the clinic's active payers (Billing ->
     * Insurances) plus the two answers every family can give. Same values Leads
     * uses, so they carry over on convert.
     */
    public static function insuranceOptions(): array
    {
        return Insurance::where('is_active', true)->orderBy('name')->pluck('name')
            ->reject(fn ($name) => in_array(mb_strtolower($name), ['self-pay', 'self pay', 'not sure yet'], true))
            ->mapWithKeys(fn ($name) => [$name => $name])
            ->merge(['Self-pay' => 'Self-pay (no insurance)', 'Not sure yet' => 'Not sure yet'])
            ->all();
    }

    /**
     * The slot can be set or moved until the decision has been emailed.
     */
    public function isSlotEditable(): bool
    {
        return in_array($this->status, [self::STATUS_NEW, self::STATUS_APPROVED, self::STATUS_REJECTED], true);
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'child_name',
        'child_age',
        'email',
        'phone',
        'interested_in',
        'insurance',
        'message',
        'booking_date',
        'booking_time',
        'booking_decision',
        'status_email_sent_at',
        'status',
        'converted_lead_id',
        'converted_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'converted_at' => 'datetime',
        'booking_date' => 'date',
        'status_email_sent_at' => 'datetime',
    ];

    /**
     * The lead this contact submission was converted into, if any.
     */
    public function lead()
    {
        return $this->belongsTo(Lead::class, 'converted_lead_id');
    }

    /**
     * Whether this contact is eligible to be converted to a lead - only once
     * the consultation has been approved (or the approval email already sent,
     * i.e. contacted). A rejected or still-undecided (new) submission has no
     * consultation to build a lead pipeline around.
     */
    public function canConvertToLead(): bool
    {
        return in_array($this->status, [self::STATUS_APPROVED, self::STATUS_CONTACTED], true)
            && ! $this->converted_lead_id;
    }

    /**
     * Whether this submission has a requested consultation slot attached
     * (i.e. it came from the booking widget, not the plain contact form).
     */
    public function hasBookingSlot(): bool
    {
        return $this->booking_date !== null && $this->booking_time !== null;
    }

    /**
     * A decision (approve/reject) must be recorded before the outcome email
     * can be sent - this is what the "Send Email" action gates on.
     */
    public function canSendStatusEmail(): bool
    {
        return in_array($this->status, [self::STATUS_APPROVED, self::STATUS_REJECTED], true);
    }

    /**
     * Get all available statuses. STATUS_CONVERTED is intentionally excluded
     * from the manual dropdown - it's only ever set via convertToLead().
     */
    public static function getStatuses(): array
    {
        return [
            self::STATUS_NEW => 'New',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_REJECTED => 'Rejected',
            self::STATUS_CONTACTED => 'Contacted',
            self::STATUS_CONVERTED => 'Converted',
            self::STATUS_CLOSED => 'Closed',
        ];
    }

    /**
     * The subset of statuses a staff member can manually pick from the
     * dropdown - converted is a side effect of convertToLead(), not a
     * direct choice.
     */
    public static function getSelectableStatuses(): array
    {
        return collect(self::getStatuses())->except(self::STATUS_CONVERTED)->all();
    }

    /**
     * Get the status label.
     */
    public function getStatusLabelAttribute(): string
    {
        return self::getStatuses()[$this->status] ?? ucfirst($this->status);
    }

    /**
     * Get the status badge color.
     */
    public function getStatusBadgeColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_NEW => 'blue',
            self::STATUS_APPROVED => 'green',
            self::STATUS_REJECTED => 'red',
            self::STATUS_CONTACTED => 'yellow',
            self::STATUS_CONVERTED => 'green',
            self::STATUS_CLOSED => 'gray',
            default => 'gray',
        };
    }

    /**
     * Scope a query to only include new contacts.
     */
    public function scopeNew($query)
    {
        return $query->where('status', self::STATUS_NEW);
    }

    /**
     * Scope a query to only include contacted contacts.
     */
    public function scopeContacted($query)
    {
        return $query->where('status', self::STATUS_CONTACTED);
    }

    /**
     * Scope a query to only include converted contacts.
     */
    public function scopeConverted($query)
    {
        return $query->where('status', self::STATUS_CONVERTED);
    }

    /**
     * Scope a query to only include closed contacts.
     */
    public function scopeClosed($query)
    {
        return $query->where('status', self::STATUS_CLOSED);
    }
}
