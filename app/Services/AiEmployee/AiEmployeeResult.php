<?php

namespace App\Services\AiEmployee;

class AiEmployeeResult
{
    /**
     * @param  array<int, int>  $knowledgeBaseEntryIds
     */
    public function __construct(
        public readonly string $reply,
        public readonly bool $escalate,
        public readonly array $knowledgeBaseEntryIds,
    ) {}
}
