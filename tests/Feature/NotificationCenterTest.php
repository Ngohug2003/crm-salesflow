<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\Notifications\NotificationCenter;
use App\Models\User;
use App\Notifications\ExportCompletedNotification;
use App\Notifications\ImportCompletedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

final class NotificationCenterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_it_protects_notifications_route_for_unauthenticated_users(): void
    {
        $this->get(route('notifications.index'))
            ->assertRedirect(route('login'));
    }

    public function test_it_renders_notification_center_for_authenticated_users(): void
    {
        $admin = User::query()->where('email', 'admin@salesflow.test')->sole();

        $admin->notify(new ImportCompletedNotification(
            batchId: 101,
            status: 'completed',
            total: 50,
            success: 48,
            failed: 2,
            skipped: 0,
        ));

        $this->actingAs($admin)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Hộp thư thông báo hệ thống')
            ->assertSee('Import Lead #101 đã hoàn tất');
    }

    public function test_it_filters_unread_and_read_notifications(): void
    {
        $admin = User::query()->where('email', 'admin@salesflow.test')->sole();

        $admin->notify(new ImportCompletedNotification(
            batchId: 101,
            status: 'completed',
            total: 10,
            success: 10,
            failed: 0,
            skipped: 0,
        ));

        $admin->notify(new ExportCompletedNotification(
            batchId: 202,
            signedUrl: 'http://salesflow.test/exports/download/202',
            totalRows: 100,
            fileName: 'export_leads.csv',
        ));

        Livewire::actingAs($admin)
            ->test(NotificationCenter::class)
            ->assertSet('unreadCount', 2)
            ->set('filter', 'unread')
            ->assertSee('Import Lead #101 đã hoàn tất')
            ->assertSee('Tệp xuất dữ liệu #202 đã sẵn sàng');
    }

    public function test_it_marks_all_as_read_and_deletes_read_notifications(): void
    {
        $admin = User::query()->where('email', 'admin@salesflow.test')->sole();

        $admin->notify(new ImportCompletedNotification(
            batchId: 303,
            status: 'completed',
            total: 5,
            success: 5,
            failed: 0,
            skipped: 0,
        ));

        Livewire::actingAs($admin)
            ->test(NotificationCenter::class)
            ->assertSet('unreadCount', 1)
            ->call('markAllAsRead')
            ->assertSet('unreadCount', 0)
            ->call('deleteAllRead')
            ->assertSet('totalCount', 0);
    }
}
