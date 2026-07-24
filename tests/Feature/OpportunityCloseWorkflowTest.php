<?php

declare(strict_types=1);

use App\Events\OpportunityStageUpdatedEvent;
use App\Models\Opportunity;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\User;
use App\Services\OpportunityCloseWorkflowService;
use Database\Seeders\DemoOpportunitySeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(DemoOpportunitySeeder::class);
});

function p508Actor(string $role): User
{
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'is_active' => true,
    ]);
    $user->assignRole($role);

    return $user;
}

it('closes opportunity as Won successfully and sets actual_close_date', function (): void {
    Event::fake([OpportunityStageUpdatedEvent::class]);

    $admin = p508Actor('admin');
    $pipeline = Pipeline::query()->where('is_default', true)->firstOrFail();
    $openStage = PipelineStage::query()->where('pipeline_id', $pipeline->id)->where('is_won', false)->where('is_lost', false)->firstOrFail();
    $wonStage = PipelineStage::query()->where('pipeline_id', $pipeline->id)->where('is_won', true)->firstOrFail();

    $opp = Opportunity::factory()->create([
        'pipeline_id' => $pipeline->id,
        'stage_id' => $openStage->id,
        'amount' => 100000000.00,
    ]);

    /** @var OpportunityCloseWorkflowService $workflow */
    $workflow = app(OpportunityCloseWorkflowService::class);
    $wonOpp = $workflow->closeWon($admin, $opp->id, 'Khách hàng ký hợp đồng thành công');

    expect($wonOpp->is_won)->toBeTrue()
        ->and($wonOpp->is_lost)->toBeFalse()
        ->and($wonOpp->stage_id)->toBe($wonStage->id)
        ->and($wonOpp->actual_close_date)->not->toBeNull();

    Event::assertDispatched(OpportunityStageUpdatedEvent::class);
});

it('rejects closing opportunity as Lost when lost_reason is empty', function (): void {
    $admin = p508Actor('admin');
    $opp = Opportunity::query()->where('is_won', false)->where('is_lost', false)->firstOrFail();

    /** @var OpportunityCloseWorkflowService $workflow */
    $workflow = app(OpportunityCloseWorkflowService::class);

    expect(fn () => $workflow->closeLost($admin, $opp->id, '   '))
        ->toThrow(InvalidArgumentException::class, 'Bắt buộc phải nhập lý do thất bại khi đóng cơ hội.');
});

it('closes opportunity as Lost successfully with lost_reason', function (): void {
    Event::fake([OpportunityStageUpdatedEvent::class]);

    $admin = p508Actor('admin');
    $pipeline = Pipeline::query()->where('is_default', true)->firstOrFail();
    $openStage = PipelineStage::query()->where('pipeline_id', $pipeline->id)->where('is_won', false)->where('is_lost', false)->firstOrFail();
    $lostStage = PipelineStage::query()->where('pipeline_id', $pipeline->id)->where('is_lost', true)->firstOrFail();

    $opp = Opportunity::factory()->create([
        'pipeline_id' => $pipeline->id,
        'stage_id' => $openStage->id,
    ]);

    /** @var OpportunityCloseWorkflowService $workflow */
    $workflow = app(OpportunityCloseWorkflowService::class);
    $lostOpp = $workflow->closeLost($admin, $opp->id, 'Đối thủ cạnh tranh giảm giá 30%');

    expect($lostOpp->is_lost)->toBeTrue()
        ->and($lostOpp->is_won)->toBeFalse()
        ->and($lostOpp->lost_reason)->toBe('Đối thủ cạnh tranh giảm giá 30%')
        ->and($lostOpp->stage_id)->toBe($lostStage->id);
});

it('reopens closed opportunity back to open stage', function (): void {
    $admin = p508Actor('admin');
    $pipeline = Pipeline::query()->where('is_default', true)->firstOrFail();
    $wonStage = PipelineStage::query()->where('pipeline_id', $pipeline->id)->where('is_won', true)->firstOrFail();
    $openStage = PipelineStage::query()->where('pipeline_id', $pipeline->id)->where('is_won', false)->where('is_lost', false)->orderBy('position', 'asc')->firstOrFail();

    $opp = Opportunity::factory()->create([
        'pipeline_id' => $pipeline->id,
        'stage_id' => $wonStage->id,
        'is_won' => true,
        'actual_close_date' => now()->format('Y-m-d'),
    ]);

    /** @var OpportunityCloseWorkflowService $workflow */
    $workflow = app(OpportunityCloseWorkflowService::class);
    $reopenedOpp = $workflow->reopen($admin, $opp->id);

    expect($reopenedOpp->is_won)->toBeFalse()
        ->and($reopenedOpp->is_lost)->toBeFalse()
        ->and($reopenedOpp->stage_id)->toBe($openStage->id)
        ->and($reopenedOpp->actual_close_date)->toBeNull();
});
