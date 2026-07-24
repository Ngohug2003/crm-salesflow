<?php

declare(strict_types=1);

use App\Data\LeadConversionData;
use App\Enums\LeadStatus;
use App\Exceptions\LeadConversionException;
use App\Models\Department;
use App\Models\Lead;
use App\Models\LeadStatusHistory;
use App\Models\User;
use App\Services\Contracts\LeadConversionContract;
use App\Services\LeadConversionService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
});

function p309Actor(string $role, ?Department $department = null): User
{
    $user = User::factory()->create([
        'department_id' => $department?->getKey(),
        'email_verified_at' => now(),
        'is_active' => true,
    ]);
    $user->assignRole($role);

    return $user;
}

it('binds LeadConversionContract to LeadConversionService', function (): void {
    $service = app(LeadConversionContract::class);

    expect($service)->toBeInstanceOf(LeadConversionService::class);
});

it('verifies conversion eligibility for a valid active lead within scope', function (): void {
    $department = Department::factory()->create();
    $admin = p309Actor('admin', $department);
    $lead = Lead::factory()->ownedBy($admin)->create([
        'full_name' => 'Nguyễn Văn A',
        'email' => 'nguyenvana@example.com',
        'phone' => '0901234567',
        'status' => LeadStatus::Qualified,
    ]);

    /** @var LeadConversionContract $service */
    $service = app(LeadConversionContract::class);
    $eligibility = $service->checkEligibility($admin, $lead);

    expect($eligibility)->toBe([
        'eligible' => true,
        'reason' => null,
        'reason_code' => null,
    ]);
});

it('rejects eligibility for an already converted lead', function (): void {
    $admin = p309Actor('admin');
    $lead = Lead::factory()->ownedBy($admin)->create([
        'full_name' => 'Nguyễn Văn B',
        'email' => 'nguyenvanb@example.com',
        'status' => LeadStatus::Converted,
        'converted_at' => now(),
    ]);

    /** @var LeadConversionContract $service */
    $service = app(LeadConversionContract::class);
    $eligibility = $service->checkEligibility($admin, $lead);

    expect($eligibility['eligible'])->toBeFalse()
        ->and($eligibility['reason_code'])->toBe('already_converted')
        ->and($eligibility['reason'])->toBe('Lead đã được chuyển đổi trước đó.');
});

it('rejects eligibility for a soft-deleted lead', function (): void {
    $admin = p309Actor('admin');
    $lead = Lead::factory()->ownedBy($admin)->create([
        'full_name' => 'Nguyễn Văn C',
        'email' => 'nguyenvanc@example.com',
        'status' => LeadStatus::Qualified,
    ]);
    $lead->delete();

    /** @var LeadConversionContract $service */
    $service = app(LeadConversionContract::class);
    $eligibility = $service->checkEligibility($admin, $lead);

    expect($eligibility['eligible'])->toBeFalse()
        ->and($eligibility['reason_code'])->toBe('trashed')
        ->and($eligibility['reason'])->toBe('Không thể chuyển đổi Lead đã bị xóa.');
});

it('rejects eligibility for a lead missing contact information', function (): void {
    $admin = p309Actor('admin');
    $lead = Lead::factory()->ownedBy($admin)->create([
        'full_name' => '',
        'email' => '',
        'phone' => '',
        'secondary_phone' => '',
        'status' => LeadStatus::Qualified,
    ]);

    /** @var LeadConversionContract $service */
    $service = app(LeadConversionContract::class);
    $eligibility = $service->checkEligibility($admin, $lead);

    expect($eligibility['eligible'])->toBeFalse()
        ->and($eligibility['reason_code'])->toBe('missing_contact')
        ->and($eligibility['reason'])->toBe('Lead phải có họ tên và thông tin liên hệ (email hoặc số điện thoại).');
});

it('rejects eligibility when actor lacks conversion permission or scope', function (): void {
    $deptA = Department::factory()->create();
    $deptB = Department::factory()->create();
    $sales = p309Actor('sales', $deptA);
    $leadInDeptB = Lead::factory()->ownedBy(p309Actor('sales', $deptB))->create([
        'full_name' => 'Nguyễn Văn D',
        'email' => 'nguyenvand@example.com',
        'status' => LeadStatus::Qualified,
    ]);

    /** @var LeadConversionContract $service */
    $service = app(LeadConversionContract::class);

    $eligibility = $service->checkEligibility($sales, $leadInDeptB);

    expect($eligibility['eligible'])->toBeFalse()
        ->and($eligibility['reason_code'])->toBe('unauthorized');
});

it('converts an eligible lead and records status history and audit log', function (): void {
    $admin = p309Actor('admin');
    $lead = Lead::factory()->ownedBy($admin)->create([
        'full_name' => 'Khách Hàng Đủ Điều Kiện',
        'email' => 'khachhang@example.com',
        'phone' => '0912345678',
        'status' => LeadStatus::Qualified,
    ]);

    $data = new LeadConversionData(
        createCompany: true,
        companyName: 'Công ty TNHH Mới',
        createContact: true,
        createOpportunity: true,
        opportunityName: 'Cơ hội từ Lead',
        estimatedValue: 50000000.0,
    );

    /** @var LeadConversionContract $service */
    $service = app(LeadConversionContract::class);
    $convertedLead = $service->convert($admin, $lead->getKey(), $data);

    expect($convertedLead->status)->toBe(LeadStatus::Converted)
        ->and($convertedLead->converted_at)->not->toBeNull();

    // Verify Status History
    $history = LeadStatusHistory::query()
        ->where('lead_id', $lead->getKey())
        ->where('to_status', LeadStatus::Converted)
        ->first();

    expect($history)->not->toBeNull()
        ->and($history->from_status)->toBe(LeadStatus::Qualified)
        ->and($history->changed_by)->toBe($admin->getKey());

    // Verify Audit Log
    $activity = Activity::query()
        ->where('subject_type', Lead::class)
        ->where('subject_id', $lead->getKey())
        ->where('event', 'converted')
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity->causer_id)->toBe($admin->getKey())
        ->and($activity->properties['new']['status'])->toBe('converted')
        ->and($activity->properties['create_company'])->toBeTrue();
});

it('throws LeadConversionException when attempting to convert an ineligible lead', function (): void {
    $admin = p309Actor('admin');
    $lead = Lead::factory()->ownedBy($admin)->create([
        'full_name' => 'Lead Đã Converted',
        'email' => 'converted@example.com',
        'status' => LeadStatus::Converted,
        'converted_at' => now(),
    ]);

    $data = new LeadConversionData;

    /** @var LeadConversionContract $service */
    $service = app(LeadConversionContract::class);

    expect(fn () => $service->convert($admin, $lead->getKey(), $data))
        ->toThrow(LeadConversionException::class, 'Lead đã được chuyển đổi trước đó.');
});

it('throws AuthorizationException when non-scoped actor attempts to convert lead', function (): void {
    $deptA = Department::factory()->create();
    $deptB = Department::factory()->create();
    $salesA = p309Actor('sales', $deptA);
    $salesB = p309Actor('sales', $deptB);

    $leadB = Lead::factory()->ownedBy($salesB)->create([
        'full_name' => 'Lead Thuộc Dept B',
        'email' => 'leadb@example.com',
        'status' => LeadStatus::Qualified,
    ]);

    $data = new LeadConversionData;

    /** @var LeadConversionContract $service */
    $service = app(LeadConversionContract::class);

    expect(fn () => $service->convert($salesA, $leadB->getKey(), $data))
        ->toThrow(ModelNotFoundException::class);
});
