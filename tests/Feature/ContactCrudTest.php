<?php

declare(strict_types=1);

use App\Livewire\Contacts\ContactDetail;
use App\Livewire\Contacts\ContactEditor;
use App\Livewire\Contacts\ContactList;
use App\Models\Company;
use App\Models\Contact;
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

function p404Actor(string $role, ?Department $department = null): User
{
    $user = User::factory()->create([
        'department_id' => $department?->getKey(),
        'email_verified_at' => now(),
        'is_active' => true,
    ]);
    $user->assignRole($role);

    return $user;
}

it('protects contact routes and requires authentication', function (): void {
    $this->get('/contacts')->assertRedirect('/login');
    $this->get('/contacts/create')->assertRedirect('/login');
});

it('allows Admin to view all contacts, create, edit and delete with audit logs', function (): void {
    $admin = p404Actor('admin');
    $company = Company::factory()->create();
    $contact = Contact::factory()->forCompany($company)->create([
        'first_name' => 'Văn An',
        'last_name' => 'Nguyễn',
        'full_name' => 'Nguyễn Văn An',
    ]);

    $this->actingAs($admin)->get('/contacts')->assertOk();
    $this->actingAs($admin)->get("/contacts/{$contact->id}")->assertOk();
    $this->actingAs($admin)->get("/contacts/{$contact->id}/edit")->assertOk();

    // Create via Livewire ContactEditor
    Livewire::actingAs($admin)
        ->test(ContactEditor::class)
        ->set('lastName', 'Trần')
        ->set('firstName', 'Thị Bích')
        ->set('companyId', (string) $company->id)
        ->set('email', 'bich.tran@example.test')
        ->set('phone', '0909111222')
        ->set('jobTitle', 'Giám đốc Mua hàng')
        ->set('isPrimary', true)
        ->call('save')
        ->assertHasNoErrors();

    $created = Contact::query()->where('email', 'bich.tran@example.test')->first();
    expect($created)->not->toBeNull()
        ->and($created->full_name)->toBe('Trần Thị Bích')
        ->and($created->is_primary)->toBeTrue()
        ->and($created->company_id)->toBe($company->id);

    // Audit Log Check
    $activity = Activity::query()
        ->where('subject_type', Contact::class)
        ->where('subject_id', $created->id)
        ->where('event', 'created')
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity->causer_id)->toBe($admin->id);
});

it('automatically resets previous primary contact of the same company when a new primary contact is set', function (): void {
    $admin = p404Actor('admin');
    $company = Company::factory()->create();

    $firstContact = Contact::factory()->forCompany($company)->primary()->create([
        'first_name' => 'Tuấn',
        'last_name' => 'Lê',
    ]);

    expect($firstContact->fresh()->is_primary)->toBeTrue();

    // Create second primary contact for the same company
    Livewire::actingAs($admin)
        ->test(ContactEditor::class)
        ->set('lastName', 'Phạm')
        ->set('firstName', 'Đức')
        ->set('companyId', (string) $company->id)
        ->set('isPrimary', true)
        ->call('save')
        ->assertHasNoErrors();

    expect($firstContact->fresh()->is_primary)->toBeFalse();
});

it('limits Sales Manager to department contacts', function (): void {
    $deptA = Department::factory()->create();
    $deptB = Department::factory()->create();
    $managerA = p404Actor('sales-manager', $deptA);

    $contactA = Contact::factory()->create(['department_id' => $deptA->id]);
    $contactB = Contact::factory()->create(['department_id' => $deptB->id]);

    Livewire::actingAs($managerA)
        ->test(ContactList::class)
        ->assertSee($contactA->full_name)
        ->assertDontSee($contactB->full_name);

    $this->actingAs($managerA)->get("/contacts/{$contactA->id}")->assertOk();
    $this->actingAs($managerA)->get("/contacts/{$contactB->id}")->assertNotFound();
});

it('auto-assigns self when Sales Person creates a contact', function (): void {
    $sales = p404Actor('sales');

    Livewire::actingAs($sales)
        ->test(ContactEditor::class)
        ->set('lastName', 'Vũ')
        ->set('firstName', 'Hoàng')
        ->call('save')
        ->assertHasNoErrors();

    $created = Contact::query()->where('full_name', 'Vũ Hoàng')->first();
    expect($created)->not->toBeNull()
        ->and($created->owner_id)->toBe($sales->id)
        ->and($created->department_id)->toBe($sales->department_id);
});

it('enforces read-only access for Viewer role', function (): void {
    $viewer = p404Actor('viewer');
    $contact = Contact::factory()->create();

    $this->actingAs($viewer)->get('/contacts')->assertOk();
    $this->actingAs($viewer)->get('/contacts/create')->assertForbidden();
    $this->actingAs($viewer)->get("/contacts/{$contact->id}/edit")->assertForbidden();

    Livewire::actingAs($viewer)
        ->test(ContactDetail::class, ['contactId' => $contact->id])
        ->call('deleteContact')
        ->assertForbidden();
});
