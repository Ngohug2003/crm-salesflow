<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Mail\UserInvitationMail;
use App\Models\Department;
use App\Models\User;
use App\Models\UserInvitation;
use App\Services\Auth\UserInvitationService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

final class UserInvitationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Department $department;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->department = Department::factory()->create([
            'name' => 'Phòng Kinh doanh',
            'code' => 'SALES_01',
        ]);

        $this->admin = User::factory()->create([
            'name' => 'System Admin',
            'email' => 'admin@salesflow.test',
            'department_id' => $this->department->id,
            'is_active' => true,
        ]);
        $this->admin->assignRole('admin');
    }

    public function test_admin_can_create_and_send_user_invitation(): void
    {
        Mail::fake();

        $service = app(UserInvitationService::class);
        $invitation = $service->createInvitation($this->admin, [
            'name' => 'Nguyễn Văn Mới',
            'email' => 'moi.nguyen@salesflow.test',
            'department_id' => $this->department->id,
            'role' => 'sales-manager',
        ]);

        $this->assertDatabaseHas('user_invitations', [
            'email' => 'moi.nguyen@salesflow.test',
            'name' => 'Nguyễn Văn Mới',
            'department_id' => $this->department->id,
            'role' => 'sales-manager',
            'invited_by' => $this->admin->id,
        ]);

        Mail::assertSent(UserInvitationMail::class, function (UserInvitationMail $mail) use ($invitation) {
            return $mail->hasTo('moi.nguyen@salesflow.test')
                && $mail->invitation->id === $invitation->id;
        });
    }

    public function test_admin_can_resend_and_revoke_invitation(): void
    {
        Mail::fake();

        $service = app(UserInvitationService::class);
        $invitation = $service->createInvitation($this->admin, [
            'name' => 'Trần Thị B',
            'email' => 'b.tran@salesflow.test',
            'department_id' => null,
            'role' => 'sales',
        ]);

        $oldToken = $invitation->token;

        $service->resendInvitation($this->admin, $invitation);

        $invitation->refresh();
        $this->assertNotEquals($oldToken, $invitation->token);
        Mail::assertSent(UserInvitationMail::class, 2);

        $service->revokeInvitation($this->admin, $invitation);
        $this->assertDatabaseMissing('user_invitations', [
            'id' => $invitation->id,
        ]);
    }

    public function test_invited_user_can_accept_invitation_and_set_password(): void
    {
        $invitation = UserInvitation::query()->create([
            'name' => 'Lê Văn C',
            'email' => 'c.le@salesflow.test',
            'department_id' => $this->department->id,
            'role' => 'sales',
            'token' => 'valid-invitation-token-123456789',
            'expires_at' => Carbon::now()->addDays(7),
            'invited_by' => $this->admin->id,
        ]);

        Livewire::test('auth.accept-invitation', ['token' => 'valid-invitation-token-123456789'])
            ->set('password', 'SecretPassword123!')
            ->set('passwordConfirmation', 'SecretPassword123!')
            ->call('accept')
            ->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('users', [
            'email' => 'c.le@salesflow.test',
            'name' => 'Lê Văn C',
            'department_id' => $this->department->id,
        ]);

        $user = User::query()->where('email', 'c.le@salesflow.test')->firstOrFail();
        $this->assertTrue($user->hasRole('sales'));
        $this->assertNotNull($user->email_verified_at);
        $this->assertAuthenticatedAs($user);

        $invitation->refresh();
        $this->assertNotNull($invitation->accepted_at);
    }

    public function test_expired_invitation_link_shows_error(): void
    {
        UserInvitation::query()->create([
            'name' => 'Đỗ Văn D',
            'email' => 'd.do@salesflow.test',
            'department_id' => null,
            'role' => 'sales',
            'token' => 'expired-token-999',
            'expires_at' => Carbon::now()->subDay(),
            'invited_by' => $this->admin->id,
        ]);

        Livewire::test('auth.accept-invitation', ['token' => 'expired-token-999'])
            ->assertSee('Liên kết lời mời đã hết hạn');
    }
}
