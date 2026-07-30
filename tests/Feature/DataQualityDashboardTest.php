<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Data\ReportFilterData;
use App\Livewire\Reports\DataQualityDashboard;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Lead;
use App\Models\User;
use App\Services\Analytics\DataQualityAuditService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

final class DataQualityDashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
    }

    public function test_data_quality_audit_service_detects_incomplete_and_orphan_records(): void
    {
        // 1. Create incomplete lead (missing phone & email)
        Lead::factory()->create([
            'full_name' => 'Nam Nguyễn',
            'phone' => null,
            'email' => null,
            'owner_id' => $this->admin->id,
        ]);

        // 2. Create orphan contact (company_id is null)
        Contact::factory()->create([
            'first_name' => 'Lan',
            'last_name' => 'Phạm',
            'company_id' => null,
            'owner_id' => $this->admin->id,
        ]);

        // 3. Create company missing tax code
        Company::factory()->create([
            'name' => 'Công ty Cổ phần ABC Test',
            'tax_code' => null,
            'owner_id' => $this->admin->id,
        ]);

        $service = app(DataQualityAuditService::class);
        $filters = new ReportFilterData;

        $metrics = $service->getOverviewMetrics($this->admin, $filters);
        $this->assertGreaterThan(0, $metrics['total_records']);
        $this->assertGreaterThanOrEqual(2, $metrics['incomplete_count']);

        $incomplete = $service->getIncompleteRecords($this->admin, $filters);
        $this->assertNotEmpty($incomplete);

        $orphans = $service->getOrphanRecords($this->admin, $filters);
        $this->assertNotEmpty($orphans);
    }

    public function test_data_quality_dashboard_livewire_component_renders_successfully(): void
    {
        $this->actingAs($this->admin);

        $this->get(route('reports.data-quality'))
            ->assertOk()
            ->assertSee('Giám sát & Chất lượng Dữ liệu CRM');

        Livewire::test(DataQualityDashboard::class)
            ->assertOk()
            ->assertSee('Điểm sức khỏe Dữ liệu CRM')
            ->set('activeTab', 'orphan')
            ->assertOk();
    }
}
