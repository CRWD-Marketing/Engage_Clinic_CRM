<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiEmployeeLog extends Model
{
    const STATUS_SUCCESS = 'success';

    const STATUS_ESCALATED = 'escalated';

    const STATUS_ERROR = 'error';

    protected $fillable = [
        'whatsapp_contact_id',
        'inbound_message_id',
        'outbound_message_id',
        'knowledge_base_entry_ids',
        'escalated',
        'status',
        'error_message',
        'llm_latency_ms',
    ];

    protected $casts = [
        'knowledge_base_entry_ids' => 'array',
        'escalated' => 'boolean',
    ];

    public function contact()
    {
        return $this->belongsTo(WhatsappContact::class, 'whatsapp_contact_id');
    }

    public function inboundMessage()
    {
        return $this->belongsTo(WhatsappMessage::class, 'inbound_message_id');
    }

    public function outboundMessage()
    {
        return $this->belongsTo(WhatsappMessage::class, 'outbound_message_id');
    }
}
