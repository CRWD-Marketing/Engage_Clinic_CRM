<?php

namespace App\Notifications;

use App\Models\CalendarSession;
use Illuminate\Notifications\Notification;

/**
 * "You've been scheduled" - sent to a therapist when they're booked onto a
 * session or a session is reassigned to them. Database-only: this is a
 * topbar-bell alert, not an email/SMS.
 */
class SessionAssigned extends Notification
{
    public function __construct(private CalendarSession $session, private bool $reassigned = false)
    {
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        $s = $this->session;
        $when = $s->session_date->format('D j M').' · '.substr($s->start_time, 0, 5);

        return [
            'icon' => 'fa-calendar-check',
            'title' => $this->reassigned ? "Session reassigned to you — {$s->displayName()}" : "You've been scheduled — {$s->displayName()}",
            'subtitle' => $when,
            'url' => route('calendar.index', ['session' => $s->id]),
        ];
    }
}
