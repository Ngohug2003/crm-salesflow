<?php

declare(strict_types=1);

use App\Data\OpportunityFilterData;
use App\Livewire\Opportunities\OpportunityEditor;
use App\Livewire\Opportunities\OpportunityList;
use App\Models\Company;
use App\Models\Opportunity;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\User;
use App\Services\OpportunityManagementService;
use Database\Seeders\DemoOpportunitySeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(DemoOpportunitySeeder::class);
});

function p504Actor(string $role): User
{
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'is_active' => true,
    ]);
    $user->assignRole($role);

    return $user;
}

it('protects opportunity routes for unauthenticated users', function (): void {
    $this->get('/opportunities')->assertRedirect('/login');
    $this->get('/opportunities/create')->assertRedirect('/login');
});

it('allows Super Admin to view opportunity list and details', function (): void {
    $admin = p504Actor('super-admin');

    $this->actingAs($admin)
        ->get('/opportunities')
        ->assertOk()
        ->assertSee('Cơ hội bán hàng');

    $opp = Opportunity::query()->firstOrFail();

    $this->actingAs($admin)
        ->get("/opportunities/{$opp->id}")
        ->assertOk()
        ->assertSee($opp->title);
});

it('creates a new opportunity with auto-generated code and correct weighted_value', function (): void {
    $admin = p504Actor('admin');
    $pipeline = Pipeline::query()->where('is_default', true)->firstOrFail();
    $stage = PipelineStage::query()->where('pipeline_id', $pipeline->id)->where('probability', 50)->firstOrFail();
    $company = Company::factory()->create();

    /** @var OpportunityManagementService $service */
    $service = app(OpportunityManagementService::class);

    $opp = $service->create($admin, [
        'title' => 'Cơ hội Triển khai Hệ thống',
        'amount' => 100000000.00,
        'pipeline_id' => $pipeline->id,
        'stage_id' => $stage->id,
        'company_id' => $company->id,
    ]);

    expect($opp->code)->toMatch('/OPP-\d{4}-\d{5}/')
        ->and((float) $opp->amount)->toBe(100000000.00)
        ->and($opp->weighted_value)->toBe(50000000.00);
});

it('summarizes visible opportunities totals accurately', function (): void {
    $admin = p504Actor('admin');

    /** @var OpportunityManagementService $service */
    $service = app(OpportunityManagementService::class);

    $summary = $service->summarize($admin, new OpportunityFilterData);

    expect($summary['total_count'])->toBe(5)
        ->and($summary['total_amount'])->toBeGreaterThan(0.0)
        ->and($summary['total_weighted_value'])->toBeGreaterThan(0.0);
});

it('creates opportunity via Livewire OpportunityEditor component', function (): void {
    $admin = p504Actor('admin');
    $pipeline = Pipeline::query()->where('is_default', true)->firstOrFail();
    $stage = PipelineStage::query()->where('pipeline_id', $pipeline->id)->firstOrFail();

    Livewire::actingAs($admin)
        ->test(OpportunityEditor::class)
        ->set('title', 'Cơ hội từ Livewire Test')
        ->set('amount', '50000000')
        ->set('pipeline_id', $pipeline->id)
        ->set('stage_id', $stage->id)
        ->call('save')
        ->assertHasNoErrors();

    $opp = Opportunity::query()->where('title', 'Cơ hội từ Livewire Test')->first();
    expect($opp)->not->toBeNull()
        ->and((float) $opp->amount)->toBe(50000000.00);
});

it('enforces read-only access for viewer role on opportunity deletion', function (): void {
    $viewer = p504Actor('viewer');
    $opp = Opportunity::query()->firstOrFail();

    Livewire::actingAs($viewer)
        ->test(OpportunityList::class)
        ->call('deleteOpportunity', $opp->id)
        ->assertForbidden();
});
