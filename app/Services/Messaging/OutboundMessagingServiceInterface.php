<?php

namespace App\Services\Messaging;

use App\Models\WhatsappContact;
use Illuminate\Http\Client\Response;

interface OutboundMessagingServiceInterface
{
    public function sendText(WhatsappContact $contact, string $message): Response;
}
