<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route as RouteFacade;
use Tests\TestCase;

final class RouteNavigationAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_authenticated_crm_route_has_an_explicit_access_boundary(): void
    {
        $exceptions = collect(config('crm.rbac.route_access_exceptions', []))
            ->flatMap(static fn (array $routes): array => array_keys($routes))
            ->all();

        $unclassified = collect(RouteFacade::getRoutes()->getRoutes())
            ->filter(static fn (Route $route): bool => self::usesAccountActiveMiddleware($route))
            ->reject(static fn (Route $route): bool => self::usesCanMiddleware($route))
            ->reject(static fn (Route $route): bool => in_array($route->getName(), $exceptions, true))
            ->map(static fn (Route $route): string => (string) $route->getName())
            ->values()
            ->all();

        self::assertSame([], $unclassified, 'Authenticated CRM routes missing can middleware or a documented backend boundary.');
    }

    public function test_every_permission_referenced_by_routes_and_sidebar_exists_in_the_catalog(): void
    {
        $catalog = collect(config('crm.rbac.permission_groups', []))
            ->flatMap(static fn (array $group): array => array_keys($group['permissions']))
            ->unique()
            ->sort()
            ->values();

        $files = collect([
            ...File::allFiles(app_path()),
            ...File::allFiles(resource_path('views')),
            ...File::allFiles(base_path('routes')),
        ])->map(static fn (\SplFileInfo $file): string => $file->getPathname());

        $referenced = collect();

        foreach ($files as $file) {
            preg_match_all(
                "/(?:@can|middleware|can|allows|authorize|hasPermissionTo)\\(\\s*['\"](?:can:)?([a-z][a-z0-9-]*\\.[a-z0-9.-]+)/",
                File::get($file),
                $matches,
            );
            $referenced->push(...$matches[1]);
        }

        $unknown = $referenced->unique()->diff($catalog)->sort()->values()->all();

        self::assertSame([], $unknown, 'Routes or sidebar reference permissions missing from config/crm.php.');
        self::assertStringNotContainsString('users.manage', File::get(base_path('routes/web.php')));
        self::assertStringNotContainsString('roles.view', File::get(resource_path('views/layouts/partials/_sidebar.blade.php')));
        self::assertStringNotContainsString('roles.assign', File::get(resource_path('views/layouts/partials/_sidebar.blade.php')));
    }

    public function test_sidebar_permissions_match_the_target_route_middleware(): void
    {
        $expected = [
            'dashboard' => 'reports.view',
            'leads.routing-rules' => 'leads.assign',
            'quotes.approvals' => 'quotes.approve',
            'reports.funnel' => 'reports.view',
            'roles.permission-matrix' => 'settings.manage',
            'system-console.index' => 'system-console.view',
        ];
        $sidebar = File::get(resource_path('views/layouts/partials/_sidebar.blade.php'));

        foreach ($expected as $routeName => $ability) {
            $route = RouteFacade::getRoutes()->getByName($routeName);

            self::assertNotNull($route);
            self::assertTrue(self::usesAbility($route, $ability), "Route [{$routeName}] is missing can:{$ability}.");
            self::assertMatchesRegularExpression(
                "/@can\\('".preg_quote($ability, '/')."'\\)[\\s\\S]{0,1600}route\\('".preg_quote($routeName, '/')."'\\)/",
                $sidebar,
                "Sidebar route [{$routeName}] is not guarded by [{$ability}].",
            );
        }
    }

    public function test_user_without_crm_permissions_cannot_open_protected_navigation_routes(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create([
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        foreach ([
            'dashboard',
            'leads.index',
            'companies.index',
            'reports.funnel',
            'users.index',
            'roles.permission-matrix',
            'system-console.index',
        ] as $routeName) {
            $this->actingAs($user)->get(route($routeName))->assertForbidden();
        }
    }

    public function test_sidebar_only_renders_routes_allowed_for_each_role(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $superAdmin = User::factory()->create([
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        $superAdmin->assignRole('super-admin');

        $sales = User::factory()->create([
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        $sales->assignRole('sales');

        $superAdminPage = $this->actingAs($superAdmin)->get(route('dashboard'))->assertOk();

        foreach ([
            'leads.index',
            'leads.routing-rules',
            'companies.index',
            'contacts.index',
            'customers.merge',
            'customers.sla',
            'opportunities.index',
            'quotes.approvals',
            'pipelines.index',
            'tasks.index',
            'reports.funnel',
            'staff.index',
            'users.index',
            'departments.index',
            'roles.permission-matrix',
            'audit-logs.index',
            'system-console.index',
            'quotes.settings',
        ] as $routeName) {
            $superAdminPage->assertSee(route($routeName), false);
        }

        $salesPage = $this->actingAs($sales)->get(route('dashboard'))->assertOk();
        $salesPage
            ->assertSee(route('leads.index'), false)
            ->assertSee(route('companies.index'), false)
            ->assertSee(route('opportunities.index'), false)
            ->assertDontSee(route('leads.routing-rules'), false)
            ->assertDontSee(route('quotes.approvals'), false)
            ->assertDontSee(route('users.index'), false)
            ->assertDontSee(route('roles.permission-matrix'), false)
            ->assertDontSee(route('system-console.index'), false)
            ->assertDontSee(route('quotes.settings'), false);
    }

    private static function usesAccountActiveMiddleware(Route $route): bool
    {
        return collect($route->gatherMiddleware())
            ->contains(static fn (mixed $middleware): bool => is_string($middleware)
                && (str_contains($middleware, 'account.active') || str_contains($middleware, 'EnsureAccountIsActive')));
    }

    private static function usesCanMiddleware(Route $route): bool
    {
        return collect($route->gatherMiddleware())
            ->contains(static fn (mixed $middleware): bool => is_string($middleware)
                && (str_starts_with($middleware, 'can:') || str_contains($middleware, 'Authorize:')));
    }

    private static function usesAbility(Route $route, string $ability): bool
    {
        return collect($route->gatherMiddleware())
            ->contains(static fn (mixed $middleware): bool => is_string($middleware)
                && (str_contains($middleware, "can:{$ability}") || str_contains($middleware, "Authorize:{$ability}")));
    }
}
