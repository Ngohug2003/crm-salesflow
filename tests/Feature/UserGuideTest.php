<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class UserGuideTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_it_protects_user_guide_route_for_unauthenticated_users(): void
    {
        $this->get(route('help.guide'))
            ->assertRedirect(route('login'));
    }

    public function test_it_renders_user_guide_page_for_authenticated_users(): void
    {
        $user = User::query()->where('email', 'demo04@salesflow.test')->sole();
        $user->update(['email_verified_at' => now(), 'is_active' => true]);

        $this->actingAs($user)
            ->get(route('help.guide'))
            ->assertOk()
            ->assertSee('Hướng dẫn sử dụng SalesFlow CRM')
            ->assertSee('Quy trình bán hàng')
            ->assertSee('Công việc')
            ->assertSee('Import / Export')
            ->assertSee('Thông báo & nhật ký', false);
    }
}
