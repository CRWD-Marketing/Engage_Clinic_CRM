'use strict';

// No test framework dependency - a handful of plain assertions is enough to
// catch a broken codec/resampler/endpointer, which is what actually matters
// here (the network-calling code in src/clients/* needs real servers to
// test against, covered separately by a live end-to-end call instead).

const assert = require('assert');
const mulaw = require('../src/audio/mulaw');
const { resample } = require('../src/audio/resample');
const wav = require('../src/audio/wav');
const { Endpointer, BargeInDetector, frameEnergy } = require('../src/vad');

let passed = 0;

function test(name, fn) {
    try {
        fn();
        console.log(`  ok - ${name}`);
        passed++;
    } catch (err) {
        console.error(`  FAIL - ${name}`);
        console.error(`    ${err.message}`);
        process.exitCode = 1;
    }
}

console.log('mulaw codec');

test('round-trips small-magnitude samples closely (mu-law has fine resolution near zero)', () => {
    for (const sample of [0, 50, -50, 200, -200, 1000, -1000]) {
        const encoded = mulaw.encodeSample(sample);
        const decoded = mulaw.decodeSample(encoded);
        assert.ok(Math.abs(decoded - sample) < 40, `sample ${sample} -> decoded ${decoded}, diff too large`);
    }
});

test('round-trips large-magnitude samples within expected mu-law quantization error', () => {
    for (const sample of [10000, -10000, 30000, -30000]) {
        const encoded = mulaw.encodeSample(sample);
        const decoded = mulaw.decodeSample(encoded);
        const relativeError = Math.abs(decoded - sample) / Math.abs(sample);
        assert.ok(relativeError < 0.15, `sample ${sample} -> decoded ${decoded}, relative error ${relativeError} too large`);
    }
});

test('clips above the codec max rather than overflowing', () => {
    const decoded = mulaw.decodeSample(mulaw.encodeSample(40000));
    assert.ok(decoded <= 32767 && decoded >= -32768);
});

test('buffer encode/decode round-trip matches sample-by-sample', () => {
    const samples = new Int16Array([0, 1000, -1000, 5000, -5000]);
    const encoded = mulaw.encodeBuffer(samples);
    const decoded = mulaw.decodeBuffer(encoded);
    assert.strictEqual(decoded.length, samples.length);
    for (let i = 0; i < samples.length; i++) {
        assert.strictEqual(decoded[i], mulaw.decodeSample(encoded[i]));
    }
});

console.log('resample');

test('same-rate resample is a no-op', () => {
    const samples = new Int16Array([1, 2, 3, 4]);
    assert.deepStrictEqual(resample(samples, 8000, 8000), samples);
});

test('upsampling 8kHz->16kHz roughly doubles sample count', () => {
    const samples = new Int16Array(160).fill(1000); // 20ms @ 8kHz
    const up = resample(samples, 8000, 16000);
    assert.strictEqual(up.length, 320);
});

test('downsampling 16kHz->8kHz roughly halves sample count', () => {
    const samples = new Int16Array(320).fill(1000);
    const down = resample(samples, 16000, 8000);
    assert.strictEqual(down.length, 160);
});

test('resampling a constant signal stays (approximately) constant', () => {
    const samples = new Int16Array(100).fill(5000);
    const resampled = resample(samples, 22050, 8000);
    for (const s of resampled) {
        assert.ok(Math.abs(s - 5000) < 5, `expected ~5000, got ${s}`);
    }
});

console.log('wav');

test('encode/decode round-trip preserves sample rate and samples', () => {
    const samples = new Int16Array([0, 1234, -1234, 32767, -32768]);
    const buffer = wav.encode(samples, 16000);
    const decoded = wav.decode(buffer);
    assert.strictEqual(decoded.sampleRate, 16000);
    assert.deepStrictEqual(Array.from(decoded.samples), Array.from(samples));
});

test('rejects a non-WAV buffer', () => {
    assert.throws(() => wav.decode(Buffer.from('not a wav file at all here')));
});

console.log('vad');

function silentFrame() {
    return new Int16Array(160); // all zeros
}

function loudFrame() {
    const f = new Int16Array(160);
    for (let i = 0; i < f.length; i++) f[i] = 8000 * Math.sin(i / 5);
    return f;
}

test('frameEnergy is ~0 for silence and large for a loud tone', () => {
    assert.strictEqual(frameEnergy(silentFrame()), 0);
    assert.ok(frameEnergy(loudFrame()) > 1000);
});

test('Endpointer stays "listening" through leading silence', () => {
    const ep = new Endpointer({ silenceMs: 700, minSpeechMs: 250, maxUtteranceMs: 25000 });
    for (let i = 0; i < 10; i++) {
        assert.strictEqual(ep.pushFrame(silentFrame()), 'listening');
    }
});

test('Endpointer discards a burst too short to count as real speech', () => {
    const ep = new Endpointer({ silenceMs: 200, minSpeechMs: 250, maxUtteranceMs: 25000 });
    // one 20ms loud frame (well under the 250ms speech floor), then silence past the gap
    let last;
    last = ep.pushFrame(loudFrame());
    assert.strictEqual(last, 'listening');
    for (let i = 0; i < 15; i++) {
        last = ep.pushFrame(silentFrame());
        if (last !== 'listening') break;
    }
    assert.strictEqual(last, 'discarded');
});

test('Endpointer signals end_of_turn after real speech followed by a silence gap', () => {
    const ep = new Endpointer({ silenceMs: 200, minSpeechMs: 100, maxUtteranceMs: 25000 });
    let last;
    for (let i = 0; i < 10; i++) last = ep.pushFrame(loudFrame()); // 200ms of speech
    assert.strictEqual(last, 'listening');
    for (let i = 0; i < 15; i++) {
        last = ep.pushFrame(silentFrame());
        if (last !== 'listening') break;
    }
    assert.strictEqual(last, 'end_of_turn');
});

test('Endpointer forces end_of_turn at the max-utterance ceiling even without silence', () => {
    const ep = new Endpointer({ silenceMs: 100000, minSpeechMs: 20, maxUtteranceMs: 200, frameMs: 20 });
    let last;
    for (let i = 0; i < 20; i++) last = ep.pushFrame(loudFrame()); // never pauses
    assert.strictEqual(last, 'end_of_turn');
});

test('BargeInDetector requires sustained speech, not a single loud frame', () => {
    const bd = new BargeInDetector({ sustainedMs: 100, frameMs: 20 }); // 5 frames
    assert.strictEqual(bd.pushFrame(loudFrame()), false);
    assert.strictEqual(bd.pushFrame(loudFrame()), false);
    assert.strictEqual(bd.pushFrame(silentFrame()), false); // resets the streak
    assert.strictEqual(bd.pushFrame(loudFrame()), false);
    assert.strictEqual(bd.pushFrame(loudFrame()), false);
    assert.strictEqual(bd.pushFrame(loudFrame()), false);
    assert.strictEqual(bd.pushFrame(loudFrame()), false);
    assert.strictEqual(bd.pushFrame(loudFrame()), true); // 5th consecutive loud frame
});

console.log(`\n${passed} test(s) passed${process.exitCode ? ', with failures' : ''}.`);
