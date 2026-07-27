<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ActivityType;
use App\Livewire\Companies\Customer360 as Customer360Component;
use App\Models\Activity;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Department;
use App\Models\Opportunity;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\User;
use App\Services\Customer360Service;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

final class Customer360Test extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    private User $salesUser;

    private Department $dept;

    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->dept = Department::factory()->create(['name' => 'Phòng Kinh doanh']);

        $this->superAdmin = User::factory()->create(['department_id' => $this->dept->id]);
        $this->superAdmin->assignRole('super-admin');

        $this->salesUser = User::factory()->create(['department_id' => $this->dept->id]);
        $this->salesUser->assignRole('sales');

        $this->company = Company::factory()->create([
            'name' => 'Tập đoàn Công nghệ FPT',
            'owner_id' => $this->salesUser->id,
            'department_id' => $this->dept->id,
        ]);
    }

    public function test_customer_360_service_calculates_metrics_and_aggregates_timeline(): void
    {
        $pipeline = Pipeline::query()->create(['name' => 'Standard Pipeline', 'code' => 'standard', 'is_default' => true]);
        $wonStage = PipelineStage::query()->create(['pipeline_id' => $pipeline->id, 'name' => 'Thành công', 'code' => 'won', 'position' => 1, 'is_won' => true]);
        $openStage = PipelineStage::query()->create(['pipeline_id' => $pipeline->id, 'name' => 'Khai thác', 'code' => 'qualification', 'position' => 2]);

        // Won opportunity
        Opportunity::factory()->create([
            'company_id' => $this->company->id,
            'pipeline_id' => $pipeline->id,
            'stage_id' => $wonStage->id,
            'amount' => 150000000,
            'owner_id' => $this->salesUser->id,
        ]);

        // Open opportunity
        Opportunity::factory()->create([
            'company_id' => $this->company->id,
            'pipeline_id' => $pipeline->id,
            'stage_id' => $openStage->id,
            'amount' => 50000000,
            'owner_id' => $this->salesUser->id,
        ]);

        // Contact
        Contact::factory()->create([
            'company_id' => $this->company->id,
            'full_name' => 'Nguyễn Văn FPT',
            'owner_id' => $this->salesUser->id,
        ]);

        // Activity
        Activity::create([
            'activity_type' => ActivityType::Call,
            'subject_type' => Company::class,
            'subject_id' => $this->company->id,
            'title' => 'Tư vấn giải pháp Cloud',
            'user_id' => $this->salesUser->id,
            'performed_at' => now(),
        ]);

        $service = app(Customer360Service::class);
        $data = $service->getCompany360Data($this->salesUser, $this->company->id);

        $this->assertEquals(150000000.0, $data['wonRevenue']);
        $this->assertEquals(50000000.0, $data['openPipelineValue']);
        $this->assertEquals(2, $data['opportunitiesCount']);
        $this->assertEquals(1, $data['contactsCount']);
        $this->assertNotEmpty($data['timeline']);
    }

    public function test_customer_360_livewire_component_renders_and_switches_tabs(): void
    {
        $this->actingAs($this->salesUser);

        Livewire::test(Customer360Component::class, ['companyId' => $this->company->id])
            ->assertOk()
            ->assertSee('Hồ sơ 360° — Tập đoàn Công nghệ FPT')
            ->call('setTab', 'opportunities')
            ->assertSet('activeTab', 'opportunities')
            ->call('setTab', 'contacts')
            ->assertSet('activeTab', 'contacts');
    }
}
