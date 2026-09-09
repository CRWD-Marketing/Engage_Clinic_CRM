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
        .wa-book-btn { transition: background 0.12s ease, border-color 0.12s ease; }
        .wa-book-btn:hover { background: #F5EFE7; border-color: #C8AF8F; }
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
        .wa-msg-convert-form { opacity: 0; transition: opacity 0.12s ease; margin-left: 4px; }
        .wa-msg-row:hover .wa-msg-convert-form { opacity: 1; }
        .wa-msg-convert-btn {
            border: none; background: none; padding: 0; cursor: pointer;
            font: 700 11px 'Nunito Sans'; color: #C8355F; text-decoration: underline;
        }
        .wa-msg-convert-btn:hover { color: #AD2A52; }
        #wa-channel-filter { overflow-x: auto; scrollbar-width: none; -ms-overflow-style: none; }
        #wa-channel-filter::-webkit-scrollbar { display: none; }
        .wa-filter-pill { flex-shrink: 0; white-space: nowrap; }
    </style>

    <!-- WhatsApp Inbox -->
    <div style="flex: 1; display: flex; min-height: 0; overflow-x: auto; margin: -22px -28px;">

        <!-- Left Sidebar - Chat List -->
        <div style="width: 320px; min-height: 0; border-right: 1px solid #EBE4DA; background: #FFFDFA; display: flex; flex-direction: column; flex-shrink: 0;">
            <div style="padding: 16px 18px; border-bottom: 1px solid #EBE4DA;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span style="position: relative; width: 8px; height: 8px;">
                        <span class="wa-live-dot" style="display: block; width: 8px; height: 8px; border-radius: 50%; background: #1FA855;"></span>
                    </span>
                    <div style="font: 600 16px 'Baloo 2'; color: #16436E;">Messages</div>
                </div>
                <div style="font: 600 11.5px 'Nunito Sans'; color: #98897A; margin-top: 1px;">WhatsApp + Instagram · leads auto-captured</div>

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
        <div style="flex: 1; display: flex; flex-direction: column; min-width: 380px; min-height: 0; background: #F1EBE1;">
            @if ($activeContact)
                <!-- Chat Header -->
                <div style="display: flex; align-items: center; gap: 12px; padding: 13px 20px; border-bottom: 1px solid #E4DCCE; background: #FFFDFA;">
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
                    <div style="flex: 1;">
                        <div style="display: flex; align-items: center; gap: 7px;">
                            <div style="font: 800 14.5px 'Nunito Sans'; color: #2B3A4C;">{{ $activeContact->name ?? $activeContact->wa_id }}</div>
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
                    <form action="{{ route('whatsapp.updateAiState', $activeContact->id) }}" method="POST" style="margin: 0;">
                        @csrf
                        <select name="ai_state" onchange="this.form.submit()" style="background: #FFFFFF; color: #16436E; border: 1px solid #E2DACE; border-radius: 9px; padding: 7px 10px; font: 800 12px 'Nunito Sans'; cursor: pointer;">
                            @foreach (\App\Models\WhatsappContact::aiStateLabels() as $value => $label)
                                <option value="{{ $value }}" @selected($activeContact->ai_state === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </form>
                    <a href="{{ route('calendar.index') }}" class="wa-book-btn" style="background: #FFFFFF; color: #16436E; border: 1px solid #E2DACE; border-radius: 9px; padding: 8px 14px; font: 800 12px 'Nunito Sans'; cursor: pointer; text-decoration: none; white-space: nowrap;">Book assessment</a>
                </div>

                <!-- Messages -->
                <div id="wa-messages" class="wa-messages" data-last-message-id="{{ (int) $messages->max('id') }}" style="flex: 1; min-height: 0; overflow-y: auto; padding: 20px 24px; display: flex; flex-direction: column; gap: 10px;">
                    @php $lastMsgDate = null; @endphp
                    @forelse ($messages as $message)
                        @php
                            $msgDate = $message->sent_at->toDateString();
                            $showDivider = $msgDate !== $lastMsgDate;
                            $lastMsgDate = $msgDate;
                            $dividerLabel = $message->sent_at->isToday()
                                ? 'Today'
                                : ($message->sent_at->isYesterday() ? 'Yesterday' : $message->sent_at->format('F j, Y'));
                        @endphp
                        @if ($showDivider)
                            <div class="wa-date-divider"><span>{{ $dividerLabel }}</span></div>
                        @endif
                        @include('whatsapp.partials.message', ['message' => $message, 'activeContact' => $activeContact])
                    @empty
                        <div style="flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 10px; text-align: center;">
                            <div style="width: 44px; height: 44px; border-radius: 50%; background: #FFFDFA; display: flex; align-items: center; justify-content: center;">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M21 11.5C21.0034 12.8199 20.6951 14.1219 20.1 15.3C19.3944 16.7118 18.3097 17.8992 16.9674 18.7293C15.6251 19.5594 14.0787 19.9994 12.5 20C11.1801 20.0035 9.87812 19.6951 8.7 19.1L3 21L4.9 15.3C4.30493 14.1219 3.99656 12.8199 4 11.5C4.00061 9.92127 4.44061 8.37485 5.27072 7.03258C6.10083 5.6903 7.28825 4.6056 8.7 3.9C9.87812 3.30493 11.1801 2.99656 12.5 3H13C15.0843 3.11499 17.053 3.99476 18.5291 5.47086C20.0052 6.94696 20.885 8.91565 21 11V11.5Z" stroke="#B0A493" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </div>
                            <div style="font: 600 12.5px 'Nunito Sans'; color: #98897A;">No messages in this conversation yet.</div>
                        </div>
                    @endforelse
                </div>

                @if ($errors->any())
                    <div style="flex-shrink: 0; padding: 10px 20px; background: #FDECEE; border-top: 1px solid #F3C9D5; font: 700 12.5px 'Nunito Sans'; color: #C8355F;">
                        {{ $errors->first() }}
                    </div>
                @endif

                <!-- Message Input -->
                <form id="wa-send-form" action="{{ route('whatsapp.send') }}" method="POST" style="flex-shrink: 0; display: flex; gap: 10px; padding: 14px 20px; background: #FFFDFA; border-top: 1px solid #E4DCCE;">
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
                            var top = messages.getBoundingClientRect().top;
                            var after = 0;
                            var sibling = messages.nextElementSibling;
                            while (sibling) {
                                after += sibling.getBoundingClientRect().height;
                                sibling = sibling.nextElementSibling;
                            }
                            messages.style.maxHeight = Math.max(window.innerHeight - top - after, 160) + 'px';
                        }

                        sizeMessages();
                        messages.scrollTop = messages.scrollHeight;
                        window.addEventListener('resize', sizeMessages);
                    })();
                    (function () {
                        var form = document.getElementById('wa-send-form');
                        if (!form) return;

                        form.addEventListener('submit', function (e) {
                            if (form.dataset.submitting === 'true') {
                                e.preventDefault();
                                return;
                            }

                            form.dataset.submitting = 'true';

                            var btn = form.querySelector('.wa-send-btn');
                            var label = form.querySelector('.wa-send-btn-label');
                            var input = form.querySelector('.wa-msg-input');

                            btn.disabled = true;
                            input.readOnly = true;
                            btn.style.opacity = '0.6';
                            btn.style.cursor = 'not-allowed';
                            if (label) label.textContent = 'Sending…';
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
        <div id="wa-family-panel" style="width: 290px; min-height: 0; border-left: 1px solid #EBE4DA; background: #FFFDFA; padding: 20px; display: flex; flex-direction: column; gap: 16px; flex-shrink: 0; overflow-y: auto;">
            <div style="font: 600 16px 'Baloo 2'; color: #16436E;">Family details</div>

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
                    $childName = $activeContact->child_name ?? $leadHints['child_name'] ?? '';
                    $interestedIn = $activeContact->interested_in ?? $leadHints['interested_in'] ?? '';
                    $insurance = $activeContact->insurance ?? $leadHints['insurance'] ?? '';
                    $fieldLabelStyle = "font: 700 10.5px 'Nunito Sans'; color: #98897A; text-transform: uppercase; letter-spacing: 0.6px; display: block; margin-bottom: 4px;";
                    $fieldInputStyle = "width: 100%; box-sizing: border-box; font: 700 12px 'Nunito Sans'; color: #2B3A4C; padding: 6px 8px; border: 1px solid #EBE4DA; border-radius: 8px; background: #FFFFFF;";
                @endphp
                <form action="{{ route('whatsapp.updateFamilyDetails', $activeContact->id) }}" method="POST">
                    @csrf
                    <div style="display: flex; flex-direction: column;">
                        <div style="padding: 6px 0; border-bottom: 1px solid #F3EDE3;">
                            <label style="{{ $fieldLabelStyle }}">Child</label>
                            <input type="text" name="child_name" value="{{ old('child_name', $childName) }}" placeholder="Child's name" style="{{ $fieldInputStyle }}">
                            @if($leadHints['child_age'] ?? null)
                                <div style="font: 600 10.5px 'Nunito Sans'; color: #98897A; margin-top: 3px;">Detected age: {{ $leadHints['child_age'] }} yrs</div>
                            @endif
                        </div>
                        <div style="padding: 6px 0; border-bottom: 1px solid #F3EDE3;">
                            <label style="{{ $fieldLabelStyle }}">Interested in</label>
                            <select name="interested_in" style="{{ $fieldInputStyle }}">
                                <option value="">—</option>
                                @foreach ($services as $service)
                                    <option value="{{ $service->name }}" @selected(old('interested_in', $interestedIn) === $service->name)>{{ $service->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div style="padding: 6px 0; border-bottom: 1px solid #F3EDE3;">
                            <label style="{{ $fieldLabelStyle }}">Source</label>
                            <div style="font: 800 13px 'Nunito Sans'; color: #2B3A4C; margin-top: 2px;">{{ ucfirst($activeContact->channel) }}</div>
                        </div>
                        <div style="padding: 6px 0; border-bottom: 1px solid #F3EDE3;">
                            <label style="{{ $fieldLabelStyle }}">First contact</label>
                            <div style="font: 800 13px 'Nunito Sans'; color: #2B3A4C; margin-top: 2px;">{{ $activeContact->created_at->format('M j, H:i') }}</div>
                        </div>
                        <div style="padding: 6px 0;">
                            <label style="{{ $fieldLabelStyle }}">Insurance mentioned</label>
                            <select name="insurance" style="{{ $fieldInputStyle }}">
                                <option value="">—</option>
                                <option value="Not sure yet" @selected(old('insurance', $insurance) === 'Not sure yet')>Not sure yet</option>
                                <option value="Daman" @selected(old('insurance', $insurance) === 'Daman')>Daman</option>
                                <option value="Daman Enhanced" @selected(old('insurance', $insurance) === 'Daman Enhanced')>Daman Enhanced</option>
                                <option value="Thiqa" @selected(old('insurance', $insurance) === 'Thiqa')>Thiqa</option>
                                <option value="ADNIC" @selected(old('insurance', $insurance) === 'ADNIC')>ADNIC</option>
                                <option value="AXA / GIG" @selected(old('insurance', $insurance) === 'AXA / GIG')>AXA / GIG</option>
                                <option value="Self-pay" @selected(old('insurance', $insurance) === 'Self-pay')>Self-pay</option>
                            </select>
                        </div>
                    </div>

                    <button type="submit" style="width: 100%; text-align: center; background: #FFFFFF; color: #16436E; border: 1px solid #16436E; border-radius: 10px; padding: 10px; font: 800 13px 'Nunito Sans'; cursor: pointer; margin-top: 10px;">Save details</button>
                </form>

                <div style="background: #F3EDE3; border-radius: 10px; padding: 12px 14px; font: 600 12px/1.5 'Nunito Sans'; color: #5A6B7E;">
                    Child/interested in/insurance are auto-detected from the conversation until you edit and save them here. No lead created yet — review and convert when ready.
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
                var maxHeight = window.innerHeight - top;
                list.style.maxHeight = Math.max(maxHeight, 160) + 'px';
            }
            sizeContactList();
            window.addEventListener('resize', sizeContactList);

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

            function poll() {
                if (document.visibilityState === 'hidden') return;

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
                            messagesEl.dataset.lastMessageId = data.latest_message_id;
                            if (wasNearBottom) messagesEl.scrollTop = messagesEl.scrollHeight;
                        }
                    })
                    .catch(function () { /* transient network hiccup - just try again next tick */ });
            }

            setInterval(poll, 4000);
        })();
    </script>
@endsection
