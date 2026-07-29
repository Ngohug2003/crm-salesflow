<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_routing_rules', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('strategy')->default('round_robin'); // round_robin, territory, value_threshold, fallback_unassigned
            $table->integer('priority')->default(1);
            $table->boolean('is_active')->default(true);
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('target_role_id')->nullable()->constrained('roles')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('lead_routing_rule_conditions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('rule_id')->constrained('lead_routing_rules')->cascadeOnDelete();
            $table->foreignId('province_id')->nullable()->constrained('provinces')->nullOnDelete();
            $table->foreignId('lead_source_id')->nullable()->constrained('lead_sources')->nullOnDelete();
            $table->decimal('min_estimated_value', 15, 2)->nullable();
            $table->timestamps();
        });

        Schema::create('lead_routing_cursors', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('rule_id')->unique()->constrained('lead_routing_rules')->cascadeOnDelete();
            $table->foreignId('last_assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('lead_routing_executions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('lead_id')->constrained('leads')->cascadeOnDelete();
            $table->foreignId('rule_id')->nullable()->constrained('lead_routing_rules')->nullOnDelete();
            $table->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('success'); // success, unassigned_pool, no_candidate, manual_override
            $table->json('candidate_user_ids')->nullable();
            $table->text('reason')->nullable();
            $table->timestamp('sla_due_at')->nullable();
            $table->foreignId('executed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('leads', function (Blueprint $table): void {
            $table->timestamp('sla_first_touch_due_at')->nullable()->index();
            $table->timestamp('sla_satisfied_at')->nullable();
            $table->boolean('is_sla_overdue')->default(false)->index();
            $table->timestamp('sla_reminder_sent_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table): void {
            $table->dropColumn([
                'sla_first_touch_due_at',
                'sla_satisfied_at',
                'is_sla_overdue',
                'sla_reminder_sent_at',
            ]);
        });

        Schema::dropIfExists('lead_routing_executions');
        Schema::dropIfExists('lead_routing_cursors');
        Schema::dropIfExists('lead_routing_rule_conditions');
        Schema::dropIfExists('lead_routing_rules');
    }
};
