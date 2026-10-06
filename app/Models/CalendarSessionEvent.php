<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One line of a session's audit trail: booked, changed (what, from, to) or
 * deleted, and by whom. Written by CalendarSession's model events, so every
 * screen that saves a session is covered without each having to remember.
 */
class CalendarSessionEvent extends Model
{
    /**
     * The fields whose changes matter to scheduling and billing, with the
     * label each is reported under.
     */
    const TRACKED = [
        'therapist_id' => 'Therapist',
        'patient_id' => 'Client',
        'activity_type' => 'Service',
        'session_date' => 'Date',
        'start_time' => 'Start',
        'duration_minutes' => 'Duration (min)',
        'status' => 'Attendance / status',
        'cancel_reason' => 'Cancelled by',
        'cancel_notice_hours' => 'Notice (h)',
        'invoice_id' => 'Invoice',
    ];

    protected $fillable = ['calendar_session_id', 'user_id', 'event', 'summary', 'changes'];

    protected $casts = ['changes' => 'array'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
