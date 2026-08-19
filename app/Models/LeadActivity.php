<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeadActivity extends Model
{
    const TYPE_NOTE = 'note';
    const TYPE_ASSIGNMENT = 'assignment';

    protected $fillable = [
        'lead_id',
        'user_id',
        'type',
        'body',
    ];

    protected $appends = ['author_name'];

    public function lead()
    {
        return $this->belongsTo(Lead::class);
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
