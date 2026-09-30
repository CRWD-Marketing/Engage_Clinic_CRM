<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One client's filled-in form. Kept permanently, even after it is converted:
 * the Lead is created from it and linked via lead_id, never in place of it.
 */
class FormSubmission extends Model
{
    const STATUS_NEW = 'new';
    const STATUS_REVIEWED = 'reviewed';
    const STATUS_CONVERTED = 'converted';
    const STATUS_IGNORED = 'ignored';

    protected $fillable = [
        'form_id',
        'lead_id',
        'status',
        'submitted_at',
        'reviewed_at',
        'reviewed_by',
        'converted_at',
        'converted_by',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'converted_at' => 'datetime',
    ];

    public static function getStatuses(): array
    {
        return [
            self::STATUS_NEW => 'New',
            self::STATUS_REVIEWED => 'Reviewed',
            self::STATUS_CONVERTED => 'Converted',
            self::STATUS_IGNORED => 'Ignored',
        ];
    }

    /**
     * Statuses staff can set by hand - converted is only ever the result of
     * Convert to Lead, same as Contact::getSelectableStatuses().
     */
    public static function getSelectableStatuses(): array
    {
        return collect(self::getStatuses())->except(self::STATUS_CONVERTED)->all();
    }

    public function form()
    {
        return $this->belongsTo(Form::class);
    }

    public function values()
    {
        return $this->hasMany(FormSubmissionValue::class)->orderBy('sort_order');
    }

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    public function convertedByUser()
    {
        return $this->belongsTo(User::class, 'converted_by');
    }

    public function reviewedByUser()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isConverted(): bool
    {
        return $this->lead_id !== null || $this->status === self::STATUS_CONVERTED;
    }

    public function canConvertToLead(): bool
    {
        return ! $this->isConverted();
    }

    public function getStatusLabelAttribute(): string
    {
        return self::getStatuses()[$this->status] ?? ucfirst($this->status);
    }

    public function getStatusBadgeColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_NEW => 'blue',
            self::STATUS_REVIEWED => 'yellow',
            self::STATUS_CONVERTED => 'green',
            self::STATUS_IGNORED => 'gray',
            default => 'gray',
        };
    }
}
