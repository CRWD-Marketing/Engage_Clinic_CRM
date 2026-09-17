<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Insurance extends Model
{
    protected $fillable = [
        'name',
        'default_coverage_percent',
        'is_active',
    ];

    protected $casts = [
        'default_coverage_percent' => 'integer',
        'is_active' => 'boolean',
    ];
}
