<?php

declare(strict_types=1);

use App\Livewire\Opportunities\OpportunityKanban;
use App\Models\Opportunity;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\User;
use Database\Seeders\DemoOpportunitySeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(DemoOpportunitySeeder::class);
});

function p506Actor(string $role): User
{
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'is_active' => true,
    ]);
    $user->assignRole($role);

    return $user;
}

it('protects kanban route for unauthenticated users', function (): void {
    $this->get('/opportunities/kanban')->assertRedirect('/login');
});

it('renders opportunity kanban page for authorized user', function (): void {
    $admin = p506Actor('admin');

    $this->actingAs($admin)
        ->get('/opportunities/kanban')
        ->assertOk()
        ->assertSee('Quy trình Bán hàng dạng Kanban');
});

it('groups opportunities by stage and calculates column summary values', function (): void {
    $admin = p506Actor('admin');
    $pipeline = Pipeline::query()->where('is_default', true)->firstOrFail();

    Livewire::actingAs($admin)
        ->test(OpportunityKanban::class, ['pipelineId' => (string) $pipeline->id])
        ->assertSee('Quy trình Bán hàng dạng Kanban');
});

it('moves opportunity across stages via Livewire moveOpportunity call', function (): void {
    $admin = p506Actor('admin');
    $pipeline = Pipeline::query()->where('is_default', true)->firstOrFail();
    $stages = PipelineStage::query()->where('pipeline_id', $pipeline->id)->orderBy('position', 'asc')->get();

    $opp = Opportunity::factory()->create([
        'pipeline_id' => $pipeline->id,
        'stage_id' => $stages[0]->id,
    ]);

    Livewire::actingAs($admin)
        ->test(OpportunityKanban::class, ['pipelineId' => (string) $pipeline->id])
        ->call('moveOpportunity', $opp->id, $stages[1]->id, $stages[0]->id)
        ->assertHasNoErrors();

    expect($opp->fresh()->stage_id)->toBe($stages[1]->id);
});

it('handles version conflict when expected fromStageId mismatch', function (): void {
    $admin = p506Actor('admin');
    $pipeline = Pipeline::query()->where('is_default', true)->firstOrFail();
    $stages = PipelineStage::query()->where('pipeline_id', $pipeline->id)->orderBy('position', 'asc')->get();

    $opp = Opportunity::factory()->create([
        'pipeline_id' => $pipeline->id,
        'stage_id' => $stages[1]->id,
    ]);

    Livewire::actingAs($admin)
        ->test(OpportunityKanban::class, ['pipelineId' => (string) $pipeline->id])
        ->call('moveOpportunity', $opp->id, $stages[2]->id, $stages[0]->id) // expected stage 0, but current is 1
        ->assertHasErrors(['kanban_error']);
});
