<?php

use App\Http\Controllers\Voice\TwilioIncomingCallController;
use App\Http\Controllers\Voice\VoiceBridgeController;
use App\Http\Controllers\Voice\VoiceCallSessionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'feature:voice'])
    ->group(function () {

        Route::get('/voice-calls', [VoiceCallSessionController::class, 'index'])->name('voice_calls.index');
        Route::get('/voice-calls/{session}', [VoiceCallSessionController::class, 'show'])->name('voice_calls.show');

    });

// Twilio calls this directly (no session auth) when a call comes in to the clinic's number.
Route::post('/voice/twilio/incoming', TwilioIncomingCallController::class)->name('voice.twilio.incoming');

// The Node bridge calls these - service-to-service, shared-secret auth (see
// VoiceBridgeController::authenticateBridge()), not user-facing.
Route::post('/voice/bridge/call-start', [VoiceBridgeController::class, 'startCall'])->name('voice.bridge.callStart');
Route::post('/voice/bridge/turn', [VoiceBridgeController::class, 'turn'])->name('voice.bridge.turn');
Route::post('/voice/bridge/call-end', [VoiceBridgeController::class, 'endCall'])->name('voice.bridge.callEnd');
