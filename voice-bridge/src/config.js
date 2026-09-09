'use strict';

// No dotenv dependency - the bridge is meant to run as a real always-on
// service (systemd/pm2/Docker/etc.), where env vars are normally supplied
// by the process manager rather than a .env file. Load one manually here
// only for local development convenience, without adding it as a runtime
// dependency.
try {
    const fs = require('fs');
    const path = require('path');
    const envPath = path.join(__dirname, '..', '.env');
    if (fs.existsSync(envPath)) {
        for (const line of fs.readFileSync(envPath, 'utf8').split('\n')) {
            const trimmed = line.trim();
            if (!trimmed || trimmed.startsWith('#')) continue;
            const eq = trimmed.indexOf('=');
            if (eq === -1) continue;
            const key = trimmed.slice(0, eq).trim();
            const value = trimmed.slice(eq + 1).trim();
            if (!(key in process.env)) process.env[key] = value;
        }
    }
} catch {
    // Best-effort only - a missing/unreadable .env is fine if real env vars are set.
}

function required(name) {
    const value = process.env[name];
    if (!value) {
        console.warn(`[config] Warning: ${name} is not set - related functionality will fail until it is configured.`);
    }
    return value;
}

module.exports = {
    port: parseInt(process.env.PORT || '3000', 10),

    laravel: {
        baseUrl: required('LARAVEL_BASE_URL'), // e.g. https://engageclinic.example.com
        sharedSecret: required('VOICE_BRIDGE_SHARED_SECRET'),
        timeoutMs: parseInt(process.env.LARAVEL_TIMEOUT_MS || '10000', 10),
    },

    stt: {
        // 'faster_whisper' (self-hosted, POST /transcribe) or 'assemblyai'
        // (hosted, upload+submit+poll - see clients/sttClient.js). This is
        // the bridge's own choice, independent of Laravel's
        // config('voice.stt.driver') - the bridge never sends audio through
        // Laravel, so the two don't have to match, though pointing both at
        // the same AssemblyAI account is the obvious common setup.
        provider: process.env.VOICE_STT_PROVIDER || 'faster_whisper',
        baseUrl: process.env.VOICE_STT_BASE_URL || 'http://127.0.0.1:8001',
        timeoutMs: parseInt(process.env.VOICE_STT_TIMEOUT_MS || '15000', 10),
        assemblyai: {
            apiKey: process.env.ASSEMBLYAI_API_KEY,
            baseUrl: process.env.ASSEMBLYAI_BASE_URL || 'https://api.assemblyai.com/v2',
            pollIntervalMs: parseInt(process.env.ASSEMBLYAI_POLL_INTERVAL_MS || '500', 10),
            pollTimeoutMs: parseInt(process.env.ASSEMBLYAI_POLL_TIMEOUT_MS || '12000', 10),
        },
    },

    tts: {
        baseUrl: process.env.VOICE_TTS_BASE_URL || 'http://127.0.0.1:8002',
        voice: process.env.VOICE_TTS_VOICE || 'en_US-lessac-medium',
        timeoutMs: parseInt(process.env.VOICE_TTS_TIMEOUT_MS || '15000', 10),
    },

    twilio: {
        // Deliberately held here too (not just in Laravel) so mid-call
        // transfer/hangup survives a Laravel outage - see the plan's "Open
        // question: Twilio credential location" (resolved: bridge-local for
        // transfer/hangup only).
        accountSid: process.env.TWILIO_ACCOUNT_SID,
        authToken: process.env.TWILIO_AUTH_TOKEN,
    },

    fallback: {
        // Used only when Laravel itself is unreachable mid-call (section
        // 2.5 of the plan) - a last-resort human number the bridge can
        // transfer to without needing Laravel to tell it to.
        emergencyHumanNumber: process.env.VOICE_EMERGENCY_HUMAN_NUMBER || null,
    },

    vad: {
        threshold: parseInt(process.env.VOICE_VAD_THRESHOLD || '400', 10),
        silenceMs: parseInt(process.env.VOICE_VAD_SILENCE_MS || '700', 10),
        minSpeechMs: parseInt(process.env.VOICE_VAD_MIN_SPEECH_MS || '250', 10),
        maxUtteranceMs: parseInt(process.env.VOICE_VAD_MAX_UTTERANCE_MS || '25000', 10),
        bargeInThreshold: parseInt(process.env.VOICE_BARGEIN_THRESHOLD || '500', 10),
        bargeInSustainedMs: parseInt(process.env.VOICE_BARGEIN_SUSTAINED_MS || '300', 10),
        bargeInEnabled: (process.env.VOICE_BARGEIN_ENABLED || 'true') === 'true',
    },
};
