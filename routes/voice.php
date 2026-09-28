<?php

use App\Http\Controllers\Voice\TwilioIncomingCallController;
use App\Http\Controllers\Voice\VoiceBridgeController;
use Illuminate\Support\Facades\Route;

// The staff-facing Voice Calls screen has been taken out of the app - no
// sidebar entry, no route, and 'voice' is no longer a grantable module. The
// call handling below stays live: a call still reaches Twilio, still runs
// through the bridge, and still lands on its WhatsApp contact thread.
// VoiceCallSessionController and resources/views/voice_calls are left in
// place, unrouted, for whenever the screen is wanted back.

// Twilio calls this directly (no session auth) when a call comes in to the clinic's number.
Route::post('/voice/twilio/incoming', TwilioIncomingCallController::class)->name('voice.twilio.incoming');

// The Node bridge calls these - service-to-service, shared-secret auth (see
// VoiceBridgeController::authenticateBridge()), not user-facing.
Route::post('/voice/bridge/call-start', [VoiceBridgeController::class, 'startCall'])->name('voice.bridge.callStart');
Route::post('/voice/bridge/turn', [VoiceBridgeController::class, 'turn'])->name('voice.bridge.turn');
Route::post('/voice/bridge/call-end', [VoiceBridgeController::class, 'endCall'])->name('voice.bridge.callEnd');
