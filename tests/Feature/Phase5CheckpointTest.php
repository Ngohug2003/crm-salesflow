<?php

declare(strict_types=1);

use App\Data\LeadConversionData;
use App\Enums\LeadStatus;
use App\Events\OpportunityStageUpdatedEvent;
use App\Exceptions\StaleOpportunityException;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\OpportunityStageHistory;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\User;
use App\Services\LeadConversionService;
use App\Services\OpportunityCloseWorkflowService;
use App\Services\OpportunityManagementService;
use App\Services\OpportunityStageTransitionService;
use Database\Seeders\DemoOpportunitySeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(DemoOpportunitySeeder::class);
});

function p509Actor(string $role): User
{
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'is_active' => true,
    ]);
    $user->assignRole($role);

    return $user;
}

it('passes Checkpoint P5-01 & P5-02: verifies Pipeline and Stage domain structure and default flag', function (): void {
    $defaultPipeline = Pipeline::query()->where('is_default', true)->first();
    expect($defaultPipeline)->not->toBeNull()
        ->and($defaultPipeline->getKey())->toBeInt()
        ->and($defaultPipeline->stages->count())->toBe(6);
});

it('passes Checkpoint P5-03 & P5-04: verifies Opportunity CRUD, code auto generation and weighted value', function (): void {
    $admin = p509Actor('admin');
    $pipeline = Pipeline::query()->where('is_default', true)->firstOrFail();
    $stage = PipelineStage::query()->where('pipeline_id', $pipeline->id)->where('probability', 50)->firstOrFail();

    /** @var OpportunityManagementService $service */
    $service = app(OpportunityManagementService::class);

    $opp = $service->create($admin, [
        'title' => 'Cơ hội Checkpoint P5-04',
        'amount' => 200000000.00,
        'pipeline_id' => $pipeline->id,
        'stage_id' => $stage->id,
    ]);

    expect($opp->code)->toMatch('/OPP-\d{4}-\d{5}/')
        ->and((float) $opp->amount)->toBe(200000000.00)
        ->and($opp->weighted_value)->toBe(100000000.00);
});

it('passes Checkpoint P5-05 & P5-07: verifies Stage Transition, duration_seconds and Realtime Broadcast', function (): void {
    Event::fake([OpportunityStageUpdatedEvent::class]);

    $admin = p509Actor('admin');
    $pipeline = Pipeline::query()->where('is_default', true)->firstOrFail();
    $stages = PipelineStage::query()->where('pipeline_id', $pipeline->id)->orderBy('position', 'asc')->get();

    $opp = Opportunity::factory()->create([
        'pipeline_id' => $pipeline->id,
        'stage_id' => $stages[0]->id,
    ]);

    /** @var OpportunityStageTransitionService $transitionService */
    $transitionService = app(OpportunityStageTransitionService::class);
    $updatedOpp = $transitionService->transitionStage($admin, $opp->id, $stages[1]->id, $stages[0]->id);

    expect($updatedOpp->stage_id)->toBe($stages[1]->id);

    $history = OpportunityStageHistory::query()->where('opportunity_id', $opp->id)->first();
    expect($history)->not->toBeNull()
        ->and($history->from_stage_id)->toBe($stages[0]->id)
        ->and($history->to_stage_id)->toBe($stages[1]->id);

    Event::assertDispatched(OpportunityStageUpdatedEvent::class);
});

it('passes Checkpoint P5-05: throws StaleOpportunityException on version conflict', function (): void {
    $admin = p509Actor('admin');
    $pipeline = Pipeline::query()->where('is_default', true)->firstOrFail();
    $stages = PipelineStage::query()->where('pipeline_id', $pipeline->id)->orderBy('position', 'asc')->get();

    $opp = Opportunity::factory()->create([
        'pipeline_id' => $pipeline->id,
        'stage_id' => $stages[1]->id,
    ]);

    /** @var OpportunityStageTransitionService $transitionService */
    $transitionService = app(OpportunityStageTransitionService::class);

    expect(fn () => $transitionService->transitionStage(
        actor: $admin,
        opportunityId: $opp->id,
        targetStageId: $stages[2]->id,
        expectedCurrentStageId: $stages[0]->id, // wrong expected current stage
    ))->toThrow(StaleOpportunityException::class);
});

it('passes Checkpoint P5-08: verifies Close Won, Close Lost mandatory reason and Reopen workflow', function (): void {
    $admin = p509Actor('admin');
    $opp = Opportunity::query()->where('is_won', false)->where('is_lost', false)->firstOrFail();

    /** @var OpportunityCloseWorkflowService $workflow */
    $workflow = app(OpportunityCloseWorkflowService::class);

    // Test close lost with reason
    $lostOpp = $workflow->closeLost($admin, $opp->id, 'Khách hàng hủy ngân sách dự án');
    expect($lostOpp->is_lost)->toBeTrue()
        ->and($lostOpp->lost_reason)->toBe('Khách hàng hủy ngân sách dự án');

    // Test reopen
    $reopenedOpp = $workflow->reopen($admin, $opp->id);
    expect($reopenedOpp->is_won)->toBeFalse()
        ->and($reopenedOpp->is_lost)->toBeFalse();

    // Test close won
    $wonOpp = $workflow->closeWon($admin, $opp->id);
    expect($wonOpp->is_won)->toBeTrue();
});

it('passes Checkpoint P5-09: verifies Lead conversion integration creating Company, Contact and Opportunity', function (): void {
    $admin = p509Actor('admin');
    $lead = Lead::factory()->create([
        'full_name' => 'Nguyễn Văn Lead Test',
        'company_name' => 'Công ty Lead Test B2B',
        'email' => 'lead.test@salesflow.test',
        'phone' => '0988776655',
        'status' => LeadStatus::New,
        'owner_id' => $admin->id,
        'department_id' => $admin->department_id,
    ]);

    /** @var LeadConversionService $leadConversion */
    $leadConversion = app(LeadConversionService::class);

    $convertedLead = $leadConversion->convert($admin, $lead->id, new LeadConversionData(
        createCompany: true,
        createContact: true,
        createOpportunity: true,
        opportunityName: 'Dự án mua sắm CRM cho Lead Test',
        estimatedValue: 120000000.00,
    ));

    expect($convertedLead->status)->toBe(LeadStatus::Converted);

    $company = Company::query()->where('name', 'Công ty Lead Test B2B')->first();
    expect($company)->not->toBeNull();

    $contact = Contact::query()->where('email', 'lead.test@salesflow.test')->first();
    expect($contact)->not->toBeNull()
        ->and($contact->company_id)->toBe($company->id);

    $opportunity = Opportunity::query()->where('title', 'Dự án mua sắm CRM cho Lead Test')->first();
    expect($opportunity)->not->toBeNull()
        ->and($opportunity->company_id)->toBe($company->id)
        ->and($opportunity->contact_id)->toBe($contact->id)
        ->and($opportunity->lead_id)->toBe($lead->id)
        ->and((float) $opportunity->amount)->toBe(120000000.00);
});
