<?php

namespace App\Services\Voice;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Talks to Twilio's Voice REST API using the same Http:: facade pattern
 * already used for WhatsApp/Facebook/Instagram, rather than pulling in the
 * twilio/sdk composer package for what's otherwise a couple of simple
 * authenticated POSTs.
 */
class TwilioVoiceCallService implements VoiceCallService
{
    private const API_BASE = 'https://api.twilio.com/2010-04-01';

    public function answerCall(string $callSid, string $streamUrl, string $from, string $to): string
    {
        $escapedUrl = htmlspecialchars($streamUrl, ENT_XML1 | ENT_QUOTES);
        $escapedFrom = htmlspecialchars($from, ENT_XML1 | ENT_QUOTES);
        $escapedTo = htmlspecialchars($to, ENT_XML1 | ENT_QUOTES);

        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<Response>
  <Connect>
    <Stream url="{$escapedUrl}">
      <Parameter name="from" value="{$escapedFrom}" />
      <Parameter name="to" value="{$escapedTo}" />
    </Stream>
  </Connect>
</Response>
XML;
    }

    public function transferToHuman(string $callSid, string $humanNumber): bool
    {
        $escapedNumber = htmlspecialchars($humanNumber, ENT_XML1 | ENT_QUOTES);
        $twiml = '<?xml version="1.0" encoding="UTF-8"?><Response><Dial>'.$escapedNumber.'</Dial></Response>';

        return $this->updateCall($callSid, ['Twiml' => $twiml]);
    }

    public function endCall(string $callSid): bool
    {
        return $this->updateCall($callSid, ['Status' => 'completed']);
    }

    private function updateCall(string $callSid, array $params): bool
    {
        $accountSid = config('voice.twilio.account_sid');

        $response = Http::asForm()
            ->withBasicAuth($accountSid, config('voice.twilio.auth_token'))
            ->post(self::API_BASE."/Accounts/{$accountSid}/Calls/{$callSid}.json", $params);

        if ($response->failed()) {
            Log::error('Twilio call-update request failed', [
                'call_sid' => $callSid,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        }

        return $response->successful();
    }
}
