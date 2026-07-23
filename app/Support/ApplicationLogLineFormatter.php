<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use JsonException;

final class ApplicationLogLineFormatter
{
    /**
     * @return array{timestamp: string, level: string, module: string, action: string, status_code: int|null, user_id: int|null, duration_ms: float|null, request_id: string, event: string, line: string}|null
     */
    public function normalize(string $line): ?array
    {
        $record = $this->decode($line);

        if ($record === null) {
            return null;
        }

        $context = is_array($record['context'] ?? null) ? $record['context'] : [];
        $extra = is_array($record['extra'] ?? null) ? $record['extra'] : [];
        $level = strtoupper($this->text($record['level_name'] ?? 'INFO', 12));
        $module = $this->text($extra['module'] ?? 'app', 50);
        $action = $this->text($extra['action'] ?? 'event', 50);
        $statusCode = $this->nullableInteger($context['status_code'] ?? null);
        $userId = $this->nullableInteger($extra['user_id'] ?? null);
        $duration = is_numeric($context['duration_ms'] ?? null)
            ? round((float) $context['duration_ms'], 2)
            : null;
        $requestId = $this->text($extra['request_id'] ?? '-', 100);
        $event = $this->event($record['message'] ?? 'application_event');
        $timestamp = $this->timestamp($record['datetime'] ?? null);

        $normalized = [
            'timestamp' => $timestamp,
            'level' => $level,
            'module' => $module,
            'action' => $action,
            'status_code' => $statusCode,
            'user_id' => $userId,
            'duration_ms' => $duration,
            'request_id' => $requestId,
            'event' => $event,
        ];

        return [
            ...$normalized,
            'line' => $this->formatNormalized($normalized),
        ];
    }

    public function format(string $line): ?string
    {
        return $this->normalize($line)['line'] ?? null;
    }

    /** @return array<string, mixed>|null */
    private function decode(string $line): ?array
    {
        try {
            $record = json_decode($line, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }

        return is_array($record) ? $record : null;
    }

    /**
     * @param  array{timestamp: string, level: string, module: string, action: string, status_code: int|null, user_id: int|null, duration_ms: float|null, request_id: string, event: string}  $entry
     */
    private function formatNormalized(array $entry): string
    {
        $parts = [
            '['.$entry['timestamp'].']',
            $entry['level'],
            $entry['module'].'/'.$entry['action'],
            'status='.($entry['status_code'] ?? '-'),
            'user='.($entry['user_id'] ?? 'guest'),
            $entry['duration_ms'] !== null ? $entry['duration_ms'].'ms' : null,
            'req='.$entry['request_id'],
            $entry['event'],
        ];

        return implode(' | ', array_values(array_filter($parts)));
    }

    private function timestamp(mixed $value): string
    {
        if (! is_string($value) || $value === '') {
            return now()->format('d/m/Y H:i:s');
        }

        try {
            return CarbonImmutable::parse($value)
                ->setTimezone('Asia/Ho_Chi_Minh')
                ->format('d/m/Y H:i:s');
        } catch (\Throwable) {
            return now()->format('d/m/Y H:i:s');
        }
    }

    private function nullableInteger(mixed $value): ?int
    {
        return is_int($value) || (is_string($value) && ctype_digit($value))
            ? (int) $value
            : null;
    }

    private function event(mixed $value): string
    {
        $event = $this->text($value, 120);

        return preg_match('/\A[A-Za-z0-9][A-Za-z0-9_.:-]*\z/', $event) === 1
            ? $event
            : 'application_event';
    }

    private function text(mixed $value, int $limit): string
    {
        if (! is_scalar($value)) {
            return '-';
        }

        $clean = preg_replace('/[\x00-\x1F\x7F]+/u', ' ', (string) $value) ?? '-';

        return mb_substr(trim($clean), 0, $limit) ?: '-';
    }
}
