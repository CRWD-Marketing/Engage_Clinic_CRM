<?php

namespace App\Services\AiEmployee;

use App\Models\AiEmployeeSettings;
use Illuminate\Support\Facades\Http;

/**
 * Talks to a self-hosted Ollama instance's chat API. This is the only class
 * that knows the concrete LLM runtime - swapping to a different self-hosted
 * server later means writing a new LlmClientInterface implementation and
 * changing one binding in AiEmployeeServiceProvider, nothing else.
 */
class OllamaLlmClient implements LlmClientInterface
{
    public function chat(array $messages, array $options = []): string
    {
        $model = AiEmployeeSettings::current()->model_name ?: config('ai_employee.llm.model');

        $http = Http::baseUrl(config('ai_employee.llm.base_url'))
            ->timeout((int) config('ai_employee.llm.timeout', 30));

        // Only local Ollama needs no auth; Ollama Cloud (and any other
        // OpenAI-style-hosted Ollama endpoint) requires a bearer API key.
        if ($apiKey = config('ai_employee.llm.api_key')) {
            $http = $http->withToken($apiKey);
        }

        $response = $http->post('/api/chat', [
                'model' => $model,
                'messages' => $messages,
                'stream' => false,
                'options' => [
                    'temperature' => $options['temperature'] ?? 0.2,
                ],
            ]);

        if ($response->failed()) {
            throw new \RuntimeException('LLM request failed: HTTP '.$response->status().' '.$response->body());
        }

        return (string) $response->json('message.content');
    }
}
