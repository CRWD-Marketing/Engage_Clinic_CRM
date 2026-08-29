@if ($message->direction === 'inbound')
    <div class="wa-msg-row" style="display: flex; flex-direction: column; align-items: flex-start; gap: 3px;">
        <div style="max-width: 62%; background: #FFFFFF; border-radius: 14px 14px 14px 4px; padding: 10px 14px; font: 600 13.5px/1.5 'Nunito Sans'; color: #2B3A4C; box-shadow: 0 1px 2px rgba(43,58,76,0.07);">
            @if ($message->type === 'story_reply' && $message->media_url)
                <a href="{{ $message->media_url }}" target="_blank" rel="noopener" style="display: flex; align-items: center; gap: 6px; margin-bottom: 6px; padding: 5px 8px; background: #F6F3EE; border-radius: 8px; text-decoration: none;">
                    {{-- Instagram's story CDN links expire after a while, so a thumbnail that 404s
                         hides itself instead of showing a broken-image icon; the label text stays either way. --}}
                    <img src="{{ $message->media_url }}" alt="" style="width: 26px; height: 26px; border-radius: 6px; object-fit: cover; flex-shrink: 0;" onerror="this.style.display='none';">
                    <span style="font: 700 10.5px 'Nunito Sans'; color: #5A6B7E;">Replied to your story</span>
                </a>
            @endif

            @if ($message->body)
                {!! \App\Models\WhatsappMessage::linkify($message->body) !!}
            @elseif ($message->type === 'image' && $message->media_url)
                <a href="{{ $message->media_url }}" target="_blank" rel="noopener" style="display: block;">
                    <img src="{{ $message->media_url }}" alt="" style="max-width: 200px; max-height: 220px; border-radius: 10px; display: block; object-fit: cover;"
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                </a>
                <div style="display:none; margin-top: 5px; font: 700 11px 'Nunito Sans'; color: #98897A;">⚠️ Photo unavailable (link expired) — check Instagram/WhatsApp directly</div>
            @elseif ($message->type === 'video' && $message->media_url)
                <video controls preload="metadata" style="max-width: 220px; max-height: 260px; border-radius: 10px; display: block;"
                       onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                    <source src="{{ $message->media_url }}">
                </video>
                <div style="display:none; margin-top: 5px; font: 700 11px 'Nunito Sans'; color: #98897A;">⚠️ Video unavailable (link expired) — check Instagram/WhatsApp directly</div>
            @elseif ($message->type === 'audio' && $message->media_url)
                <audio controls preload="metadata" style="max-width: 220px;">
                    <source src="{{ $message->media_url }}">
                </audio>
            @elseif (in_array($message->type, ['shared_post', 'file', 'story_mention'], true) && $message->media_url)
                <a href="{{ $message->media_url }}" target="_blank" rel="noopener" style="display: flex; align-items: center; gap: 6px; padding: 6px 9px; background: #F6F3EE; border-radius: 8px; text-decoration: none;">
                    <span style="font: 700 12px 'Nunito Sans'; color: #16436E;">{{ \App\Models\WhatsappMessage::fallbackLabel($message->type) }}</span>
                </a>
            @elseif ($message->media_url)
                {{-- Anything else with a url we don't have a dedicated player for yet - still
                     clickable rather than dropped, in case it happens to be viewable directly. --}}
                <a href="{{ $message->media_url }}" target="_blank" rel="noopener" style="display: block;">
                    <img src="{{ $message->media_url }}" alt="" style="max-width: 200px; max-height: 220px; border-radius: 10px; display: block; object-fit: cover;"
                         onerror="this.style.display='none';">
                </a>
                <div style="margin-top: 5px; font: 700 11px 'Nunito Sans'; color: #98897A;">{{ \App\Models\WhatsappMessage::fallbackLabel($message->type) }}</div>
            @else
                <span style="color: #98897A; font-style: italic;">{{ \App\Models\WhatsappMessage::fallbackLabel($message->type) }}</span>
            @endif
            <span style="font: 600 10px 'Nunito Sans'; color: #9AA79B; margin-left: 8px; white-space: nowrap;">{{ $message->sent_at->format('H:i') }}</span>
        </div>
        @if (! $activeContact->lead_id && $message->body)
            <form action="{{ route('whatsapp.message.convertToLead', $message->id) }}" method="POST" class="wa-msg-convert-form">
                @csrf
                <button type="submit" class="wa-msg-convert-btn">Convert to Lead</button>
            </form>
        @endif
    </div>
@else
    <div style="display: flex; justify-content: flex-end;">
        <div style="max-width: 62%; background: #DDF3E0; border-radius: 14px 14px 4px 14px; padding: 10px 14px; font: 600 13.5px/1.5 'Nunito Sans'; color: #2B3A4C; box-shadow: 0 1px 2px rgba(43,58,76,0.07);">
            @if ($message->is_ai_generated)
                <span style="display: inline-block; margin-bottom: 4px; font: 800 9.5px 'Nunito Sans'; color: #16436E; background: #E9EEF3; border-radius: 5px; padding: 1px 6px;">🤖 AI</span><br>
            @endif
            {!! \App\Models\WhatsappMessage::linkify($message->body) !!}
            <span style="display: inline-flex; align-items: center; gap: 4px; margin-left: 8px; white-space: nowrap; vertical-align: middle;">
                <span style="font: 600 10px 'Nunito Sans'; color: #9AA79B;">{{ $message->sent_at->format('H:i') }}</span>
                {!! \App\Models\WhatsappMessage::tickIcon($message->status) !!}
            </span>
        </div>
    </div>
@endif
