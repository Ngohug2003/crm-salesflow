<?php

declare(strict_types=1);

use App\Livewire\Pipelines\PipelineEditor;
use App\Livewire\Pipelines\PipelineList;
use App\Models\Pipeline;
use App\Models\User;
use App\Services\PipelineManagementService;
use Database\Seeders\DemoPipelineSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(DemoPipelineSeeder::class);
});

function p502Actor(string $role): User
{
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'is_active' => true,
    ]);
    $user->assignRole($role);

    return $user;
}

it('protects pipeline routes for unauthenticated users', function (): void {
    $this->get('/pipelines')->assertRedirect('/login');
    $this->get('/pipelines/create')->assertRedirect('/login');
});

it('allows Super Admin to view pipeline list and edit pipelines', function (): void {
    $admin = p502Actor('super-admin');

    $this->actingAs($admin)
        ->get('/pipelines')
        ->assertOk()
        ->assertSee('Quy trình Bán hàng Standard');

    $pipeline = Pipeline::query()->where('is_default', true)->firstOrFail();

    $this->actingAs($admin)
        ->get("/pipelines/{$pipeline->id}/edit")
        ->assertOk()
        ->assertSee('Chỉnh sửa Quy trình bán hàng');
});

it('enforces single is_default flag when creating or updating default pipeline', function (): void {
    $admin = p502Actor('admin');
    /** @var PipelineManagementService $service */
    $service = app(PipelineManagementService::class);

    $oldDefault = Pipeline::query()->where('is_default', true)->firstOrFail();

    $newPipeline = $service->create($admin, [
        'name' => 'Quy trình Mới Mặc định',
        'code' => 'quy-trinh-moi-mac-dinh',
        'is_default' => true,
    ], [
        ['name' => 'Bước 1', 'probability' => 50, 'color' => '#3B82F6'],
    ]);

    expect($newPipeline->fresh()->is_default)->toBeTrue()
        ->and($oldDefault->fresh()->is_default)->toBeFalse();
});

it('blocks deleting system stages when updating pipeline', function (): void {
    $admin = p502Actor('admin');
    $pipeline = Pipeline::query()->where('is_default', true)->firstOrFail();

    // System stages closed-won & closed-lost have is_system = true
    $wonStage = $pipeline->stages()->where('is_won', true)->firstOrFail();

    /** @var PipelineManagementService $service */
    $service = app(PipelineManagementService::class);

    // Try to update pipeline without the wonStage id (attempting to delete it)
    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage("Không thể xóa giai đoạn hệ thống [{$wonStage->name}].");

    $service->update($admin, $pipeline->id, [
        'name' => $pipeline->name,
    ], [
        ['name' => 'Giai đoạn duy nhất', 'probability' => 50, 'color' => '#3B82F6'],
    ]);
});

it('creates a new pipeline and stages via Livewire PipelineEditor component', function (): void {
    $admin = p502Actor('admin');

    Livewire::actingAs($admin)
        ->test(PipelineEditor::class)
        ->set('name', 'Quy trình Dự án B2B')
        ->set('code', 'quy-trinh-du-an-b2b')
        ->set('description', 'Quy trình bán hàng dành cho dự án lớn')
        ->call('save')
        ->assertHasNoErrors();

    $pipeline = Pipeline::query()->where('code', 'quy-trinh-du-an-b2b')->first();
    expect($pipeline)->not->toBeNull()
        ->and($pipeline->name)->toBe('Quy trình Dự án B2B')
        ->and($pipeline->stages()->count())->toBe(4);
});

it('enforces read-only access for viewer role on pipeline mutations', function (): void {
    $viewer = p502Actor('viewer');
    $pipeline = Pipeline::query()->where('is_default', true)->firstOrFail();

    Livewire::actingAs($viewer)
        ->test(PipelineList::class)
        ->call('toggleActive', $pipeline->id)
        ->assertForbidden();
});
