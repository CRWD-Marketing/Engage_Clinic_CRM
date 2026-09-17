<?php

namespace App\Http\Controllers\Notification;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Lead;
use App\Models\WhatsappContact;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Topbar bell dropdown: new leads, new contact submissions, and unread WhatsApp threads.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $items = [];

        if ($user->canAccessFeature('leads')) {
            $items = array_merge($items, Lead::where('status', Lead::STATUS_NEW)
                ->latest()
                ->limit(8)
                ->get()
                ->map(fn (Lead $lead) => [
                    'icon' => 'fa-filter',
                    'title' => 'New lead: '.($lead->child_name ?: $lead->parent_guardian_name),
                    'subtitle' => $lead->phone,
                    'url' => route('leads.index', ['lead' => $lead->id]),
                    'timestamp' => $lead->created_at,
                    'created_at' => $lead->created_at->diffForHumans(),
                ])
                ->all());
        }

        if ($user->canAccessFeature('contacts')) {
            $items = array_merge($items, Contact::where('status', Contact::STATUS_NEW)
                ->latest()
                ->limit(8)
                ->get()
                ->map(fn (Contact $contact) => [
                    'icon' => 'fa-envelope',
                    'title' => 'New message from '.$contact->name,
                    'subtitle' => $contact->phone ?: $contact->email,
                    'url' => route('contacts.index', ['contact' => $contact->id]),
                    'timestamp' => $contact->created_at,
                    'created_at' => $contact->created_at->diffForHumans(),
                ])
                ->all());
        }

        if ($user->canAccessFeature('whatsapp')) {
            $items = array_merge($items, WhatsappContact::where('unread_count', '>', 0)
                ->latest('last_message_at')
                ->limit(8)
                ->get()
                ->map(fn (WhatsappContact $contact) => [
                    'icon' => 'fa-whatsapp',
                    'title' => ($contact->name ?: $contact->wa_id).' · '.$contact->unread_count.' unread',
                    'subtitle' => $contact->last_message_preview,
                    'url' => route('whatsapp.index', ['contact' => $contact->id]),
                    'timestamp' => $contact->last_message_at,
                    'created_at' => optional($contact->last_message_at)->diffForHumans(),
                ])
                ->all());
        }

        // "You were assigned/scheduled" alerts - real per-user notifications
        // with their own read state, unlike the feeds above (which are
        // derived live from record status and have no concept of "read").
        $items = array_merge($items, $user->notifications()
            ->latest()
            ->limit(15)
            ->get()
            ->map(fn ($n) => [
                'id' => $n->id,
                'icon' => $n->data['icon'] ?? 'fa-bell',
                'title' => $n->data['title'] ?? '',
                'subtitle' => $n->data['subtitle'] ?? '',
                'url' => $n->data['url'] ?? '#',
                'read' => $n->read_at !== null,
                'timestamp' => $n->created_at,
                'created_at' => $n->created_at->diffForHumans(),
            ])
            ->all());

        usort($items, fn ($a, $b) => (optional($b['timestamp'])->timestamp ?? 0) <=> (optional($a['timestamp'])->timestamp ?? 0));
        $items = array_map(function ($item) {
            unset($item['timestamp']);
            $item += ['read' => true];

            return $item;
        }, array_slice($items, 0, 15));

        $count = ($user->canAccessFeature('leads') ? Lead::where('status', Lead::STATUS_NEW)->count() : 0)
            + ($user->canAccessFeature('contacts') ? Contact::where('status', Contact::STATUS_NEW)->count() : 0)
            + ($user->canAccessFeature('whatsapp') ? (int) WhatsappContact::sum('unread_count') : 0)
            + $user->unreadNotifications()->count();

        return response()->json([
            'count' => $count,
            'items' => $items,
        ]);
    }

    /**
     * Mark one database notification read - fired when its topbar-bell row
     * is clicked. The derived feeds (new leads, unread WhatsApp, etc.) have
     * no per-item read state, so this only ever targets a real notification.
     */
    public function read(Request $request, string $id)
    {
        $request->user()->notifications()->where('id', $id)->first()?->markAsRead();

        return response()->json(['ok' => true]);
    }
}
