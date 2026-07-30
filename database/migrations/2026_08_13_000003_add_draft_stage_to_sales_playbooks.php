<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_playbooks', function (Blueprint $table): void {
            $table->foreignId('draft_pipeline_stage_id')
                ->nullable()
                ->after('repeat_policy')
                ->constrained('pipeline_stages')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sales_playbooks', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('draft_pipeline_stage_id');
        });
    }
};
