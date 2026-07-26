<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('opportunities', function (Blueprint $table): void {
            $table->index(['pipeline_id', 'actual_close_date', 'is_won'], 'opportunities_report_closed_idx');
            $table->index(['pipeline_id', 'expected_close_date', 'is_won', 'is_lost'], 'opportunities_report_forecast_idx');
            $table->index(['owner_id', 'created_at'], 'opportunities_report_owner_created_idx');
        });

        Schema::table('leads', function (Blueprint $table): void {
            $table->index(['owner_id', 'created_at'], 'leads_report_owner_created_idx');
        });

        Schema::table('activities', function (Blueprint $table): void {
            $table->index(['user_id', 'created_at'], 'activities_report_user_created_idx');
        });

        Schema::table('tasks', function (Blueprint $table): void {
            $table->index(['status', 'completed_at'], 'tasks_report_completed_idx');
        });
    }

    public function down(): void
    {
        Schema::table('tasks', fn (Blueprint $table) => $table->dropIndex('tasks_report_completed_idx'));
        Schema::table('activities', fn (Blueprint $table) => $table->dropIndex('activities_report_user_created_idx'));
        Schema::table('leads', fn (Blueprint $table) => $table->dropIndex('leads_report_owner_created_idx'));
        Schema::table('opportunities', function (Blueprint $table): void {
            $table->dropIndex('opportunities_report_closed_idx');
            $table->dropIndex('opportunities_report_forecast_idx');
            $table->dropIndex('opportunities_report_owner_created_idx');
        });
    }
};
