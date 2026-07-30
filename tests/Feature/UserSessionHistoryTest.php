<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\Users\UserSessionHistory;
use App\Models\Department;
use App\Models\User;
use App\Services\Auth\UserSessionHistoryService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

final class UserSessionHistoryTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    private User $admin;

    private User $salesUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $dept = Department::factory()->create(['name' => 'Phòng Kinh doanh']);

        $this->superAdmin = User::factory()->create(['department_id' => $dept->id]);
        $this->superAdmin->assignRole('super-admin');

        $this->admin = User::factory()->create(['department_id' => $dept->id]);
        $this->admin->assignRole('admin');

        $this->salesUser = User::factory()->create(['department_id' => $dept->id]);
        $this->salesUser->assignRole('sales');
    }

    public function test_service_parses_user_agent_and_queries_sessions(): void
    {
        $service = app(UserSessionHistoryService::class);
        $ua = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';

        $parsed = $service->parseUserAgent($ua);
        $this->assertEquals('Google Chrome', $parsed['browser']);
        $this->assertEquals('Windows', $parsed['platform']);

        // Insert session record
        DB::table('sessions')->insert([
            'id' => 'sess_test_123',
            'user_id' => $this->salesUser->id,
            'ip_address' => '192.168.1.100',
            'user_agent' => $ua,
            'payload' => 'dummy',
            'last_activity' => time(),
        ]);

        $sessions = $service->getSessionsForUser($this->salesUser, 'sess_test_123');
        $this->assertCount(1, $sessions);
        $this->assertEquals('sess_test_123', $sessions[0]['id']);
        $this->assertTrue($sessions[0]['is_current']);
        $this->assertEquals('Google Chrome', $sessions[0]['browser']);
    }

    public function test_revoke_session_deletes_record_and_writes_audit_log(): void
    {
        $service = app(UserSessionHistoryService::class);

        DB::table('sessions')->insert([
            'id' => 'sess_to_revoke',
            'user_id' => $this->salesUser->id,
            'ip_address' => '10.0.0.1',
            'user_agent' => 'Mozilla/5.0 (Linux; Android 10) Chrome/119.0.0.0 Mobile',
            'payload' => 'dummy',
            'last_activity' => time(),
        ]);

        $success = $service->revokeSession($this->admin, $this->salesUser, 'sess_to_revoke');
        $this->assertTrue($success);
        $this->assertDatabaseMissing('sessions', ['id' => 'sess_to_revoke']);

        $this->assertDatabaseHas('activity_log', [
            'event' => 'session.revoked',
            'subject_type' => User::class,
            'subject_id' => $this->salesUser->id,
        ]);
    }

    public function test_revoke_other_sessions_clears_all_except_current(): void
    {
        $service = app(UserSessionHistoryService::class);

        DB::table('sessions')->insert([
            ['id' => 'sess_current', 'user_id' => $this->salesUser->id, 'ip_address' => '127.0.0.1', 'user_agent' => 'ua1', 'payload' => 'd', 'last_activity' => time()],
            ['id' => 'sess_old_1', 'user_id' => $this->salesUser->id, 'ip_address' => '127.0.0.1', 'user_agent' => 'ua2', 'payload' => 'd', 'last_activity' => time()],
            ['id' => 'sess_old_2', 'user_id' => $this->salesUser->id, 'ip_address' => '127.0.0.1', 'user_agent' => 'ua3', 'payload' => 'd', 'last_activity' => time()],
        ]);

        $revokedCount = $service->revokeOtherSessions($this->salesUser, 'sess_current');
        $this->assertEquals(2, $revokedCount);

        $this->assertDatabaseHas('sessions', ['id' => 'sess_current']);
        $this->assertDatabaseMissing('sessions', ['id' => 'sess_old_1']);
        $this->assertDatabaseMissing('sessions', ['id' => 'sess_old_2']);
    }

    public function test_livewire_user_session_history_rendering(): void
    {
        DB::table('sessions')->insert([
            'id' => 'sess_livewire_test',
            'user_id' => $this->salesUser->id,
            'ip_address' => '172.16.0.1',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0.0.0',
            'payload' => 'dummy',
            'last_activity' => time(),
        ]);

        $this->actingAs($this->salesUser);

        Livewire::test(UserSessionHistory::class)
            ->assertOk()
            ->assertSee('Phiên đăng nhập của tôi')
            ->assertSee('Google Chrome');
    }
}
