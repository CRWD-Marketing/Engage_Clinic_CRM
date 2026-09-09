<?php

namespace App\Services\Voice;

use Illuminate\Support\Facades\Http;

/**
 * Talks to a self-hosted faster-whisper HTTP wrapper. Assumes a minimal
 * POST /transcribe endpoint accepting a multipart "audio" file, returning
 * {"text": "..."}. Swapping to a cloud STT provider later means writing a
 * new SpeechToTextService implementation and changing one binding in
 * VoiceServiceProvider, nothing else - same pattern as OllamaLlmClient.
 */
class FasterWhisperSttClient implements SpeechToTextService
{
    public function transcribe(string $audio): string
    {
        $response = Http::baseUrl(config('voice.stt.base_url'))
            ->timeout((int) config('voice.stt.timeout', 15))
            ->attach('audio', $audio, 'utterance.wav')
            ->post('/transcribe');

        if ($response->failed()) {
            throw new \RuntimeException('STT request failed: HTTP '.$response->status().' '.$response->body());
        }

        return trim((string) $response->json('text'));
    }
}
