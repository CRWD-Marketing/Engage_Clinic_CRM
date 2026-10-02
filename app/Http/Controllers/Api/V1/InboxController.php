<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Whatsapp\WhatsappController;
use App\Models\WhatsappContact;
use App\Models\WhatsappMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * The WhatsApp / Instagram / Messenger inbox for the mobile app. Sending,
 * converting to a lead and changing the AI state are the web controller's
 * own actions; the list, a conversation and the poll are returned as data
 * here where the web renders HTML fragments.
 */
class InboxController extends WhatsappController
{
    /** GET /inbox — every conversation, latest activity first. */
    public function index(Request $request)
    {
        return response()->json($this->contacts());
    }

    /** GET /inbox/{contact} — the conversation; opening it marks it read. */
    public function thread(WhatsappContact $contact)
    {
        $messages = $contact->messages()->with('sentBy')->orderBy('sent_at')->get();

        if ($contact->unread_count > 0) {
            $contact->update(['unread_count' => 0]);
        }

        return response()->json([
            'contact' => $this->contactRow($contact->load('latestMessage.sentBy')),
            'messages' => $messages->map(fn (WhatsappMessage $m) => $this->messageRow($m))->values(),
            'family' => $this->family($contact, $messages),
        ]);
    }

    /** GET /inbox/poll?contact=&after= — the web poll's data, as JSON instead of rendered HTML. */
    public function poll(Request $request)
    {
        $after = (int) $request->query('after', 0);
        $contact = $request->filled('contact') ? WhatsappContact::find((int) $request->query('contact')) : null;

        $messages = collect();
        $statuses = [];
        $latest = $after;

        if ($contact) {
            // Outbound bubbles already on screen need their status kept current.
            $statuses = $contact->messages()->where('direction', 'outbound')->orderByDesc('id')->limit(30)
                ->get(['id', 'status', 'send_error'])
                ->map(fn ($m) => [
                    'id' => $m->id,
                    'status' => $m->status,
                    'label' => WhatsappMessage::statusLabel($m->status),
                    'error' => $m->send_error,
                ])->values()->all();

            $messages = $contact->messages()->with('sentBy')->where('id', '>', $after)->orderBy('sent_at')->get();

            if ($messages->isNotEmpty()) {
                // max(id), not the last by sent_at: webhooks can arrive out of order.
                $latest = (int) $messages->max('id');

                // The conversation is open in front of the user, so new arrivals count as read.
                if ($contact->unread_count > 0) {
                    $contact->update(['unread_count' => 0]);
                }
            }
        }

        return response()->json([
            'contacts' => $this->contacts(),
            'messages' => $messages->map(fn (WhatsappMessage $m) => $this->messageRow($m))->values(),
            'latest_message_id' => $latest,
            'statuses' => $statuses,
        ]);
    }

    /** POST /inbox/send {contact_id, message} */
    public function send(Request $request)
    {
        $id = parent::send($request)->getData(true)['latest_message_id'];

        return response()->json([
            'message' => $this->messageRow(WhatsappMessage::with('sentBy')->findOrFail($id)),
            'latest_message_id' => $id,
        ]);
    }

    /** POST /inbox/{contact}/ai-state {ai_state} */
    public function updateAiState(Request $request, WhatsappContact $contact)
    {
        parent::updateAiState($request, $contact);

        return response()->json(['contact' => $this->contactRow($contact->fresh()->load('latestMessage.sentBy'))]);
    }

    /** POST /inbox/{contact}/convert-to-lead — does nothing if the conversation is already linked to a lead. */
    public function convertToLead(WhatsappContact $contact)
    {
        parent::convertToLead($contact);
        $contact = $contact->fresh()->load(['latestMessage.sentBy', 'lead']);

        return response()->json(['contact' => $this->contactRow($contact), 'lead' => $contact->lead]);
    }

    // ---- Presenters ----------------------------------------------------

    private function contacts(): Collection
    {
        return WhatsappContact::with('latestMessage.sentBy')->orderByDesc('last_message_at')->get()
            ->map(fn (WhatsappContact $contact) => $this->contactRow($contact))->values();
    }

    private function contactRow(WhatsappContact $contact): array
    {
        return $contact->attributesToArray() + [
            // "AI", a staff name, or null: who sent the latest message.
            'last_responder' => $contact->latestMessage?->responderLabel(),
        ];
    }

    private function messageRow(WhatsappMessage $message): array
    {
        return $message->attributesToArray() + [
            'display_body' => $message->body ?? WhatsappMessage::fallbackLabel((string) $message->type),
            'responder_label' => $message->responderLabel(),
            'status_label' => WhatsappMessage::statusLabel($message->status),
        ];
    }

    /** The read-only "Family details" panel: the linked lead, or what the conversation suggests. */
    private function family(WhatsappContact $contact, Collection $messages): array
    {
        if ($lead = $contact->lead) {
            return [
                'child_name' => $lead->child_name,
                'child_age' => $lead->child_age,
                'interested_in' => $lead->interested_in,
                'source' => $lead->source,
                'first_contact_at' => $lead->created_at,
                'insurance' => $lead->insurance,
                'in_pipeline' => true,
            ];
        }

        $hints = $this->extractLeadHints($messages);

        return [
            'child_name' => $contact->child_name ?: $hints['child_name'],
            'child_age' => $hints['child_age'] !== null ? (string) $hints['child_age'] : null,
            'interested_in' => $contact->interested_in ?: $hints['interested_in'],
            'source' => ucfirst($contact->channel),
            'first_contact_at' => $contact->created_at,
            'insurance' => $contact->insurance ?: $hints['insurance'],
            'in_pipeline' => false,
        ];
    }
}
