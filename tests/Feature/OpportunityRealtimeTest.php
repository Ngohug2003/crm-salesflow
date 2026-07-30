<?php

declare(strict_types=1);

use App\Broadcasting\PipelineChannel;
use App\Events\OpportunityStageUpdatedEvent;
use App\Models\Opportunity;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\User;
use App\Services\OpportunityStageTransitionService;
use Database\Seeders\DemoOpportunitySeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(DemoOpportunitySeeder::class);
});

function p507Actor(string $role): User
{
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'is_active' => true,
    ]);
    $user->assignRole($role);

    return $user;
}

it('dispatches OpportunityStageUpdatedEvent on private pipeline channel when stage transitions', function (): void {
    Event::fake([OpportunityStageUpdatedEvent::class]);

    $admin = p507Actor('admin');
    $pipeline = Pipeline::query()->where('is_default', true)->firstOrFail();
    $stages = PipelineStage::query()->where('pipeline_id', $pipeline->id)->orderBy('position', 'asc')->get();

    $opp = Opportunity::factory()->create([
        'pipeline_id' => $pipeline->id,
        'stage_id' => $stages[0]->id,
    ]);

    /** @var OpportunityStageTransitionService $service */
    $service = app(OpportunityStageTransitionService::class);
    $service->transitionStage($admin, $opp->id, $stages[1]->id, $stages[0]->id);

    Event::assertDispatched(OpportunityStageUpdatedEvent::class, function (OpportunityStageUpdatedEvent $event) use ($pipeline, $opp, $stages, $admin): bool {
        return $event->opportunity->id === $opp->id
            && $event->fromStageId === $stages[0]->id
            && $event->toStageId === $stages[1]->id
            && $event->actor->id === $admin->id
            && $event->broadcastOn()->name === "private-pipelines.{$pipeline->id}";
    });
});

it('authorizes channel access via PipelineChannel for permitted users', function (): void {
    $admin = p507Actor('admin');
    $pipeline = Pipeline::query()->where('is_default', true)->firstOrFail();

    $channel = new PipelineChannel;
    expect($channel->join($admin, $pipeline->id))->toBeTrue();
});

it('denies channel access via PipelineChannel for unauthenticated or unpermitted users', function (): void {
    $userWithoutPermission = User::factory()->create(['is_active' => true]);
    $pipeline = Pipeline::query()->where('is_default', true)->firstOrFail();

    $channel = new PipelineChannel;
    expect($channel->join($userWithoutPermission, $pipeline->id))->toBeFalse();
});

it('formats broadcast payload correctly in OpportunityStageUpdatedEvent', function (): void {
    $admin = p507Actor('admin');
    $pipeline = Pipeline::query()->where('is_default', true)->firstOrFail();
    $stages = PipelineStage::query()->where('pipeline_id', $pipeline->id)->orderBy('position', 'asc')->get();

    $opp = Opportunity::factory()->create([
        'pipeline_id' => $pipeline->id,
        'stage_id' => $stages[0]->id,
        'amount' => 150000000.00,
    ]);

    $event = new OpportunityStageUpdatedEvent($opp, $stages[0]->id, $stages[1]->id, $admin);

    expect($event->broadcastAs())->toBe('OpportunityStageUpdated')
        ->and($event->broadcastOn())->toBeInstanceOf(PrivateChannel::class);

    $payload = $event->broadcastWith();
    expect($payload['opportunity_id'])->toBe($opp->id)
        ->and($payload['pipeline_id'])->toBe($pipeline->id)
        ->and($payload['from_stage_id'])->toBe($stages[0]->id)
        ->and($payload['to_stage_id'])->toBe($stages[1]->id)
        ->and($payload['actor_id'])->toBe($admin->id)
        ->and($payload['actor_name'])->toBe($admin->name);
});
