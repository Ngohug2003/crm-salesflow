<?php

declare(strict_types=1);

use App\Models\Pipeline;
use App\Models\PipelineStage;
use Database\Seeders\DemoPipelineSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
});

it('creates Pipeline and PipelineStage with PostgreSQL BIGINT keys and relationships', function (): void {
    $pipeline = Pipeline::factory()->create([
        'name' => 'Quy trình Test',
        'code' => 'quy-trinh-test',
        'is_default' => false,
    ]);

    expect($pipeline->getKey())->toBeInt();

    $stage1 = PipelineStage::factory()->create([
        'pipeline_id' => $pipeline->id,
        'name' => 'Bước 1',
        'code' => 'buec-1',
        'position' => 2,
        'probability' => 20,
    ]);

    $stage2 = PipelineStage::factory()->create([
        'pipeline_id' => $pipeline->id,
        'name' => 'Bước 0',
        'code' => 'buec-0',
        'position' => 1,
        'probability' => 10,
    ]);

    expect($stage1->getKey())->toBeInt()
        ->and($stage1->pipeline->id)->toBe($pipeline->id);

    // Verify stages relationship is ordered by position asc
    $stages = $pipeline->fresh()->stages;
    expect($stages->count())->toBe(2)
        ->and($stages->first()->id)->toBe($stage2->id)
        ->and($stages->last()->id)->toBe($stage1->id);
});

it('supports soft deletes and restore for Pipeline and PipelineStage', function (): void {
    $pipeline = Pipeline::factory()->create(['code' => 'test-soft-delete']);
    $stage = PipelineStage::factory()->create(['pipeline_id' => $pipeline->id, 'code' => 'stage-soft-delete']);

    $stage->delete();
    expect(PipelineStage::query()->find($stage->id))->toBeNull()
        ->and(PipelineStage::withTrashed()->find($stage->id))->not->toBeNull();

    $pipeline->delete();
    expect(Pipeline::query()->find($pipeline->id))->toBeNull()
        ->and(Pipeline::withTrashed()->find($pipeline->id))->not->toBeNull();

    $pipeline->restore();
    expect(Pipeline::query()->find($pipeline->id))->not->toBeNull();
});

it('seeds standard default pipeline with 6 stages via DemoPipelineSeeder', function (): void {
    $this->seed(DemoPipelineSeeder::class);

    $pipeline = Pipeline::query()->where('is_default', true)->first();
    expect($pipeline)->not->toBeNull()
        ->and($pipeline->code)->toBe('standard-sales-pipeline');

    $stages = $pipeline->stages;
    expect($stages->count())->toBe(6);

    $wonStage = $stages->firstWhere('is_won', true);
    expect($wonStage)->not->toBeNull()
        ->and($wonStage->probability)->toBe(100)
        ->and($wonStage->is_system)->toBeTrue();

    $lostStage = $stages->firstWhere('is_lost', true);
    expect($lostStage)->not->toBeNull()
        ->and($lostStage->probability)->toBe(0)
        ->and($lostStage->is_system)->toBeTrue();
});
