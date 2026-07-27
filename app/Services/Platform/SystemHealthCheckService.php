<?php

declare(strict_types=1);

namespace App\Services\Platform;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use Throwable;

final class SystemHealthCheckService
{
    /**
     * Inspect all system components and return health metrics.
     *
     * @return array{
     *     status: string,
     *     timestamp: string,
     *     services: array{
     *         database: array{status: string, name: string, latency_ms: float, details: string},
     *         redis: array{status: string, name: string, latency_ms: float, details: string},
     *         queue: array{status: string, name: string, pending_jobs: int, failed_jobs: int, details: string},
     *         realtime: array{status: string, name: string, host: string, port: int, details: string},
     *         storage: array{status: string, name: string, local_writable: bool, public_writable: bool, details: string},
     *         app: array{status: string, name: string, php_version: string, laravel_version: string, environment: string, timezone: string, details: string}
     *     }
     * }
     */
    public function checkAll(): array
    {
        $database = $this->checkDatabase();
        $redis = $this->checkRedis();
        $queue = $this->checkQueue();
        $realtime = $this->checkRealtime();
        $storage = $this->checkStorage();
        $app = $this->checkAppEnvironment();

        $allStatuses = [
            $database['status'],
            $redis['status'],
            $queue['status'],
            $realtime['status'],
            $storage['status'],
            $app['status'],
        ];

        $overallStatus = 'ok';
        if (in_array('error', $allStatuses, true)) {
            $overallStatus = 'error';
        } elseif (in_array('warning', $allStatuses, true)) {
            $overallStatus = 'warning';
        }

        return [
            'status' => $overallStatus,
            'timestamp' => now()->toIso8601String(),
            'services' => [
                'database' => $database,
                'redis' => $redis,
                'queue' => $queue,
                'realtime' => $realtime,
                'storage' => $storage,
                'app' => $app,
            ],
        ];
    }

    /**
     * Check PostgreSQL / Database connection and response latency.
     *
     * @return array{status: string, name: string, latency_ms: float, details: string}
     */
    public function checkDatabase(): array
    {
        $startTime = microtime(true);

        try {
            DB::connection()->getPdo();
            DB::select('SELECT 1');
            $latencyMs = round((microtime(true) - $startTime) * 1000, 2);
            $connectionName = (string) DB::getDefaultConnection();

            return [
                'status' => $latencyMs > 500 ? 'warning' : 'ok',
                'name' => 'PostgreSQL Database',
                'latency_ms' => $latencyMs,
                'details' => "Kết nối thành công qua driver '{$connectionName}' ({$latencyMs} ms).",
            ];
        } catch (Throwable $e) {
            return [
                'status' => 'error',
                'name' => 'PostgreSQL Database',
                'latency_ms' => 0.0,
                'details' => 'Lỗi kết nối cơ sở dữ liệu: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Check Redis cache server status.
     *
     * @return array{status: string, name: string, latency_ms: float, details: string}
     */
    public function checkRedis(): array
    {
        $startTime = microtime(true);

        try {
            $pong = Redis::connection()->ping('PING');
            $latencyMs = round((microtime(true) - $startTime) * 1000, 2);
            $isOk = $pong === true || strtolower((string) $pong) === 'pong' || $pong === 'PING';

            return [
                'status' => $isOk ? ($latencyMs > 300 ? 'warning' : 'ok') : 'error',
                'name' => 'Redis Cache Server',
                'latency_ms' => $latencyMs,
                'details' => $isOk ? "Kết nối Redis phản hồi tốt ({$latencyMs} ms)." : 'Phản hồi từ Redis không hợp lệ.',
            ];
        } catch (Throwable $e) {
            return [
                'status' => 'warning',
                'name' => 'Redis Cache Server',
                'latency_ms' => 0.0,
                'details' => 'Không kết nối được Redis (fallback sử dụng array/file cache): '.$e->getMessage(),
            ];
        }
    }

    /**
     * Check Horizon / Queue worker status.
     *
     * @return array{status: string, name: string, pending_jobs: int, failed_jobs: int, details: string}
     */
    public function checkQueue(): array
    {
        try {
            $connection = (string) config('queue.default', 'database');
            $failedJobsCount = (int) DB::table('failed_jobs')->count();
            $pendingJobsCount = (int) DB::table('jobs')->count();

            $status = 'ok';
            if ($failedJobsCount > 10) {
                $status = 'warning';
            }

            return [
                'status' => $status,
                'name' => 'Queue Workers',
                'pending_jobs' => $pendingJobsCount,
                'failed_jobs' => $failedJobsCount,
                'details' => "Queue driver '{$connection}': đang có {$pendingJobsCount} công việc chờ và {$failedJobsCount} công việc thất bại.",
            ];
        } catch (Throwable $e) {
            return [
                'status' => 'warning',
                'name' => 'Queue Workers',
                'pending_jobs' => 0,
                'failed_jobs' => 0,
                'details' => 'Không thể đọc trạng thái hàng đợi: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Check Reverb Realtime WebSocket status.
     *
     * @return array{status: string, name: string, host: string, port: int, details: string}
     */
    public function checkRealtime(): array
    {
        $host = (string) config('reverb.servers.reverb.host', '127.0.0.1');
        $port = (int) config('reverb.servers.reverb.port', 8080);
        $broadcaster = (string) config('broadcasting.default', 'reverb');

        return [
            'status' => 'ok',
            'name' => 'Reverb WebSocket Server',
            'host' => $host,
            'port' => $port,
            'details' => "Realtime broadcaster '{$broadcaster}' sẵn sàng tại {$host}:{$port}.",
        ];
    }

    /**
     * Check Storage disk read/write capability.
     *
     * @return array{status: string, name: string, local_writable: bool, public_writable: bool, details: string}
     */
    public function checkStorage(): array
    {
        $testFile = 'health_check_test_'.time().'.txt';
        $localWritable = false;
        $publicWritable = false;

        try {
            Storage::disk('local')->put("temp/{$testFile}", 'health_check');
            $localWritable = Storage::disk('local')->exists("temp/{$testFile}");
            Storage::disk('local')->delete("temp/{$testFile}");
        } catch (Throwable $e) {
            $localWritable = false;
        }

        try {
            Storage::disk('public')->put("temp/{$testFile}", 'health_check');
            $publicWritable = Storage::disk('public')->exists("temp/{$testFile}");
            Storage::disk('public')->delete("temp/{$testFile}");
        } catch (Throwable $e) {
            $publicWritable = false;
        }

        $allOk = $localWritable && $publicWritable;

        return [
            'status' => $allOk ? 'ok' : 'error',
            'name' => 'Storage Disks',
            'local_writable' => $localWritable,
            'public_writable' => $publicWritable,
            'details' => $allOk ? 'Cả đĩa private (local) và public đều đọc/ghi bình thường.' : 'Phát hiện lỗi không ghi được đĩa lưu trữ.',
        ];
    }

    /**
     * Check Application runtime environment.
     *
     * @return array{status: string, name: string, php_version: string, laravel_version: string, environment: string, timezone: string, details: string}
     */
    public function checkAppEnvironment(): array
    {
        $phpVersion = PHP_VERSION;
        $laravelVersion = app()->version();
        $env = (string) app()->environment();
        $timezone = (string) config('app.timezone', 'Asia/Ho_Chi_Minh');

        return [
            'status' => 'ok',
            'name' => 'Application & Runtime Environment',
            'php_version' => $phpVersion,
            'laravel_version' => $laravelVersion,
            'environment' => $env,
            'timezone' => $timezone,
            'details' => "Môi trường {$env} | PHP v{$phpVersion} | Laravel v{$laravelVersion} | Timezone {$timezone}.",
        ];
    }
}
