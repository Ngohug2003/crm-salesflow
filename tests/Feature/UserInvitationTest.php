<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\Users\InviteMemberModal;
use App\Mail\UserInvitationMail;
use App\Models\Department;
use App\Models\Staff;
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

    public function test_inviting_user_automatically_creates_staff_record(): void
    {
        Mail::fake();

        $service = app(UserInvitationService::class);
        $invitation = $service->createInvitation($this->admin, [
            'name' => 'Ngô Văn T',
            'email' => 't.ngo@salesflow.test',
            'department_id' => $this->department->id,
            'role' => 'sales',
        ]);

        $this->assertDatabaseHas('user_invitations', [
            'email' => 't.ngo@salesflow.test',
            'name' => 'Ngô Văn T',
        ]);

        $this->assertDatabaseHas('staff', [
            'email' => 't.ngo@salesflow.test',
            'full_name' => 'Ngô Văn T',
            'department_id' => $this->department->id,
            'position' => 'Staff (Đã mời)',
        ]);
    }

    public function test_accepting_invitation_links_staff_record_with_new_user(): void
    {
        $staff = Staff::query()->create([
            'staff_code' => 'NV-SALES-9999',
            'full_name' => 'Vũ Thị E',
            'email' => 'e.vu@salesflow.test',
            'department_id' => $this->department->id,
            'position' => 'Staff (Đã mời)',
            'is_active' => true,
        ]);

        $invitation = UserInvitation::query()->create([
            'name' => 'Vũ Thị E',
            'email' => 'e.vu@salesflow.test',
            'department_id' => $this->department->id,
            'role' => 'sales',
            'token' => 'token-vu-thi-e-12345',
            'expires_at' => Carbon::now()->addDays(7),
            'invited_by' => $this->admin->id,
        ]);

        Livewire::test('auth.accept-invitation', ['token' => 'token-vu-thi-e-12345'])
            ->set('password', 'SecretPassword123!')
            ->set('passwordConfirmation', 'SecretPassword123!')
            ->call('accept')
            ->assertRedirect(route('dashboard'));

        $newUser = User::query()->where('email', 'e.vu@salesflow.test')->firstOrFail();
        $staff->refresh();

        $this->assertEquals($newUser->id, $staff->user_id);
    }

    public function test_invite_member_modal_creates_invitation_and_emits_event(): void
    {
        Mail::fake();

        $this->actingAs($this->admin);

        Livewire::test(InviteMemberModal::class)
            ->call('openModal')
            ->assertSet('showModal', true)
            ->set('name', 'Hoàng Văn F')
            ->set('email', 'f.hoang@salesflow.test')
            ->set('departmentId', $this->department->id)
            ->set('role', 'sales')
            ->call('sendInvitation')
            ->assertHasNoErrors()
            ->assertDispatched('invitation-created')
            ->assertSee('Đã tạo lời mời thành công!')
            ->assertSee('f.hoang@salesflow.test');

        $this->assertDatabaseHas('user_invitations', [
            'email' => 'f.hoang@salesflow.test',
            'name' => 'Hoàng Văn F',
        ]);

        $this->assertDatabaseHas('staff', [
            'email' => 'f.hoang@salesflow.test',
            'full_name' => 'Hoàng Văn F',
        ]);
    }
}
