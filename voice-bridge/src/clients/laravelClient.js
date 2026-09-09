'use strict';

const config = require('../config');

/**
 * Every bridge->Laravel call is short-timeout-and-fallback (section 2.5 of
 * the plan): the caller must never be left in dead silence because Laravel
 * is slow or down. This module only makes the requests; the caller decides
 * the fallback behavior on failure.
 */

async function postJson(path, body) {
    const controller = new AbortController();
    const timeout = setTimeout(() => controller.abort(), config.laravel.timeoutMs);

    try {
        const response = await fetch(`${config.laravel.baseUrl}${path}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Voice-Bridge-Secret': config.laravel.sharedSecret,
            },
            body: JSON.stringify(body),
            signal: controller.signal,
        });

        if (!response.ok) {
            throw new Error(`Laravel ${path} returned HTTP ${response.status}`);
        }

        return await response.json();
    } finally {
        clearTimeout(timeout);
    }
}

/** @returns {Promise<{session_id: number, greeting_text: string}>} */
function startCall({ callSid, from, to }) {
    return postJson('/voice/bridge/call-start', {
        provider_call_sid: callSid,
        from_number: from,
        to_number: to,
    });
}

/** @returns {Promise<{reply_text: string, escalate: boolean, human_handoff_phone_number: string|null}>} */
function turn({ callSid, transcript }) {
    return postJson('/voice/bridge/turn', {
        provider_call_sid: callSid,
        transcript,
    });
}

function endCall({ callSid, durationSeconds }) {
    return postJson('/voice/bridge/call-end', {
        provider_call_sid: callSid,
        duration_seconds: durationSeconds,
    });
}

module.exports = { startCall, turn, endCall };
