<?php

declare(strict_types=1);

use App\Data\LeadConversionData;
use App\Enums\LeadStatus;
use App\Livewire\Leads\LeadEditor;
use App\Livewire\Leads\LeadList;
use App\Models\Department;
use App\Models\Lead;
use App\Models\User;
use App\Services\Contracts\LeadConversionContract;
use App\Services\LeadLifecycleService;
use App\Services\LeadStatusTransitionService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(DatabaseSeeder::class);
});

function p310User(string $email): User
{
    return User::query()->where('email', $email)->firstOrFail();
}

it('allows Super Admin to view all 30 demo leads and perform full lifecycle actions', function (): void {
    $superAdmin = p310User('admin@salesflow.test');

    expect(Lead::count())->toBeGreaterThanOrEqual(30);

    // View List
    Livewire::actingAs($superAdmin)
        ->test(LeadList::class)
        ->assertSee('Nguyễn Hoàng Anh');

    // Create Lead
    Livewire::actingAs($superAdmin)
        ->test(LeadEditor::class)
        ->set('form.fullName', 'Lead Checkpoint SuperAdmin')
        ->set('form.email', 'superadmin.checkpoint@salesflow.test')
        ->set('form.phone', '0988776655')
        ->call('save')
        ->assertHasNoErrors();

    $newLead = Lead::query()->where('email', 'superadmin.checkpoint@salesflow.test')->firstOrFail();
    expect($newLead->full_name)->toBe('Lead Checkpoint SuperAdmin');

    // Conversion Eligibility
    /** @var LeadConversionContract $conversionService */
    $conversionService = app(LeadConversionContract::class);
    $eligibility = $conversionService->checkEligibility($superAdmin, $newLead);
    expect($eligibility['eligible'])->toBeTrue();

    // Convert Lead
    $conversionService->convert($superAdmin, $newLead->getKey(), new LeadConversionData);
    expect($newLead->refresh()->status)->toBe(LeadStatus::Converted);
});

it('limits Sales Manager to department leads and allows scoped workflow mutations', function (): void {
    $salesManager = p310User('demo03@salesflow.test'); // Manager of SALES
    $salesDept = Department::query()->where('code', 'SALES')->firstOrFail();
    $marketingDept = Department::query()->where('code', 'MARKETING')->firstOrFail();

    $salesLead = Lead::factory()->ownedBy(User::factory()->create(['department_id' => $salesDept->getKey()]))->create();
    $marketingLead = Lead::factory()->ownedBy(User::factory()->create(['department_id' => $marketingDept->getKey()]))->create();

    // Policy Check
    expect($salesManager->can('view', $salesLead))->toBeTrue()
        ->and($salesManager->can('view', $marketingLead))->toBeFalse()
        ->and($salesManager->can('update', $marketingLead))->toBeFalse();

    // Workflow Status Transition on scoped lead
    /** @var LeadStatusTransitionService $statusService */
    $statusService = app(LeadStatusTransitionService::class);
    $updatedLead = $statusService->transition($salesManager, $salesLead->getKey(), 'contacted', null);

    expect($updatedLead->status)->toBe(LeadStatus::Contacted);
});

it('limits Sales Person to owned leads and auto-assigns self on creation', function (): void {
    $salesPerson = p310User('demo04@salesflow.test');
    $otherSales = p310User('demo05@salesflow.test');

    $ownedLead = Lead::factory()->ownedBy($salesPerson)->create();
    $otherLead = Lead::factory()->ownedBy($otherSales)->create();

    expect($salesPerson->can('view', $ownedLead))->toBeTrue()
        ->and($salesPerson->can('view', $otherLead))->toBeFalse()
        ->and($salesPerson->can('assign', $ownedLead))->toBeFalse(); // Sales cannot re-assign owner

    // Self-owned creation via LeadEditor
    Livewire::actingAs($salesPerson)
        ->test(LeadEditor::class)
        ->set('form.fullName', 'Self Created Lead')
        ->set('form.email', 'self.lead@salesflow.test')
        ->set('form.phone', '0911223344')
        ->call('save')
        ->assertHasNoErrors();

    $created = Lead::query()->where('email', 'self.lead@salesflow.test')->firstOrFail();
    expect($created->owner_id)->toBe($salesPerson->getKey());
});

it('enforces read-only protection for Viewer role across all lead mutations', function (): void {
    $viewer = p310User('demo12@salesflow.test');
    $lead = Lead::query()->whereNull('deleted_at')->firstOrFail();

    expect($viewer->can('viewAny', Lead::class))->toBeTrue()
        ->and($viewer->can('view', $lead))->toBeTrue()
        ->and($viewer->can('create', Lead::class))->toBeFalse()
        ->and($viewer->can('update', $lead))->toBeFalse()
        ->and($viewer->can('delete', $lead))->toBeFalse()
        ->and($viewer->can('assign', $lead))->toBeFalse()
        ->and($viewer->can('convert', $lead))->toBeFalse();

    // Service level mutation attempt throws Policy/Authorization/ModelNotFound Exception
    /** @var LeadLifecycleService $lifecycleService */
    $lifecycleService = app(LeadLifecycleService::class);
    expect(fn () => $lifecycleService->delete($viewer, $lead->getKey(), 'Lý do xóa thử nghiệm'))
        ->toThrow(Exception::class);
});

it('verifies DatabaseSeeder idempotency for Lead module data', function (): void {
    $initialLeadCount = Lead::count();
    expect($initialLeadCount)->toBeGreaterThanOrEqual(30);

    // Re-run DatabaseSeeder
    $this->seed(DatabaseSeeder::class);

    expect(Lead::count())->toBe($initialLeadCount);
});
