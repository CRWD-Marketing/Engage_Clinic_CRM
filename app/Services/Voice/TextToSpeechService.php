<?php

namespace App\Services\Voice;

interface TextToSpeechService
{
    /**
     * @return string raw WAV audio bytes - the bridge is responsible for
     *                 transcoding to whatever the telephony provider requires
     *                 (Twilio Media Streams is fixed at mulaw/8kHz/mono)
     */
    public function synthesize(string $text): string;
}
