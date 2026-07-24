<?php

declare(strict_types=1);

use App\Livewire\Companies\CompanyEditor;
use App\Livewire\Contacts\ContactEditor;
use App\Models\Company;
use App\Models\Contact;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
});

function p405Actor(string $role): User
{
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'is_active' => true,
    ]);
    $user->assignRole($role);

    return $user;
}

it('blocks creating duplicate Company by tax code unless confirmed with valid override reason', function (): void {
    $admin = p405Actor('admin');
    Company::factory()->create([
        'name' => 'Công ty Đầu tiên',
        'tax_code' => '0311223344',
    ]);

    // Try creating with same tax code without override reason
    Livewire::actingAs($admin)
        ->test(CompanyEditor::class)
        ->set('name', 'Công ty Trùng MST')
        ->set('taxCode', '0311223344')
        ->call('save')
        ->assertSet('showDuplicateWarning', true)
        ->assertSet('duplicateCandidates.0.name', 'Công ty Đầu tiên');

    // Confirm duplicate with short reason (< 10 chars)
    Livewire::actingAs($admin)
        ->test(CompanyEditor::class)
        ->set('name', 'Công ty Trùng MST')
        ->set('taxCode', '0311223344')
        ->call('save')
        ->set('duplicateOverrideReason', 'Ngắn quá')
        ->call('confirmDuplicateSave')
        ->assertHasErrors(['duplicateOverrideReason']);

    // Confirm duplicate with valid reason (>= 10 chars)
    Livewire::actingAs($admin)
        ->test(CompanyEditor::class)
        ->set('name', 'Công ty Trùng MST')
        ->set('taxCode', '0311223344')
        ->call('save')
        ->set('duplicateOverrideReason', 'Tạo doanh nghiệp trùng phục vụ kiểm thử nghiệp vụ đặc thù')
        ->call('confirmDuplicateSave')
        ->assertHasNoErrors();

    $created = Company::query()->where('name', 'Công ty Trùng MST')->first();
    expect($created)->not->toBeNull();

    $activity = Activity::query()
        ->where('subject_type', Company::class)
        ->where('subject_id', $created->id)
        ->where('event', 'created')
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity->properties->get('new')['duplicate_override']['reason'])->toBe('Tạo doanh nghiệp trùng phục vụ kiểm thử nghiệp vụ đặc thù');
});

it('blocks creating duplicate Contact by email unless confirmed with valid override reason', function (): void {
    $admin = p405Actor('admin');
    Contact::factory()->create([
        'first_name' => 'An',
        'last_name' => 'Nguyễn',
        'full_name' => 'Nguyễn An',
        'email' => 'duplicate.contact@example.test',
    ]);

    // Try creating contact with same email
    Livewire::actingAs($admin)
        ->test(ContactEditor::class)
        ->set('lastName', 'Trần')
        ->set('firstName', 'Bình')
        ->set('email', 'duplicate.contact@example.test')
        ->call('save')
        ->assertSet('showDuplicateWarning', true)
        ->assertSet('duplicateCandidates.0.full_name', 'Nguyễn An');

    // Confirm with valid reason (>= 10 chars)
    Livewire::actingAs($admin)
        ->test(ContactEditor::class)
        ->set('lastName', 'Trần')
        ->set('firstName', 'Bình')
        ->set('email', 'duplicate.contact@example.test')
        ->call('save')
        ->set('duplicateOverrideReason', 'Tạo người liên hệ trùng email do dùng chung hòm thư chi nhánh')
        ->call('confirmDuplicateSave')
        ->assertHasNoErrors();

    $created = Contact::query()->where('full_name', 'Trần Bình')->first();
    expect($created)->not->toBeNull();

    $activity = Activity::query()
        ->where('subject_type', Contact::class)
        ->where('subject_id', $created->id)
        ->where('event', 'created')
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity->properties->get('new')['duplicate_override']['reason'])->toBe('Tạo người liên hệ trùng email do dùng chung hòm thư chi nhánh');
});
