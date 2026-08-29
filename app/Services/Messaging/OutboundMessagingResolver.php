<?php

namespace App\Services\Messaging;

class OutboundMessagingResolver
{
    public function resolve(string $channel): OutboundMessagingServiceInterface
    {
        return match ($channel) {
            'instagram' => app(InstagramService::class),
            'facebook' => app(FacebookMessengerService::class),
            default => app(WhatsAppService::class),
        };
    }
}
