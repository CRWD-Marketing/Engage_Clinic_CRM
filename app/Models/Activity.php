<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    protected $fillable = ['user_id', 'type', 'title', 'url'];

    protected $appends = ['actor_name'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getActorNameAttribute(): string
    {
        if (! $this->relationLoaded('user')) {
            $this->load('user');
        }

        return $this->user ? trim($this->user->first_name.' '.$this->user->last_name) : 'System';
    }

    /**
     * Log one line to the cross-module activity feed. The acting user
     * defaults to whoever is signed in - pass one explicitly for
     * system-triggered entries (a scheduled command, say) with none signed in.
     */
    public static function log(string $type, string $title, ?string $url = null, ?int $userId = null): self
    {
        return static::create([
            'user_id' => $userId ?? auth()->id(),
            'type' => $type,
            'title' => $title,
            'url' => $url,
        ]);
    }
}
