'use strict';

const config = require('../config');
const wav = require('../audio/wav');

/**
 * Talks to the self-hosted Piper HTTP wrapper (same contract PiperTtsClient
 * assumes on the Laravel side). Returns decoded PCM at whatever sample rate
 * the server actually rendered - callers must resample to 8kHz themselves
 * (see audio/resample.js) rather than assuming a fixed rate.
 *
 * @param {string} text
 * @returns {Promise<{sampleRate: number, samples: Int16Array}>}
 */
async function synthesize(text) {
    const controller = new AbortController();
    const timeout = setTimeout(() => controller.abort(), config.tts.timeoutMs);

    try {
        const response = await fetch(`${config.tts.baseUrl}/synthesize`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ text, voice: config.tts.voice }),
            signal: controller.signal,
        });

        if (!response.ok) {
            throw new Error(`TTS server returned HTTP ${response.status}`);
        }

        const arrayBuffer = await response.arrayBuffer();
        return wav.decode(Buffer.from(arrayBuffer));
    } finally {
        clearTimeout(timeout);
    }
}

module.exports = { synthesize };
