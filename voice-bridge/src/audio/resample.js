'use strict';

// Linear-interpolation resampler. Deliberately simple/pure-JS (no native
// deps like libsamplerate/sox) - good enough for speech intelligibility at
// telephony bandwidth, and keeps the bridge installable on any host without
// a native build step. Used both directions: upsampling the caller's 8kHz
// mulaw-derived audio to 16kHz for the STT server, and downsampling the TTS
// server's output back to 8kHz for Twilio.

/**
 * @param {Int16Array} samples
 * @param {number} fromRate
 * @param {number} toRate
 * @returns {Int16Array}
 */
function resample(samples, fromRate, toRate) {
    if (fromRate === toRate || samples.length === 0) {
        return samples;
    }

    const ratio = fromRate / toRate;
    const outLength = Math.max(1, Math.round(samples.length / ratio));
    const out = new Int16Array(outLength);

    for (let i = 0; i < outLength; i++) {
        const srcPos = i * ratio;
        const srcIndexLow = Math.floor(srcPos);
        const srcIndexHigh = Math.min(srcIndexLow + 1, samples.length - 1);
        const frac = srcPos - srcIndexLow;

        out[i] = Math.round(
            samples[srcIndexLow] * (1 - frac) + samples[srcIndexHigh] * frac
        );
    }

    return out;
}

module.exports = { resample };
