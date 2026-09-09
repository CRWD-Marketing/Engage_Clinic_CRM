<?php

namespace App\Http\Controllers\Voice;

use App\Http\Controllers\Controller;
use App\Models\AiEmployeeSettings;
use App\Services\Voice\VoiceCallService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Twilio's Voice webhook target for an inbound call to the clinic's number.
 * This controller's only job is answering fast with TwiML pointing the call
 * at the bridge's WebSocket - it does not itself create the
 * WhatsappContact/VoiceCallSession (VoiceBridgeController::startCall does
 * that once the bridge actually has the audio stream open).
 */
class TwilioIncomingCallController extends Controller
{
    public function __invoke(Request $request, VoiceCallService $voiceCallService)
    {
        if (! $this->verifyTwilioSignature($request)) {
            Log::warning('Rejected Twilio webhook POST with invalid/missing X-Twilio-Signature.');

            return response('Invalid signature', 403);
        }

        $callSid = (string) $request->input('CallSid');
        $from = (string) $request->input('From');
        $to = (string) $request->input('To');

        if (! AiEmployeeSettings::current()->voice_enabled) {
            return response($this->declineTwiml(), 200)->header('Content-Type', 'text/xml');
        }

        $bridgeBase = rtrim((string) config('voice.bridge.base_url'), '/');
        $streamUrl = $bridgeBase."/twilio-stream/{$callSid}";

        $twiml = $voiceCallService->answerCall($callSid, $streamUrl, $from, $to);

        return response($twiml, 200)->header('Content-Type', 'text/xml');
    }

    private function declineTwiml(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<Response>
  <Say>Thank you for calling. Our AI assistant is unavailable right now, please try again later.</Say>
  <Hangup/>
</Response>
XML;
    }

    /**
     * Twilio signs webhook requests with X-Twilio-Signature: base64(HMAC-SHA1(
     * url + sorted-POST-params-concatenated, auth_token)). Different algorithm
     * than Meta's X-Hub-Signature-256, so this is new verification code rather
     * than a reuse of WhatsappController::verifySignature().
     */
    private function verifyTwilioSignature(Request $request): bool
    {
        $signature = $request->header('X-Twilio-Signature');
        $authToken = config('voice.twilio.auth_token');

        if (! $signature || ! $authToken) {
            return false;
        }

        $data = $request->fullUrl();

        if ($request->isMethod('post')) {
            $params = $request->all();
            ksort($params);

            foreach ($params as $key => $value) {
                $data .= $key.$value;
            }
        }

        $expected = base64_encode(hash_hmac('sha1', $data, (string) $authToken, true));

        return hash_equals($expected, $signature);
    }
}
