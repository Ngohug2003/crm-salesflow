<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\RequestContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

final readonly class AssignRequestId
{
    public function __construct(private RequestContext $context) {}

    public function handle(Request $request, Closure $next): Response
    {
        $requestId = $this->context->initialize($request);
        $startedAt = hrtime(true);

        try {
            /** @var Response $response */
            $response = $next($request);
        } catch (Throwable $exception) {
            $this->context->enrich($request);

            Log::channel('application')->error('http_request_failed', [
                'exception_class' => $exception::class,
                'duration_ms' => $this->durationInMilliseconds($startedAt),
            ]);

            throw $exception;
        }

        $this->context->enrich($request);
        $response->headers->set(RequestContext::HEADER, $requestId);

        if (! $this->shouldSkipCompletedLog($request)) {
            $statusCode = $response->getStatusCode();
            $level = $statusCode >= 500 ? 'error' : ($statusCode >= 400 ? 'warning' : 'debug');

            Log::channel('application')->log($level, 'http_request_completed', [
                'status_code' => $statusCode,
                'duration_ms' => $this->durationInMilliseconds($startedAt),
            ]);
        }

        return $response;
    }

    private function durationInMilliseconds(int $startedAt): float
    {
        return round((hrtime(true) - $startedAt) / 1_000_000, 2);
    }

    private function shouldSkipCompletedLog(Request $request): bool
    {
        if ($request->path() === 'up') {
            return true;
        }

        if ($request->path() !== 'livewire/update') {
            return false;
        }

        foreach ((array) $request->input('components', []) as $component) {
            $snapshot = is_array($component) ? ($component['snapshot'] ?? null) : null;

            if (! is_string($snapshot)) {
                continue;
            }

            $decoded = json_decode($snapshot, true);

            if (is_array($decoded) && data_get($decoded, 'memo.name') === 'platform.system-console') {
                return true;
            }
        }

        return false;
    }
}
