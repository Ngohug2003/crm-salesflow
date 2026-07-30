<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('opportunities', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('title');
            $table->string('code')->unique();
            $table->decimal('amount', 15, 2)->default(0.00);

            $table->foreignId('pipeline_id')->constrained('pipelines')->cascadeOnDelete();
            $table->foreignId('stage_id')->constrained('pipeline_stages')->cascadeOnDelete();

            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->foreignId('lead_id')->nullable()->constrained('leads')->nullOnDelete();

            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->date('expected_close_date')->nullable();
            $table->date('actual_close_date')->nullable();
            $table->text('lost_reason')->nullable();
            $table->text('notes')->nullable();

            $table->boolean('is_won')->default(false);
            $table->boolean('is_lost')->default(false);

            $table->softDeletes();
            $table->timestamps();

            $table->index(['pipeline_id', 'stage_id']);
            $table->index(['owner_id', 'department_id']);
            $table->index(['company_id', 'contact_id']);
            $table->index(['is_won', 'is_lost']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('opportunities');
    }
};
