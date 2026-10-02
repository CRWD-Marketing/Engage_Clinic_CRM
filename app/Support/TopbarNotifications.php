<?php

namespace App\Support;

use App\Models\Contact;
use App\Models\Lead;
use App\Models\User;
use App\Models\WhatsappContact;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * The topbar bell: new leads, new contact submissions, unread WhatsApp
 * threads and the user's own database notifications - with per-user read
 * state for all four, so the badge only counts what this user hasn't seen.
 *
 * The first three are derived live from record status. Their read state is
 * a row in notification_reads (clicked items) plus the user's
 * notifications_cleared_at ("Mark all as read").
 */
class TopbarNotifications
{
    private const PER_FEED = 8;

    private const MAX_ITEMS = 15;

    private array $readKeys;

    private function __construct(private User $user)
    {
        $this->readKeys = DB::table('notification_reads')->where('user_id', $user->id)->pluck('item_key')->all();
    }

    public static function for(User $user): self
    {
        return new self($user);
    }

    public function count(): int
    {
        return ($this->user->canAccessFeature('leads') ? $this->unread($this->leads(), 'lead')->count() : 0)
            + ($this->user->canAccessFeature('contacts') ? $this->unread($this->contacts(), 'contact')->count() : 0)
            + ($this->user->canAccessFeature('whatsapp') ? $this->unreadWhatsappThreads()->sum('unread_count') : 0)
            + $this->user->unreadNotifications()->count();
    }

    public function items(): array
    {
        $items = [];

        if ($this->user->canAccessFeature('leads')) {
            $unread = $this->unread($this->leads(), 'lead')->pluck('id')->flip();
            foreach ($this->leads()->latest()->limit(self::PER_FEED)->get() as $lead) {
                $items[] = [
                    'id' => 'lead:'.$lead->id,
                    'icon' => 'fa-filter',
                    'title' => 'New lead: '.($lead->child_name ?: $lead->parent_guardian_name),
                    'subtitle' => $lead->phone,
                    'url' => route('leads.index', ['lead' => $lead->id]),
                    'read' => ! $unread->has($lead->id),
                    'timestamp' => $lead->created_at,
                ];
            }
        }

        if ($this->user->canAccessFeature('contacts')) {
            $unread = $this->unread($this->contacts(), 'contact')->pluck('id')->flip();
            foreach ($this->contacts()->latest()->limit(self::PER_FEED)->get() as $contact) {
                $items[] = [
                    'id' => 'contact:'.$contact->id,
                    'icon' => 'fa-envelope',
                    'title' => 'New message from '.$contact->name,
                    'subtitle' => $contact->phone ?: $contact->email,
                    'url' => route('contacts.index', ['contact' => $contact->id]),
                    'read' => ! $unread->has($contact->id),
                    'timestamp' => $contact->created_at,
                ];
            }
        }

        if ($this->user->canAccessFeature('whatsapp')) {
            $unread = $this->unreadWhatsappThreads()->keyBy('id');
            foreach (WhatsappContact::where('unread_count', '>', 0)->latest('last_message_at')->limit(self::PER_FEED)->get() as $contact) {
                $items[] = [
                    'id' => $this->whatsappKey($contact),
                    'icon' => 'fa-whatsapp',
                    'title' => ($contact->name ?: $contact->wa_id).' · '.$contact->unread_count.' unread',
                    'subtitle' => $contact->last_message_preview,
                    'url' => route('whatsapp.index', ['contact' => $contact->id]),
                    'read' => ! $unread->has($contact->id),
                    // One thread carries several unread messages in the badge.
                    'weight' => (int) $contact->unread_count,
                    'timestamp' => $contact->last_message_at,
                ];
            }
        }

        // "You were assigned/scheduled" alerts - real database notifications.
        foreach ($this->user->notifications()->latest()->limit(self::MAX_ITEMS)->get() as $n) {
            $items[] = [
                'id' => $n->id,
                'icon' => $n->data['icon'] ?? 'fa-bell',
                'title' => $n->data['title'] ?? '',
                'subtitle' => $n->data['subtitle'] ?? '',
                'url' => $n->data['url'] ?? '#',
                'read' => $n->read_at !== null,
                'timestamp' => $n->created_at,
            ];
        }

        usort($items, fn ($a, $b) => (optional($b['timestamp'])->timestamp ?? 0) <=> (optional($a['timestamp'])->timestamp ?? 0));

        return array_map(function ($item) {
            $item['created_at'] = optional($item['timestamp'])->diffForHumans();
            $item += ['weight' => 1];
            unset($item['timestamp']);

            return $item;
        }, array_slice($items, 0, self::MAX_ITEMS));
    }

    /**
     * Mark one bell row read - a derived item's key (lead:12, contact:7,
     * whatsapp:3:1790000000) or a database notification's id.
     */
    public function markRead(string $key): void
    {
        if (preg_match('/^(lead|contact):\d+$|^whatsapp:\d+:\d+$/', $key)) {
            DB::table('notification_reads')->insertOrIgnore([
                'user_id' => $this->user->id,
                'item_key' => $key,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->readKeys[] = $key;

            return;
        }

        $this->user->notifications()->where('id', $key)->first()?->markAsRead();
    }

    public function markAllRead(): void
    {
        $this->user->forceFill(['notifications_cleared_at' => now()])->save();
        $this->user->unreadNotifications()->update(['read_at' => now()]);

        // Everything older than the cleared mark is read anyway.
        DB::table('notification_reads')->where('user_id', $this->user->id)->delete();
        $this->readKeys = [];
    }

    private function leads(): Builder
    {
        return Lead::where('status', Lead::STATUS_NEW);
    }

    private function contacts(): Builder
    {
        return Contact::where('status', Contact::STATUS_NEW);
    }

    /**
     * Narrow a feed to what this user hasn't read: newer than their
     * "mark all as read" and not individually clicked.
     */
    private function unread(Builder $query, string $prefix): Builder
    {
        $ids = [];
        foreach ($this->readKeys as $key) {
            if (str_starts_with($key, $prefix.':')) {
                $ids[] = (int) substr($key, strlen($prefix) + 1);
            }
        }

        return $query
            ->when($this->user->notifications_cleared_at, fn ($q, $at) => $q->where('created_at', '>', $at))
            ->when($ids, fn ($q) => $q->whereNotIn('id', $ids));
    }

    /**
     * A thread is keyed by its latest message time, so one that was read
     * comes back as unread when a new message arrives.
     */
    private function unreadWhatsappThreads()
    {
        return WhatsappContact::where('unread_count', '>', 0)
            ->when($this->user->notifications_cleared_at, fn ($q, $at) => $q->where('last_message_at', '>', $at))
            ->get(['id', 'unread_count', 'last_message_at'])
            ->reject(fn (WhatsappContact $c) => in_array($this->whatsappKey($c), $this->readKeys, true));
    }

    private function whatsappKey(WhatsappContact $contact): string
    {
        return 'whatsapp:'.$contact->id.':'.(optional($contact->last_message_at)->timestamp ?? 0);
    }
}
