<?php

use App\Http\Middleware\AssignRequestId;
use App\Http\Middleware\EnrichAuthenticatedRequestContext;
use App\Http\Middleware\EnsureAccountIsActive;
use App\Support\RequestContext;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prepend(AssignRequestId::class);
        $middleware->appendToGroup('web', EnrichAuthenticatedRequestContext::class);
        $middleware->appendToGroup('api', EnrichAuthenticatedRequestContext::class);
        $middleware->alias([
            'account.active' => EnsureAccountIsActive::class,
            'request.context.authenticated' => EnrichAuthenticatedRequestContext::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->respond(function (Response $response): Response {
            $requestId = app(RequestContext::class)->id();

            if ($requestId !== null) {
                $response->headers->set(RequestContext::HEADER, $requestId);
            }

            return $response;
        });
    })->create();
