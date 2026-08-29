<?php

namespace App\Services\Messaging;

use App\Models\WhatsappContact;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class FacebookMessengerService implements OutboundMessagingServiceInterface
{
    public function sendText(WhatsappContact $contact, string $message): Response
    {
        $apiVersion = config('services.facebook.api_version');

        return Http::withToken(config('services.facebook.page_access_token'))
            ->post("https://graph.facebook.com/{$apiVersion}/me/messages", [
                'recipient' => ['id' => $contact->wa_id],
                'message' => ['text' => $message],
                'messaging_type' => 'RESPONSE',
            ]);
    }
}
