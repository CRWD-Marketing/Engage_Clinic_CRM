<?php

namespace App\Services\Voice;

/**
 * Deliberately does NOT include receiveAudio()/sendAudio() - those describe a
 * persistent bidirectional audio loop, which PHP-FPM's request/response model
 * cannot hold open. That loop lives in the separate always-on Node bridge
 * service instead. This interface only covers what Laravel can actually do:
 * answer a call by returning TwiML, and act on a live call via the
 * telephony provider's REST API.
 */
interface VoiceCallService
{
    /**
     * Build the TwiML response for an inbound call webhook - this IS
     * "answering" the call in a request/response model. $from/$to are
     * passed through as <Stream> <Parameter> tags so the bridge has them
     * as soon as it sees the Media Streams `start` event, without an extra
     * Twilio REST round-trip to look them up.
     */
    public function answerCall(string $callSid, string $streamUrl, string $from, string $to): string;

    /**
     * Redirect an in-progress call to a human's number (live transfer).
     */
    public function transferToHuman(string $callSid, string $humanNumber): bool;

    public function endCall(string $callSid): bool;
}
