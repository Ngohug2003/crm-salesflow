<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

final class DuplicateLeadException extends RuntimeException
{
    /**
     * @param  array<int, array{id: int, full_name: string, email: ?string, phone: ?string, status: string, owner: string, department: string, trashed: bool, matched_fields: list<string>}>  $candidates
     */
    public function __construct(
        public readonly array $candidates,
        public readonly string $signature,
        string $message = 'Phát hiện khách hàng tiềm năng có khả năng trùng lặp.',
    ) {
        parent::__construct($message);
    }
}
