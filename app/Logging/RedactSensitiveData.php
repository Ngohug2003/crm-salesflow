<?php

declare(strict_types=1);

namespace App\Logging;

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

final class RedactSensitiveData implements ProcessorInterface
{
    private const string REDACTED = '[REDACTED]';

    /** @var list<string> */
    private const array SENSITIVE_KEYS = [
        'api_key',
        'client_key',
        'private_key',
    ];

    /** @var list<string> */
    private const array SENSITIVE_KEY_FRAGMENTS = [
        'authorization',
        'cookie',
        'password',
        'secret',
        'session',
        'token',
    ];

    public function __invoke(LogRecord $record): LogRecord
    {
        return $record->with(
            context: $this->sanitize($record->context),
            extra: $this->sanitize($record->extra),
        );
    }

    /**
     * @param  array<array-key, mixed>  $values
     * @return array<array-key, mixed>
     */
    private function sanitize(array $values): array
    {
        foreach ($values as $key => $value) {
            if ($this->isSensitiveKey($key)) {
                $values[$key] = self::REDACTED;
            } elseif (is_array($value)) {
                $values[$key] = $this->sanitize($value);
            }
        }

        return $values;
    }

    private function isSensitiveKey(int|string $key): bool
    {
        if (is_int($key)) {
            return false;
        }

        $normalizedKey = mb_strtolower(str_replace(['-', '.', ' '], '_', $key));

        if (in_array($normalizedKey, self::SENSITIVE_KEYS, true)) {
            return true;
        }

        foreach (self::SENSITIVE_KEY_FRAGMENTS as $fragment) {
            if (str_contains($normalizedKey, $fragment)) {
                return true;
            }
        }

        return false;
    }
}
