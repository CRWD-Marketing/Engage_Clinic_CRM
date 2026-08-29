<?php

namespace App\Services\Messaging;

use App\Models\WhatsappContact;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class InstagramService implements OutboundMessagingServiceInterface
{
    public function sendText(WhatsappContact $contact, string $message): Response
    {
        $igBusinessId = config('services.instagram.business_account_id');

        // This app uses the "Instagram API with Instagram Login" flow (token is
        // issued via graph.instagram.com's OAuth exchange, not a Facebook Page
        // login) - every call for it must go through graph.instagram.com, since
        // graph.facebook.com rejects this token type outright (401).
        return Http::withToken(config('services.instagram.access_token'))
            ->post("https://graph.instagram.com/v21.0/{$igBusinessId}/messages", [
                'recipient' => ['id' => $contact->wa_id],
                'message' => ['text' => $message],
            ]);
    }
}
