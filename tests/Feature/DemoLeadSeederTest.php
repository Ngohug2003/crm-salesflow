<?php

declare(strict_types=1);

use App\Enums\LeadPriority;
use App\Enums\LeadStatus;
use App\Models\Department;
use App\Models\Lead;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('seeds thirty distributed demo leads idempotently', function (): void {
    $this->seed(DatabaseSeeder::class);
    $this->seed(DatabaseSeeder::class);

    $demoLeads = Lead::query()
        ->whereLike('email', 'lead.demo%@salesflow.test')
        ->with('owner:id,department_id')
        ->get();
    $departmentIds = Department::query()->pluck('id', 'code');

    expect($demoLeads)->toHaveCount(30)
        ->and($demoLeads->where('department_id', $departmentIds['SALES']))->toHaveCount(18)
        ->and($demoLeads->where('department_id', $departmentIds['MARKETING']))->toHaveCount(12)
        ->and($demoLeads->every(
            fn (Lead $lead): bool => $lead->owner !== null
                && $lead->owner->department_id === $lead->department_id,
        ))->toBeTrue()
        ->and($demoLeads->pluck('status')->unique())->toHaveCount(count(LeadStatus::cases()))
        ->and($demoLeads->pluck('priority')->unique())->toHaveCount(count(LeadPriority::cases()))
        ->and(DB::table('lead_tag')->count())->toBe(60)
        ->and($demoLeads->every(fn (Lead $lead): bool => $lead->tags()->count() === 2))->toBeTrue();
});
