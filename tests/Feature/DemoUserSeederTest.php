<?php

use App\Models\Department;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('seeds twenty distributed demo users idempotently', function (): void {
    $this->seed(DatabaseSeeder::class);
    $this->seed(DatabaseSeeder::class);

    $demoUsers = User::query()
        ->whereLike('email', 'demo%@salesflow.test')
        ->get();
    $departmentIds = Department::query()->pluck('id', 'code');

    expect($demoUsers)->toHaveCount(20)
        ->and($demoUsers->where('department_id', $departmentIds['MANAGEMENT']))->toHaveCount(2)
        ->and($demoUsers->where('department_id', $departmentIds['SALES']))->toHaveCount(11)
        ->and($demoUsers->where('department_id', $departmentIds['MARKETING']))->toHaveCount(7)
        ->and($demoUsers->where('is_active', false))->toHaveCount(3)
        ->and($demoUsers->whereNull('email_verified_at'))->toHaveCount(2)
        ->and($demoUsers->every(fn (User $user): bool => $user->roles()->count() === 1))->toBeTrue();
});
