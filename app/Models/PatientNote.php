<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PatientNote extends Model
{
    protected $fillable = [
        'patient_id',
        'user_id',
        'body',
        'flagged',
        'flag_reason',
        'signed_off_at',
        'signed_off_by',
    ];

    protected $appends = ['author_name'];

    protected function casts(): array
    {
        return [
            'flagged' => 'boolean',
            'signed_off_at' => 'datetime',
        ];
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function signedOffBy()
    {
        return $this->belongsTo(User::class, 'signed_off_by');
    }

    public function scopeUnsigned($query)
    {
        return $query->whereNull('signed_off_at');
    }

    public function scopeFlagged($query)
    {
        return $query->where('flagged', true);
    }

    public function getAuthorNameAttribute(): string
    {
        if (! $this->relationLoaded('user')) {
            $this->load('user');
        }

        return $this->user ? trim($this->user->first_name.' '.$this->user->last_name) : 'System';
    }
}
