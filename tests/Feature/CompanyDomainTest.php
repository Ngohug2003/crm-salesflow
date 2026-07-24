<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Department;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
});

it('creates a company with auto-incrementing BIGINT primary key', function (): void {
    $company = Company::factory()->create([
        'name' => 'Công ty Cổ phần Công nghệ ABC',
        'tax_code' => '0312345678',
        'annual_revenue' => 150000000.50,
    ]);

    expect($company->id)->toBeGreaterThan(0)
        ->and($company->name)->toBe('Công ty Cổ phần Công nghệ ABC')
        ->and($company->tax_code)->toBe('0312345678')
        ->and((float) $company->annual_revenue)->toBe(150000000.50);
});

it('links company to owner, department, createdBy and updatedBy relationships', function (): void {
    $department = Department::factory()->create();
    $owner = User::factory()->create(['department_id' => $department->getKey()]);
    $creator = User::factory()->create();

    $company = Company::factory()->create([
        'owner_id' => $owner->getKey(),
        'department_id' => $department->getKey(),
        'created_by' => $creator->getKey(),
        'updated_by' => $creator->getKey(),
    ]);

    expect($company->owner->is($owner))->toBeTrue()
        ->and($company->department->is($department))->toBeTrue()
        ->and($company->createdBy->is($creator))->toBeTrue()
        ->and($company->updatedBy->is($creator))->toBeTrue();
});

it('supports soft deletes and keeps inactive companies out of active scope', function (): void {
    $company1 = Company::factory()->create(['name' => 'Active Company']);
    $company2 = Company::factory()->create(['name' => 'Trashed Company']);

    $company2->delete();

    expect($company2->trashed())->toBeTrue()
        ->and(Company::query()->active()->count())->toBe(1)
        ->and(Company::query()->active()->first()->id)->toBe($company1->id)
        ->and(Company::withTrashed()->count())->toBe(2);
});

it('searches companies by name, tax code, email or phone', function (): void {
    Company::factory()->create([
        'name' => 'Công ty Giải pháp phần mềm Việt',
        'tax_code' => '0109998887',
        'email' => 'contact@vietsoftware.test',
        'phone' => '02439998888',
    ]);
    Company::factory()->create([
        'name' => 'Tập đoàn Đầu tư Sản xuất B',
        'tax_code' => '0301112223',
        'email' => 'info@dautub.test',
        'phone' => '02837776666',
    ]);

    expect(Company::query()->search('phần mềm')->count())->toBe(1)
        ->and(Company::query()->search('0109998887')->count())->toBe(1)
        ->and(Company::query()->search('contact@vietsoftware')->count())->toBe(1)
        ->and(Company::query()->search('02837776666')->count())->toBe(1)
        ->and(Company::query()->search('Không tồn tại')->count())->toBe(0);
});
