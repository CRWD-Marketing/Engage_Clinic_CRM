<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Speech-to-text
    |--------------------------------------------------------------------------
    |
    | driver picks which SpeechToTextService binding VoiceServiceProvider
    | uses - 'faster_whisper' (self-hosted, no per-call cost, audio never
    | leaves your infrastructure) or 'assemblyai' (hosted, no server to run,
    | but adds upload+poll latency per turn and sends caller audio to a
    | third party - worth weighing given the PHI-adjacent context here).
    |
    */

    'stt' => [
        'driver' => env('VOICE_STT_DRIVER', 'assemblyai'),

        'base_url' => env('VOICE_STT_BASE_URL', 'http://127.0.0.1:8001'),
        'timeout' => env('VOICE_STT_TIMEOUT', 15),

        'assemblyai' => [
            'api_key' => env('ASSEMBLYAI_API_KEY'),
            'base_url' => env('ASSEMBLYAI_BASE_URL', 'https://api.assemblyai.com/v2'),
            'poll_interval_ms' => env('ASSEMBLYAI_POLL_INTERVAL_MS', 500),
            'poll_timeout_ms' => env('ASSEMBLYAI_POLL_TIMEOUT_MS', 12000),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Text-to-speech (self-hosted Piper server, by default)
    |--------------------------------------------------------------------------
    */

    'tts' => [
        'base_url' => env('VOICE_TTS_BASE_URL', 'http://127.0.0.1:8002'),
        'voice' => env('VOICE_TTS_VOICE', 'en_US-lessac-medium'),
        'timeout' => env('VOICE_TTS_TIMEOUT', 15),
    ],

    /*
    |--------------------------------------------------------------------------
    | Telephony provider (Twilio)
    |--------------------------------------------------------------------------
    */

    'twilio' => [
        'account_sid' => env('TWILIO_ACCOUNT_SID'),
        'auth_token' => env('TWILIO_AUTH_TOKEN'),
        'phone_number' => env('TWILIO_PHONE_NUMBER'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Real-time audio bridge (separate always-on Node.js service)
    |--------------------------------------------------------------------------
    |
    | base_url is where the bridge's WebSocket endpoint lives - used to build
    | the <Stream> URL returned to Twilio. shared_secret is checked against
    | the X-Voice-Bridge-Secret header on every bridge->Laravel call, the same
    | pattern as ai_employee.cron_secret.
    |
    */

    'bridge' => [
        'base_url' => env('VOICE_BRIDGE_BASE_URL'), // e.g. wss://voice-bridge.example.com
        'shared_secret' => env('VOICE_BRIDGE_SHARED_SECRET'),
    ],

];
