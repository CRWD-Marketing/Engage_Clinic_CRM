<?php

namespace App\Services\Voice;

use Illuminate\Support\Facades\Http;

/**
 * Talks to a self-hosted Piper TTS HTTP wrapper. Assumes a minimal
 * POST /synthesize endpoint accepting {"text", "voice"} JSON, returning raw
 * WAV bytes in the response body. Swapping to a cloud TTS provider later
 * means writing a new TextToSpeechService implementation and changing one
 * binding in VoiceServiceProvider, nothing else.
 */
class PiperTtsClient implements TextToSpeechService
{
    public function synthesize(string $text): string
    {
        $response = Http::baseUrl(config('voice.tts.base_url'))
            ->timeout((int) config('voice.tts.timeout', 15))
            ->post('/synthesize', [
                'text' => $text,
                'voice' => config('voice.tts.voice'),
            ]);

        if ($response->failed()) {
            throw new \RuntimeException('TTS request failed: HTTP '.$response->status().' '.$response->body());
        }

        return $response->body();
    }
}
