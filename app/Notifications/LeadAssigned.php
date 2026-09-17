<?php

namespace App\Notifications;

use App\Models\Lead;
use Illuminate\Notifications\Notification;

/**
 * "You've been assigned this lead" - sent to whoever a lead is newly
 * assigned or reassigned to. Database-only, topbar-bell alert.
 */
class LeadAssigned extends Notification
{
    public function __construct(private Lead $lead)
    {
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        $lead = $this->lead;
        $name = $lead->child_name ?: $lead->parent_guardian_name;

        return [
            'icon' => 'fa-user-check',
            'title' => "Lead assigned to you — {$name}",
            'subtitle' => $lead->phone,
            'url' => route('leads.index', ['lead' => $lead->id]),
        ];
    }
}
