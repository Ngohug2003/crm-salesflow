<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\Dashboard\DashboardOverview;
use App\Livewire\Reports\FunnelReport;
use App\Livewire\Reports\RevenueReport;
use App\Models\Department;
use App\Models\SavedReportFilter;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

final class SavedReportFiltersTest extends TestCase
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

    public function test_user_can_save_filter_preset_and_apply_it(): void
    {
        $this->actingAs($this->superAdmin);

        Livewire::test(DashboardOverview::class)
            ->assertOk()
            ->set('datePreset', 'this_quarter')
            ->set('departmentId', $this->dept->id)
            ->set('presetName', 'Báo cáo Quý này - KD')
            ->call('saveFilterPreset')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('saved_report_filters', [
            'user_id' => $this->superAdmin->id,
            'name' => 'Báo cáo Quý này - KD',
            'report_type' => 'overview',
        ]);

        /** @var SavedReportFilter $preset */
        $preset = SavedReportFilter::query()->where('user_id', $this->superAdmin->id)->firstOrFail();

        Livewire::test(DashboardOverview::class)
            ->assertOk()
            ->call('resetFilters')
            ->assertSet('datePreset', 'this_month')
            ->call('applyFilterPreset', $preset->id)
            ->assertSet('datePreset', 'this_quarter')
            ->assertSet('departmentId', $this->dept->id);
    }

    public function test_saved_presets_are_scoped_by_report_type(): void
    {
        $this->actingAs($this->superAdmin);

        // Save preset on Revenue Report
        Livewire::test(RevenueReport::class)
            ->assertOk()
            ->set('presetName', 'Bộ lọc riêng Doanh thu')
            ->call('saveFilterPreset');

        // Revenue report sees its own preset
        Livewire::test(RevenueReport::class)
            ->assertSee('Bộ lọc riêng Doanh thu');

        // Funnel report does NOT see Revenue report preset
        Livewire::test(FunnelReport::class)
            ->assertDontSee('Bộ lọc riêng Doanh thu');
    }

    public function test_user_can_delete_saved_filter_preset(): void
    {
        $this->actingAs($this->superAdmin);

        $preset = SavedReportFilter::query()->create([
            'user_id' => $this->superAdmin->id,
            'name' => 'Preset Cần Xóa',
            'report_type' => 'overview',
            'filter_data' => ['datePreset' => 'today'],
        ]);

        Livewire::test(DashboardOverview::class)
            ->assertOk()
            ->call('deleteFilterPreset', $preset->id);

        $this->assertDatabaseMissing('saved_report_filters', ['id' => $preset->id]);
    }
}
