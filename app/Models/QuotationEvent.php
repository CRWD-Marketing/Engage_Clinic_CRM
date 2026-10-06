<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One line of a quotation's history: sent, confirmed, paid, cancelled.
 */
class QuotationEvent extends Model
{
    protected $fillable = ['quotation_id', 'user_id', 'event', 'note'];

    public function quotation()
    {
        return $this->belongsTo(Quotation::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
