<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ActivityType;
use App\Livewire\Leads\LeadWorkflow;
use App\Models\Activity;
use App\Models\Department;
use App\Models\Lead;
use App\Models\LeadNote;
use App\Models\User;
use App\Services\Lead\LeadTimelineService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

final class LeadNotesTimelineTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    private User $salesUser;

    private Lead $lead;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $dept = Department::factory()->create(['name' => 'Phòng Kinh doanh']);

        $this->superAdmin = User::factory()->create(['department_id' => $dept->id]);
        $this->superAdmin->assignRole('super-admin');

        $this->salesUser = User::factory()->create(['department_id' => $dept->id]);
        $this->salesUser->assignRole('sales');

        $this->lead = Lead::factory()->create([
            'owner_id' => $this->salesUser->id,
            'department_id' => $dept->id,
            'full_name' => 'Trần Văn Nam',
        ]);
    }

    public function test_service_creates_pins_and_deletes_lead_notes(): void
    {
        $service = app(LeadTimelineService::class);

        $note = $service->createNote($this->salesUser, $this->lead, 'Cần tư vấn gói Doanh nghiệp', true);
        $this->assertInstanceOf(LeadNote::class, $note);
        $this->assertTrue($note->is_pinned);
        $this->assertEquals('Cần tư vấn gói Doanh nghiệp', $note->content);

        // Toggle pin
        $isPinned = $service->togglePinNote($this->salesUser, $note);
        $this->assertFalse($isPinned);

        // Delete note
        $deleted = $service->deleteNote($this->salesUser, $note);
        $this->assertTrue($deleted);
        $this->assertDatabaseMissing('lead_notes', ['id' => $note->id]);
    }

    public function test_aggregated_timeline_combines_notes_activities_status_and_assignments(): void
    {
        $service = app(LeadTimelineService::class);

        // 1. Note
        $service->createNote($this->salesUser, $this->lead, 'Khách đã hẹn gọi lại', true);

        // 2. Activity
        Activity::create([
            'activity_type' => ActivityType::Call,
            'subject_type' => Lead::class,
            'subject_id' => $this->lead->id,
            'title' => 'Cuộc gọi tư vấn nhu cầu',
            'description' => 'Khách trao đổi quan tâm giải pháp CRM',
            'user_id' => $this->salesUser->id,
            'performed_at' => now(),
        ]);

        $this->actingAs($this->salesUser);

        Livewire::test(LeadWorkflow::class, ['leadId' => $this->lead->id])
            ->assertOk()
            ->assertSee('Khách đã hẹn gọi lại')
            ->assertSee('Cuộc gọi tư vấn nhu cầu')
            ->assertSee('Đã ghim');
    }

    public function test_livewire_can_add_note_from_ui(): void
    {
        $this->actingAs($this->salesUser);

        Livewire::test(LeadWorkflow::class, ['leadId' => $this->lead->id])
            ->assertOk()
            ->set('noteContent', 'Ghi chú thử nghiệm qua Livewire UI')
            ->set('noteIsPinned', true)
            ->call('addNote')
            ->assertSee('Ghi chú thử nghiệm qua Livewire UI');

        $this->assertDatabaseHas('lead_notes', [
            'lead_id' => $this->lead->id,
            'content' => 'Ghi chú thử nghiệm qua Livewire UI',
            'is_pinned' => true,
        ]);
    }
}
