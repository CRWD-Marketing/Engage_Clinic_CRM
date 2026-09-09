<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PatientGoal extends Model
{
    protected $fillable = [
        'patient_id',
        'title',
        'progress_percent',
    ];

    protected $casts = [
        'progress_percent' => 'integer',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function sessions()
    {
        return $this->belongsToMany(CalendarSession::class, 'session_goals');
    }

    /**
     * How many of this patient's last $n calendar sessions this goal was
     * marked as worked on - powers the "used in 9 of the last 10 sessions"
     * line in the goals-worked-on-today widget.
     */
    public function sessionsInLast(int $n): int
    {
        $recentSessionIds = $this->patient->calendarSessions()
            ->orderByDesc('session_date')
            ->limit($n)
            ->pluck('id');

        return $this->sessions()->whereIn('calendar_sessions.id', $recentSessionIds)->count();
    }

    /**
     * The most recent session date this goal was worked on, if any.
     */
    public function lastUsedAt(): ?\Illuminate\Support\Carbon
    {
        return $this->sessions()->orderByDesc('session_date')->first()?->session_date;
    }
}
