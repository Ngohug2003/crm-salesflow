<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('opportunity_risk_snapshots', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->foreignId('opportunity_id')->constrained()->cascadeOnDelete();
            $table->string('rule_version', 40);
            $table->timestamp('evaluation_bucket');
            $table->unsignedSmallInteger('score');
            $table->string('level', 20);
            $table->string('summary', 500)->nullable();
            $table->boolean('is_current')->default(true);
            $table->timestamp('evaluated_at');
            $table->timestamps();

            $table->unique(['opportunity_id', 'rule_version', 'evaluation_bucket'], 'opportunity_risk_bucket_unique');
            $table->index(['is_current', 'level', 'evaluated_at'], 'opportunity_risk_current_level_idx');
        });

        Schema::create('opportunity_risk_factors', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->foreignId('opportunity_risk_snapshot_id')->constrained()->cascadeOnDelete();
            $table->string('code', 80);
            $table->string('title');
            $table->unsignedSmallInteger('points');
            $table->string('recommended_action', 500);
            $table->jsonb('details')->nullable();
            $table->timestamps();

            $table->unique(['opportunity_risk_snapshot_id', 'code'], 'opportunity_risk_factor_unique');
        });

        Schema::create('opportunity_risk_acknowledgements', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->foreignId('opportunity_risk_snapshot_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('status', 20);
            $table->text('note')->nullable();
            $table->timestamp('acted_at');
            $table->timestamps();

            $table->index(['opportunity_risk_snapshot_id', 'acted_at'], 'opportunity_risk_ack_snapshot_idx');
        });

        Schema::create('opportunity_risk_notification_logs', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->foreignId('opportunity_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('level', 20);
            $table->timestamp('notified_at');

            $table->index(['opportunity_id', 'user_id', 'notified_at'], 'opportunity_risk_notify_cooldown_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('opportunity_risk_notification_logs');
        Schema::dropIfExists('opportunity_risk_acknowledgements');
        Schema::dropIfExists('opportunity_risk_factors');
        Schema::dropIfExists('opportunity_risk_snapshots');
    }
};
