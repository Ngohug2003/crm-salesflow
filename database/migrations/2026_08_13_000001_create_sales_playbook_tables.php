<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_playbooks', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('code', 80);
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->string('status', 20)->default('draft');
            $table->string('repeat_policy', 20)->default('once');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['code', 'version']);
            $table->index(['status', 'updated_at']);
        });

        Schema::create('sales_playbook_steps', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->foreignId('sales_playbook_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position');
            $table->string('type', 30);
            $table->string('title');
            $table->text('instructions')->nullable();
            $table->jsonb('configuration')->nullable();
            $table->boolean('is_required')->default(false);
            $table->boolean('blocks_stage_exit')->default(false);
            $table->unsignedSmallInteger('due_business_days')->nullable();
            $table->timestamps();

            $table->unique(['sales_playbook_id', 'position']);
            $table->index(['sales_playbook_id', 'type']);
        });

        Schema::create('stage_playbook_assignments', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->foreignId('pipeline_stage_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sales_playbook_id')->constrained()->restrictOnDelete();
            $table->foreignId('assigned_by')->constrained('users')->restrictOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique('pipeline_stage_id');
            $table->index(['sales_playbook_id', 'is_active']);
        });

        Schema::create('opportunity_playbook_runs', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->foreignId('opportunity_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sales_playbook_id')->constrained()->restrictOnDelete();
            $table->foreignId('pipeline_stage_id')->constrained()->restrictOnDelete();
            $table->foreignId('started_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('idempotency_key', 160)->unique();
            $table->string('status', 20)->default('active');
            $table->string('repeat_policy', 20);
            $table->jsonb('playbook_snapshot');
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['opportunity_id', 'status']);
            $table->index(['pipeline_stage_id', 'started_at']);
        });

        Schema::create('opportunity_playbook_step_runs', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->foreignId('opportunity_playbook_run_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sales_playbook_step_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('task_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('position');
            $table->string('type', 30);
            $table->string('title');
            $table->text('instructions')->nullable();
            $table->jsonb('configuration')->nullable();
            $table->boolean('is_required')->default(false);
            $table->boolean('blocks_stage_exit')->default(false);
            $table->string('status', 20)->default('pending');
            $table->timestamp('due_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['opportunity_playbook_run_id', 'position'], 'playbook_step_run_position_unique');
            $table->index(['opportunity_playbook_run_id', 'status'], 'playbook_step_run_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('opportunity_playbook_step_runs');
        Schema::dropIfExists('opportunity_playbook_runs');
        Schema::dropIfExists('stage_playbook_assignments');
        Schema::dropIfExists('sales_playbook_steps');
        Schema::dropIfExists('sales_playbooks');
    }
};
