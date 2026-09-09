<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $fillable = [
        'name',
        'cpt_code',
        'description',
        'default_rate',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'default_rate' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function lineItems()
    {
        return $this->hasMany(InvoiceLineItem::class);
    }
}
