<?php

namespace App\Services\Voice;

use Illuminate\Support\Facades\Http;

/**
 * Talks to AssemblyAI's hosted transcription API: upload the audio, submit a
 * transcription job, poll until it completes. Unlike FasterWhisperSttClient
 * (a single synchronous call), this is inherently an upload+submit+poll
 * flow - AssemblyAI has a separate real-time WebSocket API that would suit
 * a live call's latency needs better, but this REST flow is the standard
 * integration path and keeps the same one-shot SpeechToTextService contract
 * the bridge already expects; swapping to the streaming API later means
 * writing a new implementation, same as any other STT provider swap.
 */
class AssemblyAiSttClient implements SpeechToTextService
{
    public function transcribe(string $audio): string
    {
        // Deliberately a fresh PendingRequest per call rather than one
        // shared/reused instance - Http::withBody() mutates the request's
        // body-format state in place (it's not an immutable clone), so
        // reusing the same instance for the JSON /transcript call after
        // calling withBody() for the raw-audio /upload call corrupted the
        // second request (confirmed while testing: AssemblyAI rejected it
        // with "json data invalid" even though the JSON array was correct).
        $uploadResponse = $this->request()
            ->withBody($audio, 'application/octet-stream')
            ->post('/upload');

        if ($uploadResponse->failed()) {
            throw new \RuntimeException('AssemblyAI upload failed: HTTP '.$uploadResponse->status().' '.$uploadResponse->body());
        }

        $uploadUrl = $uploadResponse->json('upload_url');

        $transcriptResponse = $this->request()->post('/transcript', ['audio_url' => $uploadUrl]);

        if ($transcriptResponse->failed()) {
            throw new \RuntimeException('AssemblyAI transcript request failed: HTTP '.$transcriptResponse->status().' '.$transcriptResponse->body());
        }

        $transcriptId = $transcriptResponse->json('id');

        return $this->pollUntilComplete($transcriptId);
    }

    private function request(): \Illuminate\Http\Client\PendingRequest
    {
        return Http::baseUrl(config('voice.stt.assemblyai.base_url'))
            ->timeout((int) config('voice.stt.timeout', 15))
            ->withHeaders(['authorization' => config('voice.stt.assemblyai.api_key')]);
    }

    private function pollUntilComplete(string $transcriptId): string
    {
        $pollIntervalMs = (int) config('voice.stt.assemblyai.poll_interval_ms', 500);
        $timeoutMs = (int) config('voice.stt.assemblyai.poll_timeout_ms', 12000);
        $elapsedMs = 0;

        while ($elapsedMs < $timeoutMs) {
            $response = $this->request()->get("/transcript/{$transcriptId}");

            if ($response->failed()) {
                throw new \RuntimeException('AssemblyAI poll failed: HTTP '.$response->status().' '.$response->body());
            }

            $status = $response->json('status');

            if ($status === 'completed') {
                return trim((string) $response->json('text'));
            }

            if ($status === 'error') {
                throw new \RuntimeException('AssemblyAI transcription failed: '.$response->json('error'));
            }

            usleep($pollIntervalMs * 1000);
            $elapsedMs += $pollIntervalMs;
        }

        throw new \RuntimeException("AssemblyAI transcription timed out after {$timeoutMs}ms (transcript id: {$transcriptId}).");
    }
}
