<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\Leads\LeadWorkflow;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Department;
use App\Models\Lead;
use App\Models\User;
use App\Services\Lead\LeadConversionPreviewService;
use Database\Seeders\DemoPipelineSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

final class LeadConversionPreviewTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    private Department $dept;

    private Lead $lead;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(DemoPipelineSeeder::class);

        $this->dept = Department::factory()->create(['name' => 'Phòng Kinh doanh']);

        $this->superAdmin = User::factory()->create(['department_id' => $this->dept->id]);
        $this->superAdmin->assignRole('super-admin');

        $this->lead = Lead::factory()->create([
            'owner_id' => $this->superAdmin->id,
            'department_id' => $this->dept->id,
            'full_name' => 'Nguyễn Thị Hoa',
            'company_name' => 'Công ty Công nghệ Việt',
            'email' => 'hoa.nguyen@techviet.vn',
            'phone' => '0912345678',
            'website' => 'techviet.vn',
            'status' => 'qualified',
        ]);
    }

    public function test_service_previews_and_detects_matching_company_and_contact(): void
    {
        $existingCompany = Company::query()->create([
            'name' => 'Công ty Công nghệ Việt Nam',
            'website' => 'techviet.vn',
            'owner_id' => $this->superAdmin->id,
        ]);

        $existingContact = Contact::query()->create([
            'first_name' => 'Hoa',
            'last_name' => 'Nguyễn',
            'full_name' => 'Nguyễn Thị Hoa',
            'email' => 'hoa.nguyen@techviet.vn',
            'phone' => '0912345678',
            'owner_id' => $this->superAdmin->id,
        ]);

        $service = app(LeadConversionPreviewService::class);
        $preview = $service->preview($this->superAdmin, $this->lead);

        $this->assertEquals($existingCompany->id, $preview['suggestedCompanyId']);
        $this->assertEquals($existingContact->id, $preview['suggestedContactId']);
        $this->assertNotNull($preview['defaultPipelineId']);
        $this->assertNotNull($preview['defaultStageId']);
    }

    public function test_livewire_conversion_modal_loads_preview_and_converts(): void
    {
        $existingCompany = Company::query()->create([
            'name' => 'Công ty Công nghệ Việt Nam',
            'website' => 'techviet.vn',
            'owner_id' => $this->superAdmin->id,
        ]);

        $this->actingAs($this->superAdmin);

        Livewire::test(LeadWorkflow::class, ['leadId' => $this->lead->id])
            ->assertOk()
            ->call('openConvert')
            ->assertSee('Xem trước và ghép nối chuyển đổi Lead')
            ->assertSee('Công ty Công nghệ Việt Nam')
            ->set('convertCompanyMode', 'existing')
            ->set('convertCompanyId', (string) $existingCompany->id)
            ->call('convertLead');

        $this->assertDatabaseHas('leads', [
            'id' => $this->lead->id,
            'status' => 'converted',
        ]);

        $this->assertDatabaseHas('opportunities', [
            'lead_id' => $this->lead->id,
            'company_id' => $existingCompany->id,
        ]);
    }
}
