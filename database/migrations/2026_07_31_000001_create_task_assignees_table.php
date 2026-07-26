<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_assignees', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['task_id', 'user_id']);
        });

        // Migrate dữ liệu từ cột assigned_to cũ sang bảng pivot task_assignees
        $existingTasks = DB::table('tasks')->whereNotNull('assigned_to')->get();
        foreach ($existingTasks as $t) {
            DB::table('task_assignees')->insertOrIgnore([
                'task_id' => $t->id,
                'user_id' => $t->assigned_to,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('task_assignees');
    }
};
