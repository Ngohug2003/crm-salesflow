<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Str;

final class RequestContext
{
    public const string HEADER = 'X-Request-ID';

    private const int MAX_REQUEST_ID_LENGTH = 100;

    public function initialize(Request $request): string
    {
        $requestId = $this->resolveRequestId($request->header(self::HEADER));

        $request->attributes->set('request_id', $requestId);
        Context::add([
            'request_id' => $requestId,
            'http_method' => $request->getMethod(),
            'request_path' => '/'.ltrim($request->path(), '/'),
        ]);

        return $requestId;
    }

    public function enrich(Request $request): void
    {
        $user = $request->user();
        $routeName = $request->route()?->getName();
        [$module, $action] = $this->moduleAndAction($request, $routeName);

        Context::add([
            'route_name' => $routeName,
            'module' => $module,
            'action' => $action,
            'user_id' => $user instanceof Authenticatable ? $user->getAuthIdentifier() : null,
        ]);
    }

    public function id(): ?string
    {
        $requestId = Context::get('request_id');

        return is_string($requestId) && $requestId !== '' ? $requestId : null;
    }

    private function resolveRequestId(?string $requestId): string
    {
        $requestId = trim((string) $requestId);

        if ($requestId !== ''
            && mb_strlen($requestId) <= self::MAX_REQUEST_ID_LENGTH
            && preg_match('/\A[A-Za-z0-9][A-Za-z0-9._:-]*\z/', $requestId) === 1) {
            return $requestId;
        }

        return (string) Str::uuid();
    }

    /** @return array{string, string} */
    private function moduleAndAction(Request $request, ?string $routeName): array
    {
        if ($routeName !== null && $routeName !== '') {
            $segments = explode('.', $routeName);

            return count($segments) === 1
                ? [$segments[0], 'view']
                : [$segments[0], $segments[array_key_last($segments)]];
        }

        $pathSegment = explode('/', trim($request->path(), '/'))[0];

        return [$pathSegment !== '' ? $pathSegment : 'platform', mb_strtolower($request->getMethod())];
    }
}
