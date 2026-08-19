<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceLineItem extends Model
{
    protected $fillable = [
        'invoice_id',
        'service_id',
        'therapist_id',
        'description',
        'cpt_code',
        'sessions',
        'rate',
        'amount',
    ];

    protected function casts(): array
    {
        return [
            'sessions' => 'integer',
            'rate' => 'decimal:2',
            'amount' => 'decimal:2',
        ];
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function therapist()
    {
        return $this->belongsTo(User::class, 'therapist_id');
    }
}
