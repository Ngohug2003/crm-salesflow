<?php

declare(strict_types=1);

use App\Livewire\Companies\CompanyEditor;
use App\Livewire\Companies\CompanyList;
use App\Livewire\Contacts\ContactEditor;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Department;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(DatabaseSeeder::class);
});

/**
 * @return array<string, string>
 */
function p407Accounts(): array
{
    return [
        'super-admin' => 'admin@salesflow.test',
        'admin' => 'it.admin@salesflow.test',
        'sales-manager' => 'demo03@salesflow.test',
        'sales' => 'demo04@salesflow.test',
        'viewer' => 'demo12@salesflow.test',
    ];
}

function p407User(string $role): User
{
    return User::query()
        ->where('email', p407Accounts()[$role])
        ->firstOrFail();
}

it('verifies system seeds one active checkpoint account for all 5 roles', function (): void {
    foreach (p407Accounts() as $role => $email) {
        $user = User::query()->where('email', $email)->firstOrFail();

        expect($user->hasRole($role))->toBeTrue()
            ->and($user->is_active)->toBeTrue()
            ->and($user->email_verified_at)->not->toBeNull();
    }

    expect(p407User('admin')->department?->code)->toBe('IT');
});

it('enforces company data scope across all 5 roles', function (): void {
    $salesManager = p407User('sales-manager');
    $salesRep = p407User('sales');
    $viewer = p407User('viewer');

    $deptId = $salesManager->department_id;
    $otherDept = Department::query()->where('id', '!=', $deptId)->firstOrFail();

    // Company owned by sales Rep
    $ownedCompany = Company::factory()->create([
        'name' => 'Công ty Phụ Trách Trực Tiếp',
        'owner_id' => $salesRep->id,
        'department_id' => $deptId,
    ]);

    // Company in same department but different owner
    $colleague = User::factory()->create(['department_id' => $deptId, 'is_active' => true]);
    $sameDeptCompany = Company::factory()->create([
        'name' => 'Công ty Đồng Nghiệp Cùng Phòng',
        'owner_id' => $colleague->id,
        'department_id' => $deptId,
    ]);

    // Company in other department
    $otherOwner = User::factory()->create(['department_id' => $otherDept->id, 'is_active' => true]);
    $otherDeptCompany = Company::factory()->create([
        'name' => 'Công ty Phòng Ban Khác',
        'owner_id' => $otherOwner->id,
        'department_id' => $otherDept->id,
    ]);

    // 1. Super Admin sees all
    Livewire::actingAs(p407User('super-admin'))
        ->test(CompanyList::class)
        ->assertSee('Công ty Phụ Trách Trực Tiếp')
        ->assertSee('Công ty Đồng Nghiệp Cùng Phòng')
        ->assertSee('Công ty Phòng Ban Khác');

    // 2. Sales Manager sees same department, blocked from other department
    Livewire::actingAs($salesManager)
        ->test(CompanyList::class)
        ->assertSee('Công ty Phụ Trách Trực Tiếp')
        ->assertSee('Công ty Đồng Nghiệp Cùng Phòng')
        ->assertDontSee('Công ty Phòng Ban Khác');

    $this->actingAs($salesManager)
        ->get(route('companies.show', $otherDeptCompany))
        ->assertNotFound();

    // 3. Sales Rep sees owned only
    Livewire::actingAs($salesRep)
        ->test(CompanyList::class)
        ->assertSee('Công ty Phụ Trách Trực Tiếp')
        ->assertDontSee('Công ty Đồng Nghiệp Cùng Phòng')
        ->assertDontSee('Công ty Phòng Ban Khác');

    $this->actingAs($salesRep)
        ->get(route('companies.show', $sameDeptCompany))
        ->assertNotFound();

    // 4. Viewer can view list but forbidden from create/edit forms
    Livewire::actingAs($viewer)
        ->test(CompanyEditor::class)
        ->assertStatus(403);

    Livewire::actingAs($viewer)
        ->test(CompanyEditor::class, ['companyId' => $ownedCompany->id])
        ->assertStatus(403);
});

it('enforces contact data scope and primary contact toggle authorization', function (): void {
    $salesManager = p407User('sales-manager');
    $salesRep = p407User('sales');
    $viewer = p407User('viewer');

    $company = Company::factory()->create([
        'owner_id' => $salesRep->id,
        'department_id' => $salesRep->department_id,
    ]);

    $contact1 = Contact::factory()->create([
        'first_name' => 'An',
        'last_name' => 'Nguyễn',
        'full_name' => 'Nguyễn An',
        'company_id' => $company->id,
        'is_primary' => true,
        'owner_id' => $salesRep->id,
        'department_id' => $salesRep->department_id,
    ]);

    // Creating second contact for same company with is_primary = true resets contact1
    Livewire::actingAs($salesRep)
        ->test(ContactEditor::class)
        ->set('lastName', 'Trần')
        ->set('firstName', 'Bình')
        ->set('companyId', (string) $company->id)
        ->set('isPrimary', true)
        ->call('save')
        ->assertHasNoErrors();

    expect($contact1->fresh()->is_primary)->toBeFalse();

    $contact2 = Contact::query()->where('full_name', 'Trần Bình')->firstOrFail();
    expect($contact2->is_primary)->toBeTrue();

    // Viewer is forbidden from editing contact
    Livewire::actingAs($viewer)
        ->test(ContactEditor::class, ['contactId' => $contact2->id])
        ->assertStatus(403);

    // Sales Rep cannot view out-of-scope contact
    $otherDept = Department::query()->where('id', '!=', $salesRep->department_id)->firstOrFail();
    $otherOwner = User::factory()->create(['department_id' => $otherDept->id, 'is_active' => true]);
    $outOfScopeContact = Contact::factory()->create([
        'owner_id' => $otherOwner->id,
        'department_id' => $otherDept->id,
    ]);

    $this->actingAs($salesRep)
        ->get(route('contacts.show', $outOfScopeContact))
        ->assertNotFound();
});
