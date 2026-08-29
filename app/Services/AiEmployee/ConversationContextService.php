<?php

namespace App\Services\AiEmployee;

use App\Models\WhatsappContact;
use App\Models\WhatsappMessage;

class ConversationContextService
{
    /**
     * Bounded prior conversation history for $contact, oldest first, excluding
     * the triggering message itself (the caller appends that separately) - an
     * easy off-by-one to get wrong, since a naive "all messages" query would
     * double-count the very message that's currently being responded to.
     *
     * @return array<int, array{role: string, content: string}>
     */
    public function historyFor(WhatsappContact $contact, WhatsappMessage $triggeringMessage, int $maxMessages): array
    {
        return $contact->messages()
            ->whereNotNull('body')
            ->where('id', '<', $triggeringMessage->id)
            ->orderByDesc('sent_at')
            ->limit($maxMessages)
            ->get()
            ->reverse()
            ->map(fn (WhatsappMessage $message) => [
                'role' => $message->direction === 'inbound' ? 'user' : 'assistant',
                'content' => (string) $message->body,
            ])
            ->values()
            ->all();
    }
}
