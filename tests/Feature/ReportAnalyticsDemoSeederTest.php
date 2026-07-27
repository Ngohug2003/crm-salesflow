<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\Task;
use Database\Seeders\DemoPipelineSeeder;
use Database\Seeders\DemoUserSeeder;
use Database\Seeders\DepartmentSeeder;
use Database\Seeders\LeadTaxonomySeeder;
use Database\Seeders\ReportAnalyticsDemoSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ReportAnalyticsDemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_one_hundred_report_scenarios_across_multiple_years(): void
    {
        $this->seed(DepartmentSeeder::class);
        $this->seed(RolePermissionSeeder::class);
        $this->seed(LeadTaxonomySeeder::class);
        $this->seed(DemoUserSeeder::class);
        $this->seed(DemoPipelineSeeder::class);
        $this->seed(ReportAnalyticsDemoSeeder::class);

        self::assertSame(100, Lead::query()->where('email', 'like', 'report.demo%@salesflow.test')->count());
        self::assertSame(100, Opportunity::query()->where('code', 'like', 'RPT-DEMO-%')->count());
        self::assertSame(100, Activity::query()->where('title', 'like', 'Hoạt động báo cáo %')->count());
        self::assertSame(100, Task::query()->where('title', 'like', 'Công việc báo cáo %')->count());

        $oldest = Opportunity::query()
            ->where('code', 'like', 'RPT-DEMO-%')
            ->min('created_at');
        $newest = Opportunity::query()
            ->where('code', 'like', 'RPT-DEMO-%')
            ->max('created_at');

        self::assertNotNull($oldest);
        self::assertNotNull($newest);
        self::assertGreaterThanOrEqual(22, now()->parse($oldest)->diffInMonths(now()->parse($newest)));
    }
}
