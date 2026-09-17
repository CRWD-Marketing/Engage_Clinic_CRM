<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class WhatsappMessage extends Model
{
    /**
     * A conversation left open for a while gets a divider even without a
     * calendar-day change - long enough that "5 minutes ago you said X"
     * would be a stretch to read as continuous.
     */
    const DATE_DIVIDER_GAP_MINUTES = 180;

    const AI_STATUS_PENDING = 'pending';

    const AI_STATUS_PROCESSING = 'processing';

    const AI_STATUS_COMPLETED = 'completed';

    const AI_STATUS_FAILED = 'failed';

    const AI_STATUS_SKIPPED = 'skipped';

    /**
     * Message types the AI Employee is allowed to auto-reply to. Stickers,
     * media, story replies/mentions, etc. are stored as usual but never trigger
     * an AI response.
     */
    const AI_ELIGIBLE_TYPES = ['text', 'button', 'interactive'];

    protected $fillable = [
        'whatsapp_contact_id',
        'wa_message_id',
        'direction',
        'type',
        'sticker_id',
        'media_url',
        'body',
        'status',
        'sent_at',
        'is_ai_generated',
        'ai_processing_status',
        'triggered_by_message_id',
        'ai_error',
        'voice_call_session_id',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'is_ai_generated' => 'boolean',
    ];

    public function contact()
    {
        return $this->belongsTo(WhatsappContact::class, 'whatsapp_contact_id');
    }

    public function triggeredReply()
    {
        return $this->hasOne(WhatsappMessage::class, 'triggered_by_message_id');
    }

    public function triggeringMessage()
    {
        return $this->belongsTo(WhatsappMessage::class, 'triggered_by_message_id');
    }

    /**
     * Set only for a voice-call turn (type='voice_turn') - links a transcribed
     * customer utterance or spoken AI reply back to its call. Voice messages
     * never flow through isEligibleForAutoReply()/dispatchAiReplyIfEligible():
     * VoiceTurnService calls AIEmployeeService::respondTo() directly and
     * synchronously per turn instead of via the delayed chat queue.
     */
    public function voiceCallSession()
    {
        return $this->belongsTo(VoiceCallSession::class);
    }

    public function isEligibleForAutoReply(): bool
    {
        return $this->direction === 'inbound'
            && in_array($this->type, self::AI_ELIGIBLE_TYPES, true)
            && filled($this->body);
    }

    /**
     * Delivery-status tick SVG for an outbound bubble (sent/delivered/read/failed).
     */
    /**
     * Whether a message needs a date/time divider above it - shown before
     * the very first message in a conversation, whenever the calendar day
     * changes from the previous message, or after a long enough gap in an
     * otherwise same-day conversation (DATE_DIVIDER_GAP_MINUTES). Shared by
     * the initial page load and the live poll endpoint via
     * whatsapp.partials.messages, so a message that arrives while the
     * conversation is already open gets exactly the same divider it would
     * have gotten on a fresh page load.
     */
    public static function needsDateDivider(Carbon $sentAt, ?Carbon $previousSentAt): bool
    {
        if ($previousSentAt === null) {
            return true;
        }

        if (! $sentAt->isSameDay($previousSentAt)) {
            return true;
        }

        return abs($sentAt->diffInMinutes($previousSentAt)) >= self::DATE_DIVIDER_GAP_MINUTES;
    }

    /**
     * Divider label for a message - always carries a time (not just the
     * date) so a gap divider inside the same day still says when the
     * conversation picked back up, e.g. "Today, 5:57 PM".
     */
    public static function dateDividerLabel(Carbon $sentAt): string
    {
        $day = $sentAt->isToday() ? 'Today' : ($sentAt->isYesterday() ? 'Yesterday' : $sentAt->format('M j, Y'));

        return $day.', '.$sentAt->format('g:i A');
    }

    public static function tickIcon(?string $status): string
    {
        return match ($status) {
            'read' => '<svg width="16" height="11" viewBox="0 0 20 11" fill="none"><path d="M1 5.5L5 9.5L11 1.5" stroke="#24619C" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/><path d="M7 5.5L11 9.5L19 1" stroke="#24619C" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>',
            'delivered' => '<svg width="16" height="11" viewBox="0 0 20 11" fill="none"><path d="M1 5.5L5 9.5L11 1.5" stroke="#9AA79B" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/><path d="M7 5.5L11 9.5L19 1" stroke="#9AA79B" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>',
            'sent' => '<svg width="12" height="11" viewBox="0 0 16 11" fill="none"><path d="M1 5.5L5 9.5L11 1.5" stroke="#9AA79B" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>',
            'failed' => '<span style="color:#C8355F; font:700 10px \'Nunito Sans\';">⚠ failed</span>',
            default => '',
        };
    }

    /**
     * Human-readable stand-in for a message with no text body - a story reply/mention,
     * media attachment, or a type Meta didn't relay content for - shown in place of a
     * raw "[type]" label in the inbox and conversation list preview.
     */
    public static function fallbackLabel(string $type): string
    {
        return match ($type) {
            'story_mention' => '📸 Mentioned you in their story',
            'story_reply' => '↩️ Replied to your story',
            'image' => '📷 Photo',
            'video' => '🎥 Video',
            'audio' => '🎵 Voice message',
            'document', 'file' => '📎 File',
            'sticker' => 'Sticker',
            'like' => '👍',
            'location' => '📍 Location shared',
            'contacts' => '👤 Contact shared',
            'shared_post' => '🔗 Shared a post',
            'unsupported_type' => '⚠️ Unsupported message — check Instagram/WhatsApp directly',
            default => 'Message unavailable',
        };
    }

    /**
     * HTML-escapes message text and turns any http(s)/www links inside it into
     * clickable anchors. Splits on the raw (unescaped) text first so the URL match
     * itself is never mangled by escaping, then escapes each piece independently -
     * escaping the whole string up front and matching against that would corrupt
     * any "&" in a query string into "&amp;" before the link regex ever sees it.
     */
    public static function linkify(?string $text): string
    {
        if ($text === null || $text === '') {
            return '';
        }

        $pattern = '/((?:https?:\/\/|www\.)[^\s<]+)/i';
        $parts = preg_split($pattern, $text, -1, PREG_SPLIT_DELIM_CAPTURE);

        if ($parts === false) {
            return e($text);
        }

        return collect($parts)->map(function (string $part, int $i) {
            // preg_split with DELIM_CAPTURE alternates plain-text/matched-url pieces,
            // starting with plain text - so odd indices are always the captured URLs.
            if ($i % 2 === 0) {
                return e($part);
            }

            $trimmed = rtrim($part, '.,!?)');
            $trailing = substr($part, strlen($trimmed));
            $href = str_starts_with($trimmed, 'http') ? $trimmed : 'https://'.$trimmed;

            return '<a href="'.e($href).'" target="_blank" rel="noopener" '
                .'style="color:#24619C; text-decoration:underline; word-break:break-all;">'
                .e($trimmed).'</a>'.e($trailing);
        })->implode('');
    }
}
