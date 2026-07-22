<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

final class LeadWorkflowException extends RuntimeException
{
    public function __construct(
        public readonly string $field,
        string $message,
    ) {
        parent::__construct($message);
    }
}
