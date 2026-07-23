<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\RequestContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class EnrichAuthenticatedRequestContext
{
    public function __construct(private RequestContext $context) {}

    public function handle(Request $request, Closure $next): Response
    {
        $this->context->enrich($request);

        return $next($request);
    }
}
