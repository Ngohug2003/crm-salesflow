<?php

declare(strict_types=1);

namespace App\Services\Platform;

final class EnvironmentReadinessCheckService
{
    /**
     * Run environment readiness checks and return structured checklist.
     *
     * @return array{
     *     overall_status: string,
     *     items: list<array{
     *         key: string,
     *         category: string,
     *         title: string,
     *         status: string,
     *         summary: string,
     *         recommendation: string|null
     *     }>
     * }
     */
    public function checkAll(): array
    {
        $items = [
            $this->checkAppKey(),
            $this->checkDebugMode(),
            $this->checkTimezone(),
            $this->checkDatabaseConfig(),
            $this->checkMailConfig(),
            $this->checkQueueConfig(),
            $this->checkReverbConfig(),
            $this->checkSessionSecurity(),
            $this->checkStorageSymlink(),
        ];

        $statuses = array_column($items, 'status');
        $overallStatus = 'passed';
        if (in_array('failed', $statuses, true)) {
            $overallStatus = 'failed';
        } elseif (in_array('warning', $statuses, true)) {
            $overallStatus = 'warning';
        }

        return [
            'overall_status' => $overallStatus,
            'items' => $items,
        ];
    }

    /**
     * Check APP_KEY presence.
     *
     * @return array{key: string, category: string, title: string, status: string, summary: string, recommendation: string|null}
     */
    public function checkAppKey(): array
    {
        $hasKey = ! empty(config('app.key'));

        return [
            'key' => 'app_key',
            'category' => 'Application',
            'title' => 'Khóa ứng dụng (APP_KEY)',
            'status' => $hasKey ? 'passed' : 'failed',
            'summary' => $hasKey ? 'APP_KEY đã được tạo và thiết lập.' : 'Chưa thiết lập APP_KEY.',
            'recommendation' => $hasKey ? null : 'Chạy lệnh php artisan key:generate để tạo khóa ứng dụng.',
        ];
    }

    /**
     * Check APP_ENV & APP_DEBUG mode.
     *
     * @return array{key: string, category: string, title: string, status: string, summary: string, recommendation: string|null}
     */
    public function checkDebugMode(): array
    {
        $env = (string) app()->environment();
        $debug = (bool) config('app.debug', false);

        if ($env === 'production' && $debug) {
            return [
                'key' => 'app_debug',
                'category' => 'Application',
                'title' => 'Môi trường & Chế độ Debug',
                'status' => 'failed',
                'summary' => "Môi trường '{$env}' đang bật APP_DEBUG=true (Nguy cơ rò rỉ thông tin nhạy cảm).",
                'recommendation' => 'Đặt APP_DEBUG=false trong file .env môi trường production.',
            ];
        }

        return [
            'key' => 'app_debug',
            'category' => 'Application',
            'title' => 'Môi trường & Chế độ Debug',
            'status' => 'passed',
            'summary' => "Môi trường '{$env}' | Debug mode ".($debug ? 'Bật' : 'Tắt').'.',
            'recommendation' => null,
        ];
    }

    /**
     * Check application timezone.
     *
     * @return array{key: string, category: string, title: string, status: string, summary: string, recommendation: string|null}
     */
    public function checkTimezone(): array
    {
        $timezone = (string) config('app.timezone', 'UTC');
        $expected = 'Asia/Ho_Chi_Minh';
        $isCorrect = $timezone === $expected;

        return [
            'key' => 'app_timezone',
            'category' => 'Application',
            'title' => 'Múi giờ hệ thống (Timezone)',
            'status' => $isCorrect ? 'passed' : 'warning',
            'summary' => "Múi giờ hiện tại là '{$timezone}'.",
            'recommendation' => $isCorrect ? null : "Khuyến nghị cấu hình APP_TIMEZONE={$expected} để đồng bộ thời gian Việt Nam.",
        ];
    }

    /**
     * Check Database driver & configuration.
     *
     * @return array{key: string, category: string, title: string, status: string, summary: string, recommendation: string|null}
     */
    public function checkDatabaseConfig(): array
    {
        $defaultConn = (string) config('database.default', 'pgsql');
        $host = (string) config("database.connections.{$defaultConn}.host", '127.0.0.1');

        return [
            'key' => 'database_config',
            'category' => 'Database',
            'title' => 'Cấu hình Cơ sở dữ liệu',
            'status' => 'passed',
            'summary' => "Driver '{$defaultConn}' kết nối tới host '{$host}'.",
            'recommendation' => null,
        ];
    }

    /**
     * Check Mailer configuration.
     *
     * @return array{key: string, category: string, title: string, status: string, summary: string, recommendation: string|null}
     */
    public function checkMailConfig(): array
    {
        $mailer = (string) config('mail.default', 'smtp');
        $host = (string) config('mail.mailers.smtp.host', '');

        $status = 'passed';
        $summary = "Mailer driver '{$mailer}'.";
        $recommendation = null;

        if ($mailer === 'smtp' && empty($host)) {
            $status = 'warning';
            $summary = "Driver 'smtp' nhưng chưa cấu hình MAIL_HOST.";
            $recommendation = 'Kiểm tra cấu hình MAIL_HOST, MAIL_PORT, MAIL_USERNAME trong file .env.';
        } elseif ($mailer === 'log' || $mailer === 'array') {
            $summary = "Driver '{$mailer}' (Dùng cho môi trường thử nghiệm/local).";
        }

        return [
            'key' => 'mail_config',
            'category' => 'Services',
            'title' => 'Dịch vụ Email (Mailer)',
            'status' => $status,
            'summary' => $summary,
            'recommendation' => $recommendation,
        ];
    }

    /**
     * Check Queue driver configuration.
     *
     * @return array{key: string, category: string, title: string, status: string, summary: string, recommendation: string|null}
     */
    public function checkQueueConfig(): array
    {
        $queueDriver = (string) config('queue.default', 'sync');

        if ($queueDriver === 'sync' && app()->environment('production')) {
            return [
                'key' => 'queue_config',
                'category' => 'Services',
                'title' => 'Hàng đợi Công việc (Queue Driver)',
                'status' => 'warning',
                'summary' => "Queue driver đang là 'sync' trên môi trường production.",
                'recommendation' => 'Khuyến nghị dùng QUEUE_CONNECTION=redis hoặc database kết hợp Horizon/Worker.',
            ];
        }

        return [
            'key' => 'queue_config',
            'category' => 'Services',
            'title' => 'Hàng đợi Công việc (Queue Driver)',
            'status' => 'passed',
            'summary' => "Queue driver hiện tại là '{$queueDriver}'.",
            'recommendation' => null,
        ];
    }

    /**
     * Check Reverb Realtime WebSocket configuration.
     *
     * @return array{key: string, category: string, title: string, status: string, summary: string, recommendation: string|null}
     */
    public function checkReverbConfig(): array
    {
        $broadcaster = (string) config('broadcasting.default', 'log');

        return [
            'key' => 'reverb_config',
            'category' => 'Services',
            'title' => 'Realtime Broadcasting (Reverb)',
            'status' => 'passed',
            'summary' => "Broadcaster driver hiện tại là '{$broadcaster}'.",
            'recommendation' => null,
        ];
    }

    /**
     * Check Session Security (Cookie & SameSite).
     *
     * @return array{key: string, category: string, title: string, status: string, summary: string, recommendation: string|null}
     */
    public function checkSessionSecurity(): array
    {
        $secureCookie = (bool) config('session.secure', false);
        $sameSite = (string) config('session.same_site', 'lax');
        $env = (string) app()->environment();

        if ($env === 'production' && ! $secureCookie) {
            return [
                'key' => 'session_security',
                'category' => 'Security',
                'title' => 'Bảo mật Session Cookie',
                'status' => 'warning',
                'summary' => "SESSION_SECURE_COOKIE=false trên môi trường production (SameSite={$sameSite}).",
                'recommendation' => 'Bật SESSION_SECURE_COOKIE=true khi ứng dụng chạy trên HTTPS.',
            ];
        }

        return [
            'key' => 'session_security',
            'category' => 'Security',
            'title' => 'Bảo mật Session Cookie',
            'status' => 'passed',
            'summary' => 'Session Secure Cookie '.($secureCookie ? 'Bật (HTTPS)' : 'Tắt (HTTP)')." | SameSite={$sameSite}.",
            'recommendation' => null,
        ];
    }

    /**
     * Check Public Storage Symlink.
     *
     * @return array{key: string, category: string, title: string, status: string, summary: string, recommendation: string|null}
     */
    public function checkStorageSymlink(): array
    {
        $publicStoragePath = public_path('storage');
        $exists = file_exists($publicStoragePath) || is_link($publicStoragePath);
        $appPublicDir = storage_path('app/public');
        $dirReady = is_dir($appPublicDir);

        if ($exists) {
            return [
                'key' => 'storage_symlink',
                'category' => 'Storage',
                'title' => 'Liên kết thư mục Public Storage',
                'status' => 'passed',
                'summary' => "Thư mục 'public/storage' đã được tạo và liên kết thành công.",
                'recommendation' => null,
            ];
        }

        return [
            'key' => 'storage_symlink',
            'category' => 'Storage',
            'title' => 'Liên kết thư mục Public Storage',
            'status' => $dirReady ? 'warning' : 'failed',
            'summary' => "Chưa có liên kết 'public/storage' (Thư mục storage/app/public ".($dirReady ? 'sẵn sàng' : 'thiếu').').',
            'recommendation' => 'Chạy lệnh php artisan storage:link để tạo symlink public/storage.',
        ];
    }
}
