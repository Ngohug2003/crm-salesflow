<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pipeline_stages', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->foreignId('pipeline_id')->constrained('pipelines')->cascadeOnDelete();
            $table->string('name');
            $table->string('code');
            $table->text('description')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->unsignedInteger('probability')->default(0);
            $table->string('color', 20)->default('#3B82F6');
            $table->boolean('is_won')->default(false);
            $table->boolean('is_lost')->default(false);
            $table->boolean('is_system')->default(false);

            $table->softDeletes();
            $table->timestamps();

            $table->index(['pipeline_id', 'position']);
            $table->index(['pipeline_id', 'is_won', 'is_lost']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pipeline_stages');
    }
};
