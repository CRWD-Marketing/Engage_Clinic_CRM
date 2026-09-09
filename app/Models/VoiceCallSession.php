<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VoiceCallSession extends Model
{
    const STATUS_IN_PROGRESS = 'in_progress';

    const STATUS_COMPLETED = 'completed';

    const STATUS_ESCALATED = 'escalated';

    const STATUS_FAILED = 'failed';

    const STATUS_NO_ANSWER = 'no_answer';

    const SUMMARY_PENDING = 'pending';

    const SUMMARY_PROCESSING = 'processing';

    const SUMMARY_COMPLETED = 'completed';

    const SUMMARY_FAILED = 'failed';

    protected $fillable = [
        'whatsapp_contact_id',
        'provider',
        'provider_call_sid',
        'from_number',
        'to_number',
        'status',
        'started_at',
        'ended_at',
        'duration_seconds',
        'recording_url',
        'escalated',
        'escalation_reason',
        'transferred_to_human',
        'summary',
        'summary_status',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'escalated' => 'boolean',
        'transferred_to_human' => 'boolean',
        'summary' => 'array',
    ];

    public function contact()
    {
        return $this->belongsTo(WhatsappContact::class, 'whatsapp_contact_id');
    }

    public function transcriptMessages()
    {
        return $this->hasMany(WhatsappMessage::class)->orderBy('sent_at');
    }
}
