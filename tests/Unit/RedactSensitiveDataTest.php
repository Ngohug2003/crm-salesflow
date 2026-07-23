<?php

use App\Logging\RedactSensitiveData;
use Monolog\Level;
use Monolog\LogRecord;

it('redacts sensitive context and extra values recursively', function (): void {
    $record = new LogRecord(
        datetime: new DateTimeImmutable,
        channel: 'testing',
        level: Level::Info,
        message: 'redaction test',
        context: [
            'authorization' => 'Bearer secret-token',
            'profile' => [
                'password_confirmation' => 'plain-password',
                'api_key' => 'plain-api-key',
                'name' => 'Visible Name',
            ],
        ],
        extra: [
            'request_id' => 'request-001',
            'session_id' => 'plain-session',
        ],
    );

    $redacted = (new RedactSensitiveData)($record);

    expect($redacted->context['authorization'])->toBe('[REDACTED]')
        ->and($redacted->context['profile']['password_confirmation'])->toBe('[REDACTED]')
        ->and($redacted->context['profile']['api_key'])->toBe('[REDACTED]')
        ->and($redacted->context['profile']['name'])->toBe('Visible Name')
        ->and($redacted->extra['request_id'])->toBe('request-001')
        ->and($redacted->extra['session_id'])->toBe('[REDACTED]');
});
