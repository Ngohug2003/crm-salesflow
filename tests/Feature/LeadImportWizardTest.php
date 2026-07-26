<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\Imports\LeadImportWizard;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

final class LeadImportWizardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Storage::fake('local');
    }

    public function test_it_protects_import_route_for_unauthenticated_users(): void
    {
        $this->get(route('imports.leads'))
            ->assertRedirect(route('login'));
    }

    public function test_it_allows_super_admin_to_access_import_wizard(): void
    {
        $admin = User::query()->where('email', 'admin@salesflow.test')->sole();

        $this->actingAs($admin)
            ->get(route('imports.leads'))
            ->assertOk()
            ->assertSee('Nhập dữ liệu Lead hàng loạt')
            ->assertSee('Tải tệp mẫu CSV');
    }

    public function test_it_downloads_sample_csv_template(): void
    {
        $admin = User::query()->where('email', 'admin@salesflow.test')->sole();

        Livewire::actingAs($admin)
            ->test(LeadImportWizard::class)
            ->call('downloadTemplate')
            ->assertFileDownloaded('lead_import_template.csv');
    }

    public function test_it_uploads_csv_file_and_generates_preview_rows(): void
    {
        $admin = User::query()->where('email', 'admin@salesflow.test')->sole();

        $csvContent = implode("\n", [
            'Họ,Tên,Email,Số điện thoại,Công ty',
            'Nguyễn,Văn A,nva@example.com,0912345678,Công ty A',
            'Trần,Thị B,ttb@example.com,0987654321,Công ty B',
            'Lê,Văn C,lvc@example.com,0911223344,Công ty C',
            'Phạm,Thị D,ptd@example.com,0955667788,Công ty D',
            'Hoàng,Văn E,hve@example.com,0999887766,Công ty E',
            'Vũ,Thị F,vtf@example.com,0900112233,Công ty F',
        ]);

        $file = UploadedFile::fake()->createWithContent('leads_test.csv', $csvContent);

        Livewire::actingAs($admin)
            ->test(LeadImportWizard::class)
            ->set('importFile', $file)
            ->assertSet('preview.original_filename', 'leads_test.csv')
            ->assertSet('preview.total_rows_estimate', 6)
            ->assertSet('preview.headers', ['Họ', 'Tên', 'Email', 'Số điện thoại', 'Công ty'])
            ->assertCount('preview.preview_rows', 5)
            ->assertSee('Nguyễn')
            ->assertSee('nva@example.com');
    }

    public function test_it_automaps_headers_and_runs_dryrun_validation_in_step_2(): void
    {
        $admin = User::query()->where('email', 'admin@salesflow.test')->sole();

        $csvContent = implode("\n", [
            'Họ,Tên,Email,Số điện thoại,Công ty',
            'Nguyễn,Văn A,nva@example.com,0912345678,Công ty A',
            'Trần,Thị B,invalid-email,0987654321,Công ty B',
        ]);

        $file = UploadedFile::fake()->createWithContent('leads_step2.csv', $csvContent);

        Livewire::actingAs($admin)
            ->test(LeadImportWizard::class)
            ->set('importFile', $file)
            ->call('proceedToMapping')
            ->assertSet('step', 2)
            ->assertSet('mapping.last_name', 'Họ')
            ->assertSet('mapping.first_name', 'Tên')
            ->assertSet('mapping.email', 'Email')
            ->assertSet('mapping.phone', 'Số điện thoại')
            ->assertSet('mapping.company_name', 'Công ty')
            ->assertSet('validationResult.total_checked', 2)
            ->assertSet('validationResult.valid_count', 1)
            ->assertSet('validationResult.warning_count', 1)
            ->assertSee('Báo cáo Kiểm tra dữ liệu');
    }

    public function test_it_proceeds_to_step_3_when_mapping_is_valid(): void
    {
        $admin = User::query()->where('email', 'admin@salesflow.test')->sole();

        $csvContent = implode("\n", [
            'Họ,Tên,Email,Số điện thoại,Công ty',
            'Nguyễn,Văn A,nva@example.com,0912345678,Công ty A',
        ]);

        $file = UploadedFile::fake()->createWithContent('leads_step3.csv', $csvContent);

        Livewire::actingAs($admin)
            ->test(LeadImportWizard::class)
            ->set('importFile', $file)
            ->call('proceedToMapping')
            ->call('proceedToDuplicates')
            ->assertSet('step', 3)
            ->assertSee('Cấu hình ghép nối cột hoàn tất!');
    }

    public function test_it_rejects_invalid_file_extension(): void
    {
        $admin = User::query()->where('email', 'admin@salesflow.test')->sole();

        $file = UploadedFile::fake()->createWithContent('malicious.exe', 'binary content');

        Livewire::actingAs($admin)
            ->test(LeadImportWizard::class)
            ->set('importFile', $file)
            ->assertHasErrors(['importFile']);
    }
}
