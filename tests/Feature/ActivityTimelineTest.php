<?php

declare(strict_types=1);

use App\Enums\ActivityType;
use App\Livewire\Customers\CustomerTimelineFeed;
use App\Models\Activity;
use App\Models\Opportunity;
use App\Models\User;
use App\Services\ActivityManagementService;
use Database\Seeders\DemoOpportunitySeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(DemoOpportunitySeeder::class);
});

function p602Actor(string $role): User
{
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'is_active' => true,
    ]);
    $user->assignRole($role);

    return $user;
}

it('creates activity for opportunity via ActivityManagementService with audit log', function (): void {
    $admin = p602Actor('admin');
    $opp = Opportunity::query()->firstOrFail();

    /** @var ActivityManagementService $service */
    $service = app(ActivityManagementService::class);

    $activity = $service->createActivity($admin, $opp, [
        'activity_type' => ActivityType::Meeting,
        'title' => 'Cuộc họp trao đổi đề xuất hợp đồng',
        'description' => 'Khách hàng đồng ý các điều khoản bảo hành.',
        'duration_minutes' => 45,
        'location' => 'Zoom',
    ]);

    expect($activity->title)->toBe('Cuộc họp trao đổi đề xuất hợp đồng')
        ->and($activity->activity_type)->toBe(ActivityType::Meeting)
        ->and($activity->subject_id)->toBe($opp->id);
});

it('updates and soft deletes activity via ActivityManagementService', function (): void {
    $admin = p602Actor('admin');
    $opp = Opportunity::query()->firstOrFail();

    /** @var ActivityManagementService $service */
    $service = app(ActivityManagementService::class);

    $activity = $service->createActivity($admin, $opp, [
        'activity_type' => ActivityType::Call,
        'title' => 'Cuộc gọi chào hàng ban đầu',
    ]);

    $updated = $service->updateActivity($admin, $activity->id, [
        'title' => 'Cuộc gọi tư vấn chi tiết giải pháp',
    ]);

    expect($updated->title)->toBe('Cuộc gọi tư vấn chi tiết giải pháp');

    $result = $service->deleteActivity($admin, $activity->id);
    expect($result)->toBeTrue()
        ->and(Activity::query()->find($activity->id))->toBeNull();
});

it('renders CustomerTimelineFeed component with activity entries and type filtering', function (): void {
    $admin = p602Actor('admin');
    $this->actingAs($admin);

    $opp = Opportunity::query()->firstOrFail();

    Activity::query()->create([
        'activity_type' => ActivityType::Call,
        'subject_type' => Opportunity::class,
        'subject_id' => $opp->id,
        'title' => 'Cuộc gọi kiểm thử trên Timeline',
        'user_id' => $admin->id,
        'performed_at' => now(),
    ]);

    Livewire::test(CustomerTimelineFeed::class, [
        'modelType' => Opportunity::class,
        'modelId' => $opp->id,
    ])
        ->assertSee('Cuộc gọi kiểm thử trên Timeline')
        ->set('filterType', 'call')
        ->assertSee('Cuộc gọi kiểm thử trên Timeline')
        ->set('filterType', 'demo')
        ->assertDontSee('Cuộc gọi kiểm thử trên Timeline');
});
