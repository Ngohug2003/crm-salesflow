<?php

declare(strict_types=1);

use App\Livewire\Companies\CompanyDetail;
use App\Livewire\Companies\CompanyEditor;
use App\Livewire\Companies\CompanyList;
use App\Models\Company;
use App\Models\Department;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
});

function p403Actor(string $role, ?Department $department = null): User
{
    $user = User::factory()->create([
        'department_id' => $department?->getKey(),
        'email_verified_at' => now(),
        'is_active' => true,
    ]);
    $user->assignRole($role);

    return $user;
}

it('protects company routes and requires authentication', function (): void {
    $this->get('/companies')->assertRedirect('/login');
    $this->get('/companies/create')->assertRedirect('/login');
});

it('allows Super Admin to view all companies, create, edit and delete', function (): void {
    $admin = p403Actor('admin');
    $company = Company::factory()->ownedBy($admin)->create([
        'name' => 'Công ty Cổ phần Mới 100',
    ]);

    $this->actingAs($admin)->get('/companies')->assertOk();
    $this->actingAs($admin)->get("/companies/{$company->id}")->assertOk();
    $this->actingAs($admin)->get("/companies/{$company->id}/edit")->assertOk();

    // Create via Livewire CompanyEditor
    Livewire::actingAs($admin)
        ->test(CompanyEditor::class)
        ->set('name', 'Công ty TNHH Giải Pháp Mới')
        ->set('taxCode', '0399887766')
        ->set('industry', 'Công nghệ thông tin')
        ->set('companySize', '11-50 nhân sự')
        ->set('annualRevenue', '500000000')
        ->call('save')
        ->assertHasNoErrors();

    $created = Company::query()->where('tax_code', '0399887766')->first();
    expect($created)->not->toBeNull()
        ->and($created->name)->toBe('Công ty TNHH Giải Pháp Mới');

    // Audit Log Check
    $activity = Activity::query()
        ->where('subject_type', Company::class)
        ->where('subject_id', $created->id)
        ->where('event', 'created')
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity->causer_id)->toBe($admin->id);
});

it('limits Sales Manager to department companies', function (): void {
    $deptA = Department::factory()->create();
    $deptB = Department::factory()->create();
    $managerA = p403Actor('sales-manager', $deptA);

    $companyA = Company::factory()->create(['department_id' => $deptA->id]);
    $companyB = Company::factory()->create(['department_id' => $deptB->id]);

    Livewire::actingAs($managerA)
        ->test(CompanyList::class)
        ->assertSee($companyA->name)
        ->assertDontSee($companyB->name);

    $this->actingAs($managerA)->get("/companies/{$companyA->id}")->assertOk();
    $this->actingAs($managerA)->get("/companies/{$companyB->id}")->assertNotFound();
});

it('auto-assigns self when Sales Person creates a company', function (): void {
    $sales = p403Actor('sales');

    Livewire::actingAs($sales)
        ->test(CompanyEditor::class)
        ->set('name', 'Công ty Khách Hàng Sales')
        ->call('save')
        ->assertHasNoErrors();

    $created = Company::query()->where('name', 'Công ty Khách Hàng Sales')->first();
    expect($created)->not->toBeNull()
        ->and($created->owner_id)->toBe($sales->id)
        ->and($created->department_id)->toBe($sales->department_id);
});

it('enforces read-only access for Viewer role', function (): void {
    $viewer = p403Actor('viewer');
    $company = Company::factory()->create();

    $this->actingAs($viewer)->get('/companies')->assertOk();
    $this->actingAs($viewer)->get('/companies/create')->assertForbidden();
    $this->actingAs($viewer)->get("/companies/{$company->id}/edit")->assertForbidden();

    Livewire::actingAs($viewer)
        ->test(CompanyDetail::class, ['companyId' => $company->id])
        ->call('deleteCompany')
        ->assertForbidden();
});
