'use strict';

// Energy-based VAD/endpointing. Twilio delivers audio in ~20ms mulaw frames,
// so all timing here is expressed in frame counts derived from a configured
// frame duration rather than a wall clock - simpler and jitter-free for a
// steady frame cadence.
//
// This is deliberately NOT streaming/incremental transcription - it only
// decides *when* a customer utterance has ended so it can be batch-sent to
// STT in one shot, which is what production self-hosted-Whisper voice
// agents actually do (see the architecture plan's decision #6).

const DEFAULT_FRAME_MS = 20;

/** Root-mean-square energy of a frame of linear PCM16 samples. */
function frameEnergy(pcmSamples) {
    if (pcmSamples.length === 0) return 0;

    let sumSquares = 0;
    for (let i = 0; i < pcmSamples.length; i++) {
        sumSquares += pcmSamples[i] * pcmSamples[i];
    }

    return Math.sqrt(sumSquares / pcmSamples.length);
}

/**
 * Tracks one customer utterance while the bridge is listening: buffers
 * speech, and signals end-of-turn after a sustained silence gap (with a
 * minimum-speech floor to reject noise/breath, and a hard ceiling so a
 * caller who never pauses doesn't block the pipeline indefinitely).
 */
class Endpointer {
    constructor({
        threshold = 400,
        silenceMs = 700,
        minSpeechMs = 250,
        maxUtteranceMs = 25000,
        frameMs = DEFAULT_FRAME_MS,
    } = {}) {
        this.threshold = threshold;
        this.silenceFrames = Math.round(silenceMs / frameMs);
        this.minSpeechFrames = Math.round(minSpeechMs / frameMs);
        this.maxUtteranceFrames = Math.round(maxUtteranceMs / frameMs);

        this.reset();
    }

    reset() {
        this.speechFrameCount = 0;
        this.silenceFrameCount = 0;
        this.totalFrameCount = 0;
        this.hasStarted = false;
    }

    /**
     * @param {Int16Array} pcmSamples one frame of decoded audio
     * @returns {'listening'|'end_of_turn'|'discarded'} discarded = silence
     *   the whole way through (no speech ever detected) - caller should
     *   drop the buffered audio rather than send an empty/noise utterance
     *   to STT.
     */
    pushFrame(pcmSamples) {
        const isSpeech = frameEnergy(pcmSamples) >= this.threshold;
        this.totalFrameCount++;

        if (isSpeech) {
            this.hasStarted = true;
            this.speechFrameCount++;
            this.silenceFrameCount = 0;
        } else if (this.hasStarted) {
            this.silenceFrameCount++;
        }

        if (this.hasStarted && this.silenceFrameCount >= this.silenceFrames) {
            return this.speechFrameCount >= this.minSpeechFrames ? 'end_of_turn' : 'discarded';
        }

        if (this.totalFrameCount >= this.maxUtteranceFrames) {
            return this.speechFrameCount >= this.minSpeechFrames ? 'end_of_turn' : 'discarded';
        }

        return 'listening';
    }
}

/**
 * Runs alongside outbound TTS playback to detect the caller barging in.
 * Uses a higher sustained-speech bar than Endpointer's per-frame threshold
 * to reduce false triggers from line noise or the clinic's own TTS audio
 * leaking into the mic (no acoustic echo cancellation is implemented here -
 * see the architecture plan's barge-in section for that known limitation).
 */
class BargeInDetector {
    constructor({ threshold = 500, sustainedMs = 300, frameMs = DEFAULT_FRAME_MS } = {}) {
        this.threshold = threshold;
        this.sustainedFrames = Math.round(sustainedMs / frameMs);
        this.reset();
    }

    reset() {
        this.consecutiveSpeechFrames = 0;
    }

    /** @returns {boolean} true once sustained speech has been detected */
    pushFrame(pcmSamples) {
        const isSpeech = frameEnergy(pcmSamples) >= this.threshold;
        this.consecutiveSpeechFrames = isSpeech ? this.consecutiveSpeechFrames + 1 : 0;
        return this.consecutiveSpeechFrames >= this.sustainedFrames;
    }
}

module.exports = { frameEnergy, Endpointer, BargeInDetector };
