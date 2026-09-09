'use strict';

// G.711 mu-law codec. Twilio Media Streams audio is fixed at mulaw/8000Hz/mono
// in both directions - not configurable - so the bridge owns this conversion
// step regardless of what the STT/TTS servers themselves prefer (16kHz PCM).
// Direct-formula implementation (no precomputed segment table) so the
// exponent/segment is derived from the sample itself - self-consistent and
// verified by test/selftest.js's round-trip check rather than a hand-copied
// lookup table.

const BIAS = 0x84;
const CLIP = 32635;

/** One 8-bit mu-law byte -> one signed 16-bit linear PCM sample. */
function decodeSample(uByte) {
    uByte = (~uByte) & 0xFF;

    const sign = uByte & 0x80;
    const exponent = (uByte >> 4) & 0x07;
    const mantissa = uByte & 0x0F;

    let magnitude = ((mantissa << 3) + BIAS) << exponent;
    magnitude -= BIAS;

    return sign ? -magnitude : magnitude;
}

/** One signed 16-bit linear PCM sample -> one 8-bit mu-law byte. */
function encodeSample(sample) {
    let sign = 0x00;

    if (sample < 0) {
        sign = 0x80;
        sample = -sample;
    }

    if (sample > CLIP) {
        sample = CLIP;
    }

    sample += BIAS;

    let exponent = 7;
    for (let mask = 0x4000; (sample & mask) === 0 && exponent > 0; mask >>= 1) {
        exponent--;
    }

    const mantissa = (sample >> (exponent + 3)) & 0x0F;

    return (~(sign | (exponent << 4) | mantissa)) & 0xFF;
}

/** @param {Buffer} mulawBuffer @returns {Int16Array} */
function decodeBuffer(mulawBuffer) {
    const out = new Int16Array(mulawBuffer.length);
    for (let i = 0; i < mulawBuffer.length; i++) {
        out[i] = decodeSample(mulawBuffer[i]);
    }
    return out;
}

/** @param {Int16Array} pcmSamples @returns {Buffer} */
function encodeBuffer(pcmSamples) {
    const out = Buffer.alloc(pcmSamples.length);
    for (let i = 0; i < pcmSamples.length; i++) {
        out[i] = encodeSample(pcmSamples[i]);
    }
    return out;
}

module.exports = { decodeSample, encodeSample, decodeBuffer, encodeBuffer };
