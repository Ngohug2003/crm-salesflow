<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\SystemLogFilters;
use App\Support\ApplicationLogLineFormatter;
use RuntimeException;

final readonly class SystemLogService
{
    private const int MAX_CANDIDATE_LINES = 2000;

    public function __construct(private ApplicationLogLineFormatter $formatter) {}

    /**
     * @return array{
     *     entries: list<array{id: string, timestamp: string, level: string, module: string, action: string, status_code: int|null, user_id: int|null, duration_ms: float|null, request_id: string, event: string, line: string}>,
     *     modules: list<string>,
     *     source: string|null
     * }
     */
    public function read(SystemLogFilters $filters): array
    {
        $path = $this->latestLogPath();

        if ($path === null) {
            return ['entries' => [], 'modules' => [], 'source' => null];
        }

        $candidateLimit = min(self::MAX_CANDIDATE_LINES, max(200, $filters->limit * 10));
        $entries = [];
        $modules = [];

        foreach (array_reverse($this->tail($path, $candidateLimit)) as $line) {
            $entry = $this->formatter->normalize($line);

            if ($entry === null) {
                continue;
            }

            if ($this->isSuccessfulLivewireNoise($entry)) {
                continue;
            }

            $modules[$entry['module']] = true;

            if (! $this->matches($entry, $filters)) {
                continue;
            }

            $entry['id'] = sha1($line);
            $entries[] = $entry;

            if (count($entries) >= $filters->limit) {
                break;
            }
        }

        $moduleOptions = array_keys($modules);
        sort($moduleOptions);

        return [
            'entries' => $entries,
            'modules' => $moduleOptions,
            'source' => basename($path),
        ];
    }

    /**
     * @param  array{timestamp: string, level: string, module: string, action: string, status_code: int|null, user_id: int|null, duration_ms: float|null, request_id: string, event: string, line: string}  $entry
     */
    private function matches(array $entry, SystemLogFilters $filters): bool
    {
        if ($filters->level !== 'ALL' && $entry['level'] !== $filters->level) {
            return false;
        }

        if ($filters->module !== 'all' && mb_strtolower($entry['module']) !== $filters->module) {
            return false;
        }

        if ($filters->search === '') {
            return true;
        }

        $haystack = mb_strtolower(implode(' ', [
            $entry['request_id'],
            $entry['module'],
            $entry['action'],
            $entry['event'],
            (string) ($entry['user_id'] ?? ''),
            (string) ($entry['status_code'] ?? ''),
        ]));

        return str_contains($haystack, $filters->search);
    }

    /**
     * @param  array{timestamp: string, level: string, module: string, action: string, status_code: int|null, user_id: int|null, duration_ms: float|null, request_id: string, event: string, line: string}  $entry
     */
    private function isSuccessfulLivewireNoise(array $entry): bool
    {
        return $entry['module'] === 'default'
            && $entry['action'] === 'update'
            && $entry['event'] === 'http_request_completed'
            && $entry['level'] === 'DEBUG';
    }

    private function latestLogPath(): ?string
    {
        $configuredPath = (string) config('logging.channels.application.path');
        $directory = dirname($configuredPath);
        $baseName = pathinfo($configuredPath, PATHINFO_FILENAME);
        $paths = glob($directory.'/'.$baseName.'*.log') ?: [];
        $paths = array_values(array_filter($paths, 'is_readable'));

        if ($paths === []) {
            return null;
        }

        usort($paths, static function (string $left, string $right): int {
            $modifiedComparison = filemtime($right) <=> filemtime($left);

            return $modifiedComparison !== 0 ? $modifiedComparison : strcmp($right, $left);
        });

        return $paths[0];
    }

    /** @return list<string> */
    private function tail(string $path, int $limit): array
    {
        $stream = fopen($path, 'rb');

        if ($stream === false) {
            throw new RuntimeException('Không thể đọc nhật ký hệ thống.');
        }

        try {
            $size = filesize($path);

            if ($size === false || $size === 0) {
                return [];
            }

            $position = $size;
            $partial = '';
            $lines = [];

            while ($position > 0 && count($lines) < $limit) {
                $length = min(8192, $position);
                $position -= $length;
                fseek($stream, $position);
                $chunk = fread($stream, $length);

                if ($chunk === false) {
                    throw new RuntimeException('Không thể đọc nhật ký hệ thống.');
                }

                $segments = explode("\n", $chunk.$partial);
                $partial = array_shift($segments);
                $lines = array_merge($segments, $lines);

                if (count($lines) > $limit) {
                    $lines = array_slice($lines, -$limit);
                }
            }

            if ($position === 0 && $partial !== '') {
                array_unshift($lines, $partial);
            }

            return array_values(array_filter($lines, static fn (string $line): bool => trim($line) !== ''));
        } finally {
            fclose($stream);
        }
    }
}
