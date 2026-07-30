<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

final class DuplicateContactException extends RuntimeException
{
    /**
     * @param  list<array{id: int, full_name: string, company: string, email: ?string, phone: ?string, owner: string, matched_fields: list<string>}>  $candidates
     */
    public function __construct(
        public readonly array $candidates,
        public readonly string $signature,
        string $message = 'Phát hiện Người liên hệ trùng lặp trong hệ thống.',
    ) {
        parent::__construct($message);
    }
}
