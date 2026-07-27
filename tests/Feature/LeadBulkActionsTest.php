<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\Leads\LeadList;
use App\Models\Department;
use App\Models\Lead;
use App\Models\Tag;
use App\Models\User;
use App\Services\Lead\LeadBulkActionService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

final class LeadBulkActionsTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    private User $salesUser1;

    private User $salesUser2;

    private Department $dept;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->dept = Department::factory()->create(['name' => 'Phòng Kinh doanh']);

        $this->superAdmin = User::factory()->create(['department_id' => $this->dept->id]);
        $this->superAdmin->assignRole('super-admin');

        $this->salesUser1 = User::factory()->create(['department_id' => $this->dept->id]);
        $this->salesUser1->assignRole('sales');

        $this->salesUser2 = User::factory()->create(['department_id' => $this->dept->id]);
        $this->salesUser2->assignRole('sales');
    }

    public function test_bulk_assign_reauthorizes_and_updates_owner(): void
    {
        $leads = Lead::factory()->count(3)->create([
            'owner_id' => $this->salesUser1->id,
            'department_id' => $this->dept->id,
        ]);

        $service = app(LeadBulkActionService::class);
        $res = $service->bulkAssign($this->superAdmin, $leads->pluck('id')->toArray(), $this->salesUser2->id, 'Phân bổ lại tài nguyên');

        $this->assertEquals(3, $res['success']);
        $this->assertEquals(0, $res['failed']);

        foreach ($leads as $lead) {
            $this->assertDatabaseHas('leads', [
                'id' => $lead->id,
                'owner_id' => $this->salesUser2->id,
            ]);
        }
    }

    public function test_bulk_status_update_and_tagging(): void
    {
        $tag = Tag::create(['name' => 'VIP 2026', 'slug' => 'vip-2026', 'color' => 'gold']);
        $leads = Lead::factory()->count(2)->create([
            'owner_id' => $this->superAdmin->id,
            'department_id' => $this->dept->id,
            'status' => 'new',
        ]);

        $service = app(LeadBulkActionService::class);

        // Bulk status
        $statusRes = $service->bulkUpdateStatus($this->superAdmin, $leads->pluck('id')->toArray(), 'contacted', 'Đã gọi điện lần 1');
        $this->assertEquals(2, $statusRes['success']);

        // Bulk tag
        $tagRes = $service->bulkAddTag($this->superAdmin, $leads->pluck('id')->toArray(), $tag->id);
        $this->assertEquals(2, $tagRes['success']);

        foreach ($leads as $lead) {
            $this->assertDatabaseHas('leads', [
                'id' => $lead->id,
                'status' => 'contacted',
            ]);
            $this->assertDatabaseHas('lead_tag', [
                'lead_id' => $lead->id,
                'tag_id' => $tag->id,
            ]);
        }
    }

    public function test_livewire_executes_bulk_delete(): void
    {
        $leads = Lead::factory()->count(2)->create([
            'owner_id' => $this->superAdmin->id,
            'department_id' => $this->dept->id,
        ]);

        $this->actingAs($this->superAdmin);

        Livewire::test(LeadList::class)
            ->assertOk()
            ->set('selectedLeadIds', $leads->pluck('id')->toArray())
            ->call('executeBulkDelete')
            ->assertSee('Xóa hàng loạt hoàn tất');

        foreach ($leads as $lead) {
            $this->assertSoftDeleted('leads', ['id' => $lead->id]);
        }
    }
}
