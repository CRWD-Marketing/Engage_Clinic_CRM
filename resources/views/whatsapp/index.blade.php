@extends('layouts.admin-sidebar')

@section('title', 'WhatsApp · Engage Clinic')
@section('page-title', '')
@section('page-subtitle', '')
@section('content-class', 'content-fill-height')

@section('content')
    @php
        $avatarPalette = ['#C8355F', '#24619C', '#B97F24', '#6E4FA8', '#1F8FA8', '#A8461F'];
        $avatarColor = fn ($seed) => $avatarPalette[crc32($seed) % count($avatarPalette)];

        $tick = function (?string $status) {
            return match ($status) {
                'read' => '<svg width="16" height="11" viewBox="0 0 20 11" fill="none"><path d="M1 5.5L5 9.5L11 1.5" stroke="#24619C" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/><path d="M7 5.5L11 9.5L19 1" stroke="#24619C" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>',
                'delivered' => '<svg width="16" height="11" viewBox="0 0 20 11" fill="none"><path d="M1 5.5L5 9.5L11 1.5" stroke="#9AA79B" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/><path d="M7 5.5L11 9.5L19 1" stroke="#9AA79B" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>',
                'sent' => '<svg width="12" height="11" viewBox="0 0 16 11" fill="none"><path d="M1 5.5L5 9.5L11 1.5" stroke="#9AA79B" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>',
                'failed' => '<span style="color:#C8355F; font:700 10px \'Nunito Sans\';">⚠ failed</span>',
                default => '',
            };
        };

        $channelBadge = function (string $channel) {
            $badgeStyle = 'position:absolute; bottom:-2px; right:-2px; width:15px; height:15px; border-radius:50%; display:flex; align-items:center; justify-content:center; border:2px solid #FFFDFA;';

            if ($channel === 'instagram') {
                return '<span style="'.$badgeStyle.' background:linear-gradient(45deg,#FEDA75,#D62976,#4F5BD5);"><svg width="8" height="8" viewBox="0 0 24 24" fill="none"><rect x="2" y="2" width="20" height="20" rx="6" stroke="white" stroke-width="2.4"/><circle cx="12" cy="12" r="4.5" stroke="white" stroke-width="2.4"/><circle cx="18" cy="6" r="1.3" fill="white"/></svg></span>';
            }

            if ($channel === 'facebook') {
                return '<span style="'.$badgeStyle.' background:linear-gradient(45deg,#0662FE,#00B2FF);"><svg width="9" height="9" viewBox="0 0 24 24" fill="white"><path d="M12 2C6.48 2 2 6.15 2 11.26c0 2.91 1.44 5.51 3.7 7.21V22l3.38-1.86c.9.25 1.87.38 2.92.38 5.52 0 10-4.15 10-9.26C22 6.15 17.52 2 12 2zm1.02 12.47l-2.55-2.72-4.98 2.72 5.48-5.82 2.61 2.72 4.92-2.72-5.48 5.82z"/></svg></span>';
            }

            return '<span style="'.$badgeStyle.' background:#1FA855;"><svg width="9" height="9" viewBox="0 0 24 24" fill="white"><path d="M12 2C6.5 2 2 6.5 2 12c0 1.8.5 3.5 1.3 5L2 22l5.2-1.4c1.4.8 3.1 1.2 4.8 1.2 5.5 0 10-4.5 10-10S17.5 2 12 2z"/></svg></span>';
        };
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
        }
        .wa-date-divider { display: flex; align-items: center; justify-content: center; margin: 2px 0 8px; }
        .wa-date-divider span {
            background: #FFFDFA; color: #8A7D6C; font: 700 10px 'Nunito Sans'; letter-spacing: 0.5px;
            text-transform: uppercase; padding: 4px 13px; border-radius: 20px; box-shadow: 0 1px 3px rgba(43,58,76,0.08);
        }
    </style>

    <!-- WhatsApp Inbox -->
    <div style="flex: 1; display: flex; min-height: 0; overflow-x: auto; margin: -22px -28px;">

        <!-- Left Sidebar - Chat List -->
        <div style="width: 300px; border-right: 1px solid #EBE4DA; background: #FFFDFA; display: flex; flex-direction: column; flex-shrink: 0;">
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
                    <div id="wa-channel-filter" style="display: flex; gap: 6px; margin-top: 10px;">
                        <button type="button" data-channel-filter="all" class="wa-filter-pill is-active" style="border: 1px solid #E2DACE; background: #2B3A4C; color: white; border-radius: 20px; padding: 4px 11px; font: 700 11px 'Nunito Sans'; cursor: pointer;">All</button>
                        <button type="button" data-channel-filter="whatsapp" class="wa-filter-pill" style="border: 1px solid #E2DACE; background: #F6F3EE; color: #5A6B7E; border-radius: 20px; padding: 4px 11px; font: 700 11px 'Nunito Sans'; cursor: pointer;">WhatsApp</button>
                        <button type="button" data-channel-filter="instagram" class="wa-filter-pill" style="border: 1px solid #E2DACE; background: #F6F3EE; color: #5A6B7E; border-radius: 20px; padding: 4px 11px; font: 700 11px 'Nunito Sans'; cursor: pointer;">Instagram</button>
                        <button type="button" data-channel-filter="facebook" class="wa-filter-pill" style="border: 1px solid #E2DACE; background: #F6F3EE; color: #5A6B7E; border-radius: 20px; padding: 4px 11px; font: 700 11px 'Nunito Sans'; cursor: pointer;">Facebook</button>
                    </div>
                @endif
            </div>
            <div id="wa-contact-list" class="wa-contact-list" style="flex: 1; overflow-y: auto;">
                @forelse ($contacts as $contact)
                    @php $isActive = $activeContact && $activeContact->id === $contact->id; @endphp
                    <a
                        href="{{ route('whatsapp.index', ['contact' => $contact->id]) }}"
                        data-search="{{ strtolower(($contact->name ?? '').' '.$contact->wa_id.' '.$contact->last_message_preview) }}"
                        data-channel="{{ $contact->channel }}"
                        class="wa-contact-row {{ $isActive ? 'is-active' : '' }}"
                        style="display: flex; gap: 11px; align-items: center; padding: 12px 18px; cursor: pointer; border-bottom: 1px solid #F3EDE3; background: {{ $isActive ? '#F5EFE7' : 'transparent' }}; text-decoration: none;"
                    >
                        <div style="position: relative; flex-shrink: 0;">
                            <div style="width: 36px; height: 36px; border-radius: 50%; background: {{ $avatarColor($contact->wa_id) }}; color: white; display: flex; align-items: center; justify-content: center; font: 600 13px 'Baloo 2';">
                                {{ strtoupper(substr($contact->name ?? $contact->wa_id, 0, 2)) }}
                            </div>
                            {!! $channelBadge($contact->channel) !!}
                        </div>
                        <div style="flex: 1; min-width: 0;">
                            <div style="display: flex; gap: 6px; align-items: baseline;">
                                <div style="font: 800 13px 'Nunito Sans'; color: #2B3A4C; flex: 1; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $contact->name ?? $contact->wa_id }}</div>
                                <div style="font: 600 10.5px 'Nunito Sans'; color: #B0A493; flex-shrink: 0;">{{ optional($contact->last_message_at)->diffForHumans(null, true) }}</div>
                            </div>
                            <div style="font: 600 12px 'Nunito Sans'; color: #98897A; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $contact->last_message_preview }}</div>
                        </div>
                        @if ($contact->unread_count > 0)
                            <span style="background: #1FA855; color: white; border-radius: 9px; padding: 1px 7px; font: 800 10.5px 'Nunito Sans'; flex-shrink: 0;">{{ $contact->unread_count }}</span>
                        @endif
                    </a>
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
        <div style="flex: 1; display: flex; flex-direction: column; min-width: 380px; background: #F1EBE1;">
            @if ($activeContact)
                <!-- Chat Header -->
                <div style="display: flex; align-items: center; gap: 12px; padding: 13px 20px; border-bottom: 1px solid #E4DCCE; background: #FFFDFA;">
                    <div style="position: relative; flex-shrink: 0;">
                        <div style="width: 38px; height: 38px; border-radius: 50%; background: {{ $avatarColor($activeContact->wa_id) }}; color: white; display: flex; align-items: center; justify-content: center; font: 600 14px 'Baloo 2';">
                            {{ strtoupper(substr($activeContact->name ?? $activeContact->wa_id, 0, 2)) }}
                        </div>
                        {!! $channelBadge($activeContact->channel) !!}
                    </div>
                    <div style="flex: 1;">
                        <div style="font: 800 14.5px 'Nunito Sans'; color: #2B3A4C;">{{ $activeContact->name ?? $activeContact->wa_id }}</div>
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
                    <a href="{{ route('calendar.index') }}" class="wa-book-btn" style="background: #FFFFFF; color: #16436E; border: 1px solid #E2DACE; border-radius: 9px; padding: 8px 14px; font: 800 12px 'Nunito Sans'; cursor: pointer; text-decoration: none; white-space: nowrap;">Book assessment</a>
                </div>

                <!-- Messages -->
                <div class="wa-messages" style="flex: 1; overflow-y: auto; padding: 20px 24px; display: flex; flex-direction: column; gap: 10px;">
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
                        @if ($message->direction === 'inbound')
                            <div style="display: flex; justify-content: flex-start;">
                                <div style="max-width: 62%; background: #FFFFFF; border-radius: 14px 14px 14px 4px; padding: 10px 14px; font: 600 13.5px/1.5 'Nunito Sans'; color: #2B3A4C; box-shadow: 0 1px 2px rgba(43,58,76,0.07);">
                                    {{ $message->body ?? '['.$message->type.']' }}
                                    <span style="font: 600 10px 'Nunito Sans'; color: #9AA79B; margin-left: 8px; white-space: nowrap;">{{ $message->sent_at->format('H:i') }}</span>
                                </div>
                            </div>
                        @else
                            <div style="display: flex; justify-content: flex-end;">
                                <div style="max-width: 62%; background: #DDF3E0; border-radius: 14px 14px 4px 14px; padding: 10px 14px; font: 600 13.5px/1.5 'Nunito Sans'; color: #2B3A4C; box-shadow: 0 1px 2px rgba(43,58,76,0.07);">
                                    {{ $message->body }}
                                    <span style="display: inline-flex; align-items: center; gap: 4px; margin-left: 8px; white-space: nowrap; vertical-align: middle;">
                                        <span style="font: 600 10px 'Nunito Sans'; color: #9AA79B;">{{ $message->sent_at->format('H:i') }}</span>
                                        {!! $tick($message->status) !!}
                                    </span>
                                </div>
                            </div>
                        @endif
                    @empty
                        <div style="flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 10px; text-align: center;">
                            <div style="width: 44px; height: 44px; border-radius: 50%; background: #FFFDFA; display: flex; align-items: center; justify-content: center;">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M21 11.5C21.0034 12.8199 20.6951 14.1219 20.1 15.3C19.3944 16.7118 18.3097 17.8992 16.9674 18.7293C15.6251 19.5594 14.0787 19.9994 12.5 20C11.1801 20.0035 9.87812 19.6951 8.7 19.1L3 21L4.9 15.3C4.30493 14.1219 3.99656 12.8199 4 11.5C4.00061 9.92127 4.44061 8.37485 5.27072 7.03258C6.10083 5.6903 7.28825 4.6056 8.7 3.9C9.87812 3.30493 11.1801 2.99656 12.5 3H13C15.0843 3.11499 17.053 3.99476 18.5291 5.47086C20.0052 6.94696 20.885 8.91565 21 11V11.5Z" stroke="#B0A493" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </div>
                            <div style="font: 600 12.5px 'Nunito Sans'; color: #98897A;">No messages in this conversation yet.</div>
                        </div>
                    @endforelse
                </div>

                <!-- Message Input -->
                <form action="{{ route('whatsapp.send') }}" method="POST" style="display: flex; gap: 10px; padding: 14px 20px; background: #FFFDFA; border-top: 1px solid #E4DCCE;">
                    @csrf
                    <input type="hidden" name="contact_id" value="{{ $activeContact->id }}">
                    <input
                        type="text"
                        name="message"
                        placeholder="Type a reply…"
                        required
                        class="wa-msg-input"
                        style="flex: 1; padding: 11px 16px; border: 1px solid #E2DACE; border-radius: 22px; background: #F6F3EE; font: 600 13.5px 'Nunito Sans'; color: #2B3A4C; outline: none; transition: border-color 0.12s ease, box-shadow 0.12s ease;"
                    >
                    <button type="submit" class="wa-send-btn" style="display: flex; align-items: center; gap: 7px; background: #1FA855; color: white; border: none; border-radius: 22px; padding: 0 20px; font: 800 13px 'Nunito Sans'; cursor: pointer;">
                        Send
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M22 2L11 13" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M22 2L15 22L11 13L2 9L22 2Z" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </button>
                </form>
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
        <div style="width: 290px; border-left: 1px solid #EBE4DA; background: #FFFDFA; padding: 20px; display: flex; flex-direction: column; gap: 16px; flex-shrink: 0; overflow-y: auto;">
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

                <a href="{{ route('leads.show', $lead->id) }}" class="wa-view-lead" style="text-align: center; background: #C8355F; color: white; border: none; border-radius: 10px; padding: 12px; font: 800 13px 'Nunito Sans'; cursor: pointer; text-decoration: none;">View lead</a>

                <div style="background: #F3EDE3; border-radius: 10px; padding: 12px 14px; font: 600 12px/1.5 'Nunito Sans'; color: #5A6B7E;">
                    Auto-capture is on: new WhatsApp or Instagram contacts create a lead card in "New" with the first message attached.
                </div>
            @else
                <div style="display: flex; flex-direction: column; align-items: center; text-align: center; gap: 10px; padding: 24px 4px;">
                    <div style="width: 44px; height: 44px; border-radius: 50%; background: #F3EDE3; display: flex; align-items: center; justify-content: center;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M20 21V19C20 17.9391 19.5786 16.9217 18.8284 16.1716C18.0783 15.4214 17.0609 15 16 15H8C6.93913 15 5.92172 15.4214 5.17157 16.1716C4.42143 16.9217 4 17.9391 4 19V21" stroke="#B0A493" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/><circle cx="12" cy="7" r="4" stroke="#B0A493" stroke-width="1.6"/></svg>
                    </div>
                    <div style="font: 700 12.5px 'Nunito Sans'; color: #98897A;">
                        @if ($activeContact)
                            No linked lead for this conversation.
                        @else
                            Select a conversation to see family details.
                        @endif
                    </div>
                </div>
            @endif
        </div>

    </div>

    @if ($contacts->isNotEmpty())
        <script>
            (function () {
                var input = document.getElementById('wa-contact-search');
                var list = document.getElementById('wa-contact-list');
                var filterBar = document.getElementById('wa-channel-filter');
                if (!input || !list) return;

                var activeChannel = 'all';

                function applyFilters() {
                    var term = input.value.trim().toLowerCase();
                    var rows = list.querySelectorAll('.wa-contact-row');

                    rows.forEach(function (row) {
                        var haystack = row.getAttribute('data-search') || '';
                        var matchesSearch = haystack.includes(term);
                        var matchesChannel = activeChannel === 'all' || row.getAttribute('data-channel') === activeChannel;
                        row.style.display = (matchesSearch && matchesChannel) ? 'flex' : 'none';
                    });
                }

                input.addEventListener('input', applyFilters);

                if (filterBar) {
                    filterBar.querySelectorAll('.wa-filter-pill').forEach(function (pill) {
                        pill.addEventListener('click', function () {
                            activeChannel = pill.getAttribute('data-channel-filter');

                            filterBar.querySelectorAll('.wa-filter-pill').forEach(function (p) {
                                p.classList.remove('is-active');
                                p.style.background = '#F6F3EE';
                                p.style.color = '#5A6B7E';
                            });
                            pill.classList.add('is-active');
                            pill.style.background = '#2B3A4C';
                            pill.style.color = 'white';

                            applyFilters();
                        });
                    });
                }
            })();
        </script>
    @endif
@endsection
