<?php

use App\Data\SystemLogFilters;
use App\Services\SystemLogService;
use Illuminate\Support\Facades\File;

beforeEach(function (): void {
    $this->systemLogDirectory = storage_path('framework/testing/system-log-service');
    File::deleteDirectory($this->systemLogDirectory);
    File::ensureDirectoryExists($this->systemLogDirectory);
    config(['logging.channels.application.path' => $this->systemLogDirectory.'/application.log']);
});

afterEach(function (): void {
    File::deleteDirectory($this->systemLogDirectory);
});

it('reads the newest json log backwards and applies safe filters', function (): void {
    File::put($this->systemLogDirectory.'/application-2026-07-22.log', systemLogJson([
        'message' => 'old_event',
        'datetime' => '2026-07-22T08:00:00+07:00',
    ]));

    File::put($this->systemLogDirectory.'/application-2026-07-23.log', implode("\n", [
        systemLogJson([
            'message' => 'lead_created',
            'level_name' => 'INFO',
            'datetime' => '2026-07-23T08:01:00+07:00',
            'context' => ['status_code' => 201, 'duration_ms' => 12.34, 'password' => 'hidden-password'],
            'extra' => ['request_id' => 'req-lead-001', 'module' => 'leads', 'action' => 'create', 'user_id' => 7, 'api_token' => 'hidden-token'],
        ]),
        systemLogJson([
            'extra' => ['request_id' => 'livewire-noise', 'module' => 'default', 'action' => 'update'],
        ]),
        'malformed secret=must-not-leak',
        systemLogJson([
            'message' => 'http_request_failed',
            'level_name' => 'ERROR',
            'datetime' => '2026-07-23T08:02:00+07:00',
            'context' => ['status_code' => 500, 'duration_ms' => 8.5],
            'extra' => ['request_id' => 'req-error-001', 'module' => 'leads', 'action' => 'show', 'user_id' => 9],
        ]),
    ])."\n");

    $result = app(SystemLogService::class)->read(new SystemLogFilters(
        search: 'req-error-001',
        level: 'error',
        module: 'leads',
        limit: 50,
    ));

    expect($result['source'])->toBe('application-2026-07-23.log')
        ->and($result['modules'])->toBe(['leads'])
        ->and($result['entries'])->toHaveCount(1)
        ->and($result['entries'][0]['request_id'])->toBe('req-error-001')
        ->and($result['entries'][0]['level'])->toBe('ERROR')
        ->and(json_encode($result, JSON_THROW_ON_ERROR))->not->toContain('hidden-password', 'hidden-token', 'must-not-leak', 'old_event', 'livewire-noise');
});

it('returns an empty state when no application log exists', function (): void {
    expect(app(SystemLogService::class)->read(new SystemLogFilters))->toBe([
        'entries' => [],
        'modules' => [],
        'source' => null,
    ]);
});

/** @param array<string, mixed> $overrides */
function systemLogJson(array $overrides): string
{
    return json_encode(array_replace_recursive([
        'message' => 'http_request_completed',
        'level_name' => 'DEBUG',
        'datetime' => '2026-07-23T08:00:00+07:00',
        'context' => ['status_code' => 200, 'duration_ms' => 5.2],
        'extra' => ['request_id' => 'req-default', 'module' => 'dashboard', 'action' => 'view', 'user_id' => null],
    ], $overrides), JSON_THROW_ON_ERROR);
}
