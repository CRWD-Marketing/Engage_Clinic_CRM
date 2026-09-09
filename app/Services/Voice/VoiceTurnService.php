<?php

namespace App\Services\Voice;

use App\Models\AiEmployeeLog;
use App\Models\AiEmployeeSettings;
use App\Models\VoiceCallSession;
use App\Services\AiEmployee\AIEmployeeService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * The live-call counterpart to ProcessAiEmployeeReply: same guards
 * (kill switch, contact must be AI-active), same AIEmployeeService reuse,
 * same escalate->needs_human_attention wiring and AiEmployeeLog audit trail
 * - but run synchronously in the request instead of via the delayed queue,
 * since a live call needs an answer now, not after a configured delay.
 */
class VoiceTurnService
{
    public function __construct(private AIEmployeeService $aiEmployeeService) {}

    /**
     * @return array{handled: bool, reply_text?: string, escalate?: bool}
     */
    public function handleTurn(VoiceCallSession $session, string $transcript): array
    {
        $settings = AiEmployeeSettings::current();
        $contact = $session->contact;

        if (! $settings->voice_enabled || ! $contact->isAiActive()) {
            return ['handled' => false];
        }

        $inbound = $contact->messages()->create([
            'voice_call_session_id' => $session->id,
            'direction' => 'inbound',
            'type' => 'voice_turn',
            'body' => $transcript,
            'status' => 'received',
            'sent_at' => now(),
        ]);

        try {
            $result = $this->aiEmployeeService->respondTo($contact, $inbound);
        } catch (\Throwable $e) {
            Log::error('Voice AI Employee turn failed', ['session_id' => $session->id, 'exception' => $e]);

            $contact->update([
                'needs_human_attention' => true,
                'needs_human_reason' => 'AI Employee failed mid-call: '.$e->getMessage(),
            ]);

            $session->update(['escalated' => true, 'escalation_reason' => 'AI failure mid-call']);

            AiEmployeeLog::create([
                'whatsapp_contact_id' => $contact->id,
                'inbound_message_id' => $inbound->id,
                'status' => AiEmployeeLog::STATUS_ERROR,
                'error_message' => $e->getMessage(),
            ]);

            return ['handled' => false];
        }

        $outbound = $contact->messages()->create([
            'voice_call_session_id' => $session->id,
            'direction' => 'outbound',
            'type' => 'voice_turn',
            'body' => $result->reply,
            'status' => 'sent',
            'sent_at' => now(),
            'is_ai_generated' => true,
            'triggered_by_message_id' => $inbound->id,
        ]);

        $contact->update([
            'last_message_preview' => Str::limit($result->reply, 255, ''),
            'last_message_at' => now(),
        ]);

        if ($result->escalate) {
            $contact->update([
                'needs_human_attention' => true,
                'needs_human_reason' => 'Model determined this needs human review (voice call).',
            ]);

            $session->update(['escalated' => true, 'escalation_reason' => 'Model requested escalation']);
        }

        AiEmployeeLog::create([
            'whatsapp_contact_id' => $contact->id,
            'inbound_message_id' => $inbound->id,
            'outbound_message_id' => $outbound->id,
            'knowledge_base_entry_ids' => $result->knowledgeBaseEntryIds,
            'escalated' => $result->escalate,
            'status' => $result->escalate ? AiEmployeeLog::STATUS_ESCALATED : AiEmployeeLog::STATUS_SUCCESS,
        ]);

        return ['handled' => true, 'reply_text' => $result->reply, 'escalate' => $result->escalate];
    }
}
