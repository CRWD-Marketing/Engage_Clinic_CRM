<?php

namespace App\Jobs;

use App\Models\VoiceCallSession;
use App\Services\AiEmployee\LlmClientInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Runs once a voice call ends. Reuses LlmClientInterface directly (not
 * AIEmployeeService - that class is the live-reply brain, tied to the
 * reply/escalate JSON contract for one turn, not a whole-transcript
 * summary). Not latency-sensitive like a live turn, so this is queued.
 */
class GenerateVoiceCallSummary implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [30, 120, 300];

    public function __construct(public int $voiceCallSessionId) {}

    public function handle(LlmClientInterface $llm): void
    {
        $session = VoiceCallSession::find($this->voiceCallSessionId);

        if (! $session) {
            return;
        }

        $session->update(['summary_status' => VoiceCallSession::SUMMARY_PROCESSING]);

        $transcript = $session->transcriptMessages()->get()
            ->map(fn ($m) => ($m->direction === 'inbound' ? 'Caller' : 'AI Employee').': '.$m->body)
            ->implode("\n");

        $prompt = <<<PROMPT
You are summarizing a completed phone call transcript between a caller and
Engage Clinic's AI Employee, for an internal CRM record. Respond with ONLY a
single JSON object, no other text before or after it, in exactly this shape:
{"intent": "<short phrase>", "sentiment": "positive|neutral|negative|distressed", "outcome": "<short phrase>", "follow_up_required": true or false, "notes": "<1-2 sentence summary>"}
Do not wrap it in markdown code fences. Do not add explanation.

TRANSCRIPT:
{$transcript}
PROMPT;

        try {
            // A bare system-role message gets an empty response from the
            // configured model (confirmed while testing this job) - the
            // instruction has to travel as the user turn, same as any
            // normal one-shot completion request.
            $raw = trim($llm->chat([['role' => 'user', 'content' => $prompt]], ['temperature' => 0.1]));
            $summary = $this->decodeSummaryJson($raw);

            if ($summary === null) {
                $summary = ['notes' => $raw];
            }

            $session->update(['summary' => $summary, 'summary_status' => VoiceCallSession::SUMMARY_COMPLETED]);
        } catch (\Throwable $e) {
            Log::error('Voice call summary generation failed', ['session_id' => $this->voiceCallSessionId, 'exception' => $e]);
            $session->update(['summary_status' => VoiceCallSession::SUMMARY_FAILED]);
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        VoiceCallSession::find($this->voiceCallSessionId)?->update(['summary_status' => VoiceCallSession::SUMMARY_FAILED]);
    }

    /**
     * Same leniency as AIEmployeeService::decodeJsonReply() - models don't
     * always skip the markdown fence/preamble despite being told not to.
     */
    private function decodeSummaryJson(string $raw): ?array
    {
        $candidates = [$raw];

        if (preg_match('/\{.*\}/s', $raw, $matches)) {
            $candidates[] = $matches[0];
        }

        foreach ($candidates as $candidate) {
            $decoded = json_decode($candidate, true);

            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }
}
