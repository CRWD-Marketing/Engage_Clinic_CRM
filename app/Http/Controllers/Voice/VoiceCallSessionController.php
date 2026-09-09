<?php

namespace App\Http\Controllers\Voice;

use App\Http\Controllers\Controller;
use App\Models\VoiceCallSession;
use Illuminate\Http\Request;

class VoiceCallSessionController extends Controller
{
    public function index(Request $request)
    {
        $sessions = VoiceCallSession::with('contact')->orderByDesc('started_at')->get();

        $activeSession = $request->filled('session')
            ? $sessions->firstWhere('id', (int) $request->query('session'))
            : $sessions->first();

        $transcript = $activeSession ? $activeSession->transcriptMessages()->get() : collect();

        return view('voice_calls.index', compact('sessions', 'activeSession', 'transcript'));
    }

    public function show(VoiceCallSession $session)
    {
        return redirect()->route('voice_calls.index', ['session' => $session->id]);
    }
}
