<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class AiEmployeeSettings extends Model
{
    const CACHE_KEY = 'ai_employee_settings';

    protected $table = 'ai_employee_settings';

    protected $fillable = [
        'is_enabled',
        'model_name',
        'max_history_messages',
        'max_kb_entries',
        'response_delay_seconds',
        'system_prompt_override',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'max_history_messages' => 'integer',
        'max_kb_entries' => 'integer',
        'response_delay_seconds' => 'integer',
    ];

    /**
     * The single settings row, cached briefly since ProcessAiEmployeeReply reads
     * this on every inbound message. Callers that change settings must call
     * forgetCache() so the kill switch/tuning takes effect within the request
     * that saved it, not just after the cache expires.
     */
    public static function current(): self
    {
        return Cache::remember(self::CACHE_KEY, 10, fn () => self::query()->firstOrCreate(['id' => 1]));
    }

    public static function forgetCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
