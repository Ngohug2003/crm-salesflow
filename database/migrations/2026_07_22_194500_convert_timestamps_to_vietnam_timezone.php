<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** @var array<string, list<string>> */
    private array $timestampColumns = [
        'users' => ['email_verified_at', 'two_factor_confirmed_at', 'created_at', 'updated_at'],
        'password_reset_tokens' => ['created_at'],
        'permissions' => ['created_at', 'updated_at'],
        'roles' => ['created_at', 'updated_at'],
        'activity_log' => ['created_at', 'updated_at'],
        'passkeys' => ['last_used_at', 'created_at', 'updated_at'],
        'personal_access_tokens' => ['last_used_at', 'expires_at', 'created_at', 'updated_at'],
        'departments' => ['created_at', 'updated_at'],
    ];

    public function up(): void
    {
        $this->shift('7 hours');
    }

    public function down(): void
    {
        $this->shift('-7 hours');
    }

    private function shift(string $interval): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        foreach ($this->timestampColumns as $table => $columns) {
            foreach ($columns as $column) {
                DB::table($table)
                    ->whereNotNull($column)
                    ->update([$column => DB::raw("{$column} + interval '{$interval}'")]);
            }
        }
    }
};
