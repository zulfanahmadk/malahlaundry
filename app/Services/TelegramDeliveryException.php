<?php

namespace App\Services;

use RuntimeException;

class TelegramDeliveryException extends RuntimeException
{
    public function __construct(string $message, public readonly bool $retryable = false, public readonly int $retryAfter = 60)
    {
        parent::__construct($message);
    }
}
