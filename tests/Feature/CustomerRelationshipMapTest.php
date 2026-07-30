<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ActivityType;
use App\Enums\LeadStatus;
use App\Models\Activity;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Department;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\User;
use App\Services\CustomerRelationshipMapService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

final class CustomerRelationshipMapTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Department $department;

    private Company $company;

    private Contact $contact;

    private Opportunity $opportunity;

    private Lead $convertedLead;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->department = Department::factory()->create([
            'name' => 'Phòng Kinh Doanh Hàng Hải',
            'code' => 'SALES_MARITIME',
        ]);

        $this->user = User::factory()->create([
            'name' => 'Nguyễn Văn Rep',
            'email' => 'rep@salesflow.test',
            'department_id' => $this->department->id,
            'is_active' => true,
        ]);
        $this->user->assignRole('sales');

        $this->company = Company::factory()->create([
            'name' => 'Tập đoàn Logistics Biển Đông',
            'tax_code' => '0109998888',
            'industry' => 'Vận tải biển',
            'email' => 'contact@biendong.test',
            'phone' => '02439998888',
            'owner_id' => $this->user->id,
            'department_id' => $this->department->id,
        ]);

        $this->contact = Contact::factory()->create([
            'company_id' => $this->company->id,
            'first_name' => 'Tuấn',
            'last_name' => 'Trần',
            'full_name' => 'Trần Tuấn',
            'email' => 'tuan.tran@biendong.test',
            'phone' => '0912345678',
            'job_title' => 'Giám đốc Mua hàng',
            'is_primary' => true,
            'owner_id' => $this->user->id,
            'department_id' => $this->department->id,
        ]);

        $pipeline = Pipeline::factory()->create(['is_default' => true]);
        $stage = PipelineStage::factory()->create(['pipeline_id' => $pipeline->id, 'is_won' => false]);

        $this->convertedLead = Lead::factory()->create([
            'full_name' => 'Trần Tuấn (Lead Ban Đầu)',
            'email' => 'tuan.tran@biendong.test',
            'phone' => '0912345678',
            'company_name' => 'Tập đoàn Logistics Biển Đông',
            'status' => LeadStatus::Converted,
            'converted_at' => now(),
            'owner_id' => $this->user->id,
            'department_id' => $this->department->id,
        ]);

        $this->opportunity = Opportunity::factory()->create([
            'company_id' => $this->company->id,
            'contact_id' => $this->contact->id,
            'lead_id' => $this->convertedLead->id,
            'title' => 'Gói Cung Cấp Dịch Vụ Vận Tải 2026',
            'amount' => 250000000,
            'pipeline_id' => $pipeline->id,
            'stage_id' => $stage->id,
            'owner_id' => $this->user->id,
            'department_id' => $this->department->id,
        ]);

        Activity::query()->create([
            'activity_type' => ActivityType::Call,
            'title' => 'Cuộc gọi trao đổi báo giá vận tải',
            'description' => 'Cuộc gọi trao đổi báo giá vận tải',
            'subject_type' => Company::class,
            'subject_id' => $this->company->id,
            'user_id' => $this->user->id,
        ]);
    }

    public function test_service_builds_complete_relationship_map_tree(): void
    {
        $service = app(CustomerRelationshipMapService::class);
        $tree = $service->buildCompanyMap($this->user, $this->company->id);

        $this->assertEquals($this->company->id, $tree['company']['id']);
        $this->assertEquals('Tập đoàn Logistics Biển Đông', $tree['company']['name']);

        $this->assertCount(1, $tree['contacts']);
        $this->assertEquals('Trần Tuấn', $tree['contacts'][0]['full_name']);
        $this->assertTrue($tree['contacts'][0]['is_primary']);

        $this->assertCount(1, $tree['opportunities']);
        $this->assertEquals('Gói Cung Cấp Dịch Vụ Vận Tải 2026', $tree['opportunities'][0]['title']);

        $this->assertCount(1, $tree['converted_leads']);
        $this->assertEquals('Trần Tuấn (Lead Ban Đầu)', $tree['converted_leads'][0]['full_name']);

        $this->assertCount(1, $tree['recent_activities']);
        $this->assertEquals('Cuộc gọi trao đổi báo giá vận tải', $tree['recent_activities'][0]['description']);

        $this->assertEquals(1, $tree['summary']['total_contacts']);
        $this->assertEquals(1, $tree['summary']['total_opportunities']);
        $this->assertEquals(1, $tree['summary']['total_converted_leads']);
    }

    public function test_relationship_map_livewire_component_renders_successfully(): void
    {
        $this->actingAs($this->user);

        Livewire::test('companies.customer-relationship-map', ['companyId' => $this->company->id])
            ->assertSee('Tập đoàn Logistics Biển Đông')
            ->assertSee('Trần Tuấn')
            ->assertSee('Gói Cung Cấp Dịch Vụ Vận Tải 2026')
            ->assertSee('Trần Tuấn (Lead Ban Đầu)');
    }

    public function test_customer_360_relationship_map_tab_loads_correctly(): void
    {
        $this->actingAs($this->user);

        Livewire::test('companies.customer-360', ['companyId' => $this->company->id])
            ->call('setTab', 'relationship_map')
            ->assertSet('activeTab', 'relationship_map')
            ->assertSee('Sơ đồ mối quan hệ');
    }
}
