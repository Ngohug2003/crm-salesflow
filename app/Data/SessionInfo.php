<?php

declare(strict_types=1);

namespace App\Data;

final readonly class SessionInfo
{
    public function __construct(
        public string $token,
        public string $fingerprint,
        public string $ipAddress,
        public string $device,
        public string $browser,
        public string $platform,
        public string $lastActiveAt,
        public bool $isCurrent,
    ) {}
}
