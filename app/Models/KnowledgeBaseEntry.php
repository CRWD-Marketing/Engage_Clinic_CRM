<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KnowledgeBaseEntry extends Model
{
    const STATUS_ACTIVE = 'active';

    const STATUS_DRAFT = 'draft';

    const STATUS_ARCHIVED = 'archived';

    protected $fillable = [
        'title',
        'category',
        'content',
        'status',
        'priority',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'priority' => 'integer',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public static function statusLabels(): array
    {
        return [
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_ARCHIVED => 'Archived',
        ];
    }
}
