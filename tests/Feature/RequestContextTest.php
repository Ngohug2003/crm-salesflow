<?php

use App\Http\Middleware\AssignRequestId;
use App\Logging\ConfigureStructuredLogging;
use App\Support\RequestContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Monolog\Formatter\JsonFormatter;

beforeEach(function (): void {
    $this->structuredLogPath = storage_path('framework/testing/request-context.log');
    File::delete($this->structuredLogPath);

    config([
        'logging.channels.request-context-test' => [
            'driver' => 'single',
            'path' => $this->structuredLogPath,
            'level' => 'debug',
            'replace_placeholders' => true,
            'formatter' => JsonFormatter::class,
            'tap' => [ConfigureStructuredLogging::class],
        ],
    ]);

    Log::forgetChannel('request-context-test');

    Route::middleware('web')
        ->get('/_testing/request-context', function (Request $request) {
            return response()->json([
                'attribute_request_id' => $request->attributes->get('request_id'),
                'context_request_id' => Context::get('request_id'),
            ]);
        })
        ->name('testing.request-context');

    Route::middleware('web')
        ->get('/_testing/request-context/log', function () {
            Log::channel('request-context-test')->info('structured_context_test', [
                'password' => 'plain-password',
                'profile' => [
                    'api_token' => 'plain-token',
                    'name' => 'Visible Name',
                ],
            ]);

            return response()->noContent();
        })
        ->name('testing.request-context.log');

    Route::middleware('web')
        ->get('/_testing/request-context/failure', function (): never {
            throw new RuntimeException('Request context failure test.');
        })
        ->name('testing.request-context.failure');
});

afterEach(function (): void {
    Log::forgetChannel('request-context-test');
    File::delete($this->structuredLogPath);
});

it('generates one request id for context and the response', function (): void {
    $response = $this->get('/_testing/request-context')->assertOk();
    $requestId = (string) $response->headers->get(RequestContext::HEADER);

    expect(Str::isUuid($requestId))->toBeTrue();

    $response->assertJson([
        'attribute_request_id' => $requestId,
        'context_request_id' => $requestId,
    ]);
});

it('preserves a valid inbound request id', function (): void {
    $requestId = 'client-request_20260723:0001';

    $this->withHeader(RequestContext::HEADER, $requestId)
        ->get('/_testing/request-context')
        ->assertOk()
        ->assertHeader(RequestContext::HEADER, $requestId)
        ->assertJson([
            'attribute_request_id' => $requestId,
            'context_request_id' => $requestId,
        ]);
});

it('replaces an unsafe inbound request id', function (): void {
    $response = $this->withHeader(RequestContext::HEADER, '../../bad request')
        ->get('/_testing/request-context')
        ->assertOk();
    $requestId = (string) $response->headers->get(RequestContext::HEADER);

    expect($requestId)->not->toBe('../../bad request')
        ->and(Str::isUuid($requestId))->toBeTrue();
});

it('writes structured context and redacts nested secrets', function (): void {
    $requestId = 'structured-log-request-001';

    $this->withHeader(RequestContext::HEADER, $requestId)
        ->get('/_testing/request-context/log')
        ->assertNoContent()
        ->assertHeader(RequestContext::HEADER, $requestId);

    Log::forgetChannel('request-context-test');

    $record = json_decode(trim(File::get($this->structuredLogPath)), true, flags: JSON_THROW_ON_ERROR);

    expect($record['message'])->toBe('structured_context_test')
        ->and($record['extra']['request_id'])->toBe($requestId)
        ->and($record['extra']['http_method'])->toBe('GET')
        ->and($record['extra']['route_name'])->toBe('testing.request-context.log')
        ->and($record['extra']['module'])->toBe('testing')
        ->and($record['extra']['action'])->toBe('log')
        ->and($record['context']['password'])->toBe('[REDACTED]')
        ->and($record['context']['profile']['api_token'])->toBe('[REDACTED]')
        ->and($record['context']['profile']['name'])->toBe('Visible Name');
});

it('adds the request id to an internal server error response', function (): void {
    config(['app.debug' => false]);

    $response = $this->get('/_testing/request-context/failure')->assertInternalServerError();
    $requestId = (string) $response->headers->get(RequestContext::HEADER);

    expect(Str::isUuid($requestId))->toBeTrue();

    $response->assertSee($requestId);
});

it('configures separate structured application and security channels', function (): void {
    expect(config('logging.channels.application.driver'))->toBe('daily')
        ->and(config('logging.channels.application.formatter'))->toBe(JsonFormatter::class)
        ->and(config('logging.channels.security.formatter'))->toBe(JsonFormatter::class)
        ->and(config('logging.channels.application.tap'))->toContain(ConfigureStructuredLogging::class)
        ->and(config('logging.channels.security.tap'))->toContain(ConfigureStructuredLogging::class);
});

it('does not let system console polling generate another successful access log', function (): void {
    Log::spy();

    $request = Request::create('/livewire/update', 'POST', [
        'components' => [[
            'snapshot' => json_encode([
                'memo' => ['name' => 'platform.system-console'],
            ], JSON_THROW_ON_ERROR),
        ]],
    ]);

    app(AssignRequestId::class)->handle($request, fn () => response()->noContent());

    Log::shouldNotHaveReceived('channel');
});
