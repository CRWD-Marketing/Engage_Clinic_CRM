<?php

namespace App\Providers;

use App\Services\AiEmployee\LlmClientInterface;
use App\Services\AiEmployee\OllamaLlmClient;
use Illuminate\Support\ServiceProvider;

class AiEmployeeServiceProvider extends ServiceProvider
{
    /**
     * The single binding that makes the LLM runtime a config-level choice
     * rather than something hardcoded through the AI Employee's call chain.
     * Swapping self-hosted runtimes later means writing a new
     * LlmClientInterface implementation and changing this one line.
     */
    public function register(): void
    {
        $this->app->bind(LlmClientInterface::class, OllamaLlmClient::class);
    }
}
