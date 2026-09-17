<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    const METHODS = ['Bank transfer', 'Card', 'Cash', 'Cheque', 'Insurance remittance'];

    protected $fillable = ['invoice_id', 'receipt_number', 'amount', 'method', 'received_on', 'reference', 'recorded_by'];

    protected $casts = [
        'amount' => 'decimal:2',
        'received_on' => 'date',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function recorder()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
