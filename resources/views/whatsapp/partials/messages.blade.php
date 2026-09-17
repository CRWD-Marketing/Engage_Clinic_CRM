{{--
    Renders a run of messages with date/time-gap dividers between them -
    shared by the initial page load (index.blade.php, $previousSentAt null)
    and the live poll endpoint (WhatsappController::poll(), $previousSentAt
    set to whatever was last shown), so a gap divider appears correctly
    whether it falls inside the messages already on screen or is crossed by
    a message that arrives later while the conversation stays open.
--}}
@php
    $lastMsgAt = $previousSentAt ?? null;
@endphp
@foreach ($messages as $message)
    @php
        $showDivider = \App\Models\WhatsappMessage::needsDateDivider($message->sent_at, $lastMsgAt);
        $lastMsgAt = $message->sent_at;
    @endphp
    @if ($showDivider)
        <div class="wa-date-divider"><span>{{ \App\Models\WhatsappMessage::dateDividerLabel($message->sent_at) }}</span></div>
    @endif
    @include('whatsapp.partials.message', ['message' => $message, 'activeContact' => $activeContact])
@endforeach
