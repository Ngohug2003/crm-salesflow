<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\TaskStatus;
use App\Exceptions\StageRequirementsUnfulfilledException;
use App\Jobs\ActivateOpportunityPlaybookJob;
use App\Livewire\Opportunities\OpportunityDetail;
use App\Livewire\Opportunities\OpportunityPlaybookProgress;
use App\Livewire\SalesPlaybooks\SalesPlaybookManager;
use App\Models\Department;
use App\Models\Opportunity;
use App\Models\OpportunityPlaybookRun;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\SalesPlaybook;
use App\Models\Task;
use App\Models\User;
use App\Services\OpportunityPlaybookService;
use App\Services\OpportunityStageTransitionService;
use App\Services\SalesPlaybookManagementService;
use Carbon\CarbonImmutable;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

final class P1003StagePlaybookTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $viewer;

    private Pipeline $pipeline;

    private PipelineStage $firstStage;

    private PipelineStage $secondStage;

    private Opportunity $opportunity;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $department = Department::factory()->create();
        $this->admin = User::factory()->create(['department_id' => $department->id, 'is_active' => true]);
        $this->admin->assignRole('admin');
        $this->viewer = User::factory()->create(['department_id' => $department->id, 'is_active' => true]);
        $this->viewer->assignRole('viewer');

        $this->pipeline = Pipeline::factory()->create(['is_active' => true]);
        $this->firstStage = PipelineStage::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'position' => 1,
            'probability' => 10,
            'is_won' => false,
            'is_lost' => false,
        ]);
        $this->secondStage = PipelineStage::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'position' => 2,
            'probability' => 10,
            'is_won' => false,
            'is_lost' => false,
        ]);
        $this->opportunity = Opportunity::factory()->create([
            'pipeline_id' => $this->pipeline->id,
            'stage_id' => $this->firstStage->id,
            'owner_id' => $this->admin->id,
            'department_id' => $department->id,
            'amount' => 1000000,
        ]);
    }

    public function test_stage_activation_creates_task_once_and_uses_business_days(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-07-31 09:00:00', 'Asia/Ho_Chi_Minh'));
        $playbook = $this->publishedPlaybook('once', [[
            'type' => 'task',
            'title' => 'Gọi xác nhận nhu cầu',
            'instructions' => 'Liên hệ khách hàng.',
            'is_required' => true,
            'blocks_stage_exit' => false,
            'due_business_days' => 2,
        ]]);

        $service = app(OpportunityPlaybookService::class);
        $first = $service->activateForStage($this->admin, $this->opportunity, $this->firstStage->id, 101);
        $retry = $service->activateForStage($this->admin, $this->opportunity, $this->firstStage->id, 101);

        self::assertNotNull($first);
        self::assertSame($first->id, $retry?->id);
        self::assertSame($playbook->id, $first->sales_playbook_id);
        self::assertSame(1, OpportunityPlaybookRun::query()->count());
        self::assertSame(1, Task::query()->count());
        self::assertSame('2026-08-04', Task::query()->firstOrFail()->due_date?->format('Y-m-d'));

        CarbonImmutable::setTestNow();
    }

    public function test_exit_criteria_blocks_stage_until_required_step_is_completed(): void
    {
        $this->publishedPlaybook('once', [[
            'type' => 'checklist',
            'title' => 'Xác nhận người ra quyết định',
            'instructions' => '',
            'is_required' => true,
            'blocks_stage_exit' => true,
            'due_business_days' => '',
        ]]);

        $playbooks = app(OpportunityPlaybookService::class);
        $run = $playbooks->activateForStage($this->admin, $this->opportunity, $this->firstStage->id, 102);
        self::assertNotNull($run);

        try {
            app(OpportunityStageTransitionService::class)->transitionStage(
                $this->admin,
                $this->opportunity->id,
                $this->secondStage->id,
                $this->firstStage->id,
            );
            self::fail('Exit criteria should block the stage transition.');
        } catch (StageRequirementsUnfulfilledException $exception) {
            self::assertStringContainsString('Xác nhận người ra quyết định', $exception->getMessage());
        }

        $playbooks->completeStep($this->admin, $run->steps->firstOrFail()->id);
        $updated = app(OpportunityStageTransitionService::class)->transitionStage(
            $this->admin,
            $this->opportunity->id,
            $this->secondStage->id,
            $this->firstStage->id,
        );

        self::assertSame($this->secondStage->id, $updated->stage_id);
    }

    public function test_every_entry_policy_creates_one_run_per_stage_entry(): void
    {
        $this->publishedPlaybook('every_entry', [[
            'type' => 'guidance',
            'title' => 'Đọc mục tiêu giai đoạn',
            'instructions' => '',
            'is_required' => false,
            'blocks_stage_exit' => false,
            'due_business_days' => '',
        ]]);

        $service = app(OpportunityPlaybookService::class);
        $service->activateForStage($this->admin, $this->opportunity, $this->firstStage->id, 201);
        $service->activateForStage($this->admin, $this->opportunity, $this->firstStage->id, 201);
        $service->activateForStage($this->admin, $this->opportunity, $this->firstStage->id, 202);

        self::assertSame(2, OpportunityPlaybookRun::query()->count());
    }

    public function test_viewer_can_read_but_cannot_complete_playbook_steps(): void
    {
        $this->publishedPlaybook('once', [[
            'type' => 'checklist',
            'title' => 'Checklist chỉ đọc',
            'instructions' => '',
            'is_required' => true,
            'blocks_stage_exit' => false,
            'due_business_days' => '',
        ]]);
        $run = app(OpportunityPlaybookService::class)
            ->activateForStage($this->admin, $this->opportunity, $this->firstStage->id, 301);
        self::assertNotNull($run);
        self::assertNotNull(app(OpportunityPlaybookService::class)->activeRun($this->viewer, $this->opportunity));

        $this->expectException(AuthorizationException::class);
        app(OpportunityPlaybookService::class)->completeStep($this->viewer, $run->steps->firstOrFail()->id);
    }

    public function test_manager_screen_is_permission_protected(): void
    {
        Livewire::actingAs($this->admin)
            ->test(SalesPlaybookManager::class)
            ->assertOk()
            ->assertSee('Sales Playbook');

        $this->actingAs($this->viewer)
            ->get(route('sales-playbooks.index'))
            ->assertForbidden();
    }

    public function test_stage_change_updates_in_place_without_dispatching_attachment_reload_event(): void
    {
        Livewire::actingAs($this->admin)
            ->test(OpportunityDetail::class, ['opportunityId' => $this->opportunity->id])
            ->call('changeStage', $this->secondStage->id)
            ->assertSet('opportunity.stage_id', $this->secondStage->id)
            ->assertNotDispatched('attachment-updated');
    }

    public function test_playbook_progress_waits_for_automatic_activation_without_manual_page_reload(): void
    {
        $management = app(SalesPlaybookManagementService::class);
        $draft = $management->createDraft($this->admin, 'Playbook tự động', 'Kiểm tra chờ job automation.', 'once', [[
            'type' => 'checklist',
            'title' => 'Bước tự động',
            'instructions' => '',
            'is_required' => true,
            'blocks_stage_exit' => false,
            'due_business_days' => '',
        ]]);
        $management->publish($this->admin, $draft->id, $this->secondStage->id);
        $this->opportunity->update(['stage_id' => $this->secondStage->id]);

        Livewire::actingAs($this->admin)
            ->test(OpportunityPlaybookProgress::class, ['opportunityId' => $this->opportunity->id])
            ->assertSee('Đang khởi tạo Playbook cho giai đoạn mới...')
            ->assertDontSee('Khởi chạy playbook');

        app(OpportunityPlaybookService::class)->activateForStage(
            $this->admin,
            $this->opportunity->fresh(),
            $this->secondStage->id,
            901,
        );

        Livewire::actingAs($this->admin)
            ->test(OpportunityPlaybookProgress::class, ['opportunityId' => $this->opportunity->id])
            ->assertSee('Playbook tự động')
            ->assertSee('Bước tự động');
    }

    public function test_publishing_a_new_playbook_preserves_the_selected_stage_assignment(): void
    {
        Livewire::actingAs($this->admin)
            ->test(SalesPlaybookManager::class)
            ->set('name', 'Playbook giữ stage đã chọn')
            ->set('description', 'Kiểm tra lỗi stage bị xóa khi lưu nháp trước lúc phát hành.')
            ->set('repeatPolicy', 'once')
            ->set('stageId', (string) $this->secondStage->id)
            ->set('steps', [[
                'type' => 'checklist',
                'title' => 'Xác nhận nhu cầu',
                'instructions' => '',
                'field_name' => '',
                'document_url' => '',
                'is_required' => true,
                'blocks_stage_exit' => false,
                'due_business_days' => '',
            ]])
            ->call('publish')
            ->assertSet('stageId', (string) $this->secondStage->id)
            ->assertSee('Đã phát hành phiên bản 1.');

        $this->assertDatabaseHas('stage_playbook_assignments', [
            'pipeline_stage_id' => $this->secondStage->id,
            'is_active' => true,
        ]);
    }

    public function test_draft_keeps_the_selected_stage_after_reloading_the_editor(): void
    {
        Livewire::actingAs($this->admin)
            ->test(SalesPlaybookManager::class)
            ->set('name', 'Bản nháp có stage')
            ->set('stageId', (string) $this->secondStage->id)
            ->set('steps.0.title', 'Checklist bản nháp')
            ->call('save')
            ->assertSet('stageId', (string) $this->secondStage->id);

        $draft = SalesPlaybook::query()->where('name', 'Bản nháp có stage')->firstOrFail();
        self::assertSame($this->secondStage->id, $draft->draft_pipeline_stage_id);

        Livewire::actingAs($this->admin)
            ->test(SalesPlaybookManager::class)
            ->call('selectPlaybook', $draft->id)
            ->assertSet('stageId', (string) $this->secondStage->id);
    }

    public function test_archived_playbook_can_be_edited_as_a_new_draft_version(): void
    {
        $management = app(SalesPlaybookManagementService::class);
        $published = $this->publishedPlaybook('once', [[
            'type' => 'checklist',
            'title' => 'Bước của phiên bản cũ',
            'instructions' => '',
            'is_required' => true,
            'blocks_stage_exit' => false,
            'due_business_days' => '',
        ]]);
        $management->archive($this->admin, $published->id);

        $draft = $management->createNextVersion($this->admin, $published->id);

        self::assertSame('draft', $draft->status->value);
        self::assertSame(2, $draft->version);
        self::assertSame($this->firstStage->id, $draft->draft_pipeline_stage_id);
        self::assertSame('Bước của phiên bản cũ', $draft->steps->firstOrFail()->title);
    }

    public function test_completing_a_task_step_synchronizes_task_and_run(): void
    {
        $this->publishedPlaybook('once', [[
            'type' => 'task',
            'title' => 'Chuẩn bị nội dung demo',
            'instructions' => '',
            'is_required' => true,
            'blocks_stage_exit' => true,
            'due_business_days' => 1,
        ]]);
        $service = app(OpportunityPlaybookService::class);
        $run = $service->activateForStage($this->admin, $this->opportunity, $this->firstStage->id, 401);
        self::assertNotNull($run);

        $completedRun = $service->completeStep($this->admin, $run->steps->firstOrFail()->id);

        self::assertSame(TaskStatus::Completed, Task::query()->firstOrFail()->status);
        self::assertSame('completed', $completedRun->status);
        self::assertNotNull($completedRun->completed_at);
        self::assertSame($completedRun->id, $service->activeRun($this->admin, $this->opportunity)?->id);
    }

    public function test_manual_policy_waits_for_explicit_start(): void
    {
        $this->publishedPlaybook('manual', [[
            'type' => 'checklist',
            'title' => 'Checklist chạy thủ công',
            'instructions' => '',
            'is_required' => true,
            'blocks_stage_exit' => false,
            'due_business_days' => '',
        ]]);
        $service = app(OpportunityPlaybookService::class);

        self::assertNull($service->activateForStage($this->admin, $this->opportunity, $this->firstStage->id, 501));
        self::assertTrue($service->canStart($this->admin, $this->opportunity));
        self::assertNotNull($service->activateForStage($this->admin, $this->opportunity, $this->firstStage->id, 501, true));
        self::assertSame(1, OpportunityPlaybookRun::query()->count());
    }

    public function test_qualification_question_requires_and_stores_an_answer(): void
    {
        $this->publishedPlaybook('once', [[
            'type' => 'question',
            'title' => 'Ai là người ra quyết định?',
            'instructions' => '',
            'is_required' => true,
            'blocks_stage_exit' => true,
            'due_business_days' => '',
        ]]);
        $service = app(OpportunityPlaybookService::class);
        $run = $service->activateForStage($this->admin, $this->opportunity, $this->firstStage->id, 601);
        self::assertNotNull($run);
        $step = $run->steps->firstOrFail();

        try {
            $service->completeStep($this->admin, $step->id);
            self::fail('A qualification answer must be required.');
        } catch (ValidationException $exception) {
            self::assertArrayHasKey('response', $exception->errors());
        }

        $service->completeStep($this->admin, $step->id, 'Giám đốc tài chính');
        self::assertSame('Giám đốc tài chính', $step->fresh()?->response_text);
    }

    public function test_queued_activation_is_idempotent_when_horizon_retries_the_job(): void
    {
        $this->publishedPlaybook('once', [[
            'type' => 'task',
            'title' => 'Task tạo từ queue',
            'instructions' => '',
            'is_required' => true,
            'blocks_stage_exit' => false,
            'due_business_days' => 1,
        ]]);
        $job = new ActivateOpportunityPlaybookJob(
            $this->admin->id,
            $this->opportunity->id,
            $this->firstStage->id,
            701,
        );

        $job->handle(app(OpportunityPlaybookService::class));
        $job->handle(app(OpportunityPlaybookService::class));

        self::assertSame(['default', 'automation'], config('horizon.defaults.supervisor-1.queue'));
        self::assertSame(1, OpportunityPlaybookRun::query()->count());
        self::assertSame(1, Task::query()->count());
    }

    public function test_sales_can_restart_a_playbook_without_losing_the_previous_run(): void
    {
        $this->publishedPlaybook('once', [[
            'type' => 'checklist',
            'title' => 'Checklist cần thực hiện lại',
            'instructions' => '',
            'is_required' => true,
            'blocks_stage_exit' => false,
            'due_business_days' => '',
        ]]);
        $service = app(OpportunityPlaybookService::class);
        $firstRun = $service->activateForStage($this->admin, $this->opportunity, $this->firstStage->id, 801);
        self::assertNotNull($firstRun);

        Livewire::actingAs($this->admin)
            ->test(OpportunityPlaybookProgress::class, ['opportunityId' => $this->opportunity->id])
            ->set('showRestartConfirmation', true)
            ->call('restart')
            ->assertSet('showRestartConfirmation', false)
            ->assertSee('Đã tạo lượt thực hiện Playbook mới.');

        self::assertSame(2, OpportunityPlaybookRun::query()->count());
        self::assertSame('superseded', $firstRun->fresh()?->status);
        self::assertSame('active', OpportunityPlaybookRun::query()->latest('id')->firstOrFail()->status);
    }

    /** @param list<array<string, mixed>> $steps */
    private function publishedPlaybook(string $repeatPolicy, array $steps): SalesPlaybook
    {
        $management = app(SalesPlaybookManagementService::class);
        $draft = $management->createDraft(
            $this->admin,
            'Playbook test '.bin2hex(random_bytes(3)),
            'Dữ liệu kiểm thử P10-03',
            $repeatPolicy,
            $steps,
        );

        return $management->publish($this->admin, $draft->id, $this->firstStage->id);
    }
}
