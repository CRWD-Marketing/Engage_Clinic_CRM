'use strict';

const config = require('../config');
const wav = require('../audio/wav');

/**
 * @param {Int16Array} pcmSamples mono PCM16 at sampleRate
 * @param {number} sampleRate
 * @returns {Promise<string>}
 */
async function transcribe(pcmSamples, sampleRate) {
    return config.stt.provider === 'assemblyai'
        ? transcribeWithAssemblyAi(pcmSamples, sampleRate)
        : transcribeWithFasterWhisper(pcmSamples, sampleRate);
}

/**
 * Talks to the self-hosted faster-whisper HTTP wrapper (same contract
 * FasterWhisperSttClient.php assumes on the Laravel side - documented for
 * consistency; the bridge talks to it directly, never through Laravel,
 * since Laravel only ever sees transcript text, not audio).
 */
async function transcribeWithFasterWhisper(pcmSamples, sampleRate) {
    const wavBuffer = wav.encode(pcmSamples, sampleRate);

    const form = new FormData();
    form.append('audio', new Blob([wavBuffer], { type: 'audio/wav' }), 'utterance.wav');

    const controller = new AbortController();
    const timeout = setTimeout(() => controller.abort(), config.stt.timeoutMs);

    try {
        const response = await fetch(`${config.stt.baseUrl}/transcribe`, {
            method: 'POST',
            body: form,
            signal: controller.signal,
        });

        if (!response.ok) {
            throw new Error(`STT server returned HTTP ${response.status}`);
        }

        const data = await response.json();
        return (data.text || '').trim();
    } finally {
        clearTimeout(timeout);
    }
}

/**
 * Talks to AssemblyAI's hosted API directly (same account/key as the
 * Laravel side's AssemblyAiSttClient, if VOICE_STT_PROVIDER=assemblyai and
 * ASSEMBLYAI_API_KEY are set here) - upload the utterance, submit a
 * transcription job, poll until it completes. This is NOT low-latency
 * streaming transcription; each utterance is a separate upload+poll round
 * trip, same tradeoff as documented on the Laravel side.
 */
async function transcribeWithAssemblyAi(pcmSamples, sampleRate) {
    const { apiKey, baseUrl, pollIntervalMs, pollTimeoutMs } = config.stt.assemblyai;

    if (!apiKey) {
        throw new Error('ASSEMBLYAI_API_KEY is not configured');
    }

    const wavBuffer = wav.encode(pcmSamples, sampleRate);
    const headers = { authorization: apiKey };

    const uploadResponse = await fetch(`${baseUrl}/upload`, {
        method: 'POST',
        headers: { ...headers, 'Content-Type': 'application/octet-stream' },
        body: wavBuffer,
    });

    if (!uploadResponse.ok) {
        throw new Error(`AssemblyAI upload failed: HTTP ${uploadResponse.status} ${await uploadResponse.text()}`);
    }

    const { upload_url: uploadUrl } = await uploadResponse.json();

    const transcriptResponse = await fetch(`${baseUrl}/transcript`, {
        method: 'POST',
        headers: { ...headers, 'Content-Type': 'application/json' },
        body: JSON.stringify({ audio_url: uploadUrl }),
    });

    if (!transcriptResponse.ok) {
        throw new Error(`AssemblyAI transcript request failed: HTTP ${transcriptResponse.status} ${await transcriptResponse.text()}`);
    }

    const { id: transcriptId } = await transcriptResponse.json();

    let elapsedMs = 0;
    while (elapsedMs < pollTimeoutMs) {
        const pollResponse = await fetch(`${baseUrl}/transcript/${transcriptId}`, { headers });

        if (!pollResponse.ok) {
            throw new Error(`AssemblyAI poll failed: HTTP ${pollResponse.status} ${await pollResponse.text()}`);
        }

        const result = await pollResponse.json();

        if (result.status === 'completed') {
            return (result.text || '').trim();
        }

        if (result.status === 'error') {
            throw new Error(`AssemblyAI transcription failed: ${result.error}`);
        }

        await new Promise((resolve) => setTimeout(resolve, pollIntervalMs));
        elapsedMs += pollIntervalMs;
    }

    throw new Error(`AssemblyAI transcription timed out after ${pollTimeoutMs}ms (transcript id: ${transcriptId})`);
}

module.exports = { transcribe };
