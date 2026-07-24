<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contacts', function (Blueprint $table): void {
            $table->bigIncrements('id');

            $table->foreignId('company_id')
                ->nullable()
                ->constrained('companies')
                ->nullOnDelete();

            $table->foreignId('owner_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('department_id')
                ->nullable()
                ->constrained('departments')
                ->nullOnDelete();

            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('full_name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('secondary_phone')->nullable();
            $table->string('job_title')->nullable();
            $table->string('department_name')->nullable();
            $table->date('birthday')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('province')->nullable();
            $table->string('country')->default('Việt Nam');
            $table->text('notes')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index('company_id');
            $table->index(['owner_id', 'department_id']);
            $table->index('full_name');
            $table->index('email');
            $table->index('phone');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
