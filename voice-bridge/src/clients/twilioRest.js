'use strict';

const config = require('../config');

// Mirrors TwilioVoiceCallService.php's REST calls. Held bridge-side (not
// proxied through Laravel) specifically so mid-call transfer/hangup still
// works if Laravel itself is down - see config.js's note on this. This is
// the ONLY place the bridge talks to Twilio's REST API; everything else is
// the Media Streams WebSocket.

const API_BASE = 'https://api.twilio.com/2010-04-01';

function escapeXml(value) {
    return String(value).replace(/[<>&'"]/g, (c) => ({
        '<': '&lt;', '>': '&gt;', '&': '&amp;', "'": '&apos;', '"': '&quot;',
    }[c]));
}

async function updateCall(callSid, params) {
    const { accountSid, authToken } = config.twilio;

    if (!accountSid || !authToken) {
        console.error('[twilioRest] Cannot update call - TWILIO_ACCOUNT_SID/TWILIO_AUTH_TOKEN not configured.');
        return false;
    }

    const auth = Buffer.from(`${accountSid}:${authToken}`).toString('base64');
    const body = new URLSearchParams(params);

    const response = await fetch(`${API_BASE}/Accounts/${accountSid}/Calls/${callSid}.json`, {
        method: 'POST',
        headers: {
            Authorization: `Basic ${auth}`,
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: body.toString(),
    });

    if (!response.ok) {
        console.error(`[twilioRest] Call update failed for ${callSid}: HTTP ${response.status} ${await response.text()}`);
    }

    return response.ok;
}

function transferToHuman(callSid, humanNumber) {
    const twiml = `<?xml version="1.0" encoding="UTF-8"?><Response><Dial>${escapeXml(humanNumber)}</Dial></Response>`;
    return updateCall(callSid, { Twiml: twiml });
}

function endCall(callSid) {
    return updateCall(callSid, { Status: 'completed' });
}

module.exports = { transferToHuman, endCall };
