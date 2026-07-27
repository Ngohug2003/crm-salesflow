<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\Customers\CustomerMergeTool;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Department;
use App\Models\Opportunity;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\User;
use App\Services\CustomerMergeService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

final class CustomerMergeTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    private Department $dept;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->dept = Department::factory()->create(['name' => 'Phòng Kinh doanh']);
        $this->superAdmin = User::factory()->create(['department_id' => $this->dept->id]);
        $this->superAdmin->assignRole('super-admin');
    }

    public function test_customer_merge_service_scans_and_merges_companies(): void
    {
        $masterCompany = Company::factory()->create([
            'name' => 'Công ty Viễn thông Viettel',
            'owner_id' => $this->superAdmin->id,
            'department_id' => $this->dept->id,
        ]);

        $sourceCompany = Company::factory()->create([
            'name' => 'Công ty Viễn thông Viettel',
            'owner_id' => $this->superAdmin->id,
            'department_id' => $this->dept->id,
        ]);

        // Child contact on source
        $childContact = Contact::factory()->create([
            'company_id' => $sourceCompany->id,
            'full_name' => 'Lê Văn Viettel',
            'owner_id' => $this->superAdmin->id,
        ]);

        // Child opportunity on source
        $pipeline = Pipeline::query()->create(['name' => 'Standard Pipeline', 'code' => 'standard_merge', 'is_default' => true]);
        $stage = PipelineStage::query()->create(['pipeline_id' => $pipeline->id, 'name' => 'Khai thác', 'code' => 'init_merge', 'position' => 1]);

        $childOpp = Opportunity::factory()->create([
            'company_id' => $sourceCompany->id,
            'pipeline_id' => $pipeline->id,
            'stage_id' => $stage->id,
            'owner_id' => $this->superAdmin->id,
        ]);

        $service = app(CustomerMergeService::class);

        // Scan
        $duplicateGroups = $service->scanCompanyDuplicates($this->superAdmin);
        $this->assertNotEmpty($duplicateGroups);

        // Merge
        $service->mergeCompanies($this->superAdmin, $masterCompany, $sourceCompany);

        // Verify child records moved
        $this->assertDatabaseHas('contacts', [
            'id' => $childContact->id,
            'company_id' => $masterCompany->id,
        ]);

        $this->assertDatabaseHas('opportunities', [
            'id' => $childOpp->id,
            'company_id' => $masterCompany->id,
        ]);

        // Verify source soft-deleted
        $this->assertSoftDeleted('companies', ['id' => $sourceCompany->id]);
    }

    public function test_customer_merge_tool_livewire_component_executes_merge(): void
    {
        $this->actingAs($this->superAdmin);

        $contactMaster = Contact::factory()->create([
            'full_name' => 'Trần Văn A',
            'email' => 'tranvana@test.com',
            'owner_id' => $this->superAdmin->id,
        ]);

        $contactSource = Contact::factory()->create([
            'full_name' => 'Trần Văn A',
            'email' => 'tranvana@test.com',
            'owner_id' => $this->superAdmin->id,
        ]);

        Livewire::test(CustomerMergeTool::class)
            ->assertOk()
            ->call('setMode', 'contact')
            ->set('masterId', $contactMaster->id)
            ->set('sourceId', $contactSource->id)
            ->call('executeContactMerge')
            ->assertHasNoErrors();

        $this->assertSoftDeleted('contacts', ['id' => $contactSource->id]);
    }
}
