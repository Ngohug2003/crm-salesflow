<?php

use App\Support\ApplicationLogLineFormatter;

it('formats only safe operational fields for the system console', function (): void {
    $line = json_encode([
        'message' => "http_request_completed\nignored-line",
        'level_name' => 'DEBUG',
        'datetime' => '2026-07-23T03:03:16.054762+07:00',
        'context' => [
            'status_code' => 302,
            'duration_ms' => 85.891,
            'password' => 'must-not-be-shown',
        ],
        'extra' => [
            'request_id' => 'docker-realtime-log-001',
            'module' => 'dashboard',
            'action' => 'view',
            'user_id' => null,
            'authorization' => 'must-not-be-shown',
        ],
    ], JSON_THROW_ON_ERROR);

    $formatted = app(ApplicationLogLineFormatter::class)->format($line);

    expect($formatted)
        ->toBe('[23/07/2026 03:03:16] | DEBUG | dashboard/view | status=302 | user=guest | 85.89ms | req=docker-realtime-log-001 | application_event')
        ->not->toContain('must-not-be-shown');
});

it('ignores malformed lines instead of exposing their raw contents', function (): void {
    expect(app(ApplicationLogLineFormatter::class)->format('not-json secret=plain-text'))->toBeNull();
});
