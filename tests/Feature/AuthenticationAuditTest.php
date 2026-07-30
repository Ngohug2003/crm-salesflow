<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;

uses(RefreshDatabase::class);

it('records successful login and logout with masked request metadata', function (): void {
    $user = User::factory()->create([
        'email' => 'audit-login@salesflow.test',
        'password' => 'Password@123',
        'email_verified_at' => now(),
        'is_active' => true,
    ]);

    $this->withServerVariables([
        'REMOTE_ADDR' => '192.168.10.25',
        'HTTP_USER_AGENT' => 'SalesFlow Test Browser',
    ])->post('/login', [
        'email' => $user->email,
        'password' => 'Password@123',
    ])->assertRedirect('/dashboard');

    $this->post('/logout')->assertRedirect('/');

    $events = Activity::query()->orderBy('id')->get();

    expect($events)->toHaveCount(2)
        ->and($events->pluck('event')->all())->toBe(['logged-in', 'logged-out'])
        ->and($events->first()->properties->get('ip_address'))->toBe('192.168.***.***')
        ->and($events->first()->properties->get('user_agent'))->toBe('SalesFlow Test Browser')
        ->and($events->first()->causer_id)->toBe($user->getKey());
});
