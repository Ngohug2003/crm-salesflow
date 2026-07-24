<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

final class LeadConversionException extends RuntimeException
{
    public function __construct(
        public readonly string $reasonCode,
        string $message,
    ) {
        parent::__construct($message);
    }
}
