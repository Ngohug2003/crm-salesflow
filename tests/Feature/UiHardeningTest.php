<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class UiHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_unauthenticated_users_are_redirected_to_login_from_main_ui_routes(): void
    {
        $routes = [
            'dashboard',
            'leads.index',
            'companies.index',
            'contacts.index',
            'pipelines.index',
            'opportunities.index',
            'tasks.index',
            'reports.funnel',
            'reports.revenue',
            'reports.performance',
            'imports.leads',
            'notifications.index',
            'help.guide',
        ];

        foreach ($routes as $routeName) {
            $this->get(route($routeName))->assertRedirect(route('login'));
        }
    }

    public function test_authenticated_super_admin_can_access_all_main_ui_routes(): void
    {
        $user = User::query()->where('email', 'demo01@salesflow.test')->sole();
        $user->update(['email_verified_at' => now(), 'is_active' => true]);
        $user->syncRoles(['super-admin']);

        $routes = [
            'dashboard',
            'leads.index',
            'companies.index',
            'contacts.index',
            'pipelines.index',
            'opportunities.index',
            'tasks.index',
            'reports.funnel',
            'reports.revenue',
            'reports.performance',
            'imports.leads',
            'notifications.index',
            'audit-logs.index',
            'help.guide',
        ];

        foreach ($routes as $routeName) {
            $response = $this->actingAs($user)->get(route($routeName));
            $response->assertOk();
        }
    }

    public function test_authenticated_sales_user_can_access_standard_crm_routes(): void
    {
        $user = User::query()->where('email', 'demo04@salesflow.test')->sole();
        $user->update(['email_verified_at' => now(), 'is_active' => true]);

        $routes = [
            'dashboard',
            'leads.index',
            'companies.index',
            'contacts.index',
            'pipelines.index',
            'opportunities.index',
            'tasks.index',
            'reports.funnel',
            'notifications.index',
            'help.guide',
        ];

        foreach ($routes as $routeName) {
            $response = $this->actingAs($user)->get(route($routeName));
            $response->assertOk();
        }
    }
}
