<?php

namespace App\Jobs;

use App\Models\AiEmployeeLog;
use App\Models\AiEmployeeSettings;
use App\Models\WhatsappMessage;
use App\Services\AiEmployee\AIEmployeeService;
use App\Services\Messaging\OutboundMessagingResolver;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Runs the AI Employee for one inbound message, outside the webhook
 * request/response cycle so Meta always gets its fast 200 regardless of how
 * long the LLM call takes. Every guard here fails closed: if anything is
 * ambiguous (kill switch off, conversation not AI-owned, already handled),
 * the job quietly no-ops rather than risking a stray reply.
 */
class ProcessAiEmployeeReply implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /**
     * Light retry for transient LLM/network hiccups only - not for a model
     * that simply declined to answer (that's escalate=true, a valid outcome).
     */
    public array $backoff = [10, 60, 300];

    /**
     * Outer sanity ceiling for the sync-mode blocking sleep() below - not a
     * "safe" cap, just a backstop against a misconfigured huge number
     * hanging a web worker indefinitely. The configured response_delay_seconds
     * is honored in full up to this ceiling; set_time_limit() is used to ask
     * PHP for enough execution budget to actually get there.
     */
    private const MAX_SYNC_SLEEP_SECONDS = 120;

    public function __construct(public int $whatsappMessageId) {}

    public function handle(AIEmployeeService $aiEmployeeService, OutboundMessagingResolver $messagingResolver): void
    {
        // First line logged by this job for a given message - if a message's
        // "AI processing started" (logged at dispatch time) is never followed
        // by this, the job never actually ran: the queue isn't being drained
        // (no worker/cron), not anything about the AI/LLM itself.
        Log::info('[Messaging] AI reply job picked up', ['message_id' => $this->whatsappMessageId]);

        $inboundMessage = WhatsappMessage::find($this->whatsappMessageId);

        if (! $inboundMessage || ! $inboundMessage->isEligibleForAutoReply()) {
            Log::info('[Messaging] AI reply skipped - message not found or not eligible', ['message_id' => $this->whatsappMessageId]);

            return;
        }

        $contact = $inboundMessage->contact;
        $settings = AiEmployeeSettings::current();

        if (! $settings->is_enabled) {
            Log::info('[Messaging] AI reply skipped - AI Employee is OFF', ['message_id' => $inboundMessage->id]);

            return;
        }

        if (! $contact || ! $contact->isAiActive()) {
            Log::info('[Messaging] AI reply skipped - contact is not AI-active', ['message_id' => $inboundMessage->id, 'contact_id' => $contact?->id]);

            return;
        }

        if ($inboundMessage->ai_processing_status === WhatsappMessage::AI_STATUS_COMPLETED) {
            Log::info('[Messaging] AI reply skipped - already completed', ['message_id' => $inboundMessage->id]);

            return; // already handled - defends against a duplicate dispatch/queue redelivery
        }

        // The response delay is normally applied at dispatch time (see
        // WhatsappController::dispatchAiReplyIfEligible()) via delay() - by
        // the time this job runs, that wait is already over.
        //
        // Under `sync` (no persistent worker/cron to process a real delayed
        // queue), delay() is a no-op and this job runs immediately - a
        // previous version of this code tried to compensate by blocking here
        // with sleep() instead. That was reverted: confirmed in production
        // logs that Meta's webhook delivery treats a slow-to-respond
        // endpoint as a failed delivery and retries the SAME event roughly
        // every ~20 seconds, compounding the problem (each retry blocks
        // again) instead of solving it. There is no in-request trick that
        // can honor a real delay without breaking webhook delivery - it
        // requires an actual queued job that runs after the webhook has
        // already returned its fast acknowledgement, which in turn requires
        // a persistent queue worker or a cron-driven `queue:work` on the
        // `database` connection. Under `sync`, every eligible message is
        // answered immediately instead - correct, if instant, rather than a
        // delay that actively causes messages to go unanswered.

        // What's left to guard against here is consolidation: if a newer
        // inbound message has already arrived (e.g. the customer sent a
        // follow-up while this one was still waiting out its delay), let
        // that newer message's own (separately delayed) job answer
        // instead - it'll see this message in its conversation history
        // anyway, so nothing is lost, and the contact doesn't get two AI
        // replies fired in the same breath.
        $hasNewerInboundMessage = $contact->messages()
            ->where('direction', 'inbound')
            ->where('id', '>', $inboundMessage->id)
            ->exists();

        if ($hasNewerInboundMessage) {
            Log::info('[Messaging] AI reply skipped - newer message will answer instead', ['message_id' => $inboundMessage->id]);

            $inboundMessage->update(['ai_processing_status' => WhatsappMessage::AI_STATUS_SKIPPED]);

            return;
        }

        $inboundMessage->update(['ai_processing_status' => WhatsappMessage::AI_STATUS_PROCESSING]);

        try {
            Log::info('[Messaging] AI reply - calling LLM', ['message_id' => $inboundMessage->id]);

            $result = $aiEmployeeService->respondTo($contact, $inboundMessage);

            Log::info('[Messaging] AI reply - LLM responded', ['message_id' => $inboundMessage->id, 'escalate' => $result->escalate]);

            // Escalation means a human should also follow up - it does NOT
            // mean the customer gets left with silence instead of the AI's
            // reply. The model's escalate=true reply text is deliberately
            // written to be sendable on its own (e.g. "let me connect you
            // with our team"), per the system prompt - so it's always sent,
            // exactly like a normal reply, with escalation only adding the
            // staff-facing flag on top.
            $response = $messagingResolver->resolve($contact->channel)->sendText($contact, $result->reply);

            if ($response->failed()) {
                throw new \RuntimeException('Outbound send failed: HTTP '.$response->status().' '.$response->body());
            }

            $waMessageId = match ($contact->channel) {
                'instagram', 'facebook' => $response->json('message_id'),
                default => $response->json('messages.0.id'),
            };

            $outbound = $contact->messages()->create([
                'wa_message_id' => $waMessageId,
                'direction' => 'outbound',
                'type' => 'text',
                'body' => $result->reply,
                'status' => 'sent',
                'sent_at' => now(),
                'is_ai_generated' => true,
                'ai_processing_status' => WhatsappMessage::AI_STATUS_COMPLETED,
                'triggered_by_message_id' => $inboundMessage->id,
            ]);

            $contactUpdates = [
                // last_message_preview is varchar(255) - an AI reply (unlike the
                // short manual-send box) can easily run longer than that.
                'last_message_preview' => Str::limit($result->reply, 255, ''),
                'last_message_at' => now(),
            ];

            if ($result->escalate) {
                $contactUpdates['needs_human_attention'] = true;
                $contactUpdates['needs_human_reason'] = 'Model determined this needs human review.';
            }

            $contact->update($contactUpdates);

            $inboundMessage->update(['ai_processing_status' => WhatsappMessage::AI_STATUS_COMPLETED]);

            Log::info('[Messaging] AI reply sent successfully', [
                'message_id' => $inboundMessage->id,
                'outbound_message_id' => $outbound->id,
                'channel' => $contact->channel,
                'escalate' => $result->escalate,
            ]);

            AiEmployeeLog::create([
                'whatsapp_contact_id' => $contact->id,
                'inbound_message_id' => $inboundMessage->id,
                'outbound_message_id' => $outbound->id,
                'knowledge_base_entry_ids' => $result->knowledgeBaseEntryIds,
                'escalated' => $result->escalate,
                'status' => $result->escalate ? AiEmployeeLog::STATUS_ESCALATED : AiEmployeeLog::STATUS_SUCCESS,
            ]);
        } catch (\Throwable $e) {
            Log::error('[Messaging] AI reply failed', [
                'message_id' => $this->whatsappMessageId,
                'attempt' => $this->attempts(),
                'error' => $e->getMessage(),
                'exception' => $e,
            ]);

            $inboundMessage->update([
                'ai_processing_status' => WhatsappMessage::AI_STATUS_FAILED,
                'ai_error' => $e->getMessage(),
            ]);

            throw $e; // let the queue's retry/backoff run - failed() below handles the terminal case
        }
    }

    /**
     * Final attempt exhausted - never fail silently. Flag the human side
     * instead of leaving a family's message unanswered with no visible signal.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('[Messaging] AI reply gave up after all retries', [
            'message_id' => $this->whatsappMessageId,
            'error' => $exception->getMessage(),
        ]);

        $inboundMessage = WhatsappMessage::find($this->whatsappMessageId);

        if (! $inboundMessage) {
            return; // message was deleted since dispatch - nothing left to flag
        }

        $inboundMessage->update([
            'ai_processing_status' => WhatsappMessage::AI_STATUS_FAILED,
            'ai_error' => $exception->getMessage(),
        ]);

        $inboundMessage->contact?->update([
            'needs_human_attention' => true,
            'needs_human_reason' => 'AI Employee failed to respond after retries: '.$exception->getMessage(),
        ]);

        AiEmployeeLog::create([
            'whatsapp_contact_id' => $inboundMessage->whatsapp_contact_id,
            'inbound_message_id' => $inboundMessage->id,
            'status' => AiEmployeeLog::STATUS_ERROR,
            'error_message' => $exception->getMessage(),
        ]);
    }
}
