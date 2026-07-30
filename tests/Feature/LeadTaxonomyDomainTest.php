<?php

declare(strict_types=1);

use App\Models\LeadSource;
use App\Models\Tag;
use Database\Seeders\LeadTaxonomySeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('creates lead sources and tags with auto-incrementing identifiers and typed state', function (): void {
    $source = LeadSource::factory()->create([
        'is_active' => true,
        'sort_order' => 15,
    ]);
    $tag = Tag::factory()->create([
        'is_active' => false,
        'sort_order' => 25,
    ]);

    expect($source->getKey())->toBeInt()->toBeGreaterThan(0)
        ->and($source->is_active)->toBeTrue()
        ->and($source->sort_order)->toBe(15)
        ->and($tag->getKey())->toBeInt()->toBeGreaterThan(0)
        ->and($tag->is_active)->toBeFalse()
        ->and($tag->sort_order)->toBe(25);
});

it('stores the taxonomy fields required by lead forms and filters', function (): void {
    expect(Schema::hasColumns('lead_sources', [
        'id',
        'name',
        'code',
        'description',
        'color',
        'is_active',
        'sort_order',
        'created_at',
        'updated_at',
    ]))->toBeTrue()
        ->and(Schema::hasColumns('tags', [
            'id',
            'name',
            'slug',
            'description',
            'color',
            'is_active',
            'sort_order',
            'created_at',
            'updated_at',
        ]))->toBeTrue();
});

it('filters and orders active lead sources', function (): void {
    $second = LeadSource::factory()->create(['name' => 'Second', 'sort_order' => 20]);
    $first = LeadSource::factory()->create(['name' => 'First', 'sort_order' => 10]);
    LeadSource::factory()->inactive()->create(['sort_order' => 5]);

    expect(LeadSource::query()->active()->ordered()->pluck('id')->all())
        ->toBe([$first->getKey(), $second->getKey()]);
});

it('filters and orders active tags', function (): void {
    $sameOrderSecond = Tag::factory()->create(['name' => 'Beta', 'sort_order' => 10]);
    $sameOrderFirst = Tag::factory()->create(['name' => 'Alpha', 'sort_order' => 10]);
    Tag::factory()->inactive()->create(['sort_order' => 5]);

    expect(Tag::query()->active()->ordered()->pluck('id')->all())
        ->toBe([$sameOrderFirst->getKey(), $sameOrderSecond->getKey()]);
});

it('enforces unique business keys for lead sources', function (): void {
    LeadSource::factory()->create(['code' => 'WEBSITE']);

    expect(fn () => LeadSource::factory()->create(['code' => 'WEBSITE']))
        ->toThrow(QueryException::class);
});

it('enforces unique business keys for tags', function (): void {
    Tag::factory()->create(['slug' => 'vip']);

    expect(fn () => Tag::factory()->create(['slug' => 'vip']))
        ->toThrow(QueryException::class);
});

it('seeds and repairs the default taxonomy idempotently', function (): void {
    $this->seed(LeadTaxonomySeeder::class);

    LeadSource::query()->where('code', 'WEBSITE')->update([
        'name' => 'Outdated',
        'is_active' => false,
    ]);
    Tag::query()->where('slug', 'vip')->update([
        'name' => 'Outdated',
        'is_active' => false,
    ]);

    $this->seed(LeadTaxonomySeeder::class);

    expect(LeadSource::query()->count())->toBe(8)
        ->and(Tag::query()->count())->toBe(6)
        ->and(LeadSource::query()->active()->ordered()->pluck('code')->all())->toBe([
            'WEBSITE',
            'FACEBOOK',
            'ZALO',
            'REFERRAL',
            'HOTLINE',
            'EVENT',
            'PARTNER',
            'MANUAL',
        ])
        ->and(Tag::query()->active()->ordered()->pluck('slug')->all())->toBe([
            'moi',
            'tiem-nang-cao',
            'vip',
            'can-cham-soc',
            'da-xac-thuc',
            'khach-hang-cu',
        ])
        ->and(LeadSource::query()->where('code', 'WEBSITE')->sole()->name)->toBe('Website')
        ->and(Tag::query()->where('slug', 'vip')->sole()->name)->toBe('VIP');
});
