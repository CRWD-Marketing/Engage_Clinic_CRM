'use strict';

const config = require('./config');
const mulaw = require('./audio/mulaw');
const { resample } = require('./audio/resample');
const { Endpointer, BargeInDetector } = require('./vad');
const laravelClient = require('./clients/laravelClient');
const sttClient = require('./clients/sttClient');
const ttsClient = require('./clients/ttsClient');
const twilioRest = require('./clients/twilioRest');

const FRAME_MS = 20;
const TWILIO_SAMPLE_RATE = 8000;
const STT_SAMPLE_RATE = 16000; // faster-whisper's / AssemblyAI's expected input rate
const FRAME_SAMPLES = (TWILIO_SAMPLE_RATE * FRAME_MS) / 1000; // 160

const TECHNICAL_DIFFICULTY_LINE =
    "We're experiencing a technical issue. Please try calling back shortly.";
const AI_TROUBLE_LINE =
    "I'm sorry, I'm having trouble understanding right now - let me get you to a team member.";
const NO_HUMAN_AVAILABLE_LINE =
    "I'm sorry, no one is available to take your call right now, but I've made a note and our team will reach out to you as soon as possible.";

function sleep(ms) {
    return new Promise((resolve) => setTimeout(resolve, ms));
}

/**
 * Sends PCM16@8kHz audio to Twilio as paced ~20ms mulaw frames (not all at
 * once - Twilio expects roughly real-time delivery). Interruptible via
 * stop(), used for barge-in: send a `clear` event to flush whatever Twilio
 * has buffered, then stop emitting further frames from this playback.
 */
class Playback {
    constructor(ws, streamSid) {
        this.ws = ws;
        this.streamSid = streamSid;
        this.stopped = false;
    }

    stop() {
        this.stopped = true;
        if (this.ws.readyState === this.ws.OPEN) {
            this.ws.send(JSON.stringify({ event: 'clear', streamSid: this.streamSid }));
        }
    }

    /** @param {Int16Array} pcm8k */
    async play(pcm8k) {
        for (let offset = 0; offset < pcm8k.length && !this.stopped; offset += FRAME_SAMPLES) {
            const frame = pcm8k.subarray(offset, offset + FRAME_SAMPLES);
            const mulawFrame = mulaw.encodeBuffer(frame);

            if (this.ws.readyState !== this.ws.OPEN) return;

            this.ws.send(JSON.stringify({
                event: 'media',
                streamSid: this.streamSid,
                media: { payload: mulawFrame.toString('base64') },
            }));

            await sleep(FRAME_MS);
        }
    }
}

/** One call's full lifecycle, from Twilio `start` to `stop`. */
class CallSession {
    constructor(ws) {
        this.ws = ws;
        this.streamSid = null;
        this.callSid = null;
        this.from = null;
        this.to = null;
        this.startedAt = null;
        this.endpointer = new Endpointer({
            threshold: config.vad.threshold,
            silenceMs: config.vad.silenceMs,
            minSpeechMs: config.vad.minSpeechMs,
            maxUtteranceMs: config.vad.maxUtteranceMs,
        });
        this.bargeIn = new BargeInDetector({
            threshold: config.vad.bargeInThreshold,
            sustainedMs: config.vad.bargeInSustainedMs,
        });
        this.utteranceFrames = [];
        this.currentPlayback = null;
        this.ended = false;
    }

    async handleStart(startEvent) {
        this.streamSid = startEvent.streamSid;
        this.callSid = startEvent.callSid;

        const params = startEvent.customParameters || {};
        this.from = params.from || 'unknown';
        this.to = params.to || 'unknown';
        this.startedAt = Date.now();

        console.log(`[call ${this.callSid}] started, from=${this.from} to=${this.to}`);

        try {
            const { greeting_text: greeting } = await laravelClient.startCall({
                callSid: this.callSid,
                from: this.from,
                to: this.to,
            });

            await this.speak(greeting);
        } catch (err) {
            console.error(`[call ${this.callSid}] call-start failed:`, err.message);
            await this.handleLaravelDown({ callNeverStarted: true });
        }
    }

    async handleMedia(mediaEvent) {
        const mulawBuffer = Buffer.from(mediaEvent.payload, 'base64');
        const pcm = mulaw.decodeBuffer(mulawBuffer);

        if (this.currentPlayback && !this.currentPlayback.stopped) {
            if (config.vad.bargeInEnabled && this.bargeIn.pushFrame(pcm)) {
                console.log(`[call ${this.callSid}] barge-in detected, stopping playback`);
                this.currentPlayback.stop();
                this.bargeIn.reset();
                this.endpointer.reset();
                this.utteranceFrames = [pcm];
                this.endpointer.pushFrame(pcm); // count the frame that triggered barge-in
            }
            return; // still mid-playback (or just interrupted this frame) - don't also endpoint it below
        }

        this.utteranceFrames.push(pcm);
        const result = this.endpointer.pushFrame(pcm);

        if (result === 'discarded') {
            this.endpointer.reset();
            this.utteranceFrames = [];
        } else if (result === 'end_of_turn') {
            const frames = this.utteranceFrames;
            this.utteranceFrames = [];
            this.endpointer.reset();
            await this.processUtterance(frames);
        }
    }

    async processUtterance(frames) {
        const totalSamples = frames.reduce((sum, f) => sum + f.length, 0);
        const pcm8k = new Int16Array(totalSamples);
        let offset = 0;
        for (const f of frames) {
            pcm8k.set(f, offset);
            offset += f.length;
        }

        const pcm16k = resample(pcm8k, TWILIO_SAMPLE_RATE, STT_SAMPLE_RATE);

        let transcript;
        try {
            transcript = await sttClient.transcribe(pcm16k, STT_SAMPLE_RATE);
        } catch (err) {
            console.error(`[call ${this.callSid}] STT failed:`, err.message);
            await this.handleLaravelDown();
            return;
        }

        if (!transcript) {
            return; // nothing intelligible - just keep listening
        }

        console.log(`[call ${this.callSid}] caller: ${transcript}`);

        let turnResult;
        try {
            turnResult = await laravelClient.turn({ callSid: this.callSid, transcript });
        } catch (err) {
            console.error(`[call ${this.callSid}] turn failed:`, err.message);
            await this.handleLaravelDown();
            return;
        }

        console.log(`[call ${this.callSid}] AI: ${turnResult.reply_text}`);
        await this.speak(turnResult.reply_text);

        if (turnResult.escalate) {
            await this.handleEscalation(turnResult.human_handoff_phone_number);
        }
    }

    /** @param {string} text */
    async speak(text) {
        if (!text) return;

        let synthesized;
        try {
            synthesized = await ttsClient.synthesize(text);
        } catch (err) {
            console.error(`[call ${this.callSid}] TTS failed:`, err.message);
            return; // can't speak - caller hears silence for this one turn, not fatal to the call
        }

        const pcm8k = resample(synthesized.samples, synthesized.sampleRate, TWILIO_SAMPLE_RATE);

        this.currentPlayback = new Playback(this.ws, this.streamSid);
        await this.currentPlayback.play(pcm8k);
        this.currentPlayback = null;
    }

    /**
     * escalate=true from Laravel: reply_text (already spoken by speak() in
     * processUtterance) reads as the handoff line per the system prompt's
     * own escalation rule - no separate line needed for that part. This
     * only handles what happens AFTER it: transfer if possible, else a
     * fixed fallback and end the call.
     */
    async handleEscalation(humanNumber) {
        if (humanNumber) {
            const transferred = await twilioRest.transferToHuman(this.callSid, humanNumber);
            if (transferred) {
                console.log(`[call ${this.callSid}] transferred to ${humanNumber}`);
                return; // Twilio will end this Media Stream once the <Dial> takes over
            }
            console.error(`[call ${this.callSid}] transfer to ${humanNumber} failed`);
        }

        await this.speak(NO_HUMAN_AVAILABLE_LINE);
        await twilioRest.endCall(this.callSid);
    }

    /**
     * Laravel (or STT) failed mid-call. Never leave the caller in silence:
     * apologize, then fall back to the same transfer-or-end logic as a
     * normal escalation, using a bridge-local emergency number if
     * configured (this path exists specifically for when Laravel - and
     * therefore its configured handoff number - may be unreachable).
     */
    async handleLaravelDown({ callNeverStarted = false } = {}) {
        await this.speak(callNeverStarted ? TECHNICAL_DIFFICULTY_LINE : AI_TROUBLE_LINE);
        await this.handleEscalation(config.fallback.emergencyHumanNumber);
    }

    async handleStop() {
        if (this.ended) return;
        this.ended = true;

        const durationSeconds = this.startedAt
            ? Math.round((Date.now() - this.startedAt) / 1000)
            : null;

        console.log(`[call ${this.callSid}] ended, duration=${durationSeconds}s`);

        try {
            await laravelClient.endCall({ callSid: this.callSid, durationSeconds });
        } catch (err) {
            // Caller isn't waiting on this - log and move on. Worst case a
            // VoiceCallSession is left in_progress; that's a reconciliation
            // job for Laravel, not something worth retrying here (see the
            // architecture plan's section 2.5).
            console.error(`[call ${this.callSid}] call-end notify failed:`, err.message);
        }
    }
}

/**
 * Wires one Twilio Media Streams WebSocket connection to a CallSession.
 * @param {import('ws').WebSocket} ws
 */
function handleConnection(ws) {
    const session = new CallSession(ws);

    ws.on('message', async (raw) => {
        let event;
        try {
            event = JSON.parse(raw.toString());
        } catch {
            return;
        }

        try {
            switch (event.event) {
                case 'connected':
                    break;
                case 'start':
                    await session.handleStart(event.start);
                    break;
                case 'media':
                    await session.handleMedia(event.media);
                    break;
                case 'stop':
                    await session.handleStop();
                    break;
                default:
                    break;
            }
        } catch (err) {
            console.error(`[call ${session.callSid || 'unknown'}] unhandled error processing "${event.event}":`, err);
        }
    });

    ws.on('close', () => {
        session.handleStop();
    });

    ws.on('error', (err) => {
        console.error(`[call ${session.callSid || 'unknown'}] WebSocket error:`, err.message);
    });
}

module.exports = { handleConnection, CallSession, Playback };
