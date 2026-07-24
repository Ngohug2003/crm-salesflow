<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Contact;
use App\Models\Department;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
});

it('creates a contact with auto-incrementing BIGINT primary key', function (): void {
    $contact = Contact::factory()->create([
        'full_name' => 'Nguyễn Văn Nam',
        'email' => 'nam.nguyen@example.com',
        'phone' => '0909123456',
        'job_title' => 'Trưởng phòng Kinh doanh',
    ]);

    expect($contact->id)->toBeGreaterThan(0)
        ->and($contact->full_name)->toBe('Nguyễn Văn Nam')
        ->and($contact->email)->toBe('nam.nguyen@example.com')
        ->and($contact->job_title)->toBe('Trưởng phòng Kinh doanh');
});

it('links contact to company, owner, department and createdBy relationships', function (): void {
    $department = Department::factory()->create();
    $owner = User::factory()->create(['department_id' => $department->getKey()]);
    $company = Company::factory()->ownedBy($owner)->create();

    $contact = Contact::factory()->forCompany($company)->create([
        'full_name' => 'Trần Thị Thu',
        'is_primary' => true,
    ]);

    expect($contact->company->is($company))->toBeTrue()
        ->and($contact->owner->is($owner))->toBeTrue()
        ->and($contact->department->is($department))->toBeTrue()
        ->and($company->contacts->contains($contact))->toBeTrue()
        ->and(Contact::query()->primary()->count())->toBe(1);
});

it('supports soft deletes and keeps inactive contacts out of active scope', function (): void {
    $contact1 = Contact::factory()->create(['full_name' => 'Active Contact']);
    $contact2 = Contact::factory()->create(['full_name' => 'Trashed Contact']);

    $contact2->delete();

    expect($contact2->trashed())->toBeTrue()
        ->and(Contact::query()->active()->count())->toBe(1)
        ->and(Contact::query()->active()->first()->id)->toBe($contact1->id)
        ->and(Contact::withTrashed()->count())->toBe(2);
});

it('searches contacts by full name, email, phone or job title', function (): void {
    Contact::factory()->create([
        'full_name' => 'Lê Hoàng Hải',
        'email' => 'hai.le@vietcompany.test',
        'phone' => '0918777666',
        'job_title' => 'Giám đốc Công nghệ',
    ]);
    Contact::factory()->create([
        'full_name' => 'Phạm Minh Anh',
        'email' => 'anh.pham@dautub.test',
        'phone' => '0988555444',
        'job_title' => 'Trưởng phòng Kế toán',
    ]);

    expect(Contact::query()->search('Hoàng Hải')->count())->toBe(1)
        ->and(Contact::query()->search('hai.le@vietcompany')->count())->toBe(1)
        ->and(Contact::query()->search('0988555444')->count())->toBe(1)
        ->and(Contact::query()->search('Công nghệ')->count())->toBe(1)
        ->and(Contact::query()->search('Không tồn tại')->count())->toBe(0);
});
