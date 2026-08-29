<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Self-hosted LLM connection
    |--------------------------------------------------------------------------
    |
    | Deploy-time connection details for the self-hosted model runtime (e.g.
    | Ollama). Business-facing tuning (the kill switch, history/KB limits,
    | prompt override) lives in the ai_employee_settings DB table instead -
    | see App\Models\AiEmployeeSettings - so staff can adjust it without a
    | deploy. These config values are only the fallback when that row's
    | model_name is unset, plus the endpoint/timeout the DB doesn't control.
    |
    */

    'llm' => [
        'base_url' => env('AI_EMPLOYEE_LLM_BASE_URL', 'http://127.0.0.1:11434'),
        'model' => env('AI_EMPLOYEE_LLM_MODEL', 'llama3.1:8b'),
        'timeout' => env('AI_EMPLOYEE_LLM_TIMEOUT', 30),
        // Only needed for a hosted endpoint like Ollama Cloud - local Ollama
        // needs no auth, so this is blank by default.
        'api_key' => env('AI_EMPLOYEE_LLM_API_KEY'),
    ],

    // Fallback defaults if the ai_employee_settings row is somehow missing.
    'max_history_messages' => env('AI_EMPLOYEE_MAX_HISTORY', 10),
    'max_kb_entries' => env('AI_EMPLOYEE_MAX_KB_ENTRIES', 5),

    // Inbound message types the AI Employee is allowed to auto-reply to.
    'allowed_message_types' => ['text', 'button', 'interactive'],

    // Shared secret an external scheduler (e.g. a free cron-ping service)
    // must present to trigger queue processing via
    // ProcessQueueController - only meaningful when QUEUE_CONNECTION
    // supports delayed jobs (e.g. `database`), not `sync`.
    'cron_secret' => env('AI_EMPLOYEE_CRON_SECRET'),

];
