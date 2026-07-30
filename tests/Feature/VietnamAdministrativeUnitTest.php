<?php

declare(strict_types=1);

use App\Livewire\Companies\CompanyEditor;
use App\Livewire\Contacts\ContactEditor;
use App\Livewire\Leads\LeadEditor;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Lead;
use App\Models\Province;
use App\Models\User;
use App\Models\Ward;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\VietnamAdministrativeUnitSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function administrativeUnitActor(): User
{
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'is_active' => true,
    ]);
    $user->assignRole('admin');

    return $user;
}

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
});

it('imports the official two-level dataset idempotently and backfills safe legacy addresses', function (): void {
    $legacyCompany = Company::factory()->create([
        'province_id' => null,
        'ward_id' => null,
        'province' => 'Thành phố Hà Nội',
        'city' => 'Phường Ba Đình',
    ]);

    $this->seed(VietnamAdministrativeUnitSeeder::class);

    expect(Province::query()->count())->toBe(34)
        ->and(Ward::query()->count())->toBe(3321)
        ->and(Ward::query()->whereHas('province')->count())->toBe(3321);

    $legacyCompany->refresh();
    expect($legacyCompany->province_id)->not->toBeNull()
        ->and($legacyCompany->ward_id)->not->toBeNull()
        ->and($legacyCompany->provinceUnit?->full_name)->toBe('Thành phố Hà Nội')
        ->and($legacyCompany->ward?->full_name)->toBe('Phường Ba Đình');

    $this->seed(VietnamAdministrativeUnitSeeder::class);

    expect(Province::query()->count())->toBe(34)
        ->and(Ward::query()->count())->toBe(3321);
});

it('imports administrative units through the artisan command', function (): void {
    $this->artisan('administrative-units:import', ['--no-backfill' => true])
        ->expectsOutput('Đã import 34 tỉnh/thành phố.')
        ->expectsOutput('Đã import 3321 phường/xã.')
        ->assertSuccessful();
});

it('uses dependent province and ward selects on the Lead editor', function (): void {
    $this->seed(VietnamAdministrativeUnitSeeder::class);
    $actor = administrativeUnitActor();
    $haNoi = Province::query()->where('code', '01')->firstOrFail();
    $baDinh = Ward::query()->where('code', '00004')->firstOrFail();
    $otherWard = Ward::query()->where('province_id', '!=', $haNoi->getKey())->firstOrFail();

    Livewire::actingAs($actor)
        ->test(LeadEditor::class)
        ->assertSee('Tìm Tỉnh/Thành phố…')
        ->set('form.fullName', 'Lead địa giới Việt Nam')
        ->set('form.provinceId', (string) $haNoi->getKey())
        ->assertSee('Tìm Phường/Xã…')
        ->set('form.wardId', (string) $otherWard->getKey())
        ->call('save')
        ->assertHasErrors(['form.wardId'])
        ->set('form.wardId', (string) $baDinh->getKey())
        ->call('save')
        ->assertHasNoErrors();

    $lead = Lead::query()->where('full_name', 'Lead địa giới Việt Nam')->firstOrFail();
    expect($lead->province_id)->toBe($haNoi->getKey())
        ->and($lead->ward_id)->toBe($baDinh->getKey())
        ->and($lead->province)->toBe('Thành phố Hà Nội')
        ->and($lead->city)->toBe('Phường Ba Đình')
        ->and($lead->country)->toBe('Việt Nam');
});

it('stores normalized administrative units from Company and Contact editors', function (): void {
    $this->seed(VietnamAdministrativeUnitSeeder::class);
    $actor = administrativeUnitActor();
    $province = Province::query()->where('code', '79')->firstOrFail();
    $ward = Ward::query()->where('province_id', $province->getKey())->orderBy('code')->firstOrFail();

    Livewire::actingAs($actor)
        ->test(CompanyEditor::class)
        ->set('name', 'Công ty địa giới chuẩn')
        ->set('address', '123 Đường thử nghiệm')
        ->set('provinceId', (string) $province->getKey())
        ->set('wardId', (string) $ward->getKey())
        ->call('save')
        ->assertHasNoErrors();

    $company = Company::query()->where('name', 'Công ty địa giới chuẩn')->firstOrFail();
    expect($company->province_id)->toBe($province->getKey())
        ->and($company->ward_id)->toBe($ward->getKey())
        ->and($company->province)->toBe($province->full_name)
        ->and($company->city)->toBe($ward->full_name);

    Livewire::actingAs($actor)
        ->test(ContactEditor::class)
        ->set('lastName', 'Nguyễn')
        ->set('firstName', 'Địa Giới')
        ->set('companyId', (string) $company->getKey())
        ->set('provinceId', (string) $province->getKey())
        ->set('wardId', (string) $ward->getKey())
        ->call('save')
        ->assertHasNoErrors();

    $contact = Contact::query()->where('full_name', 'Nguyễn Địa Giới')->firstOrFail();
    expect($contact->province_id)->toBe($province->getKey())
        ->and($contact->ward_id)->toBe($ward->getKey())
        ->and($contact->province)->toBe($province->full_name)
        ->and($contact->city)->toBe($ward->full_name);
});
