<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

final class StaleOpportunityException extends RuntimeException
{
    public function __construct(string $message = 'Cơ hội bán hàng đã bị thay đổi bởi người dùng khác. Vui lòng tải lại trang và thử lại.')
    {
        parent::__construct($message, Response::HTTP_CONFLICT);
    }
}
