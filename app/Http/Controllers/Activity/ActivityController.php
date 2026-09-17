<?php

namespace App\Http\Controllers\Activity;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\LeadActivity;
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    /**
     * Topbar "Activity" dropdown: lead notes/assignment changes, plus
     * cross-module events (calendar reassignments, billing) logged to the
     * generic Activity model - merged into one feed, newest first.
     */
    public function index(Request $request)
    {
        $items = [];

        if ($request->user()->canAccessFeature('leads')) {
            $items = array_merge($items, LeadActivity::with(['lead', 'user'])
                ->latest()
                ->limit(20)
                ->get()
                ->map(fn (LeadActivity $activity) => [
                    'icon' => $activity->type === LeadActivity::TYPE_ASSIGNMENT ? 'fa-user-check' : 'fa-sticky-note',
                    'title' => $activity->body,
                    'actor' => $activity->author_name,
                    'url' => $activity->lead ? route('leads.index', ['lead' => $activity->lead->id]) : null,
                    'timestamp' => $activity->created_at,
                    'created_at' => $activity->created_at->diffForHumans(),
                ])
                ->all());
        }

        $items = array_merge($items, Activity::with('user')
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn (Activity $activity) => [
                'icon' => match ($activity->type) {
                    'session_moved' => 'fa-calendar-days',
                    'credit_note' => 'fa-file-invoice-dollar',
                    'invoice_voided' => 'fa-ban',
                    'claim_status' => 'fa-file-medical',
                    'bulk_run' => 'fa-layer-group',
                    default => 'fa-clock-rotate-left',
                },
                'title' => $activity->title,
                'actor' => $activity->actor_name,
                'url' => $activity->url,
                'timestamp' => $activity->created_at,
                'created_at' => $activity->created_at->diffForHumans(),
            ])
            ->all());

        usort($items, fn ($a, $b) => $b['timestamp']->timestamp <=> $a['timestamp']->timestamp);
        $items = array_map(function ($item) {
            unset($item['timestamp']);

            return $item;
        }, array_slice($items, 0, 20));

        return response()->json(['items' => $items]);
    }
}
