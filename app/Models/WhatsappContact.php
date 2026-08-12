<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsappContact extends Model
{
    protected $fillable = [
        'wa_id',
        'channel',
        'name',
        'lead_id',
        'last_message_preview',
        'last_message_at',
        'unread_count',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
    ];

    public function messages()
    {
        return $this->hasMany(WhatsappMessage::class);
    }

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }
}
