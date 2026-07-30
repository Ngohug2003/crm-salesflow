<?php

declare(strict_types=1);

namespace App\Logging;

use Illuminate\Log\Logger;
use Monolog\Logger as MonologLogger;

final readonly class ConfigureStructuredLogging
{
    public function __construct(
        private RedactSensitiveData $redactor,
        private SystemLogBroadcastHandler $broadcastHandler,
    ) {}

    public function __invoke(Logger $logger): void
    {
        $monolog = $logger->getLogger();

        if ($monolog instanceof MonologLogger) {
            $monolog->pushProcessor($this->redactor);
            $monolog->pushHandler($this->broadcastHandler);
        }
    }
}
