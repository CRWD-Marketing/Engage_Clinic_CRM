<?php

namespace App\Services\Messaging;

use App\Models\WhatsappContact;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class WhatsAppService implements OutboundMessagingServiceInterface
{
    public function sendText(WhatsappContact $contact, string $message): Response
    {
        $phoneNumberId = config('services.whatsapp.phone_number_id');

        return Http::withToken(config('services.whatsapp.access_token'))
            ->post("https://graph.facebook.com/v21.0/{$phoneNumberId}/messages", [
                'messaging_product' => 'whatsapp',
                'to' => $contact->wa_id,
                'type' => 'text',
                'text' => ['body' => $message],
            ]);
    }
}
