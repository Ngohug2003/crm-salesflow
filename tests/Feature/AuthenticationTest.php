<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('uses the isolated test environment', function (): void {
    expect(config('database.default'))->toBe('sqlite')
        ->and(app()->environment())->toBe('testing');
});

it('redirects guests to the login screen', function (): void {
    $this->get('/dashboard')->assertRedirect('/login');
});

it('prefills the demo password on the local login screen', function (): void {
    $this->app->detectEnvironment(fn (): string => 'local');
    config(['crm.local_login_password' => 'local-demo-password']);

    $this->get('/login')
        ->assertOk()
        ->assertSee('value="local-demo-password"', false);
});

it('does not expose the demo password outside the local environment', function (): void {
    config(['crm.local_login_password' => 'local-demo-password']);

    $this->get('/login')
        ->assertOk()
        ->assertDontSee('local-demo-password');
});

it('authenticates an active user', function (): void {
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'is_active' => true,
        'password' => 'secret-password',
    ]);

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'secret-password',
    ])->assertRedirect('/dashboard');

    $this->assertAuthenticatedAs($user);
});

it('does not authenticate a locked user', function (): void {
    $user = User::factory()->create([
        'is_active' => false,
        'password' => 'secret-password',
    ]);

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'secret-password',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('renders the dashboard for a verified active user', function (): void {
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'is_active' => true,
    ]);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('SalesFlow CRM')
        ->assertSee('Nền tảng SalesFlow đã sẵn sàng');
});

it('requires email verification', function (): void {
    $user = User::factory()->unverified()->create(['is_active' => true]);

    $this->actingAs($user)->get('/dashboard')->assertRedirect('/email/verify');
});
