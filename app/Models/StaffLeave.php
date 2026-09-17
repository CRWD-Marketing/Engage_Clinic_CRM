<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StaffLeave extends Model
{
    const TYPES = ['Annual leave', 'Sick leave', 'Public holiday', 'Emergency leave', 'Unpaid leave'];

    protected $fillable = [
        'user_id',
        'leave_date',
        'leave_type',
        'reason',
        'created_by',
    ];

    protected $casts = [
        'leave_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
