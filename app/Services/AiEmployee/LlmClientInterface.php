<?php

namespace App\Services\AiEmployee;

interface LlmClientInterface
{
    /**
     * Send a single chat completion request and return the raw text response.
     *
     * @param  array<int, array{role: string, content: string}>  $messages
     * @param  array{temperature?: float, max_tokens?: int}  $options
     */
    public function chat(array $messages, array $options = []): string;
}
