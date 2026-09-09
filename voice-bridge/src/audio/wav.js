'use strict';

// Minimal 16-bit PCM mono WAV read/write - just enough to hand an utterance
// to the STT server as a proper .wav file, and to read whatever sample rate
// the TTS server rendered its response at (so it can be resampled correctly
// rather than assumed).

/**
 * @param {Int16Array} samples
 * @param {number} sampleRate
 * @returns {Buffer}
 */
function encode(samples, sampleRate) {
    const dataSize = samples.length * 2;
    const buffer = Buffer.alloc(44 + dataSize);

    buffer.write('RIFF', 0, 'ascii');
    buffer.writeUInt32LE(36 + dataSize, 4);
    buffer.write('WAVE', 8, 'ascii');

    buffer.write('fmt ', 12, 'ascii');
    buffer.writeUInt32LE(16, 16); // fmt chunk size
    buffer.writeUInt16LE(1, 20); // PCM
    buffer.writeUInt16LE(1, 22); // mono
    buffer.writeUInt32LE(sampleRate, 24);
    buffer.writeUInt32LE(sampleRate * 2, 28); // byte rate (16-bit mono)
    buffer.writeUInt16LE(2, 32); // block align
    buffer.writeUInt16LE(16, 34); // bits per sample

    buffer.write('data', 36, 'ascii');
    buffer.writeUInt32LE(dataSize, 40);

    for (let i = 0; i < samples.length; i++) {
        buffer.writeInt16LE(samples[i], 44 + i * 2);
    }

    return buffer;
}

/**
 * Parses a 16-bit PCM WAV buffer. Walks chunks rather than assuming a fixed
 * 44-byte header, since some TTS servers emit extra chunks (e.g. LIST/fact)
 * before the data chunk.
 *
 * @param {Buffer} buffer
 * @returns {{sampleRate: number, samples: Int16Array}}
 */
function decode(buffer) {
    if (buffer.toString('ascii', 0, 4) !== 'RIFF' || buffer.toString('ascii', 8, 12) !== 'WAVE') {
        throw new Error('Not a RIFF/WAVE buffer');
    }

    let offset = 12;
    let sampleRate = 0;
    let bitsPerSample = 16;
    let dataStart = -1;
    let dataLength = 0;

    while (offset + 8 <= buffer.length) {
        const chunkId = buffer.toString('ascii', offset, offset + 4);
        const chunkSize = buffer.readUInt32LE(offset + 4);
        const chunkBodyStart = offset + 8;

        if (chunkId === 'fmt ') {
            sampleRate = buffer.readUInt32LE(chunkBodyStart + 4);
            bitsPerSample = buffer.readUInt16LE(chunkBodyStart + 14);
        } else if (chunkId === 'data') {
            dataStart = chunkBodyStart;
            dataLength = chunkSize;
        }

        offset = chunkBodyStart + chunkSize + (chunkSize % 2); // chunks are word-aligned
    }

    if (dataStart === -1 || sampleRate === 0) {
        throw new Error('WAV buffer missing fmt/data chunk');
    }

    if (bitsPerSample !== 16) {
        throw new Error(`Unsupported WAV bit depth: ${bitsPerSample} (only 16-bit PCM is supported)`);
    }

    const sampleCount = Math.floor(dataLength / 2);
    const samples = new Int16Array(sampleCount);

    for (let i = 0; i < sampleCount; i++) {
        samples[i] = buffer.readInt16LE(dataStart + i * 2);
    }

    return { sampleRate, samples };
}

module.exports = { encode, decode };
