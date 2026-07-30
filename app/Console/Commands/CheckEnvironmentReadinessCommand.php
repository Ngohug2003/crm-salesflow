<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Platform\EnvironmentReadinessCheckService;
use Illuminate\Console\Command;

final class CheckEnvironmentReadinessCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:env-check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Kiểm tra độ sẵn sàng của cấu hình môi trường ứng dụng (Environment Readiness Checklist)';

    /**
     * Execute the console command.
     */
    public function handle(EnvironmentReadinessCheckService $service): int
    {
        $this->info('Đang kiểm tra cấu hình môi trường SalesFlow CRM...');
        $this->newLine();

        $report = $service->checkAll();

        $rows = [];
        foreach ($report['items'] as $item) {
            $statusLabel = match ($item['status']) {
                'passed' => '✓ OK',
                'warning' => '⚠ WARNING',
                default => '✗ FAILED',
            };

            $rows[] = [
                $item['category'],
                $item['title'],
                $statusLabel,
                $item['summary'],
                $item['recommendation'] ?? '—',
            ];
        }

        $this->table(
            ['Phân loại', 'Mục kiểm tra', 'Trạng thái', 'Tóm tắt', 'Khuyến nghị'],
            $rows
        );

        $this->newLine();

        if ($report['overall_status'] === 'failed') {
            $this->error('PHÁT HIỆN LỖI: Cấu hình môi trường hiện tại có mục chưa đạt tiêu chuẩn.');

            return self::FAILURE;
        }

        if ($report['overall_status'] === 'warning') {
            $this->warn('CẢNH BÁO: Cấu hình có mục cần chú ý trước khi deploy production.');

            return self::SUCCESS;
        }

        $this->info('THÀNH CÔNG: Tất cả các mục kiểm tra môi trường đều đạt tiêu chuẩn.');

        return self::SUCCESS;
    }
}
