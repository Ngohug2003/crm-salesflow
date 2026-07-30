<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Contact;
use App\Models\Opportunity;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use Database\Seeders\DemoOpportunitySeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
});

it('creates Opportunity with PostgreSQL BIGINT key and relationships', function (): void {
    $pipeline = Pipeline::factory()->create();
    $stage = PipelineStage::factory()->create([
        'pipeline_id' => $pipeline->id,
        'probability' => 30,
    ]);
    $company = Company::factory()->create();
    $contact = Contact::factory()->create();

    $opportunity = Opportunity::factory()->create([
        'title' => 'Cơ hội Test B2B',
        'code' => 'OPP-TEST-001',
        'amount' => 100000000.00,
        'pipeline_id' => $pipeline->id,
        'stage_id' => $stage->id,
        'company_id' => $company->id,
        'contact_id' => $contact->id,
    ]);

    expect($opportunity->getKey())->toBeInt()
        ->and($opportunity->pipeline->id)->toBe($pipeline->id)
        ->and($opportunity->stage->id)->toBe($stage->id)
        ->and($opportunity->company->id)->toBe($company->id)
        ->and($opportunity->contact->id)->toBe($contact->id);

    // Verify relationships from Company and Contact
    expect($company->opportunities->count())->toBe(1)
        ->and($contact->opportunities->count())->toBe(1);
});

it('calculates weighted_value attribute correctly based on stage probability', function (): void {
    $pipeline = Pipeline::factory()->create();
    $stage = PipelineStage::factory()->create([
        'pipeline_id' => $pipeline->id,
        'probability' => 40,
    ]);

    $opportunity = Opportunity::factory()->create([
        'amount' => 200000000.00,
        'pipeline_id' => $pipeline->id,
        'stage_id' => $stage->id,
    ]);

    // 200,000,000 * 40 / 100 = 80,000,000
    expect($opportunity->weighted_value)->toBe(80000000.00);
});

it('supports soft deletes and restore for Opportunity', function (): void {
    $opportunity = Opportunity::factory()->create(['code' => 'OPP-SOFT-DELETE']);

    $opportunity->delete();
    expect(Opportunity::query()->find($opportunity->id))->toBeNull()
        ->and(Opportunity::withTrashed()->find($opportunity->id))->not->toBeNull();

    $opportunity->restore();
    expect(Opportunity::query()->find($opportunity->id))->not->toBeNull();
});

it('seeds 5 demo opportunities via DemoOpportunitySeeder', function (): void {
    $this->seed(DemoOpportunitySeeder::class);

    expect(Opportunity::query()->count())->toBe(5);

    $wonOpp = Opportunity::query()->where('is_won', true)->first();
    expect($wonOpp)->not->toBeNull()
        ->and((float) $wonOpp->amount)->toBe(220000000.00)
        ->and($wonOpp->weighted_value)->toBe(220000000.00);

    $lostOpp = Opportunity::query()->where('is_lost', true)->first();
    expect($lostOpp)->not->toBeNull()
        ->and($lostOpp->lost_reason)->not->toBeEmpty()
        ->and($lostOpp->weighted_value)->toBe(0.00);
});
