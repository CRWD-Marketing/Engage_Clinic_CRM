<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsappContact extends Model
{
    const AI_STATE_ACTIVE = 'ai_active';

    const AI_STATE_HUMAN_ASSIGNED = 'human_assigned';

    const AI_STATE_HUMAN_TAKEOVER = 'human_takeover';

    const AI_STATE_CLOSED = 'closed';

    protected $fillable = [
        'wa_id',
        'channel',
        'name',
        'avatar_url',
        'child_name',
        'interested_in',
        'insurance',
        'lead_id',
        'last_message_preview',
        'last_message_at',
        'unread_count',
        'ai_state',
        'assigned_user_id',
        'needs_human_attention',
        'needs_human_reason',
        'ai_state_changed_by',
        'ai_state_changed_at',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
        'needs_human_attention' => 'boolean',
        'ai_state_changed_at' => 'datetime',
    ];

    public function messages()
    {
        return $this->hasMany(WhatsappMessage::class);
    }

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function aiEmployeeLogs()
    {
        return $this->hasMany(AiEmployeeLog::class);
    }

    public function voiceCallSessions()
    {
        return $this->hasMany(VoiceCallSession::class, 'whatsapp_contact_id');
    }

    public function isAiActive(): bool
    {
        return $this->ai_state === self::AI_STATE_ACTIVE;
    }

    public function isClosed(): bool
    {
        return $this->ai_state === self::AI_STATE_CLOSED;
    }

    public static function aiStateLabels(): array
    {
        return [
            self::AI_STATE_ACTIVE => 'AI Active',
            self::AI_STATE_HUMAN_ASSIGNED => 'Human Assigned',
            self::AI_STATE_HUMAN_TAKEOVER => 'Human Takeover',
            self::AI_STATE_CLOSED => 'Closed',
        ];
    }

    private const AVATAR_PALETTE = ['#C8355F', '#24619C', '#B97F24', '#6E4FA8', '#1F8FA8', '#A8461F'];

    /**
     * Deterministic avatar background color for the initials fallback.
     */
    public static function avatarColor(string $seed): string
    {
        return self::AVATAR_PALETTE[crc32($seed) % count(self::AVATAR_PALETTE)];
    }

    /**
     * Small channel-badge icon (Instagram/Facebook/WhatsApp) overlaid on an avatar.
     */
    public static function channelBadgeHtml(string $channel): string
    {
        $badgeStyle = 'position:absolute; bottom:-2px; right:-2px; width:15px; height:15px; border-radius:50%; display:flex; align-items:center; justify-content:center; border:2px solid #FFFDFA;';

        if ($channel === 'instagram') {
            return '<span style="'.$badgeStyle.' background:linear-gradient(45deg,#FEDA75,#D62976,#4F5BD5);"><svg width="8" height="8" viewBox="0 0 24 24" fill="none"><rect x="2" y="2" width="20" height="20" rx="6" stroke="white" stroke-width="2.4"/><circle cx="12" cy="12" r="4.5" stroke="white" stroke-width="2.4"/><circle cx="18" cy="6" r="1.3" fill="white"/></svg></span>';
        }

        if ($channel === 'facebook') {
            return '<span style="'.$badgeStyle.' background:linear-gradient(45deg,#0662FE,#00B2FF);"><svg width="9" height="9" viewBox="0 0 24 24" fill="white"><path d="M12 2C6.48 2 2 6.15 2 11.26c0 2.91 1.44 5.51 3.7 7.21V22l3.38-1.86c.9.25 1.87.38 2.92.38 5.52 0 10-4.15 10-9.26C22 6.15 17.52 2 12 2zm1.02 12.47l-2.55-2.72-4.98 2.72 5.48-5.82 2.61 2.72 4.92-2.72-5.48 5.82z"/></svg></span>';
        }

        if ($channel === 'voice') {
            return '<span style="'.$badgeStyle.' background:#B97F24;"><svg width="8" height="8" viewBox="0 0 24 24" fill="white"><path d="M6.62 10.79a15.05 15.05 0 006.59 6.59l2.2-2.2a1 1 0 011.11-.21c1.21.49 2.53.76 3.88.76a1 1 0 011 1V20.5a1 1 0 01-1 1C10.61 21.5 2.5 13.39 2.5 3.5a1 1 0 011-1H6.5a1 1 0 011 1c0 1.35.27 2.67.76 3.88a1 1 0 01-.21 1.11l-2.2 2.2z"/></svg></span>';
        }

        return '<span style="'.$badgeStyle.' background:#1FA855;"><svg width="9" height="9" viewBox="0 0 24 24" fill="white"><path d="M12 2C6.5 2 2 6.5 2 12c0 1.8.5 3.5 1.3 5L2 22l5.2-1.4c1.4.8 3.1 1.2 4.8 1.2 5.5 0 10-4.5 10-10S17.5 2 12 2z"/></svg></span>';
    }
}
