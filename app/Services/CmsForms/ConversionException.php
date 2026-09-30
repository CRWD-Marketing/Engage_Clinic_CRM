<?php

namespace App\Services\CmsForms;

class ConversionException extends \RuntimeException
{
    public function __construct(string $message, public readonly ?int $existingLeadId = null)
    {
        parent::__construct($message);
    }
}
