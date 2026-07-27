<?php

declare(strict_types=1);

namespace App\Logging;

use App\Events\SystemLogEntryCreated;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\App;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Level;
use Monolog\LogRecord;
use Throwable;

/**
 * Custom Monolog handler that broadcasts each processed log entry
 * over the Reverb WebSocket channel 'private-system-console'.
 *
 * Safety guards:
 * - Disabled in CLI / Artisan context (queue workers, scheduler, etc.)
 * - Disabled in testing environment to avoid circular broadcasting
 * - Skips entries from 'broadcasting' or 'reverb' modules to prevent loops
 */
final class SystemLogBroadcastHandler extends AbstractProcessingHandler
{
    /** Modules whose logs must never be re-broadcast to avoid infinite loops */
    private const array SKIP_MODULES = ['broadcasting', 'reverb', 'pusher', 'echo', 'websockets'];

    public function __construct(Level $level = Level::Debug, bool $bubble = true)
    {
        parent::__construct($level, $bubble);
    }

    protected function write(LogRecord $record): void
    {
        // Never broadcast during artisan CLI or tests
        if (App::runningInConsole() || App::runningUnitTests()) {
            return;
        }

        try {
            $extra = $record->extra;
            $context = $record->context;

            $module = $this->text($extra['module'] ?? 'app', 50);

            // Prevent circular: skip broadcasting/reverb module logs
            if (in_array(strtolower($module), self::SKIP_MODULES, true)) {
                return;
            }

            $action = $this->text($extra['action'] ?? 'event', 50);
            $level = strtoupper($record->level->name);
            $statusCode = $this->nullableInt($context['status_code'] ?? null);
            $userId = $this->nullableInt($extra['user_id'] ?? null);
            $durationMs = is_numeric($context['duration_ms'] ?? null)
                ? round((float) $context['duration_ms'], 2)
                : null;
            $requestId = $this->text($extra['request_id'] ?? '-', 100);
            $event = $this->sanitizeEvent($record->message);
            $timestamp = $this->formatTimestamp($record->datetime->format('c'));

            $normalized = [
                'timestamp' => $timestamp,
                'level' => $level,
                'module' => $module,
                'action' => $action,
                'status_code' => $statusCode,
                'user_id' => $userId,
                'duration_ms' => $durationMs,
                'request_id' => $requestId,
                'event' => $event,
            ];

            $parts = [
                '['.$timestamp.']',
                $level,
                $module.'/'.$action,
                'status='.($statusCode ?? '-'),
                'user='.($userId ?? 'guest'),
                $durationMs !== null ? $durationMs.'ms' : null,
                'req='.$requestId,
                $event,
            ];

            $entry = [
                ...$normalized,
                'id' => uniqid('rt_', true),
                'line' => implode(' | ', array_values(array_filter($parts))),
            ];

            broadcast(new SystemLogEntryCreated($entry));
        } catch (Throwable) {
            // Silently swallow — never let broadcast failure disrupt the main request
        }
    }

    private function text(mixed $value, int $limit): string
    {
        if (! is_scalar($value)) {
            return '-';
        }

        $clean = preg_replace('/[\x00-\x1F\x7F]+/u', ' ', (string) $value) ?? '-';

        return mb_substr(trim($clean), 0, $limit) ?: '-';
    }

    private function nullableInt(mixed $value): ?int
    {
        return is_int($value) || (is_string($value) && ctype_digit($value))
            ? (int) $value
            : null;
    }

    private function sanitizeEvent(string $message): string
    {
        $clean = mb_substr(trim($message), 0, 120);

        return preg_match('/\A[A-Za-z0-9][A-Za-z0-9_.:-]*\z/', $clean) === 1
            ? $clean
            : 'application_event';
    }

    private function formatTimestamp(string $value): string
    {
        try {
            return CarbonImmutable::parse($value)
                ->setTimezone('Asia/Ho_Chi_Minh')
                ->format('d/m/Y H:i:s');
        } catch (Throwable) {
            return now()->format('d/m/Y H:i:s');
        }
    }
}
