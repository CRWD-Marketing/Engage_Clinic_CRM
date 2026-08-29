@php $isActive = $activeContactId && $activeContactId === $contact->id; @endphp
<a
    href="{{ route('whatsapp.index', ['contact' => $contact->id]) }}"
    data-contact-id="{{ $contact->id }}"
    data-search="{{ strtolower(($contact->name ?? '').' '.$contact->wa_id.' '.$contact->last_message_preview) }}"
    data-channel="{{ $contact->channel }}"
    class="wa-contact-row {{ $isActive ? 'is-active' : '' }}"
    style="display: flex; gap: 11px; align-items: center; padding: 12px 18px; cursor: pointer; border-bottom: 1px solid #F3EDE3; background: {{ $isActive ? '#F5EFE7' : 'transparent' }}; text-decoration: none;"
>
    <div style="position: relative; flex-shrink: 0;">
        @if ($contact->avatar_url)
            <img src="{{ $contact->avatar_url }}" alt="" style="width: 36px; height: 36px; border-radius: 50%; object-fit: cover; display: block;">
        @else
            <div style="width: 36px; height: 36px; border-radius: 50%; background: {{ \App\Models\WhatsappContact::avatarColor($contact->wa_id) }}; color: white; display: flex; align-items: center; justify-content: center; font: 600 13px 'Baloo 2';">
                {{ strtoupper(substr($contact->name ?? $contact->wa_id, 0, 2)) }}
            </div>
        @endif
        {!! \App\Models\WhatsappContact::channelBadgeHtml($contact->channel) !!}
    </div>
    <div style="flex: 1; min-width: 0;">
        <div style="display: flex; gap: 6px; align-items: baseline;">
            <div style="font: 800 13px 'Nunito Sans'; color: #2B3A4C; flex: 1; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $contact->name ?? $contact->wa_id }}</div>
            @if ($contact->needs_human_attention)
                <span title="Needs attention" style="width: 7px; height: 7px; border-radius: 50%; background: #C8355F; flex-shrink: 0;"></span>
            @endif
            <div style="font: 600 10.5px 'Nunito Sans'; color: #B0A493; flex-shrink: 0;">{{ optional($contact->last_message_at)->diffForHumans(null, true) }}</div>
        </div>
        <div style="display: flex; gap: 6px; align-items: center;">
            @if ($contact->ai_state !== \App\Models\WhatsappContact::AI_STATE_ACTIVE)
                <span style="font: 800 9px 'Nunito Sans'; color: #16436E; background: #E9EEF3; border-radius: 5px; padding: 1px 5px; flex-shrink: 0;">{{ \App\Models\WhatsappContact::aiStateLabels()[$contact->ai_state] ?? $contact->ai_state }}</span>
            @endif
            <div style="font: 600 12px 'Nunito Sans'; color: #98897A; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $contact->last_message_preview }}</div>
        </div>
    </div>
    @if ($contact->unread_count > 0)
        <span style="background: #1FA855; color: white; border-radius: 9px; padding: 1px 7px; font: 800 10.5px 'Nunito Sans'; flex-shrink: 0;">{{ $contact->unread_count }}</span>
    @endif
</a>
