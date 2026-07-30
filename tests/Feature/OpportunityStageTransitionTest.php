<?php

declare(strict_types=1);

use App\Exceptions\StaleOpportunityException;
use App\Models\Opportunity;
use App\Models\OpportunityStageHistory;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\User;
use App\Services\CustomerTimelineService;
use App\Services\OpportunityStageTransitionService;
use Database\Seeders\DemoOpportunitySeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(DemoOpportunitySeeder::class);
});

function p505Actor(string $role): User
{
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'is_active' => true,
    ]);
    $user->assignRole($role);

    return $user;
}

it('transitions stage successfully and records immutable history', function (): void {
    $admin = p505Actor('admin');
    $pipeline = Pipeline::query()->where('is_default', true)->firstOrFail();
    $stages = PipelineStage::query()->where('pipeline_id', $pipeline->id)->orderBy('position', 'asc')->get();

    $opportunity = Opportunity::factory()->create([
        'pipeline_id' => $pipeline->id,
        'stage_id' => $stages[0]->id,
        'created_at' => now()->subHours(5),
    ]);

    /** @var OpportunityStageTransitionService $transitionService */
    $transitionService = app(OpportunityStageTransitionService::class);

    $updatedOpp = $transitionService->transitionStage(
        actor: $admin,
        opportunityId: $opportunity->id,
        targetStageId: $stages[1]->id,
        expectedCurrentStageId: $stages[0]->id,
        notes: 'Chuyển sang giai đoạn Phân tích nhu cầu sau khi gọi điện.',
    );

    expect($updatedOpp->stage_id)->toBe($stages[1]->id);

    $history = OpportunityStageHistory::query()->where('opportunity_id', $opportunity->id)->first();
    expect($history)->not->toBeNull()
        ->and($history->from_stage_id)->toBe($stages[0]->id)
        ->and($history->to_stage_id)->toBe($stages[1]->id)
        ->and($history->user_id)->toBe($admin->id)
        ->and($history->notes)->toBe('Chuyển sang giai đoạn Phân tích nhu cầu sau khi gọi điện.')
        ->and($history->duration_seconds)->toBeGreaterThanOrEqual(17990); // ~5 hours
});

it('throws StaleOpportunityException on version conflict', function (): void {
    $admin = p505Actor('admin');
    $pipeline = Pipeline::query()->where('is_default', true)->firstOrFail();
    $stages = PipelineStage::query()->where('pipeline_id', $pipeline->id)->orderBy('position', 'asc')->get();

    $opportunity = Opportunity::factory()->create([
        'pipeline_id' => $pipeline->id,
        'stage_id' => $stages[1]->id,
    ]);

    /** @var OpportunityStageTransitionService $transitionService */
    $transitionService = app(OpportunityStageTransitionService::class);

    // Expecting stage[0] while current stage is stage[1]
    expect(fn () => $transitionService->transitionStage(
        actor: $admin,
        opportunityId: $opportunity->id,
        targetStageId: $stages[2]->id,
        expectedCurrentStageId: $stages[0]->id,
    ))->toThrow(StaleOpportunityException::class);
});

it('includes stage transition events in customer timeline', function (): void {
    $admin = p505Actor('admin');
    $pipeline = Pipeline::query()->where('is_default', true)->firstOrFail();
    $stages = PipelineStage::query()->where('pipeline_id', $pipeline->id)->orderBy('position', 'asc')->get();

    $opportunity = Opportunity::factory()->create([
        'pipeline_id' => $pipeline->id,
        'stage_id' => $stages[0]->id,
    ]);

    /** @var OpportunityStageTransitionService $transitionService */
    $transitionService = app(OpportunityStageTransitionService::class);
    $transitionService->transitionStage(
        actor: $admin,
        opportunityId: $opportunity->id,
        targetStageId: $stages[2]->id,
        expectedCurrentStageId: $stages[0]->id,
        notes: 'Chuyển thẳng sang Báo giá',
    );

    /** @var CustomerTimelineService $timelineService */
    $timelineService = app(CustomerTimelineService::class);
    $items = $timelineService->timelineForModel($admin, $opportunity->fresh());

    $stageItem = collect($items)->firstWhere('event', 'stage_transition');
    expect($stageItem)->not->toBeNull()
        ->and($stageItem->type)->toBe('stage_change')
        ->and($stageItem->title)->toContain($stages[2]->name);
});
