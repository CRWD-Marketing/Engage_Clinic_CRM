<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One line of an invoice's own audit trail: who did what to it and when,
 * from draft through Finance verification to dispatch.
 */
class InvoiceEvent extends Model
{
    protected $fillable = ['invoice_id', 'user_id', 'event', 'note'];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
