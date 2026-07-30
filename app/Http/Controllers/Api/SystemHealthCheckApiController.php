<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Platform\SystemHealthCheckService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

final class SystemHealthCheckApiController extends Controller
{
    public function __invoke(SystemHealthCheckService $healthService): JsonResponse
    {
        Gate::authorize('system-console.view');

        $report = $healthService->checkAll();

        $statusCode = match ($report['status']) {
            'ok' => 200,
            'warning' => 200,
            default => 500,
        };

        return response()->json($report, $statusCode);
    }
}
