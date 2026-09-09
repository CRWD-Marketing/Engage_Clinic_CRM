<?php

namespace App\Http\Controllers\Voice;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateVoiceCallSummary;
use App\Models\AiEmployeeSettings;
use App\Models\VoiceCallSession;
use App\Models\WhatsappContact;
use App\Services\Voice\VoiceTurnService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Service-to-service endpoints called by the separate always-on Node voice
 * bridge (never by a browser) - authenticated with a shared secret, the same
 * pattern as ProcessQueueController's cron_secret. The bridge only ever
 * exchanges transcript/reply text here, never audio - Laravel remains the
 * sole AI brain (AIEmployeeService/Ollama/KB/settings), same as chat.
 */
class VoiceBridgeController extends Controller
{
    private const DEFAULT_GREETING = "Thanks for calling. How can I help you today?";

    private const AI_UNAVAILABLE_LINE = "I'm sorry, I'm having trouble understanding right now - let me get you to a team member.";

    public function __construct(private VoiceTurnService $voiceTurnService)
    {
        $this->authenticateBridge();
    }

    /**
     * Called once the bridge has a live Media Streams connection for a new
     * inbound call. Creates the voice contact + session and hands back the
     * configured greeting for the bridge to synthesize and play.
     */
    public function startCall(Request $request)
    {
        $validated = $request->validate([
            'provider_call_sid' => ['required', 'string'],
            'from_number' => ['required', 'string'],
            'to_number' => ['required', 'string'],
        ]);

        $settings = AiEmployeeSettings::current();

        if (! $settings->voice_enabled) {
            return response()->json(['message' => 'Voice AI Employee is disabled.'], 403);
        }

        $existing = VoiceCallSession::where('provider_call_sid', $validated['provider_call_sid'])->first();

        if ($existing) {
            // Bridge retried call-start for the same call (e.g. a reconnect) -
            // hand back the same session rather than creating a duplicate.
            return response()->json([
                'session_id' => $existing->id,
                'greeting_text' => $settings->voice_greeting ?: self::DEFAULT_GREETING,
            ]);
        }

        $contact = WhatsappContact::firstOrCreate(
            ['wa_id' => 'voice:'.$validated['from_number']],
            ['channel' => 'voice', 'ai_state' => WhatsappContact::AI_STATE_ACTIVE]
        );

        $session = $contact->voiceCallSessions()->create([
            'provider' => 'twilio',
            'provider_call_sid' => $validated['provider_call_sid'],
            'from_number' => $validated['from_number'],
            'to_number' => $validated['to_number'],
            'status' => VoiceCallSession::STATUS_IN_PROGRESS,
            'started_at' => now(),
        ]);

        $greeting = $settings->voice_greeting ?: self::DEFAULT_GREETING;

        $contact->messages()->create([
            'voice_call_session_id' => $session->id,
            'direction' => 'outbound',
            'type' => 'voice_turn',
            'body' => $greeting,
            'status' => 'sent',
            'sent_at' => now(),
            'is_ai_generated' => true,
        ]);

        return response()->json(['session_id' => $session->id, 'greeting_text' => $greeting]);
    }

    /**
     * The synchronous hot path: one customer utterance in, one AI reply out.
     */
    public function turn(Request $request)
    {
        $validated = $request->validate([
            'provider_call_sid' => ['required', 'string'],
            'transcript' => ['required', 'string'],
        ]);

        $session = VoiceCallSession::where('provider_call_sid', $validated['provider_call_sid'])->first();

        if (! $session) {
            return response()->json(['message' => 'Unknown call session.'], 404);
        }

        $result = $this->voiceTurnService->handleTurn($session, $validated['transcript']);

        if (! $result['handled']) {
            return response()->json([
                'reply_text' => self::AI_UNAVAILABLE_LINE,
                'escalate' => true,
                'human_handoff_phone_number' => AiEmployeeSettings::current()->human_handoff_phone_number,
            ]);
        }

        return response()->json([
            'reply_text' => $result['reply_text'],
            'escalate' => $result['escalate'],
            'human_handoff_phone_number' => $result['escalate']
                ? AiEmployeeSettings::current()->human_handoff_phone_number
                : null,
        ]);
    }

    /**
     * Called when the bridge sees the call end (Twilio's `stop` event, or a
     * REST-initiated hangup/transfer). Closes the session and kicks off the
     * post-call summary in the background - not latency-sensitive, so it's
     * queued rather than run inline here.
     */
    public function endCall(Request $request)
    {
        $validated = $request->validate([
            'provider_call_sid' => ['required', 'string'],
            'duration_seconds' => ['nullable', 'integer', 'min:0'],
        ]);

        $session = VoiceCallSession::where('provider_call_sid', $validated['provider_call_sid'])->first();

        if (! $session) {
            return response()->json(['message' => 'Unknown call session.'], 404);
        }

        $session->update([
            'status' => $session->escalated ? VoiceCallSession::STATUS_ESCALATED : VoiceCallSession::STATUS_COMPLETED,
            'ended_at' => now(),
            'duration_seconds' => $validated['duration_seconds'] ?? $session->started_at->diffInSeconds(now()),
        ]);

        GenerateVoiceCallSummary::dispatch($session->id);

        return response()->json(['status' => 'ok']);
    }

    private function authenticateBridge(): void
    {
        $secret = config('voice.bridge.shared_secret');
        $provided = (string) request()->header('X-Voice-Bridge-Secret', '');

        if (! $secret || ! hash_equals((string) $secret, $provided)) {
            Log::warning('Rejected voice bridge request with invalid/missing shared secret.');
            abort(403);
        }
    }
}
