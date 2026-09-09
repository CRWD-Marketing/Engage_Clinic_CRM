'use strict';

const http = require('http');
const express = require('express');
const { WebSocketServer } = require('ws');

const config = require('./config');
const { handleConnection } = require('./twilioStream');

const app = express();

app.get('/health', (req, res) => {
    res.json({ status: 'ok' });
});

const server = http.createServer(app);

// Twilio's <Stream url="wss://.../twilio-stream/{callSid}"> - the callSid
// segment is informational (also arrives in the `start` event payload,
// which is what CallSession actually keys off); accepting any path under
// /twilio-stream/ keeps this route tolerant of that. noServer:true because
// ws's built-in `path` option only matches one exact string, not a pattern
// with a variable segment - upgrades are routed manually below instead.
const wss = new WebSocketServer({ noServer: true });

server.on('upgrade', (request, socket, head) => {
    if (!request.url || !request.url.startsWith('/twilio-stream/')) {
        socket.destroy();
        return;
    }

    wss.handleUpgrade(request, socket, head, (ws) => {
        wss.emit('connection', ws, request);
    });
});

wss.on('connection', (ws) => {
    handleConnection(ws);
});

server.listen(config.port, () => {
    console.log(`Voice bridge listening on port ${config.port}`);
    console.log(`Laravel base URL: ${config.laravel.baseUrl || '(not configured)'}`);
    console.log(`STT provider: ${config.stt.provider}`);
});
