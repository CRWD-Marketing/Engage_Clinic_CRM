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
    const STATUS_CONTACTED = 'contacted';
    const STATUS_CONVERTED = 'converted';
    const STATUS_CLOSED = 'closed';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'child_age',
        'email',
        'phone',
        'interested_in',
        'message',
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
    ];

    /**
     * The lead this contact submission was converted into, if any.
     */
    public function lead()
    {
        return $this->belongsTo(Lead::class, 'converted_lead_id');
    }

    /**
     * Whether this contact is eligible to be converted to a lead - must not
     * already be converted.
     */
    public function canConvertToLead(): bool
    {
        return $this->status !== self::STATUS_CONVERTED && ! $this->converted_lead_id;
    }

    /**
     * Get all available statuses.
     */
    public static function getStatuses(): array
    {
        return [
            self::STATUS_NEW => 'New',
            self::STATUS_CONTACTED => 'Contacted',
            self::STATUS_CONVERTED => 'Converted',
            self::STATUS_CLOSED => 'Closed',
        ];
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
