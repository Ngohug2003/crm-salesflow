<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\NotificationEventCategory;
use App\Models\User;
use App\Services\NotificationPreferenceService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

final class NotificationPreferencesTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->user = User::factory()->create([
            'name' => 'Nguyễn Minh Trí',
            'email' => 'tri.nguyen@salesflow.test',
            'is_active' => true,
        ]);
        $this->user->assignRole('sales');
    }

    public function test_service_returns_default_true_preferences_for_new_user(): void
    {
        $service = app(NotificationPreferenceService::class);
        $preferences = $service->getPreferencesForUser($this->user);

        $this->assertCount(5, $preferences);
        $this->assertTrue($preferences['tasks_sla']['email']);
        $this->assertTrue($preferences['tasks_sla']['database']);
        $this->assertTrue($preferences['tasks_sla']['broadcast']);

        $this->assertTrue($service->shouldSend($this->user, NotificationEventCategory::TasksSla, 'email'));
        $this->assertTrue($service->shouldSend($this->user, NotificationEventCategory::TasksSla, 'database'));
    }

    public function test_service_updates_and_respects_user_preferences(): void
    {
        $service = app(NotificationPreferenceService::class);

        $service->updatePreferencesForUser($this->user, [
            'tasks_sla' => [
                'database' => true,
                'email' => false, // Disabled email for Tasks & SLA
                'broadcast' => true,
            ],
        ]);

        $this->assertTrue($service->shouldSend($this->user, NotificationEventCategory::TasksSla, 'database'));
        $this->assertFalse($service->shouldSend($this->user, NotificationEventCategory::TasksSla, 'email'));
        $this->assertFalse($service->shouldSend($this->user, NotificationEventCategory::TasksSla, 'mail'));
    }

    public function test_service_can_reset_user_preferences_to_default(): void
    {
        $service = app(NotificationPreferenceService::class);

        $service->updatePreferencesForUser($this->user, [
            'tasks_sla' => [
                'database' => false,
                'email' => false,
                'broadcast' => false,
            ],
        ]);

        $this->assertFalse($service->shouldSend($this->user, NotificationEventCategory::TasksSla, 'email'));

        $service->resetToDefault($this->user);

        $this->assertTrue($service->shouldSend($this->user, NotificationEventCategory::TasksSla, 'email'));
    }

    public function test_notification_preferences_livewire_component_updates_and_resets(): void
    {
        $this->actingAs($this->user);

        Livewire::test('notifications.notification-preferences')
            ->set('preferences.tasks_sla.email', false)
            ->call('savePreferences')
            ->assertSee('Đã cập nhật cài đặt nhận thông báo thành công!');

        $service = app(NotificationPreferenceService::class);
        $this->assertFalse($service->shouldSend($this->user, NotificationEventCategory::TasksSla, 'email'));

        Livewire::test('notifications.notification-preferences')
            ->call('resetDefaults')
            ->assertSee('Đã khôi phục toàn bộ cài đặt nhận thông báo về mặc định!');

        $this->assertTrue($service->shouldSend($this->user, NotificationEventCategory::TasksSla, 'email'));
    }

    public function test_authenticated_user_can_access_notification_settings_page(): void
    {
        $response = $this->actingAs($this->user)->get(route('notifications.settings'));
        $response->assertStatus(200);
        $response->assertSee('Tùy chỉnh Cài đặt Nhận Thông báo');
        $response->assertSee('Nhiệm vụ & SLA chăm sóc');
    }
}
