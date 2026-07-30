<?php

declare(strict_types=1);

use App\Enums\LeadPriority;
use App\Enums\LeadStatus;
use App\Models\Department;
use App\Models\Lead;
use App\Models\LeadSource;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('defines the lead status and priority catalogs with Vietnamese labels', function (): void {
    expect(array_column(LeadStatus::cases(), 'value'))->toBe([
        'new',
        'contacted',
        'qualified',
        'unqualified',
        'converted',
        'lost',
    ])->and(LeadStatus::Qualified->label())->toBe('Đủ điều kiện')
        ->and(array_column(LeadPriority::cases(), 'value'))->toBe([
            'low',
            'medium',
            'high',
            'urgent',
        ])
        ->and(LeadPriority::Urgent->label())->toBe('Khẩn cấp');
});

it('stores the fields required by the lead domain', function (): void {
    expect(Schema::hasColumns('leads', [
        'id',
        'lead_source_id',
        'owner_id',
        'department_id',
        'full_name',
        'email',
        'phone',
        'secondary_phone',
        'company_name',
        'job_title',
        'website',
        'address',
        'city',
        'province',
        'country',
        'status',
        'priority',
        'estimated_value',
        'notes',
        'converted_at',
        'created_by',
        'updated_by',
        'created_at',
        'updated_at',
        'deleted_at',
    ]))->toBeTrue()
        ->and(Schema::hasColumns('lead_tag', [
            'lead_id',
            'tag_id',
            'created_at',
            'updated_at',
        ]))->toBeTrue();
});

it('casts domain values and applies database defaults', function (): void {
    $lead = Lead::query()->create([
        'full_name' => 'Nguyễn Văn Lead',
        'estimated_value' => '1250000.50',
    ])->refresh();

    expect($lead->getKey())->toBeInt()->toBeGreaterThan(0)
        ->and($lead->status)->toBe(LeadStatus::New)
        ->and($lead->priority)->toBe(LeadPriority::Medium)
        ->and($lead->estimated_value)->toBe('1250000.50')
        ->and($lead->converted_at)->toBeNull();
});

it('links a lead to its source owner department and audit users', function (): void {
    $department = Department::factory()->create();
    $owner = User::factory()->create(['department_id' => $department->getKey()]);
    $actor = User::factory()->create();
    $source = LeadSource::factory()->create();
    $lead = Lead::factory()->ownedBy($owner)->createdBy($actor)->create([
        'lead_source_id' => $source->getKey(),
    ]);

    expect($lead->source->is($source))->toBeTrue()
        ->and($lead->owner->is($owner))->toBeTrue()
        ->and($lead->department->is($department))->toBeTrue()
        ->and($lead->createdBy->is($actor))->toBeTrue()
        ->and($lead->updatedBy->is($actor))->toBeTrue()
        ->and($source->leads()->sole()->is($lead))->toBeTrue()
        ->and($owner->ownedLeads()->sole()->is($lead))->toBeTrue()
        ->and($department->leads()->sole()->is($lead))->toBeTrue()
        ->and($actor->createdLeads()->sole()->is($lead))->toBeTrue()
        ->and($actor->updatedLeads()->sole()->is($lead))->toBeTrue();
});

it('attaches multiple tags with timestamps and prevents duplicate membership', function (): void {
    $lead = Lead::factory()->create();
    $first = Tag::factory()->create();
    $second = Tag::factory()->create();

    $lead->tags()->attach([$first->getKey(), $second->getKey()]);

    expect($lead->tags()->pluck('tags.id')->all())->toBe([
        $first->getKey(),
        $second->getKey(),
    ])->and($first->leads()->sole()->is($lead))->toBeTrue()
        ->and(DB::table('lead_tag')->where('lead_id', $lead->getKey())->whereNotNull('created_at')->count())->toBe(2)
        ->and(fn () => $lead->tags()->attach($first->getKey()))->toThrow(QueryException::class);
});

it('keeps tags during soft delete and cascades the pivot on force delete', function (): void {
    $lead = Lead::factory()->create();
    $tag = Tag::factory()->create();
    $lead->tags()->attach($tag);

    $lead->delete();

    $this->assertSoftDeleted('leads', ['id' => $lead->getKey()]);
    expect(DB::table('lead_tag')->where('lead_id', $lead->getKey())->count())->toBe(1);

    $lead->restore();

    expect(Lead::query()->find($lead->getKey()))->not->toBeNull();

    $lead->forceDelete();

    $this->assertDatabaseMissing('lead_tag', ['lead_id' => $lead->getKey()]);
});

it('nulls optional references when related records are removed technically', function (): void {
    $department = Department::factory()->create();
    $owner = User::factory()->create(['department_id' => $department->getKey()]);
    $actor = User::factory()->create();
    $source = LeadSource::factory()->create();
    $lead = Lead::factory()->create([
        'lead_source_id' => $source->getKey(),
        'owner_id' => $owner->getKey(),
        'department_id' => $department->getKey(),
        'created_by' => $actor->getKey(),
        'updated_by' => $actor->getKey(),
    ]);

    $source->delete();
    $owner->delete();
    $department->delete();
    $actor->delete();

    $lead->refresh();

    expect($lead->lead_source_id)->toBeNull()
        ->and($lead->owner_id)->toBeNull()
        ->and($lead->department_id)->toBeNull()
        ->and($lead->created_by)->toBeNull()
        ->and($lead->updated_by)->toBeNull();
});

it('provides consistent factory states for assignment and conversion', function (): void {
    $department = Department::factory()->create();
    $owner = User::factory()->create(['department_id' => $department->getKey()]);
    $lead = Lead::factory()
        ->ownedBy($owner)
        ->createdBy($owner)
        ->withPriority(LeadPriority::Urgent)
        ->converted()
        ->create();

    expect($lead->owner_id)->toBe($owner->getKey())
        ->and($lead->department_id)->toBe($department->getKey())
        ->and($lead->created_by)->toBe($owner->getKey())
        ->and($lead->updated_by)->toBe($owner->getKey())
        ->and($lead->priority)->toBe(LeadPriority::Urgent)
        ->and($lead->status)->toBe(LeadStatus::Converted)
        ->and($lead->converted_at)->not->toBeNull();
});

it('creates the indexes used by lead filtering and data scope', function (): void {
    $indexes = collect(Schema::getIndexes('leads'))->pluck('name')->all();

    expect($indexes)->toContain(
        'leads_status_created_at_index',
        'leads_owner_id_status_index',
        'leads_department_id_status_index',
        'leads_lead_source_id_status_index',
        'leads_priority_status_index',
        'leads_email_index',
        'leads_phone_index',
        'leads_created_at_index',
    );
});
