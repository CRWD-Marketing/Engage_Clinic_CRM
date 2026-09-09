# Engage Clinic Voice Bridge

Always-on Node.js service that bridges Twilio Media Streams audio to Engage
Clinic's Laravel AI Employee for real-time inbound phone calls. It never
talks to Ollama directly, only to Laravel's `/voice/bridge/*` endpoints with
transcript text in, reply text out. Laravel remains the sole AI brain.

This lives inside the Laravel repo (`Engage_Clinic/voice-bridge/`) for
convenience, but it is a genuinely separate deployable: it needs an
always-on process (not the shared/cron-ping hosting the Laravel app itself
runs on), so it is not part of the PHP application and does not get
deployed the same way. Think of it as a sibling service that happens to
live in the same folder tree, not a subsystem of the Laravel app.

## What it does

1. Accepts Twilio's Media Streams WebSocket for one call at a time per connection.
2. On call start: fetches the configured greeting from Laravel, synthesizes it via TTS, plays it.
3. Buffers the caller's audio, uses simple energy-based VAD to detect end-of-utterance (~700ms silence by default).
4. Transcribes the utterance (faster-whisper self-hosted, or AssemblyAI - see `VOICE_STT_PROVIDER` below), sends the transcript to Laravel, gets back the AI Employee's reply.
5. Synthesizes the reply via a self-hosted Piper server and streams it back to the caller.
6. Detects the caller talking over the AI (barge-in) and stops playback early.
7. Handles escalation (transfer to a human number) and Laravel-down fallbacks without ever leaving the caller in silence.
8. Notifies Laravel when the call ends so it can generate a post-call summary.

See `../app/Services/Voice` for the Laravel side this talks to, and the
architecture plan for the full design rationale (VAD tuning, barge-in
limitations, credential placement).

## Setup

```
cd voice-bridge
npm install
cp .env.example .env
# fill in LARAVEL_BASE_URL, VOICE_BRIDGE_SHARED_SECRET (must match Laravel's
# voice.bridge.shared_secret), TWILIO_ACCOUNT_SID/AUTH_TOKEN, and either the
# STT/TTS server URLs or ASSEMBLYAI_API_KEY depending on VOICE_STT_PROVIDER.
npm start
```

### Speech-to-text: two options

Set `VOICE_STT_PROVIDER`:
- `faster_whisper` (default) - requires a self-hosted faster-whisper HTTP
  server (`POST /transcribe`, multipart `audio` field, `{"text": "..."}`
  response) reachable at `VOICE_STT_BASE_URL`. Not included in this repo -
  it's a small wrapper around the open-source faster-whisper project.
- `assemblyai` - requires `ASSEMBLYAI_API_KEY`. No server to run, but each
  utterance is an upload+submit+poll round trip against AssemblyAI's REST
  API (typically a few seconds), noticeably slower per conversational turn
  than a self-hosted server would be. This can be the same account/key as
  the Laravel side's `config('voice.stt.driver')`, or a different one - the
  bridge never routes audio through Laravel, so the two choices are
  independent.

Text-to-speech always needs a self-hosted Piper HTTP server (`POST
/synthesize`, `{"text","voice"}` JSON, raw WAV response) reachable at
`VOICE_TTS_BASE_URL` - also not included here.

## Testing without a real phone call

`npm test` runs `test/selftest.js`, which exercises the pure-logic pieces
(mulaw codec round-trip, resampler, WAV encode/decode, VAD endpointing) with
no network/Twilio/STT/TTS dependency - useful for verifying a change to
`src/audio/*` or `src/vad.js` didn't break anything, but does not exercise
the network-calling code in `src/clients/*` or the full call flow in
`src/twilioStream.js`.

Testing the full flow needs: this service running and reachable at a public
`wss://` URL (e.g. via ngrok during development, same as how the Laravel
app's own webhook testing works), the Laravel app configured with a matching
`VOICE_BRIDGE_SHARED_SECRET` and `VOICE_BRIDGE_BASE_URL` pointing at that
URL, STT/TTS reachable, and a Twilio number's Voice webhook pointed at the
Laravel app's `/voice/twilio/incoming`.

## Known limitations (MVP, by design - see the architecture plan)

- **Not streaming transcription.** Each utterance is batch-transcribed after
  VAD detects a pause, not transcribed word-by-word. This matches how
  production self-hosted-Whisper voice agents actually work, and is even
  more true of the AssemblyAI REST option (a full upload+poll cycle per turn).
- **Barge-in has no acoustic echo cancellation.** On a speakerphone-style
  audio path, the AI's own voice leaking into the mic can be mistaken for
  the caller interrupting. If this proves disruptive in testing, set
  `VOICE_BARGEIN_ENABLED=false`.
- **The resampler is linear interpolation**, not a proper sample-rate-converter
  filter - adequate for telephony-bandwidth speech, not hi-fi audio.
- **VAD is a fixed energy threshold**, not adaptive to a line's actual noise
  floor. Expect to tune `VOICE_VAD_THRESHOLD` from real test calls.
