<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Waitlist extends Model
{
    protected $table = 'waitlist_entries';

    protected $fillable = [
        'lead_id',
        'child_name',
        'child_age',
        'programme',
        'hours_per_week',
        'status',
        'joined_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'joined_at' => 'datetime',
        ];
    }

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    public function scopeWaiting($query)
    {
        return $query->where('status', 'waiting');
    }

    public function waitingWeeks(): int
    {
        return (int) round($this->joined_at->diffInWeeks(now()));
    }
}
