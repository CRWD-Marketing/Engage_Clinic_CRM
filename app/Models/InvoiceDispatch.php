<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One time an invoice (or a reminder for it) went out to the customer:
 * channel, recipient, timestamp, and whether receipt was confirmed.
 */
class InvoiceDispatch extends Model
{
    const CHANNELS = ['email', 'whatsapp'];

    protected $fillable = ['invoice_id', 'channel', 'kind', 'sent_to', 'sent_at', 'sent_by', 'receipt_confirmed_at', 'receipt_confirmed_by', 'note'];

    protected $casts = [
        'sent_at' => 'datetime',
        'receipt_confirmed_at' => 'datetime',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function channelLabel(): string
    {
        return $this->channel === 'whatsapp' ? 'WhatsApp' : 'Email';
    }
}
