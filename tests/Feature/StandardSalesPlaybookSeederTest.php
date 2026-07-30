<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\PlaybookRepeatPolicy;
use App\Enums\SalesPlaybookStatus;
use App\Models\Pipeline;
use App\Models\SalesPlaybook;
use App\Models\StagePlaybookAssignment;
use App\Models\User;
use Database\Seeders\DemoPipelineSeeder;
use Database\Seeders\StandardSalesPlaybookSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class StandardSalesPlaybookSeederTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['email' => 'admin@salesflow.test']);
        $this->seed(DemoPipelineSeeder::class);
    }

    public function test_it_seeds_a_published_playbook_for_every_standard_pipeline_stage(): void
    {
        $this->seed(StandardSalesPlaybookSeeder::class);

        $pipeline = Pipeline::query()
            ->where('code', 'standard-sales-pipeline')
            ->with(['stages.playbookAssignment.playbook.steps'])
            ->firstOrFail();

        self::assertCount(6, $pipeline->stages);
        self::assertSame(
            [
                'standard-initial-contact',
                'standard-needs-analysis',
                'standard-proposal-quote',
                'standard-negotiation',
                'standard-closed-won',
                'standard-closed-lost',
            ],
            $pipeline->stages
                ->map(fn ($stage): ?string => $stage->playbookAssignment?->playbook->code)
                ->all(),
        );

        foreach ($pipeline->stages as $stage) {
            $assignment = $stage->playbookAssignment;

            self::assertNotNull($assignment);
            self::assertTrue($assignment->is_active);
            self::assertSame(SalesPlaybookStatus::Published, $assignment->playbook->status);
            self::assertSame($stage->id, $assignment->playbook->draft_pipeline_stage_id);
            self::assertNotEmpty($assignment->playbook->steps);
        }

        self::assertSame(5, $pipeline->stages->firstWhere('code', 'initial-contact')?->playbookAssignment?->playbook->steps->count());
        self::assertSame(7, $pipeline->stages->firstWhere('code', 'needs-analysis')?->playbookAssignment?->playbook->steps->count());
        self::assertSame(7, $pipeline->stages->firstWhere('code', 'proposal-quote')?->playbookAssignment?->playbook->steps->count());
        self::assertSame(7, $pipeline->stages->firstWhere('code', 'negotiation')?->playbookAssignment?->playbook->steps->count());
        self::assertSame(5, $pipeline->stages->firstWhere('code', 'closed-won')?->playbookAssignment?->playbook->steps->count());
        self::assertSame(5, $pipeline->stages->firstWhere('code', 'closed-lost')?->playbookAssignment?->playbook->steps->count());
    }

    public function test_it_is_idempotent_and_terminal_stage_playbooks_do_not_block_closing(): void
    {
        $this->seed(StandardSalesPlaybookSeeder::class);
        $this->seed(StandardSalesPlaybookSeeder::class);

        $this->assertDatabaseCount('sales_playbooks', 6);
        $this->assertDatabaseCount('stage_playbook_assignments', 6);
        $this->assertDatabaseCount('sales_playbook_steps', 36);

        $terminalCodes = ['standard-closed-won', 'standard-closed-lost'];
        $blockingTerminalSteps = SalesPlaybook::query()
            ->whereIn('code', $terminalCodes)
            ->withCount(['steps as blocking_steps_count' => fn ($query) => $query->where('blocks_stage_exit', true)])
            ->get()
            ->sum('blocking_steps_count');

        self::assertSame(0, $blockingTerminalSteps);
    }

    public function test_it_restores_the_standard_assignment_when_rerun(): void
    {
        $this->seed(StandardSalesPlaybookSeeder::class);

        $stage = Pipeline::query()
            ->where('code', 'standard-sales-pipeline')
            ->firstOrFail()
            ->stages()
            ->where('code', 'initial-contact')
            ->firstOrFail();

        $customPlaybook = SalesPlaybook::query()->create([
            'code' => 'custom-initial-contact',
            'name' => 'Playbook tùy chỉnh của doanh nghiệp',
            'description' => 'Dùng để kiểm tra seeder khôi phục assignment chuẩn.',
            'version' => 1,
            'status' => SalesPlaybookStatus::Published,
            'repeat_policy' => PlaybookRepeatPolicy::Once,
            'draft_pipeline_stage_id' => $stage->id,
            'created_by' => $this->admin->id,
            'published_by' => $this->admin->id,
            'published_at' => now(),
        ]);

        StagePlaybookAssignment::query()
            ->where('pipeline_stage_id', $stage->id)
            ->update([
                'sales_playbook_id' => $customPlaybook->id,
                'assigned_by' => $this->admin->id,
                'is_active' => true,
            ]);

        $this->seed(StandardSalesPlaybookSeeder::class);

        $assignment = StagePlaybookAssignment::query()
            ->with('playbook')
            ->where('pipeline_stage_id', $stage->id)
            ->firstOrFail();

        self::assertNotSame($customPlaybook->id, $assignment->sales_playbook_id);
        self::assertSame('standard-initial-contact', $assignment->playbook->code);
        self::assertTrue($assignment->is_active);
    }
}
