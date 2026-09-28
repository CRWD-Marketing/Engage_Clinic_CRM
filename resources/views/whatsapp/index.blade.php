@extends('layouts.admin-sidebar')

@section('title', 'WhatsApp · Engage Clinic')
@section('page-title', '')
@section('page-subtitle', '')
@section('content-class', 'content-fill-height content-full-width')

@section('content')
    @php
        $avatarColor = fn ($seed) => \App\Models\WhatsappContact::avatarColor($seed);
        $tick = fn (?string $status) => \App\Models\WhatsappMessage::tickIcon($status);
        $channelBadge = fn (string $channel) => \App\Models\WhatsappContact::channelBadgeHtml($channel);
    @endphp

    <style>
        .wa-contact-row { transition: background 0.12s ease; }
        .wa-contact-row:hover { background: #FAF5EC !important; }
        .wa-contact-row.is-active:hover { background: #F5EFE7 !important; }
        .wa-search-input:focus { border-color: #C8AF8F; background: #FFFDFA !important; }
        .wa-msg-input:focus { border-color: #1FA855; box-shadow: 0 0 0 3px rgba(31, 168, 85, 0.12); }
        .wa-send-btn { transition: background 0.12s ease, transform 0.06s ease; }
        .wa-send-btn:hover { background: #17903F; }
        .wa-send-btn:active { transform: scale(0.97); }
        .wa-view-lead { transition: background 0.12s ease; }
        .wa-view-lead:hover { background: #AD2A52; }
        .wa-live-dot { position: relative; }
        .wa-live-dot::after {
            content: ''; position: absolute; inset: 0; border-radius: 50%;
            background: #1FA855; animation: wa-pulse 2s ease-out infinite;
        }
        @keyframes wa-pulse {
            0% { opacity: 0.6; transform: scale(1); }
            100% { opacity: 0; transform: scale(2.4); }
        }
        .wa-contact-list::-webkit-scrollbar, .wa-messages::-webkit-scrollbar { width: 6px; }
        .wa-contact-list::-webkit-scrollbar-thumb, .wa-messages::-webkit-scrollbar-thumb { background: #E2DACE; border-radius: 3px; }
        .wa-messages {
            background-image: radial-gradient(rgba(43, 58, 76, 0.05) 1px, transparent 1px);
            background-size: 22px 22px;
            overscroll-behavior: contain;
        }
        .wa-contact-list, #wa-family-panel {
            overscroll-behavior: contain;
        }
        .wa-date-divider { display: flex; align-items: center; justify-content: center; margin: 2px 0 8px; }
        .wa-date-divider span {
            background: #FFFDFA; color: #8A7D6C; font: 700 10px 'Nunito Sans'; letter-spacing: 0.5px;
            text-transform: uppercase; padding: 4px 13px; border-radius: 20px; box-shadow: 0 1px 3px rgba(43,58,76,0.08);
        }
        #wa-channel-filter { overflow-x: auto; scrollbar-width: none; -ms-overflow-style: none; }
        #wa-channel-filter::-webkit-scrollbar { display: none; }
        .wa-filter-pill { flex-shrink: 0; white-space: nowrap; }
        .wa-msg-status { font: 700 10px 'Nunito Sans'; color: #98897A; padding-right: 3px; }
        .wa-msg-status:empty { display: none; }
        .wa-msg-status.is-failed { color: #C8355F; cursor: help; }
        .wa-chat-actions { display: flex; align-items: center; gap: 10px; flex-shrink: 0; }
        .wa-panel-backdrop { display: none !important; }

        /* --- Responsive -------------------------------------------------------
           Three columns on a wide screen. Under 1180px the family panel leaves
           the flow and becomes a slide-over drawer, so the thread keeps a usable
           width. Under 820px it goes one pane at a time the way a phone
           messaging app does: the conversation list, or the thread with a back
           arrow. Which one shows is decided by whether a conversation is
           selected, so the existing ?contact=... navigation already drives it
           and there is no extra client-side routing to keep in sync.           */
        @media (max-width: 1180px) {
            .wa-sidebar { width: 272px !important; }
            .wa-chat { min-width: 0 !important; }
            .wa-details-toggle { display: inline-flex !important; }
            .wa-panel-close { display: inline-flex !important; }
            .wa-panel {
                position: fixed; top: 0; right: 0; bottom: 0;
                width: min(320px, 86vw) !important;
                z-index: 1200; /* above the layout's own mobile sidebar rail */
                transform: translateX(102%);
                transition: transform 0.22s ease;
                box-shadow: -14px 0 44px rgba(43, 58, 76, 0.18);
            }
            .wa-panel.is-open { transform: translateX(0); }
            .wa-panel-backdrop.is-open {
                display: block !important;
                position: fixed; inset: 0; z-index: 1190;
                background: rgba(43, 58, 76, 0.34);
            }
        }

        /* --- Phone: Facebook Messenger's shape in Engage Clinic's colours -----
           One pane at a time - the conversation list, then the thread behind a
           back arrow - with the roomier rows, round avatars, pill search, fully
           rounded bubbles and circular send button Messenger uses. The chat's
           controls sit behind the header's person button, which is where
           Messenger keeps a conversation's settings too.                       */
        @media (max-width: 820px) {
            .wa-inbox { overflow-x: hidden !important; }
            .wa-sidebar { width: 100% !important; border-right: none !important; }
            .wa-inbox.wa-has-active .wa-sidebar { display: none !important; }
            .wa-inbox:not(.wa-has-active) .wa-chat { display: none !important; }
            /* The controller preselects the newest conversation so the desktop
               three-pane view is never empty; on a phone that pane is not on
               screen yet, so it must not look picked either. */
            .wa-inbox:not(.wa-has-active) .wa-contact-row.is-active { background: transparent !important; }

            /* Conversation list */
            .wa-list-header { padding: 14px 16px 12px !important; border-bottom: none !important; }
            .wa-list-title { font-size: 25px !important; letter-spacing: -0.3px; }
            .wa-list-sub { display: none; }
            .wa-search-input { border-radius: 22px !important; padding: 10px 14px 10px 34px !important; background: #F1EDE6 !important; }
            .wa-contact-row { padding: 9px 14px !important; gap: 13px !important; border-bottom: none !important; }
            .wa-row-avatar { width: 52px !important; height: 52px !important; font-size: 18px !important; }
            .wa-row-name { font-size: 15px !important; }
            .wa-row-preview { font-size: 13.5px !important; }
            .wa-row-time { font-size: 12px !important; }
            .wa-unread {
                min-width: 19px; height: 19px; align-self: center;
                display: flex; align-items: center; justify-content: center;
                border-radius: 50% !important; padding: 0 4px !important; font-size: 10.5px !important;
            }

            /* Thread */
            .wa-chat-header { padding: 8px 12px !important; gap: 10px !important; flex-wrap: nowrap !important; }
            .wa-back-btn { display: inline-flex !important; }
            .wa-chat-actions { flex: 0 0 auto; gap: 5px; }
            .wa-chat-header .wa-ai-state-form { display: none !important; }
            .wa-details-toggle { border-radius: 50% !important; }
            .wa-messages { padding: 14px 12px !important; gap: 6px !important; }
            .wa-msg-avatar { display: block !important; }
            .wa-bubble { max-width: 78% !important; border-radius: 18px !important; }
            .wa-send-error { padding: 10px 14px !important; }

            /* Composer */
            .wa-send-form { padding: 9px 12px !important; gap: 8px !important; align-items: center; }
            .wa-msg-input { border-radius: 22px !important; padding: 10px 16px !important; }
            .wa-send-btn {
                width: 42px; height: 42px; flex-shrink: 0;
                padding: 0 !important; border-radius: 50% !important; justify-content: center;
            }
            .wa-send-btn-label { display: none; }

            /* iOS zooms the whole page in when a focused field is under 16px. */
            .wa-msg-input, .wa-search-input { font-size: 16px !important; }

            /* Details sheet - full width, the way Messenger's chat settings open. */
            .wa-panel { width: 100% !important; }
            .wa-panel-mobile-actions { display: flex !important; }
        }

        @media (max-width: 480px) {
            .wa-list-title { font-size: 23px !important; }
            .wa-row-avatar { width: 48px !important; height: 48px !important; }
            .wa-bubble { max-width: 82% !important; }
        }

        /* A phone's browser chrome eats into 100vh, which would push the reply
           box below the fold; dvh tracks the height actually on screen. */
        @supports (height: 100dvh) {
            @media (max-width: 820px) {
                body.admin-body, body.admin-body .main-content { height: 100dvh; }
            }
        }
    </style>

    <!-- WhatsApp Inbox -->
    <div class="wa-inbox {{ request()->filled('contact') && $activeContact ? 'wa-has-active' : '' }}" style="flex: 1; display: flex; min-height: 0; overflow-x: auto; margin: -22px -28px;">

        <!-- Left Sidebar - Chat List -->
        <div class="wa-sidebar" style="width: 320px; min-height: 0; border-right: 1px solid #EBE4DA; background: #FFFDFA; display: flex; flex-direction: column; flex-shrink: 0;">
            <div class="wa-list-header" style="padding: 16px 18px; border-bottom: 1px solid #EBE4DA;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span style="position: relative; width: 8px; height: 8px;">
                        <span class="wa-live-dot" style="display: block; width: 8px; height: 8px; border-radius: 50%; background: #1FA855;"></span>
                    </span>
                    <div class="wa-list-title" style="font: 600 16px 'Baloo 2'; color: #16436E;">Messages</div>
                </div>
                <div class="wa-list-sub" style="font: 600 11.5px 'Nunito Sans'; color: #98897A; margin-top: 1px;">WhatsApp + Instagram · leads auto-captured</div>

                @if ($contacts->isNotEmpty())
                    <div style="position: relative; margin-top: 12px;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" style="position: absolute; left: 11px; top: 50%; transform: translateY(-50%); pointer-events: none;">
                            <circle cx="11" cy="11" r="7" stroke="#B0A493" stroke-width="2"/>
                            <path d="M21 21L16.65 16.65" stroke="#B0A493" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                        <input
                            type="text"
                            id="wa-contact-search"
                            placeholder="Search conversations…"
                            class="wa-search-input"
                            style="width: 100%; padding: 8px 12px 8px 32px; border: 1px solid #E2DACE; border-radius: 9px; background: #F6F3EE; font: 600 12px 'Nunito Sans'; color: #2B3A4C; outline: none; box-sizing: border-box;"
                        >
                    </div>
                    <div id="wa-channel-filter" style="display: flex; gap: 6px; margin-top: 10px; padding-bottom: 2px;">
                        <button type="button" data-channel-filter="all" class="wa-filter-pill is-active" style="border: 1px solid #E2DACE; background: #2B3A4C; color: white; border-radius: 20px; padding: 4px 11px; font: 700 11px 'Nunito Sans'; cursor: pointer;">All</button>
                        <button type="button" data-channel-filter="whatsapp" class="wa-filter-pill" style="border: 1px solid #E2DACE; background: #F6F3EE; color: #5A6B7E; border-radius: 20px; padding: 4px 11px; font: 700 11px 'Nunito Sans'; cursor: pointer;">WhatsApp</button>
                        <button type="button" data-channel-filter="instagram" class="wa-filter-pill" style="border: 1px solid #E2DACE; background: #F6F3EE; color: #5A6B7E; border-radius: 20px; padding: 4px 11px; font: 700 11px 'Nunito Sans'; cursor: pointer;">Instagram</button>
                        <button type="button" data-channel-filter="facebook" class="wa-filter-pill" style="border: 1px solid #E2DACE; background: #F6F3EE; color: #5A6B7E; border-radius: 20px; padding: 4px 11px; font: 700 11px 'Nunito Sans'; cursor: pointer;">Facebook</button>
                    </div>
                @endif
            </div>
            <div id="wa-contact-list" class="wa-contact-list" style="flex: 1; min-height: 0; overflow-y: auto;">
                @forelse ($contacts as $contact)
                    @include('whatsapp.partials.contact_row', ['contact' => $contact, 'activeContactId' => $activeContact->id ?? null])
                @empty
                    <div style="display: flex; flex-direction: column; align-items: center; text-align: center; gap: 12px; padding: 48px 24px;">
                        <div style="width: 52px; height: 52px; border-radius: 50%; background: #F3EDE3; display: flex; align-items: center; justify-content: center;">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M21 11.5C21.0034 12.8199 20.6951 14.1219 20.1 15.3C19.3944 16.7118 18.3097 17.8992 16.9674 18.7293C15.6251 19.5594 14.0787 19.9994 12.5 20C11.1801 20.0035 9.87812 19.6951 8.7 19.1L3 21L4.9 15.3C4.30493 14.1219 3.99656 12.8199 4 11.5C4.00061 9.92127 4.44061 8.37485 5.27072 7.03258C6.10083 5.6903 7.28825 4.6056 8.7 3.9C9.87812 3.30493 11.1801 2.99656 12.5 3H13C15.0843 3.11499 17.053 3.99476 18.5291 5.47086C20.0052 6.94696 20.885 8.91565 21 11V11.5Z" stroke="#B0A493" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </div>
                        <div style="font: 800 13.5px 'Nunito Sans'; color: #2B3A4C;">No conversations yet</div>
                        <div style="font: 600 12px/1.5 'Nunito Sans'; color: #98897A;">Messages sent to your WhatsApp Business number will show up here automatically.</div>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Middle - Chat Messages -->
        <div class="wa-chat" style="flex: 1; display: flex; flex-direction: column; min-width: 380px; min-height: 0; background: #F1EBE1;">
            @if ($activeContact)
                <!-- Chat Header -->
                <div class="wa-chat-header" style="display: flex; align-items: center; gap: 12px; padding: 13px 20px; border-bottom: 1px solid #E4DCCE; background: #FFFDFA; flex-wrap: wrap;">
                    {{-- Only ever on screen in the one-pane phone layout, where the
                         conversation list is hidden while a chat is open. --}}
                    <a href="{{ route('whatsapp.index') }}" class="wa-back-btn" aria-label="Back to conversations" style="display: none; align-items: center; justify-content: center; width: 30px; height: 30px; margin-left: -6px; border-radius: 9px; text-decoration: none; flex-shrink: 0;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M15 18L9 12L15 6" stroke="#16436E" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </a>
                    <div style="position: relative; flex-shrink: 0;">
                        @if ($activeContact->avatar_url)
                            <img src="{{ $activeContact->avatar_url }}" alt="" style="width: 38px; height: 38px; border-radius: 50%; object-fit: cover; display: block;">
                        @else
                            <div style="width: 38px; height: 38px; border-radius: 50%; background: {{ $avatarColor($activeContact->wa_id) }}; color: white; display: flex; align-items: center; justify-content: center; font: 600 14px 'Baloo 2';">
                                {{ strtoupper(substr($activeContact->name ?? $activeContact->wa_id, 0, 2)) }}
                            </div>
                        @endif
                        {!! $channelBadge($activeContact->channel) !!}
                    </div>
                    <div style="flex: 1; min-width: 0;">
                        <div style="display: flex; align-items: center; gap: 7px; min-width: 0;">
                            <div style="font: 800 14.5px 'Nunito Sans'; color: #2B3A4C; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $activeContact->name ?? $activeContact->wa_id }}</div>
                            @if ($activeContact->needs_human_attention)
                                <span title="{{ $activeContact->needs_human_reason }}" style="display: inline-flex; align-items: center; gap: 4px; background: #FDECEE; color: #C8355F; border-radius: 999px; padding: 2px 8px; font: 800 10px 'Nunito Sans';">● Needs attention</span>
                            @endif
                        </div>
                        <div style="font: 600 11.5px 'Nunito Sans'; color: #98897A;">
                            @if ($activeContact->channel === 'instagram')
                                {{ '@' }}{{ $activeContact->name ?? 'Instagram DM' }} · Instagram
                            @elseif ($activeContact->channel === 'facebook')
                                {{ $activeContact->name ?? 'Facebook Messenger' }} · Facebook
                            @else
                                +{{ $activeContact->wa_id }} · WhatsApp
                            @endif
                        </div>
                    </div>
                    <div class="wa-chat-actions">
                        <form action="{{ route('whatsapp.updateAiState', $activeContact->id) }}" method="POST" class="wa-ai-state-form" style="margin: 0;">
                            @csrf
                            <select name="ai_state" onchange="this.form.submit()" class="wa-ai-state-select" style="background: #FFFFFF; color: #16436E; border: 1px solid #E2DACE; border-radius: 9px; padding: 7px 10px; font: 800 12px 'Nunito Sans'; cursor: pointer;">
                                @foreach (\App\Models\WhatsappContact::aiStateLabels() as $value => $label)
                                    <option value="{{ $value }}" @selected($activeContact->ai_state === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </form>
                        {{-- Below 1180px the family panel is a drawer rather than a
                             column, so it needs something to open it. --}}
                        <button type="button" id="wa-details-toggle" class="wa-details-toggle" aria-label="Family details" style="display: none; align-items: center; justify-content: center; width: 34px; height: 34px; flex-shrink: 0; background: #FFFFFF; border: 1px solid #E2DACE; border-radius: 9px; cursor: pointer;">
                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none"><path d="M20 21V19C20 17.9391 19.5786 16.9217 18.8284 16.1716C18.0783 15.4214 17.0609 15 16 15H8C6.93913 15 5.92172 15.4214 5.17157 16.1716C4.42143 16.9217 4 17.9391 4 19V21" stroke="#16436E" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><circle cx="12" cy="7" r="4" stroke="#16436E" stroke-width="1.8"/></svg>
                        </button>
                    </div>
                </div>

                <!-- Messages -->
                <div id="wa-messages" class="wa-messages" data-last-message-id="{{ (int) $messages->max('id') }}" style="flex: 1; min-height: 0; overflow-y: auto; padding: 20px 24px; display: flex; flex-direction: column; gap: 10px;">
                    @if ($messages->isNotEmpty())
                        @include('whatsapp.partials.messages', ['messages' => $messages, 'activeContact' => $activeContact, 'previousSentAt' => null])
                    @else
                        <div style="flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 10px; text-align: center;">
                            <div style="width: 44px; height: 44px; border-radius: 50%; background: #FFFDFA; display: flex; align-items: center; justify-content: center;">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M21 11.5C21.0034 12.8199 20.6951 14.1219 20.1 15.3C19.3944 16.7118 18.3097 17.8992 16.9674 18.7293C15.6251 19.5594 14.0787 19.9994 12.5 20C11.1801 20.0035 9.87812 19.6951 8.7 19.1L3 21L4.9 15.3C4.30493 14.1219 3.99656 12.8199 4 11.5C4.00061 9.92127 4.44061 8.37485 5.27072 7.03258C6.10083 5.6903 7.28825 4.6056 8.7 3.9C9.87812 3.30493 11.1801 2.99656 12.5 3H13C15.0843 3.11499 17.053 3.99476 18.5291 5.47086C20.0052 6.94696 20.885 8.91565 21 11V11.5Z" stroke="#B0A493" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </div>
                            <div style="font: 600 12.5px 'Nunito Sans'; color: #98897A;">No messages in this conversation yet.</div>
                        </div>
                    @endif
                </div>

                <div id="wa-send-error" class="wa-send-error" style="flex-shrink: 0; padding: 10px 20px; background: #FDECEE; border-top: 1px solid #F3C9D5; font: 700 12.5px 'Nunito Sans'; color: #C8355F; {{ $errors->any() ? '' : 'display: none;' }}">
                    {{ $errors->first() }}
                </div>

                <!-- Message Input -->
                <form id="wa-send-form" class="wa-send-form" action="{{ route('whatsapp.send') }}" method="POST" style="flex-shrink: 0; display: flex; gap: 10px; padding: 14px 20px; background: #FFFDFA; border-top: 1px solid #E4DCCE;">
                    @csrf
                    <input type="hidden" name="contact_id" value="{{ $activeContact->id }}">
                    <input
                        type="text"
                        name="message"
                        value="{{ old('message') }}"
                        placeholder="Type a reply…"
                        required
                        class="wa-msg-input"
                        style="flex: 1; padding: 11px 16px; border: 1px solid #E2DACE; border-radius: 22px; background: #F6F3EE; font: 600 13.5px 'Nunito Sans'; color: #2B3A4C; outline: none; transition: border-color 0.12s ease, box-shadow 0.12s ease;"
                    >
                    <button type="submit" class="wa-send-btn" style="display: flex; align-items: center; gap: 7px; background: #1FA855; color: white; border: none; border-radius: 22px; padding: 0 20px; font: 800 13px 'Nunito Sans'; cursor: pointer;">
                        <span class="wa-send-btn-label">Send</span>
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M22 2L11 13" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M22 2L15 22L11 13L2 9L22 2Z" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </button>
                </form>
                <script>
                    (function () {
                        var messages = document.getElementById('wa-messages');
                        if (!messages) return;

                        // Bound the message thread to the space between the chat header and the
                        // reply box so it scrolls in place — the reply box stays put instead of
                        // being pushed off-screen once a conversation has a lot of messages.
                        function sizeMessages() {
                            // Stay pinned to the newest message across a resize if that
                            // is where the thread already was - on a phone this fires
                            // when the keyboard opens, and the reply being typed must
                            // not slide out of view behind it.
                            var wasNearBottom = messages.scrollHeight - messages.scrollTop - messages.clientHeight < 80;
                            var top = messages.getBoundingClientRect().top;
                            var after = 0;
                            var sibling = messages.nextElementSibling;
                            while (sibling) {
                                after += sibling.getBoundingClientRect().height;
                                sibling = sibling.nextElementSibling;
                            }
                            var viewport = (window.visualViewport && window.visualViewport.height) || window.innerHeight;
                            messages.style.maxHeight = Math.max(viewport - top - after, 160) + 'px';
                            if (wasNearBottom) messages.scrollTop = messages.scrollHeight;
                        }

                        sizeMessages();
                        messages.scrollTop = messages.scrollHeight;
                        window.addEventListener('resize', sizeMessages);
                        window.addEventListener('orientationchange', sizeMessages);
                        // iOS resizes the visual viewport for the on-screen keyboard
                        // without ever firing a window resize event.
                        if (window.visualViewport) window.visualViewport.addEventListener('resize', sizeMessages);
                    })();
                    (function () {
                        var form = document.getElementById('wa-send-form');
                        if (!form) return;

                        var messagesEl = document.getElementById('wa-messages');
                        var errorBox = document.getElementById('wa-send-error');

                        // Send over fetch instead of a normal form POST, so replying
                        // doesn't navigate/reload the page - the new bubble is appended
                        // straight into the thread from the JSON response below.
                        form.addEventListener('submit', function (e) {
                            e.preventDefault();

                            var btn = form.querySelector('.wa-send-btn');
                            var input = form.querySelector('.wa-msg-input');
                            var csrf = form.querySelector('input[name="_token"]').value;
                            var body = new FormData(form);

                            if (!input.value.trim()) return;

                            // The composer frees up straight away and the button
                            // never sits in a loading state: the message is
                            // written server-side before the response comes back,
                            // so its own bubble reports where it has got to.
                            input.value = '';
                            input.focus();
                            if (errorBox) errorBox.style.display = 'none';

                            var reloading = false;

                            fetch(form.action, {
                                method: 'POST',
                                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                                body: body,
                            })
                                .then(function (r) {
                                    // Read as text first: an expired session makes the auth
                                    // middleware redirect to the login page, which fetch
                                    // follows silently and resolves as a 200 whose body is
                                    // login HTML, not JSON - calling r.json() directly on
                                    // that throws a confusing parse error instead of the
                                    // "your session expired" message this can show instead.
                                    return r.text().then(function (text) {
                                        var data = null;
                                        try { data = text ? JSON.parse(text) : null; } catch (e) { /* not JSON */ }
                                        return { ok: r.ok, status: r.status, data: data };
                                    });
                                })
                                .then(function (result) {
                                    // 419 = CSRF token mismatch; a non-JSON 200 body here means
                                    // the request got bounced to a login page instead - either
                                    // way the session is gone and the embedded CSRF token can
                                    // never become valid again without a fresh page load.
                                    if (result.status === 419 || (result.ok && !result.data)) {
                                        reloading = true;
                                        if (errorBox) {
                                            errorBox.textContent = 'Your session expired - reloading…';
                                            errorBox.style.display = 'block';
                                        }
                                        setTimeout(function () { location.reload(); }, 1200);
                                        return;
                                    }

                                    if (!result.ok) throw new Error((result.data && result.data.message) || 'Failed to send message.');

                                    if (messagesEl && result.data.message_html) {
                                        var newId = result.data.latest_message_id;
                                        // The regular poll tick can win the race and render this
                                        // same message first if it resolves before this request
                                        // does (the row is committed to the DB before this
                                        // response is even built) - skip re-inserting it.
                                        if (!document.getElementById('wa-msg-' + newId)) {
                                            messagesEl.insertAdjacentHTML('beforeend', result.data.message_html);
                                            messagesEl.scrollTop = messagesEl.scrollHeight;
                                        }
                                        if (window.__waAdvanceCursor) window.__waAdvanceCursor(newId);
                                    }

                                    // Pick up the updated sidebar preview/order right away
                                    // instead of waiting for the next 4s poll tick.
                                    if (window.__waPollNow) window.__waPollNow();
                                })
                                .catch(function (err) {
                                    if (errorBox) {
                                        errorBox.textContent = err.message || 'Failed to send message.';
                                        errorBox.style.display = 'block';
                                    }
                                })
                                .finally(function () {
                                    // Only an expired session locks the composer -
                                    // the page is about to reload under it.
                                    btn.disabled = reloading;
                                    input.readOnly = reloading;
                                });
                        });
                    })();
                </script>
            @else
                <div style="flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 12px; text-align: center;">
                    <div style="width: 56px; height: 56px; border-radius: 50%; background: #FFFDFA; display: flex; align-items: center; justify-content: center; box-shadow: 0 1px 2px rgba(43,58,76,0.07);">
                        <svg width="26" height="26" viewBox="0 0 24 24" fill="none"><path d="M21 11.5C21.0034 12.8199 20.6951 14.1219 20.1 15.3C19.3944 16.7118 18.3097 17.8992 16.9674 18.7293C15.6251 19.5594 14.0787 19.9994 12.5 20C11.1801 20.0035 9.87812 19.6951 8.7 19.1L3 21L4.9 15.3C4.30493 14.1219 3.99656 12.8199 4 11.5C4.00061 9.92127 4.44061 8.37485 5.27072 7.03258C6.10083 5.6903 7.28825 4.6056 8.7 3.9C9.87812 3.30493 11.1801 2.99656 12.5 3H13C15.0843 3.11499 17.053 3.99476 18.5291 5.47086C20.0052 6.94696 20.885 8.91565 21 11V11.5Z" stroke="#C8AF8F" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </div>
                    <div style="font: 800 14px 'Nunito Sans'; color: #2B3A4C;">Select a conversation</div>
                    <div style="font: 600 12.5px 'Nunito Sans'; color: #98897A; max-width: 220px;">Choose a chat from the list to view messages and reply.</div>
                </div>
            @endif
        </div>

        <!-- Right Sidebar - Family Details -->
        <div id="wa-family-panel" class="wa-panel" style="width: 290px; min-height: 0; border-left: 1px solid #EBE4DA; background: #FFFDFA; padding: 20px; display: flex; flex-direction: column; gap: 16px; flex-shrink: 0; overflow-y: auto;">
            <div style="display: flex; align-items: center; gap: 10px; flex-shrink: 0;">
                <div style="flex: 1; font: 600 16px 'Baloo 2'; color: #16436E;">Family details</div>
                <button type="button" id="wa-panel-close" class="wa-panel-close" aria-label="Close family details" style="display: none; align-items: center; justify-content: center; width: 30px; height: 30px; flex-shrink: 0; background: #F6F3EE; border: 1px solid #E2DACE; border-radius: 9px; cursor: pointer;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M18 6L6 18M6 6L18 18" stroke="#5A6B7E" stroke-width="2.2" stroke-linecap="round"/></svg>
                </button>
            </div>

            @if ($activeContact)
                <div class="wa-panel-mobile-actions" style="display: none; flex-direction: column; gap: 9px;">
                    <form action="{{ route('whatsapp.updateAiState', $activeContact->id) }}" method="POST" style="margin: 0;">
                        @csrf
                        <select name="ai_state" onchange="this.form.submit()" style="width: 100%; background: #FFFFFF; color: #16436E; border: 1px solid #E2DACE; border-radius: 10px; padding: 11px 10px; font: 800 12.5px 'Nunito Sans'; cursor: pointer;">
                            @foreach (\App\Models\WhatsappContact::aiStateLabels() as $value => $label)
                                <option value="{{ $value }}" @selected($activeContact->ai_state === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </form>
                </div>
            @endif

            @if ($activeContact?->lead)
                @php $lead = $activeContact->lead; @endphp
                <div style="display: flex; flex-direction: column;">
                    <div style="padding: 10px 0; border-bottom: 1px solid #F3EDE3;">
                        <div style="font: 700 10.5px 'Nunito Sans'; color: #98897A; text-transform: uppercase; letter-spacing: 0.6px;">Child</div>
                        <div style="font: 800 13px 'Nunito Sans'; color: #2B3A4C; margin-top: 2px;">{{ $lead->child_name ?? '—' }} @if($lead->child_age) · {{ $lead->child_age }} @endif</div>
                    </div>
                    <div style="padding: 10px 0; border-bottom: 1px solid #F3EDE3;">
                        <div style="font: 700 10.5px 'Nunito Sans'; color: #98897A; text-transform: uppercase; letter-spacing: 0.6px;">Interested in</div>
                        <div style="font: 800 13px 'Nunito Sans'; color: #2B3A4C; margin-top: 2px;">{{ $lead->interested_in ?? '—' }}</div>
                    </div>
                    <div style="padding: 10px 0; border-bottom: 1px solid #F3EDE3;">
                        <div style="font: 700 10.5px 'Nunito Sans'; color: #98897A; text-transform: uppercase; letter-spacing: 0.6px;">Source</div>
                        <div style="font: 800 13px 'Nunito Sans'; color: #2B3A4C; margin-top: 2px;">{{ $lead->source ?? '—' }}</div>
                    </div>
                    <div style="padding: 10px 0; border-bottom: 1px solid #F3EDE3;">
                        <div style="font: 700 10.5px 'Nunito Sans'; color: #98897A; text-transform: uppercase; letter-spacing: 0.6px;">First contact</div>
                        <div style="font: 800 13px 'Nunito Sans'; color: #2B3A4C; margin-top: 2px;">{{ $lead->created_at->format('M j, H:i') }}</div>
                    </div>
                    <div style="padding: 10px 0;">
                        <div style="font: 700 10.5px 'Nunito Sans'; color: #98897A; text-transform: uppercase; letter-spacing: 0.6px;">Insurance mentioned</div>
                        <div style="font: 800 13px 'Nunito Sans'; color: #2B3A4C; margin-top: 2px;">{{ $lead->insurance ?? '—' }}</div>
                    </div>
                </div>

                <div style="text-align: center; background: #E3F1E9; color: #1F7A4D; border-radius: 10px; padding: 12px; font: 800 13px 'Nunito Sans';">In leads pipeline &#10003;</div>
            @elseif ($activeContact)
                @php
                    $childName = $activeContact->child_name ?? $leadHints['child_name'] ?? null;
                    $interestedIn = $activeContact->interested_in ?? $leadHints['interested_in'] ?? null;
                    $insurance = $activeContact->insurance ?? $leadHints['insurance'] ?? null;
                @endphp
                <div style="display: flex; flex-direction: column;">
                    <div style="padding: 10px 0; border-bottom: 1px solid #F3EDE3;">
                        <div style="font: 700 10.5px 'Nunito Sans'; color: #98897A; text-transform: uppercase; letter-spacing: 0.6px;">Child</div>
                        <div style="font: 800 13px 'Nunito Sans'; color: #2B3A4C; margin-top: 2px;">{{ $childName ?? '—' }} @if($leadHints['child_age'] ?? null) · {{ $leadHints['child_age'] }} @endif</div>
                    </div>
                    <div style="padding: 10px 0; border-bottom: 1px solid #F3EDE3;">
                        <div style="font: 700 10.5px 'Nunito Sans'; color: #98897A; text-transform: uppercase; letter-spacing: 0.6px;">Interested in</div>
                        <div style="font: 800 13px 'Nunito Sans'; color: #2B3A4C; margin-top: 2px;">{{ $interestedIn ?? '—' }}</div>
                    </div>
                    <div style="padding: 10px 0; border-bottom: 1px solid #F3EDE3;">
                        <div style="font: 700 10.5px 'Nunito Sans'; color: #98897A; text-transform: uppercase; letter-spacing: 0.6px;">Source</div>
                        <div style="font: 800 13px 'Nunito Sans'; color: #2B3A4C; margin-top: 2px;">{{ ucfirst($activeContact->channel) }}</div>
                    </div>
                    <div style="padding: 10px 0; border-bottom: 1px solid #F3EDE3;">
                        <div style="font: 700 10.5px 'Nunito Sans'; color: #98897A; text-transform: uppercase; letter-spacing: 0.6px;">First contact</div>
                        <div style="font: 800 13px 'Nunito Sans'; color: #2B3A4C; margin-top: 2px;">{{ $activeContact->created_at->format('M j, H:i') }}</div>
                    </div>
                    <div style="padding: 10px 0;">
                        <div style="font: 700 10.5px 'Nunito Sans'; color: #98897A; text-transform: uppercase; letter-spacing: 0.6px;">Insurance mentioned</div>
                        <div style="font: 800 13px 'Nunito Sans'; color: #2B3A4C; margin-top: 2px;">{{ $insurance ?? '—' }}</div>
                    </div>
                </div>

                <div style="background: #F3EDE3; border-radius: 10px; padding: 12px 14px; font: 600 12px/1.5 'Nunito Sans'; color: #5A6B7E;">
                    Auto-detected from the conversation. No lead created yet — review and convert when ready.
                </div>

                <form action="{{ route('whatsapp.convertToLead', $activeContact->id) }}" method="POST">
                    @csrf
                    <button type="submit" class="wa-view-lead" style="width: 100%; text-align: center; background: #C8355F; color: white; border: none; border-radius: 10px; padding: 12px; font: 800 13px 'Nunito Sans'; cursor: pointer;">Convert to Lead</button>
                </form>
            @else
                <div style="display: flex; flex-direction: column; align-items: center; text-align: center; gap: 10px; padding: 24px 4px;">
                    <div style="width: 44px; height: 44px; border-radius: 50%; background: #F3EDE3; display: flex; align-items: center; justify-content: center;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M20 21V19C20 17.9391 19.5786 16.9217 18.8284 16.1716C18.0783 15.4214 17.0609 15 16 15H8C6.93913 15 5.92172 15.4214 5.17157 16.1716C4.42143 16.9217 4 17.9391 4 19V21" stroke="#B0A493" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/><circle cx="12" cy="7" r="4" stroke="#B0A493" stroke-width="1.6"/></svg>
                    </div>
                    <div style="font: 700 12.5px 'Nunito Sans'; color: #98897A;">
                        Select a conversation to see family details.
                    </div>
                </div>
            @endif
        </div>

        <div id="wa-panel-backdrop" class="wa-panel-backdrop"></div>

    </div>

    <script>
        (function () {
            var input = document.getElementById('wa-contact-search');
            var list = document.getElementById('wa-contact-list');
            var filterBar = document.getElementById('wa-channel-filter');
            var activeChannel = 'all';

            function applyFilters() {
                if (!input || !list) return;
                var term = input.value.trim().toLowerCase();
                var rows = list.querySelectorAll('.wa-contact-row');

                rows.forEach(function (row) {
                    var haystack = row.getAttribute('data-search') || '';
                    var matchesSearch = haystack.includes(term);
                    var matchesChannel = activeChannel === 'all' || row.getAttribute('data-channel') === activeChannel;
                    row.style.display = (matchesSearch && matchesChannel) ? 'flex' : 'none';
                });
            }

            function wirePillHighlight(pill) {
                filterBar.querySelectorAll('.wa-filter-pill').forEach(function (p) {
                    p.classList.remove('is-active');
                    p.style.background = '#F6F3EE';
                    p.style.color = '#5A6B7E';
                });
                pill.classList.add('is-active');
                pill.style.background = '#2B3A4C';
                pill.style.color = 'white';
            }

            if (input) input.addEventListener('input', applyFilters);

            // Guarantee the conversation list scrolls within its own column instead of
            // overflowing the page once there are enough conversations to exceed the
            // available height (belt-and-suspenders on top of the flexbox sizing, since
            // that chain can get thrown off by ancestor layout quirks at small viewports).
            function sizeContactList() {
                if (!list) return;
                var top = list.getBoundingClientRect().top;
                var viewport = (window.visualViewport && window.visualViewport.height) || window.innerHeight;
                list.style.maxHeight = Math.max(viewport - top, 160) + 'px';
            }
            sizeContactList();
            window.addEventListener('resize', sizeContactList);
            window.addEventListener('orientationchange', sizeContactList);
            if (window.visualViewport) window.visualViewport.addEventListener('resize', sizeContactList);

            if (filterBar) {
                filterBar.querySelectorAll('.wa-filter-pill').forEach(function (pill) {
                    pill.addEventListener('click', function () {
                        activeChannel = pill.getAttribute('data-channel-filter');
                        wirePillHighlight(pill);
                        applyFilters();
                    });
                });
            }

            // Live-ish inbox: poll for new messages/contacts every few seconds instead of
            // requiring a manual refresh. Reuses the same Blade partials the initial page
            // load used (rendered server-side, returned as HTML), so there's no separate
            // JS rendering logic to keep in sync with the PHP version.
            var pollUrl = @json(route('whatsapp.poll'));
            var activeContactId = new URLSearchParams(window.location.search).get('contact');
            var messagesEl = document.getElementById('wa-messages');
            var pollInFlight = false;

            // Never move the cursor backwards - a manual poll (fired right after a
            // send) and the regular interval tick can both be in flight at once, and
            // whichever response happens to resolve second must not undo a cursor
            // the other one already advanced.
            function advanceCursor(id) {
                if (!messagesEl || !id) return;
                var current = parseInt(messagesEl.dataset.lastMessageId || '0', 10);
                if (id > current) messagesEl.dataset.lastMessageId = String(id);
            }

            function poll() {
                if (document.visibilityState === 'hidden') return;
                // Two overlapping requests with the same stale `after` cursor would
                // both come back with the same "new" messages and each insert them,
                // producing duplicate bubbles - only one poll may be in flight.
                if (pollInFlight) return;
                pollInFlight = true;

                var params = new URLSearchParams();
                if (activeContactId) params.set('contact', activeContactId);
                params.set('after', (messagesEl && messagesEl.dataset.lastMessageId) || 0);

                fetch(pollUrl + '?' + params.toString(), { headers: { 'Accept': 'application/json' } })
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        if (list && typeof data.contacts_html === 'string') {
                            list.innerHTML = data.contacts_html;
                            applyFilters();
                        }

                        if (messagesEl && data.messages_html) {
                            var wasNearBottom = messagesEl.scrollHeight - messagesEl.scrollTop - messagesEl.clientHeight < 80;
                            messagesEl.insertAdjacentHTML('beforeend', data.messages_html);
                            advanceCursor(data.latest_message_id);
                            if (wasNearBottom) messagesEl.scrollTop = messagesEl.scrollHeight;
                        }

                        // A bubble is drawn before its send has finished, so the
                        // word underneath it ("Sending") has to be brought up to
                        // date here rather than only on a reload.
                        (data.statuses || []).forEach(function (s) {
                            var el = document.querySelector('[data-msg-status="' + s.id + '"]');
                            if (el && el.textContent !== s.label) {
                                el.textContent = s.label;
                                el.classList.toggle('is-failed', s.status === 'failed');
                                if (s.error) el.setAttribute('title', s.error);
                                else el.removeAttribute('title');
                            }

                            var tick = document.querySelector('[data-msg-tick="' + s.id + '"]');
                            if (tick && tick.innerHTML !== s.tick) tick.innerHTML = s.tick;
                        });
                    })
                    .catch(function () { /* transient network hiccup - just try again next tick */ })
                    .finally(function () { pollInFlight = false; });
            }

            // Exposed so a just-sent message can force an immediate refresh of the
            // sidebar (preview text, ordering) instead of waiting for the next tick.
            window.__waPollNow = poll;
            window.__waAdvanceCursor = advanceCursor;

            setInterval(poll, 4000);
        })();

        // Family details drawer - only reachable below 1180px, where the panel
        // is a slide-over instead of a third column.
        (function () {
            var panel = document.getElementById('wa-family-panel');
            var backdrop = document.getElementById('wa-panel-backdrop');
            var toggle = document.getElementById('wa-details-toggle');
            var closeBtn = document.getElementById('wa-panel-close');
            if (!panel) return;

            function open() {
                panel.classList.add('is-open');
                if (backdrop) backdrop.classList.add('is-open');
            }

            function close() {
                panel.classList.remove('is-open');
                if (backdrop) backdrop.classList.remove('is-open');
            }

            if (toggle) toggle.addEventListener('click', function () {
                panel.classList.contains('is-open') ? close() : open();
            });
            if (backdrop) backdrop.addEventListener('click', close);
            if (closeBtn) closeBtn.addEventListener('click', close);
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') close();
            });

            // Widening past the breakpoint puts the panel back in the flow; drop
            // the open state with it so the backdrop can't linger over a layout
            // that no longer has a drawer.
            window.addEventListener('resize', function () {
                if (window.innerWidth > 1180) close();
            });
        })();
    </script>
@endsection
