<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobApplication extends Model
{
    use HasFactory;

    /**
     * Status constants.
     */
    const STATUS_NEW = 'new';
    const STATUS_REVIEWED = 'reviewed';
    const STATUS_INTERVIEWING = 'interviewing';
    const STATUS_HIRED = 'hired';
    const STATUS_REJECTED = 'rejected';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'job_posting_id',
        'job_title',
        'first_name',
        'last_name',
        'email',
        'years_experience',
        'cover_letter',
        'resume_path',
        'resume_original_name',
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
    ];

    /**
     * The job posting this application was submitted against, if it still exists.
     */
    public function jobPosting()
    {
        return $this->belongsTo(JobPosting::class);
    }

    /**
     * Internal HR notes log, newest first.
     */
    public function notesLog()
    {
        return $this->hasMany(JobApplicationNote::class)->latest();
    }

    /**
     * Get all available statuses.
     */
    public static function getStatuses(): array
    {
        return [
            self::STATUS_NEW => 'New',
            self::STATUS_REVIEWED => 'Reviewed',
            self::STATUS_INTERVIEWING => 'Interviewing',
            self::STATUS_HIRED => 'Hired',
            self::STATUS_REJECTED => 'Rejected',
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
            self::STATUS_REVIEWED => 'yellow',
            self::STATUS_INTERVIEWING => 'purple',
            self::STATUS_HIRED => 'green',
            self::STATUS_REJECTED => 'gray',
            default => 'gray',
        };
    }

    /**
     * The applicant's full name.
     */
    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }
}
