<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobApplicationNote extends Model
{
    protected $fillable = [
        'job_application_id',
        'user_id',
        'body',
    ];

    protected $appends = ['author_name'];

    public function jobApplication()
    {
        return $this->belongsTo(JobApplication::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getAuthorNameAttribute(): string
    {
        if (! $this->relationLoaded('user')) {
            $this->load('user');
        }

        return $this->user ? trim($this->user->first_name.' '.$this->user->last_name) : 'System';
    }
}
