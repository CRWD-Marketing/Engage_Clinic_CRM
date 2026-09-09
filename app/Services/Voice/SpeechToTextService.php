<?php

namespace App\Services\Voice;

interface SpeechToTextService
{
    /**
     * @param  string  $audio  raw WAV audio bytes for one already-endpointed
     *                         customer utterance (the bridge does VAD/turn
     *                         segmentation before calling this - this is not
     *                         a continuous streaming transcription contract)
     */
    public function transcribe(string $audio): string;
}
