<?php

namespace App\Providers;

use App\Services\Voice\AssemblyAiSttClient;
use App\Services\Voice\FasterWhisperSttClient;
use App\Services\Voice\PiperTtsClient;
use App\Services\Voice\SpeechToTextService;
use App\Services\Voice\TextToSpeechService;
use App\Services\Voice\TwilioVoiceCallService;
use App\Services\Voice\VoiceCallService;
use Illuminate\Support\ServiceProvider;

class VoiceServiceProvider extends ServiceProvider
{
    /**
     * Same pattern as AiEmployeeServiceProvider: the concrete STT/TTS/
     * telephony runtimes are config-level choices, not hardcoded through the
     * voice call chain. Swapping any of these later means writing a new
     * implementation and changing one binding here.
     */
    public function register(): void
    {
        $this->app->bind(SpeechToTextService::class, function () {
            return config('voice.stt.driver') === 'assemblyai'
                ? new AssemblyAiSttClient()
                : new FasterWhisperSttClient();
        });

        $this->app->bind(TextToSpeechService::class, PiperTtsClient::class);
        $this->app->bind(VoiceCallService::class, TwilioVoiceCallService::class);
    }
}
