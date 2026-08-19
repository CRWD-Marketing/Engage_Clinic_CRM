<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PatientGoal extends Model
{
    protected $fillable = [
        'patient_id',
        'title',
        'progress_percent',
    ];

    protected $casts = [
        'progress_percent' => 'integer',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }
}
