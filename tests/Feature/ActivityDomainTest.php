<?php

declare(strict_types=1);

use App\Enums\ActivityType;
use App\Models\Activity;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\User;
use Database\Seeders\DemoActivitySeeder;
use Database\Seeders\DemoOpportunitySeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(DemoOpportunitySeeder::class);
});

it('creates Activity with PostgreSQL BIGINT key and casts ActivityType enum', function (): void {
    $user = User::factory()->create();
    $opp = Opportunity::query()->firstOrFail();

    $activity = Activity::query()->create([
        'activity_type' => ActivityType::Call,
        'subject_type' => Opportunity::class,
        'subject_id' => $opp->id,
        'title' => 'Cuộc gọi trao đổi nhu cầu tích hợp ERP',
        'description' => 'Khách hàng mong muốn demo tính năng trong tuần này.',
        'user_id' => $user->id,
        'performed_at' => now(),
        'duration_minutes' => 30,
        'location' => 'Điện thoại',
    ]);

    expect($activity->id)->toBeInt()
        ->and($activity->activity_type)->toBe(ActivityType::Call)
        ->and($activity->activity_type->label())->toBe('Cuộc gọi')
        ->and($activity->duration_minutes)->toBe(30);
});

it('supports polymorphic morphTo and morphMany relationships for Lead, Company, Contact, Opportunity', function (): void {
    $user = User::factory()->create();

    $lead = Lead::factory()->create();
    $company = Company::factory()->create();
    $contact = Contact::factory()->create();
    $opp = Opportunity::factory()->create();

    $actLead = Activity::factory()->create(['subject_type' => Lead::class, 'subject_id' => $lead->id, 'user_id' => $user->id]);
    $actCompany = Activity::factory()->create(['subject_type' => Company::class, 'subject_id' => $company->id, 'user_id' => $user->id]);
    $actContact = Activity::factory()->create(['subject_type' => Contact::class, 'subject_id' => $contact->id, 'user_id' => $user->id]);
    $actOpp = Activity::factory()->create(['subject_type' => Opportunity::class, 'subject_id' => $opp->id, 'user_id' => $user->id]);

    expect($actLead->subject->id)->toBe($lead->id)
        ->and($actCompany->subject->id)->toBe($company->id)
        ->and($actContact->subject->id)->toBe($contact->id)
        ->and($actOpp->subject->id)->toBe($opp->id);

    expect($lead->activities->count())->toBeGreaterThanOrEqual(1)
        ->and($company->activities->count())->toBeGreaterThanOrEqual(1)
        ->and($contact->activities->count())->toBeGreaterThanOrEqual(1)
        ->and($opp->activities->count())->toBeGreaterThanOrEqual(1);
});

it('supports soft deletes and restore for Activity', function (): void {
    $user = User::factory()->create();
    $opp = Opportunity::query()->firstOrFail();

    $activity = Activity::factory()->create([
        'subject_type' => Opportunity::class,
        'subject_id' => $opp->id,
        'user_id' => $user->id,
    ]);

    $activity->delete();
    expect($activity->trashed())->toBeTrue();

    $activity->restore();
    expect($activity->trashed())->toBeFalse();
});

it('seeds demo activities via DemoActivitySeeder', function (): void {
    $this->seed(DemoActivitySeeder::class);

    expect(Activity::query()->count())->toBeGreaterThanOrEqual(5);
});
